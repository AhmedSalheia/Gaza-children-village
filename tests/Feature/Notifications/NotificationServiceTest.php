<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Notifications\Actions\NotifyOnTransition;
use Modules\Notifications\Data\NotificationType;
use Modules\Notifications\Models\PortalNotification;
use Modules\Notifications\Services\NotificationService;
use Modules\Notifications\Services\OperationStatusService;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeNotificationService(): NotificationService
{
    return new NotificationService;
}

function sendTestNotification(
    string $type = NotificationType::CORRECTION_REQUEST_APPROVED,
    string $accountType = 'guardian',
    int $accountId = 1,
    string $portal = 'guardian',
    string $messageKey = 'correction_request.approved',
    array $params = ['student_name' => 'Alice', 'request_id' => 42],
): ?PortalNotification {
    return makeNotificationService()->send(
        notificationType: $type,
        recipientAccountType: $accountType,
        recipientAccountId: $accountId,
        portal: $portal,
        messageKey: $messageKey,
        messageParams: $params,
    );
}

// ---------------------------------------------------------------------------
// NotificationType catalogue
// ---------------------------------------------------------------------------

describe('NotificationType catalogue', function (): void {

    it('all() returns exactly 18 types', function (): void {
        expect(NotificationType::all())->toHaveCount(18);
    });

    it('all types contain a dot (domain.action format)', function (): void {
        foreach (NotificationType::all() as $type) {
            expect($type)->toContain('.');
        }
    });

});

// ---------------------------------------------------------------------------
// NotificationService::send() — message key validation
// ---------------------------------------------------------------------------

describe('NotificationService::send() — message key validation', function (): void {

    it('creates a notification when message key exists in translation file', function (): void {
        $notification = sendTestNotification();

        expect($notification)->not->toBeNull()
            ->and(PortalNotification::count())->toBe(1);
    });

    it('throws InvalidArgumentException for an unknown message key', function (): void {
        expect(fn () => makeNotificationService()->send(
            notificationType: NotificationType::CORRECTION_REQUEST_APPROVED,
            recipientAccountType: 'guardian',
            recipientAccountId: 1,
            portal: 'guardian',
            messageKey: 'nonexistent.key.that.does.not.exist',
        ))->toThrow(InvalidArgumentException::class, 'Unknown notification message key');
    });

    it('throws InvalidArgumentException for an unknown notification type', function (): void {
        expect(fn () => makeNotificationService()->send(
            notificationType: 'unknown.type.xyz',
            recipientAccountType: 'guardian',
            recipientAccountId: 1,
            portal: 'guardian',
            messageKey: 'correction_request.approved',
        ))->toThrow(InvalidArgumentException::class, 'Unknown notification type');
    });

});

// ---------------------------------------------------------------------------
// NotificationService::send() — param sanitization
// ---------------------------------------------------------------------------

describe('NotificationService::send() — param sanitization', function (): void {

    it('strips params not in the per-type safe allowlist', function (): void {
        $notification = makeNotificationService()->send(
            notificationType: NotificationType::CORRECTION_REQUEST_APPROVED,
            recipientAccountType: 'guardian',
            recipientAccountId: 1,
            portal: 'guardian',
            messageKey: 'correction_request.approved',
            messageParams: [
                'student_name' => 'Alice',
                'request_id' => 42,
                'national_id' => 'SENSITIVE-123',   // must be stripped
                'password_hash' => 'SENSITIVE-HASH',  // must be stripped
                'stack_trace' => 'SENSITIVE-TRACE', // must be stripped
            ],
        );

        expect($notification)->not->toBeNull();
        $stored = $notification->message_params;

        expect($stored)->toHaveKey('student_name')
            ->and($stored)->toHaveKey('request_id')
            ->and($stored)->not->toHaveKey('national_id')
            ->and($stored)->not->toHaveKey('password_hash')
            ->and($stored)->not->toHaveKey('stack_trace');
    });

    it('stores null for message_params when all params are stripped', function (): void {
        $notification = makeNotificationService()->send(
            notificationType: NotificationType::CORRECTION_REQUEST_APPROVED,
            recipientAccountType: 'guardian',
            recipientAccountId: 1,
            portal: 'guardian',
            messageKey: 'correction_request.approved',
            messageParams: ['national_id' => 'SENSITIVE'],
        );

        expect($notification)->not->toBeNull()
            ->and($notification->message_params)->toBeNull();
    });

});

// ---------------------------------------------------------------------------
// Cross-portal access isolation — mark-read
// ---------------------------------------------------------------------------

describe('Cross-portal access isolation — markRead', function (): void {

    it('mark-read by the correct recipient succeeds', function (): void {
        $notification = sendTestNotification(accountType: 'guardian', accountId: 10, portal: 'guardian');

        $updated = makeNotificationService()->markRead(
            notificationId: $notification->id,
            actorAccountType: 'guardian',
            actorAccountId: 10,
            portal: 'guardian',
        );

        expect($updated->read_at)->not->toBeNull();
    });

    it('mark-read by a different account is denied', function (): void {
        $notification = sendTestNotification(accountType: 'guardian', accountId: 10, portal: 'guardian');

        expect(fn () => makeNotificationService()->markRead(
            notificationId: $notification->id,
            actorAccountType: 'guardian',
            actorAccountId: 999,  // wrong account
            portal: 'guardian',
        ))->toThrow(RuntimeException::class, 'Access denied');
    });

    it('mark-read from a different portal is denied', function (): void {
        $notification = sendTestNotification(accountType: 'guardian', accountId: 10, portal: 'guardian');

        expect(fn () => makeNotificationService()->markRead(
            notificationId: $notification->id,
            actorAccountType: 'admin',   // wrong portal type
            actorAccountId: 10,
            portal: 'admin',
        ))->toThrow(RuntimeException::class, 'Access denied');
    });

    it('mark-read is idempotent', function (): void {
        $notification = sendTestNotification(accountType: 'guardian', accountId: 5, portal: 'guardian');

        makeNotificationService()->markRead($notification->id, 'guardian', 5, 'guardian');
        $second = makeNotificationService()->markRead($notification->id, 'guardian', 5, 'guardian');

        expect($second->read_at)->not->toBeNull();
        // Only one update should have been applied — read_at is set once
        expect(PortalNotification::find($notification->id)->read_at)->not->toBeNull();
    });

});

// ---------------------------------------------------------------------------
// Cross-portal access isolation — dismiss
// ---------------------------------------------------------------------------

describe('Cross-portal access isolation — dismiss', function (): void {

    it('dismiss by the correct recipient succeeds', function (): void {
        $notification = sendTestNotification(accountType: 'staff', accountId: 7, portal: 'staff',
            type: NotificationType::MARK_SHEET_RETURNED, messageKey: 'mark_sheet.returned',
            params: ['subject_name' => 'Math', 'class_name' => '5A', 'sheet_id' => 1]);

        $updated = makeNotificationService()->dismiss(
            notificationId: $notification->id,
            actorAccountType: 'staff',
            actorAccountId: 7,
            portal: 'staff',
        );

        expect($updated->dismissed_at)->not->toBeNull();
    });

    it('dismiss by a different account is denied', function (): void {
        $notification = sendTestNotification(accountType: 'staff', accountId: 7, portal: 'staff',
            type: NotificationType::MARK_SHEET_RETURNED, messageKey: 'mark_sheet.returned',
            params: ['subject_name' => 'Math', 'class_name' => '5A', 'sheet_id' => 1]);

        expect(fn () => makeNotificationService()->dismiss(
            notificationId: $notification->id,
            actorAccountType: 'staff',
            actorAccountId: 999,
            portal: 'staff',
        ))->toThrow(RuntimeException::class, 'Access denied');
    });

});

// ---------------------------------------------------------------------------
// Notification preferences — opt-out suppresses delivery
// ---------------------------------------------------------------------------

describe('Notification preferences — opt-out', function (): void {

    it('notification is skipped when recipient has disabled the type', function (): void {
        // Insert a preference row disabling this notification type
        DB::table('notification_preferences')->insert([
            'account_type' => 'guardian',
            'account_id' => 1,
            'portal' => 'guardian',
            'notification_type' => NotificationType::CORRECTION_REQUEST_APPROVED,
            'enabled' => false,
            'email_enabled' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = sendTestNotification();

        expect($result)->toBeNull()
            ->and(PortalNotification::count())->toBe(0);
    });

    it('notification is created when recipient has no preference row (default enabled)', function (): void {
        // No preference row → default is enabled
        $result = sendTestNotification();

        expect($result)->not->toBeNull()
            ->and(PortalNotification::count())->toBe(1);
    });

});

// ---------------------------------------------------------------------------
// Unread count and portal scoping
// ---------------------------------------------------------------------------

describe('Unread count and portal scoping', function (): void {

    it('unreadCount returns only unread non-expired notifications for the recipient', function (): void {
        // Create two notifications for guardian account 1
        sendTestNotification(accountId: 1);
        sendTestNotification(accountId: 1);
        // Create one for a different account — must not be counted
        sendTestNotification(accountId: 2);

        $count = makeNotificationService()->unreadCount('guardian', 1, 'guardian');

        expect($count)->toBe(2);
    });

    it('read notifications are excluded from unread count', function (): void {
        $n = sendTestNotification(accountId: 3);
        makeNotificationService()->markRead($n->id, 'guardian', 3, 'guardian');

        $count = makeNotificationService()->unreadCount('guardian', 3, 'guardian');

        expect($count)->toBe(0);
    });

    it('expired notifications are excluded from unread count', function (): void {
        makeNotificationService()->send(
            notificationType: NotificationType::CORRECTION_REQUEST_APPROVED,
            recipientAccountType: 'guardian',
            recipientAccountId: 4,
            portal: 'guardian',
            messageKey: 'correction_request.approved',
            messageParams: ['student_name' => 'Bob', 'request_id' => 1],
            expiresAt: now()->subMinute(), // already expired
        );

        $count = makeNotificationService()->unreadCount('guardian', 4, 'guardian');

        expect($count)->toBe(0);
    });

    it('notifications in different portals are isolated from each other', function (): void {
        // Send an admin notification to account ID 1
        makeNotificationService()->send(
            notificationType: NotificationType::WORKFLOW_TRANSITION,
            recipientAccountType: 'admin',
            recipientAccountId: 1,
            portal: 'admin',
            messageKey: 'workflow.transition',
            messageParams: ['from_state' => 'draft', 'to_state' => 'approved', 'instance_id' => 1],
        );

        // Guardian account 1 should see zero admin notifications
        $guardianCount = makeNotificationService()->unreadCount('guardian', 1, 'guardian');
        $adminCount = makeNotificationService()->unreadCount('admin', 1, 'admin');

        expect($guardianCount)->toBe(0)
            ->and($adminCount)->toBe(1);
    });

});

// ---------------------------------------------------------------------------
// NotifyOnTransition action
// ---------------------------------------------------------------------------

describe('NotifyOnTransition action', function (): void {

    it('dispatches a notification via the action', function (): void {
        $action = new NotifyOnTransition(makeNotificationService());

        $action(
            notificationType: NotificationType::CORRECTION_REQUEST_SUBMITTED,
            recipientAccountType: 'admin',
            recipientAccountId: 1,
            portal: 'admin',
            messageKey: 'correction_request.submitted',
            messageParams: ['student_name' => 'Alice', 'subject' => 'Name', 'request_id' => 1],
            subjectType: 'CorrectionRequest',
            subjectId: 1,
        );

        expect(PortalNotification::count())->toBe(1)
            ->and(PortalNotification::first()->subject_type)->toBe('CorrectionRequest')
            ->and(PortalNotification::first()->subject_id)->toBe(1);
    });

    it('throws InvalidArgumentException for unknown notification type', function (): void {
        $action = new NotifyOnTransition(makeNotificationService());

        expect(fn () => $action(
            notificationType: 'unknown.type',
            recipientAccountType: 'admin',
            recipientAccountId: 1,
            portal: 'admin',
            messageKey: 'correction_request.submitted',
        ))->toThrow(InvalidArgumentException::class);
    });

});

// ---------------------------------------------------------------------------
// OperationStatus actor isolation
// ---------------------------------------------------------------------------

describe('OperationStatus actor isolation', function (): void {

    it('findForActor returns null when operation belongs to a different actor', function (): void {
        $svc = new OperationStatusService;

        // Actor 1 creates an operation
        $op = $svc->create('admin', 1, 'admin', 'pdf_export');

        // Actor 2 tries to access it → must get null
        $result = $svc->findForActor($op->id, 'admin', 2, 'admin');

        expect($result)->toBeNull();
    });

    it('findForActor returns the operation when actor matches', function (): void {
        $svc = new OperationStatusService;
        $op = $svc->create('admin', 1, 'admin', 'bulk_import');

        $result = $svc->findForActor($op->id, 'admin', 1, 'admin');

        expect($result)->not->toBeNull()
            ->and($result->id)->toBe($op->id);
    });

    it('operation status transitions from queued through running to completed', function (): void {
        $svc = new OperationStatusService;
        $op = $svc->create('staff', 5, 'staff', 'report_export');

        expect($op->status)->toBe('queued');

        $running = $svc->markRunning($op->id, jobId: 99);
        expect($running->status)->toBe('running')
            ->and($running->attempts)->toBe(1)
            ->and($running->started_at)->not->toBeNull();

        $completed = $svc->markCompleted($op->id, '/exports/report.pdf');
        expect($completed->status)->toBe('completed')
            ->and($completed->progress_percent)->toBe(100)
            ->and($completed->output_reference)->toBe('/exports/report.pdf');
    });

    it('failure_summary is truncated to 500 characters', function (): void {
        $svc = new OperationStatusService;
        $op = $svc->create('admin', 1, 'admin', 'pdf_export');
        $svc->markRunning($op->id);

        $longSummary = str_repeat('x', 600);
        $failed = $svc->markFailed($op->id, $longSummary);

        expect(mb_strlen($failed->failure_summary))->toBeLessThanOrEqual(500);
    });

});

<?php

declare(strict_types=1);

namespace Modules\Notifications\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\Notifications\Data\NotificationType;
use Modules\Notifications\Models\NotificationPreference;
use Modules\Notifications\Models\PortalNotification;

/**
 * Creates, reads, and dismisses in-app portal notifications.
 *
 * Security properties enforced here:
 *   - message_key must exist in the English translation file (prevents undefined-key exploits)
 *   - message_params is validated against a per-type allowlist (no sensitive values stored)
 *   - recipient identity (type + id + portal) is set by the caller from server-verified context
 *   - read/dismiss actions re-verify that the acting user is the notification owner
 */
final class NotificationService
{
    /**
     * Per-type allowlist of params keys that are safe to persist.
     * Any key not in this list is stripped silently before DB insert.
     *
     * Values may be institution names, dates, counts — never national IDs,
     * passwords, tokens, or raw stack traces.
     *
     * @var array<string, list<string>>
     */
    private const SAFE_PARAM_KEYS = [
        NotificationType::CORRECTION_REQUEST_SUBMITTED => ['student_name', 'subject', 'request_id'],
        NotificationType::CORRECTION_REQUEST_APPROVED => ['student_name', 'subject', 'request_id'],
        NotificationType::CORRECTION_REQUEST_REJECTED => ['student_name', 'subject', 'request_id', 'reason'],
        NotificationType::CORRECTION_REQUEST_APPLIED => ['student_name', 'subject', 'request_id'],
        NotificationType::DOCUMENT_REQUEST_SUBMITTED => ['student_name', 'document_type', 'request_id'],
        NotificationType::DOCUMENT_REQUEST_APPROVED => ['student_name', 'document_type', 'request_id'],
        NotificationType::DOCUMENT_REQUEST_REJECTED => ['student_name', 'document_type', 'request_id', 'reason'],
        NotificationType::DOCUMENT_REQUEST_READY => ['student_name', 'document_type', 'request_id'],
        NotificationType::DOCUMENT_REQUEST_ISSUED => ['student_name', 'document_type', 'request_id'],
        NotificationType::FORMAL_REQUEST_SUBMITTED => ['institution_name', 'request_id', 'subject'],
        NotificationType::FORMAL_REQUEST_RESPONDED => ['institution_name', 'request_id', 'subject'],
        NotificationType::MARK_SHEET_RETURNED => ['subject_name', 'class_name', 'sheet_id', 'reason'],
        NotificationType::MARK_SHEET_VERIFIED => ['subject_name', 'class_name', 'sheet_id'],
        NotificationType::ATTENDANCE_SHEET_RETURNED => ['class_name', 'date', 'sheet_id', 'reason'],
        NotificationType::ATTENDANCE_SHEET_VERIFIED => ['class_name', 'date', 'sheet_id'],
        NotificationType::WORKFLOW_TRANSITION => ['from_state', 'to_state', 'instance_id'],
        NotificationType::OPERATION_COMPLETED => ['operation_type', 'duration_seconds'],
        NotificationType::OPERATION_FAILED => ['operation_type', 'failure_summary'],
    ];

    /**
     * Send an in-app notification.
     *
     * Does nothing when the recipient has disabled this notification type in
     * their preferences. Validates the message_key and strips unsafe params.
     *
     * @param  string  $notificationType  NotificationType constant value
     * @param  string  $recipientAccountType  'admin' | 'staff' | 'guardian'
     * @param  string  $portal  'admin' | 'staff' | 'guardian'
     * @param  string  $messageKey  Translation key (must exist in lang/en/notifications.php)
     * @param  array<string, scalar>  $messageParams  Safe interpolation values
     * @param  string|null  $subjectType  Domain class name (e.g. 'CorrectionRequest')
     * @param  int|null  $subjectId  Domain entity primary key
     * @param  int  $priority  1–4
     * @param  string|null  $actionRouteIdentifier  Named route key (resolved server-side)
     */
    public function send(
        string $notificationType,
        string $recipientAccountType,
        int $recipientAccountId,
        string $portal,
        string $messageKey,
        array $messageParams = [],
        ?string $subjectType = null,
        ?int $subjectId = null,
        int $priority = 2,
        ?string $actionRouteIdentifier = null,
        ?\DateTimeInterface $expiresAt = null,
    ): ?PortalNotification {
        // Guard: validate notification type is known
        if (! in_array($notificationType, NotificationType::all(), true)) {
            throw new \InvalidArgumentException("Unknown notification type: {$notificationType}");
        }

        // Guard: validate message key exists in translation catalogue
        if (! $this->messageKeyExists($messageKey)) {
            throw new \InvalidArgumentException("Unknown notification message key: {$messageKey}");
        }

        // Guard: check recipient preference (default = enabled when no row exists)
        $pref = NotificationPreference::where('account_type', $recipientAccountType)
            ->where('account_id', $recipientAccountId)
            ->where('portal', $portal)
            ->where('notification_type', $notificationType)
            ->first();

        if ($pref !== null && ! $pref->enabled) {
            // Recipient has disabled this notification type — skip silently
            return null;
        }

        // Sanitize params: strip any key not in the per-type allowlist
        $safeParams = $this->sanitizeParams($notificationType, $messageParams);

        $notification = new PortalNotification;
        $notification->recipient_account_type = $recipientAccountType;
        $notification->recipient_account_id = $recipientAccountId;
        $notification->portal = $portal;
        $notification->notification_type = $notificationType;
        $notification->message_key = $messageKey;
        $notification->message_params = empty($safeParams) ? null : $safeParams;
        $notification->subject_type = $subjectType;
        $notification->subject_id = $subjectId;
        $notification->priority = max(1, min(4, $priority));
        $notification->action_route_identifier = $actionRouteIdentifier;
        $notification->expires_at = $expiresAt;
        $notification->save();

        return $notification;
    }

    /**
     * Mark a notification as read.
     *
     * Re-verifies that the acting user is the notification recipient before
     * updating. Idempotent — already-read notifications are returned as-is.
     *
     * @throws ModelNotFoundException
     * @throws \RuntimeException When the actor is not the recipient.
     */
    public function markRead(
        int $notificationId,
        string $actorAccountType,
        int $actorAccountId,
        string $portal,
    ): PortalNotification {
        $notification = PortalNotification::findOrFail($notificationId);

        $this->assertRecipient($notification, $actorAccountType, $actorAccountId, $portal);

        if ($notification->read_at === null) {
            DB::table('portal_notifications')
                ->where('id', $notificationId)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            $notification->refresh();
        }

        return $notification;
    }

    /**
     * Dismiss (hide) a notification.
     *
     * Dismissed notifications are excluded from the bell dropdown and unread
     * count but remain in the database for audit purposes.
     *
     * @throws ModelNotFoundException
     * @throws \RuntimeException When the actor is not the recipient.
     */
    public function dismiss(
        int $notificationId,
        string $actorAccountType,
        int $actorAccountId,
        string $portal,
    ): PortalNotification {
        $notification = PortalNotification::findOrFail($notificationId);

        $this->assertRecipient($notification, $actorAccountType, $actorAccountId, $portal);

        if ($notification->dismissed_at === null) {
            DB::table('portal_notifications')
                ->where('id', $notificationId)
                ->whereNull('dismissed_at')
                ->update(['dismissed_at' => now()]);

            $notification->refresh();
        }

        return $notification;
    }

    /**
     * Count unread notifications for a recipient.
     */
    public function unreadCount(string $accountType, int $accountId, string $portal): int
    {
        return (int) PortalNotification::forRecipient($accountType, $accountId, $portal)
            ->unread()
            ->count();
    }

    /**
     * Fetch recent active notifications for the bell dropdown.
     *
     * @return Collection<int, PortalNotification>
     */
    public function recent(string $accountType, int $accountId, string $portal, int $limit = 15)
    {
        return PortalNotification::forRecipient($accountType, $accountId, $portal)
            ->active()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    // -----------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------

    private function messageKeyExists(string $key): bool
    {
        // Check the English translation file as the canonical source.
        // trans() returns the key itself when translation is missing.
        $translated = trans("notifications.{$key}");

        return $translated !== "notifications.{$key}";
    }

    /**
     * Strip any param key not in the per-type safe allowlist.
     *
     * @param  array<string, scalar>  $params
     * @return array<string, scalar>
     */
    private function sanitizeParams(string $type, array $params): array
    {
        $allowed = self::SAFE_PARAM_KEYS[$type] ?? [];

        if (empty($allowed)) {
            return [];
        }

        return array_intersect_key($params, array_flip($allowed));
    }

    /**
     * Assert that the acting user is the intended recipient.
     *
     * @throws \RuntimeException
     */
    private function assertRecipient(
        PortalNotification $notification,
        string $actorAccountType,
        int $actorAccountId,
        string $portal,
    ): void {
        if (
            $notification->recipient_account_type !== $actorAccountType
            || (int) $notification->recipient_account_id !== $actorAccountId
            || $notification->portal !== $portal
        ) {
            throw new \RuntimeException(
                'Access denied: the acting user is not the recipient of this notification.'
            );
        }
    }
}

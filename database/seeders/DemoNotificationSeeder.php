<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Notifications\Data\NotificationType;
use Modules\Notifications\Services\NotificationService;

/**
 * Seeds portal notifications for all three portals (admin, staff, guardian)
 * covering every notification type in the catalogue, with a mix of unread,
 * read, dismissed, and one expired notification.
 *
 * Idempotent: skips when demo notifications already exist for the guardian.
 *
 * Runs AFTER: DemoAccountSeeder.
 */
final class DemoNotificationSeeder extends Seeder
{
    public function run(): void
    {
        abort_if(
            app()->isProduction(),
            403,
            'DemoNotificationSeeder must not run in production.',
        );

        $guardianId = (int) DB::table('guardian_accounts')
            ->where('login_identifier', 'guardian@gcv.demo')->value('id');
        $teacherId = (int) DB::table('staff_accounts')->where('username', 'teacher@gcv.demo')->value('id');
        $principalId = (int) DB::table('staff_accounts')->where('username', 'principal@gcv.demo')->value('id');
        $adminId = (int) DB::table('administrative_accounts')->where('username', 'admin@gcv.demo')->value('id');

        if ($guardianId === 0 || $teacherId === 0 || $adminId === 0) {
            $this->command?->warn('DemoNotificationSeeder: demo accounts missing. Skipping.');

            return;
        }

        // Note: correction/document services already dispatch real guardian
        // notifications during earlier demo seeders, so key idempotency on a
        // type only this seeder creates.
        if (DB::table('portal_notifications')
            ->where('notification_type', 'workflow.transition')
            ->where('recipient_account_type', 'admin')
            ->exists()) {
            $this->command?->info('DemoNotificationSeeder: demo notifications already seeded. Skipping.');

            return;
        }

        /** @var NotificationService $svc */
        $svc = app(NotificationService::class);

        $studentParams = ['student_name' => 'أحمد ناصر'];

        $send = fn (string $type, string $accountType, int $accountId, string $portal, array $params, int $priority = 2) => $svc->send(
            notificationType: $type,
            recipientAccountType: $accountType,
            recipientAccountId: $accountId,
            portal: $portal,
            messageKey: $type,
            messageParams: $params,
            priority: $priority,
        );

        $sheetParams = ['subject_name' => 'Mathematics', 'class_name' => 'Grade 1 — A', 'date' => now()->subDays(2)->toDateString()];

        $plan = [
            // Guardian portal
            ['correction_request.submitted', 'guardian', $guardianId, 'guardian', $studentParams],
            ['correction_request.approved', 'guardian', $guardianId, 'guardian', $studentParams],
            ['correction_request.rejected', 'guardian', $guardianId, 'guardian', $studentParams],
            ['correction_request.applied', 'guardian', $guardianId, 'guardian', $studentParams],
            ['document_request.submitted', 'guardian', $guardianId, 'guardian', $studentParams],
            ['document_request.approved', 'guardian', $guardianId, 'guardian', $studentParams],
            ['document_request.rejected', 'guardian', $guardianId, 'guardian', $studentParams],
            ['document_request.ready', 'guardian', $guardianId, 'guardian', $studentParams + ['document_type' => 'إثبات قيد']],
            ['document_request.issued', 'guardian', $guardianId, 'guardian', $studentParams + ['document_type' => 'إثبات قيد']],

            // Staff portal
            ['mark_sheet.returned', 'staff', $teacherId, 'staff', $sheetParams],
            ['mark_sheet.verified', 'staff', $teacherId, 'staff', $sheetParams],
            ['attendance_sheet.returned', 'staff', $teacherId, 'staff', $sheetParams],
            ['attendance_sheet.verified', 'staff', $teacherId, 'staff', $sheetParams],
            ['formal_request.returned', 'staff', $principalId ?: $teacherId, 'staff', ['institution_name' => 'Academy 1']],
            ['formal_request.responded', 'staff', $principalId ?: $teacherId, 'staff', ['institution_name' => 'Academy 1']],

            // Admin portal
            ['formal_request.submitted', 'admin', $adminId, 'admin', ['institution_name' => 'Academy 1']],
            ['workflow.transition', 'admin', $adminId, 'admin', ['from_state' => 'submitted', 'to_state' => 'under_review']],
            ['operation.completed', 'admin', $adminId, 'admin', ['operation_type' => 'report export']],
            ['operation.failed', 'admin', $adminId, 'admin', ['operation_type' => 'report export']],
        ];

        // Sanity: make sure every catalogue type is covered.
        $covered = array_column($plan, 0);
        foreach (NotificationType::all() as $type) {
            if (! in_array($type, $covered, true)) {
                $plan[] = [$type, 'admin', $adminId, 'admin', []];
            }
        }

        $ids = [];
        foreach ($plan as [$type, $accountType, $accountId, $portal, $params]) {
            $n = $send($type, $accountType, $accountId, $portal, $params);
            if ($n !== null) {
                $ids[$type.'|'.$accountType] = $n->id;
            }
        }

        // Mark a mix of read / dismissed / expired.
        $mark = function (?int $id, array $attrs): void {
            if ($id !== null) {
                DB::table('portal_notifications')->where('id', $id)->update($attrs);
            }
        };

        $mark($ids['correction_request.approved|guardian'] ?? null, ['read_at' => now()->subDay()]);
        $mark($ids['document_request.issued|guardian'] ?? null, ['read_at' => now()->subHours(3)]);
        $mark($ids['mark_sheet.verified|staff'] ?? null, ['read_at' => now()->subDay()]);
        $mark($ids['correction_request.rejected|guardian'] ?? null, ['read_at' => now()->subDays(2), 'dismissed_at' => now()->subDay()]);
        $mark($ids['operation.completed|admin'] ?? null, ['expires_at' => now()->subDay()]); // expired

        $this->command?->info('DemoNotificationSeeder: seeded notifications for all portals and types.');
    }
}

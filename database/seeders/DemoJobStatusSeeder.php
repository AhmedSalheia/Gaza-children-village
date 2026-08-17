<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Notifications\Services\OperationStatusService;

/**
 * Seeds background-operation status records (queued/running/completed/failed)
 * and report_run history rows for the demo dataset.
 *
 * Idempotent: skips when the demo operation scope marker exists.
 *
 * Runs AFTER: DemoAccountSeeder.
 */
final class DemoJobStatusSeeder extends Seeder
{
    public function run(): void
    {
        abort_if(
            app()->isProduction(),
            403,
            'DemoJobStatusSeeder must not run in production.',
        );

        $adminId = (int) DB::table('administrative_accounts')->where('username', 'admin@gcv.demo')->value('id');
        $principalId = (int) DB::table('staff_accounts')->where('username', 'principal@gcv.demo')->value('id');

        if ($adminId === 0) {
            $this->command?->warn('DemoJobStatusSeeder: demo admin account missing. Skipping.');

            return;
        }

        if (DB::table('operation_statuses')->where('scope', 'like', '%"demo_seed":true%')->exists()) {
            $this->command?->info('DemoJobStatusSeeder: demo job statuses already seeded. Skipping.');

            return;
        }

        /** @var OperationStatusService $svc */
        $svc = app(OperationStatusService::class);
        $scope = ['demo_seed' => true];

        // queued
        $svc->create('administrative', $adminId, 'admin', 'report_export', $scope + ['report' => 'student_registry']);

        // running
        $running = $svc->create('administrative', $adminId, 'admin', 'report_export', $scope + ['report' => 'correction_requests']);
        $svc->markRunning($running->id);
        $svc->updateProgress($running->id, 45);

        // completed
        $completed = $svc->create('administrative', $adminId, 'admin', 'report_export', $scope + ['report' => 'issued_documents']);
        $svc->markRunning($completed->id);
        $svc->markCompleted($completed->id, 'exports/demo/issued_documents.xlsx');

        // failed
        $failed = $svc->create('staff', $principalId ?: $adminId, 'staff', 'report_export', $scope + ['report' => 'student_attendance']);
        $svc->markRunning($failed->id);
        $svc->markFailed($failed->id, 'Export exceeded the maximum row limit and was aborted.');

        // Report run history (preview + export runs)
        $now = now();
        $runs = [
            ['definition_code' => 'student_registry', 'actor_type' => 'administrative', 'actor_account_id' => $adminId, 'portal' => 'admin', 'run_mode' => 'preview', 'row_count' => 32, 'operation_status_id' => null],
            ['definition_code' => 'correction_requests', 'actor_type' => 'administrative', 'actor_account_id' => $adminId, 'portal' => 'admin', 'run_mode' => 'preview', 'row_count' => 11, 'operation_status_id' => null],
            ['definition_code' => 'issued_documents', 'actor_type' => 'administrative', 'actor_account_id' => $adminId, 'portal' => 'admin', 'run_mode' => 'export', 'row_count' => 4, 'operation_status_id' => $completed->id],
            ['definition_code' => 'student_attendance', 'actor_type' => 'staff', 'actor_account_id' => $principalId ?: $adminId, 'portal' => 'staff', 'run_mode' => 'export', 'row_count' => null, 'operation_status_id' => $failed->id],
        ];

        foreach ($runs as $run) {
            if (! DB::table('report_definitions')->where('code', $run['definition_code'])->exists()) {
                continue;
            }

            DB::table('report_runs')->insert($run + [
                'scope' => json_encode(['demo_seed' => true]),
                'locale' => 'ar',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command?->info('DemoJobStatusSeeder: seeded operation statuses and report runs.');
    }
}

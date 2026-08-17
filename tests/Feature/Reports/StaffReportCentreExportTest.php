<?php

declare(strict_types=1);

namespace Tests\Feature\Reports;

use App\Livewire\Staff\Reports\StaffReportCentre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Accounts\Models\StaffAccount;
use Tests\TestCase;

/**
 * Staff report centre export behavior:
 *  1. Over-threshold exports are queued (GenerateReportJob) instead of being
 *     materialized in the Livewire request.
 *  2. Under-threshold exports run synchronously (no job pushed).
 *  3. Export without export.create → 403 even with a forged canExport flag.
 */
final class StaffReportCentreExportTest extends TestCase
{
    use RefreshDatabase;

    private int $instId = 0;

    private int $semId = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $orgId = (int) DB::table('organizations')->insertGetId([
            'code' => 'ORG-RPT', 'name_en' => 'Org', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $typeId = (int) DB::table('institution_types')->insertGetId([
            'code' => 'TYPE-RPT', 'name_en' => 'School', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->instId = (int) DB::table('institutions')->insertGetId([
            'organization_id' => $orgId, 'institution_type_id' => $typeId,
            'code' => 'INST-RPT', 'name_en' => 'Inst', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $yearId = (int) DB::table('academic_years')->insertGetId([
            'organization_id' => $orgId, 'code' => 'AY-RPT', 'name_en' => 'Y', 'name_ar' => 'سنة',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $semesterId = (int) DB::table('semesters')->insertGetId([
            'academic_year_id' => $yearId, 'code' => 'S1-RPT', 'name_en' => 'S1', 'name_ar' => 'فصل',
            'sequence' => 1, 'starts_on' => '2026-01-01', 'ends_on' => '2026-06-30', 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->semId = (int) DB::table('institution_semesters')->insertGetId([
            'institution_id' => $this->instId, 'semester_id' => $semesterId, 'status' => 'open',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $seederClass = 'Modules\\Reporting\\Database\\Seeders\\ReportDefinitionSeeder';
        (new $seederClass)->run();
    }

    private function seedPermissions(array $keys): void
    {
        $roleId = (int) DB::table('roles')->insertGetId([
            'code' => 'RPT_TEST_ROLE_'.implode('_', array_map(fn ($k) => substr(md5($k), 0, 4), $keys)),
            'label' => 'Report role', 'is_protected' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach ($keys as $key) {
            $permId = (int) DB::table('permissions')->insertGetId([
                'key' => $key, 'description' => $key, 'group' => 'reporting', 'is_system' => false,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('role_permissions')->insert([
                'role_id' => $roleId, 'permission_id' => $permId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        DB::table('position_role_grants')->insert([
            'position_definition' => 'principal', 'role_id' => $roleId, 'granted_by' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function makeStaffAccount(): StaffAccount
    {
        $account = StaffAccount::factory()->active()->create();

        $personId = (int) DB::table('people')->insertGetId([
            'full_name_ar' => 'موظف تقارير', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $profileId = (int) DB::table('staff_profiles')->insertGetId([
            'person_id' => $personId, 'staff_code' => 'STF-RPT-'.$personId,
            'employment_status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('staff_accounts')->where('id', $account->getKey())
            ->update(['staff_profile_id' => $profileId]);
        $account->setAttribute('staff_profile_id', $profileId);

        $assignmentId = (int) DB::table('staff_institution_assignments')->insertGetId([
            'staff_profile_id' => $profileId, 'institution_id' => $this->instId,
            'started_on' => '2024-01-01', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('staff_positions')->insert([
            'staff_profile_id' => $profileId,
            'staff_institution_assignment_id' => $assignmentId,
            'institution_id' => $this->instId,
            'institution_semester_id' => $this->semId,
            'position_definition' => 'principal',
            'started_on' => '2024-01-01', 'ended_on' => null, 'created_by' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $account;
    }

    private function seedExportHistoryRows(int $accountId, int $count): void
    {
        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $rows[] = [
                'export_type' => 'student_registry', 'actor_type' => 'staff',
                'actor_account_id' => $accountId, 'scope' => '{}', 'locale' => 'ar',
                'row_count' => 1, 'file_path' => "reports/h{$i}.xlsx",
                'created_at' => now(), 'updated_at' => now(),
            ];
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('report_exports')->insert($chunk);
        }
    }

    public function test_over_threshold_staff_export_queues_a_background_job(): void
    {
        Queue::fake();
        $this->seedPermissions(['report.read', 'export.create']);
        $account = $this->makeStaffAccount();

        $this->seedExportHistoryRows((int) $account->getKey(), StaffReportCentre::ASYNC_THRESHOLD + 5);

        $component = Livewire::actingAs($account, 'staff')
            ->test(StaffReportCentre::class)
            ->call('selectDefinition', 'export_job_history')
            ->call('exportReport');

        $jobClass = 'Modules\\Reporting\\Jobs\\GenerateReportJob';
        Queue::assertPushed($jobClass, 1);

        $this->assertNotNull($component->get('pendingOperationId'));
        $this->assertSame(0, DB::table('report_exports')
            ->where('export_type', 'export_job_history')->count(),
            'Over-threshold export must not be materialized synchronously');
    }

    public function test_under_threshold_staff_export_runs_synchronously(): void
    {
        Queue::fake();
        $this->seedPermissions(['report.read', 'export.create']);
        $account = $this->makeStaffAccount();

        $this->seedExportHistoryRows((int) $account->getKey(), 3);

        Livewire::actingAs($account, 'staff')
            ->test(StaffReportCentre::class)
            ->call('selectDefinition', 'export_job_history')
            ->call('exportReport')
            ->assertDispatched('start-download');

        $jobClass = 'Modules\\Reporting\\Jobs\\GenerateReportJob';
        Queue::assertNotPushed($jobClass);
    }

    public function test_actions_blocked_when_report_read_is_missing(): void
    {
        // Definition permission present, but centre permission report.read absent.
        $this->seedPermissions(['export.create']);
        $account = $this->makeStaffAccount();

        Livewire::actingAs($account, 'staff')
            ->test(StaffReportCentre::class)
            ->assertStatus(403);
    }

    public function test_run_report_rejected_when_report_read_revoked_after_mount(): void
    {
        $this->seedPermissions(['report.read', 'export.create']);
        $account = $this->makeStaffAccount();

        $component = Livewire::actingAs($account, 'staff')
            ->test(StaffReportCentre::class)
            ->call('selectDefinition', 'export_job_history');

        // Revoke report.read after the component snapshot was issued
        DB::table('permissions')->where('key', 'report.read')->delete();

        $component->call('runReport')->assertStatus(403);
    }

    public function test_download_rejected_after_permission_revocation(): void
    {
        $this->seedPermissions(['report.read', 'export.create']);
        $account = $this->makeStaffAccount();

        $this->seedExportHistoryRows((int) $account->getKey(), 2);

        // Generate a sync export (creates a report_exports row + file)
        Livewire::actingAs($account, 'staff')
            ->test(StaffReportCentre::class)
            ->call('selectDefinition', 'export_job_history')
            ->call('exportReport')
            ->assertDispatched('start-download');

        $exportId = (int) DB::table('report_exports')
            ->where('export_type', 'export_job_history')->value('id');

        // Download works while authorized
        $this->actingAs($account, 'staff')
            ->get(route('staff.reports.download', ['export' => encrypt($exportId)]))
            ->assertOk();

        // Revoke permissions → the saved download URL must stop working
        DB::table('permissions')->whereIn('key', ['report.read', 'export.create'])->delete();

        $this->actingAs($account, 'staff')
            ->get(route('staff.reports.download', ['export' => encrypt($exportId)]))
            ->assertForbidden();
    }

    public function test_queued_job_fails_when_authorization_revoked_before_execution(): void
    {
        $this->seedPermissions(['report.read', 'export.create']);
        $account = $this->makeStaffAccount();
        $accountId = (int) $account->getKey();

        $statusServiceClass = 'Modules\\Notifications\\Services\\OperationStatusService';
        $operation = app($statusServiceClass)->create(
            'staff', $accountId, 'staff', 'report_export:export_job_history', [],
        );

        // Revoke permission between dispatch and execution
        DB::table('permissions')->where('key', 'export.create')->delete();

        $jobClass = 'Modules\\Reporting\\Jobs\\GenerateReportJob';
        (new $jobClass('export_job_history', [
            'actor_type' => 'staff',
            'actor_account_id' => $accountId,
            'portal' => 'staff',
            'locale' => 'ar',
        ], (int) $operation->id, 0))->handle(
            app('Modules\Reporting\Services\ReportQueryService'),
            app('Modules\Reporting\Services\FormulaInjectionSanitizer'),
        );

        $status = DB::table('operation_statuses')->where('id', (int) $operation->id)->first();
        $this->assertSame('failed', $status->status);
        $this->assertStringContainsString('Authorization revoked', (string) $status->failure_summary);
        $this->assertSame(0, DB::table('report_exports')->count());
    }

    public function test_null_semester_position_yields_no_rows_and_no_export(): void
    {
        Queue::fake();
        $this->seedPermissions(['report.read', 'export.create']);
        $account = $this->makeStaffAccount();

        // Simulate an active position with no operational scope
        DB::table('staff_positions')->update(['institution_semester_id' => null]);

        $this->seedExportHistoryRows((int) $account->getKey(), 3);

        $component = Livewire::actingAs($account, 'staff')
            ->test(StaffReportCentre::class)
            ->call('selectDefinition', 'export_job_history')
            ->call('runReport');

        $this->assertCount(0, $component->instance()->rows, 'Null-semester position must not yield report rows');

        $component->call('exportReport')
            ->assertNotDispatched('start-download');

        $jobClass = 'Modules\\Reporting\\Jobs\\GenerateReportJob';
        Queue::assertNotPushed($jobClass);

        // No new export row beyond the seeded history
        $this->assertSame(0, DB::table('report_exports')
            ->where('export_type', 'export_job_history')->count());
    }

    public function test_queued_job_fails_when_staff_position_loses_semester_scope(): void
    {
        $this->seedPermissions(['report.read', 'export.create']);
        $account = $this->makeStaffAccount();
        $accountId = (int) $account->getKey();

        $statusServiceClass = 'Modules\\Notifications\\Services\\OperationStatusService';
        $operation = app($statusServiceClass)->create(
            'staff', $accountId, 'staff', 'report_export:export_job_history', [],
        );

        // Scope removed between dispatch and execution
        DB::table('staff_positions')->update(['institution_semester_id' => null]);

        $jobClass = 'Modules\\Reporting\\Jobs\\GenerateReportJob';
        (new $jobClass('export_job_history', [
            'actor_type' => 'staff',
            'actor_account_id' => $accountId,
            'portal' => 'staff',
            'locale' => 'ar',
        ], (int) $operation->id, 0))->handle(
            app('Modules\Reporting\Services\ReportQueryService'),
            app('Modules\Reporting\Services\FormulaInjectionSanitizer'),
        );

        $status = DB::table('operation_statuses')->where('id', (int) $operation->id)->first();
        $this->assertSame('failed', $status->status);
        $this->assertSame(0, DB::table('report_exports')->count());
    }

    public function test_legacy_export_type_downloads_when_authorized_and_blocks_after_revocation(): void
    {
        $this->seedPermissions(['attendance_report.export']);
        $account = $this->makeStaffAccount();

        // Simulate a legacy attendance-report export row + file
        Storage::disk('local')->put('reports/legacy.xlsx', 'x');
        $exportId = (int) DB::table('report_exports')->insertGetId([
            'export_type' => 'attendance_report', 'actor_type' => 'staff',
            'actor_account_id' => (int) $account->getKey(), 'scope' => '{}',
            'locale' => 'ar', 'row_count' => 1, 'file_path' => 'reports/legacy.xlsx',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($account, 'staff')
            ->get(route('staff.reports.download', ['export' => encrypt($exportId)]))
            ->assertOk();

        DB::table('permissions')->where('key', 'attendance_report.export')->delete();

        $this->actingAs($account, 'staff')
            ->get(route('staff.reports.download', ['export' => encrypt($exportId)]))
            ->assertForbidden();
    }

    public function test_queued_job_generates_export_in_bounded_chunks_with_row_cap(): void
    {
        // Tight resource policy: chunk of 10, hard cap of 35 rows.
        config()->set('reporting.export_chunk_size', 10);
        config()->set('reporting.max_export_rows', 35);

        $this->seedPermissions(['report.read', 'export.create']);
        $account = $this->makeStaffAccount();
        $accountId = (int) $account->getKey();

        // 120 history rows — well beyond both chunk size and cap.
        $this->seedExportHistoryRows($accountId, 120);

        $statusServiceClass = 'Modules\\Notifications\\Services\\OperationStatusService';
        $operation = app($statusServiceClass)->create(
            'staff', $accountId, 'staff', 'report_export:export_job_history', [],
        );
        $runId = (int) DB::table('report_runs')->insertGetId([
            'definition_code' => 'export_job_history', 'actor_type' => 'staff',
            'actor_account_id' => $accountId, 'portal' => 'staff', 'scope' => '{}',
            'locale' => 'ar', 'run_mode' => 'export', 'row_count' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $jobClass = 'Modules\\Reporting\\Jobs\\GenerateReportJob';
        (new $jobClass('export_job_history', [
            'actor_type' => 'staff',
            'actor_account_id' => $accountId,
            'portal' => 'staff',
            'locale' => 'ar',
        ], (int) $operation->id, $runId))->handle(
            app('Modules\Reporting\Services\ReportQueryService'),
            app('Modules\Reporting\Services\FormulaInjectionSanitizer'),
        );

        $status = DB::table('operation_statuses')->where('id', (int) $operation->id)->first();
        $this->assertSame('completed', $status->status);

        $export = DB::table('report_exports')
            ->where('export_type', 'export_job_history')->first();
        $this->assertNotNull($export);
        // Row count is capped by the explicit policy — never full materialization.
        $this->assertSame(35, (int) $export->row_count);
        $this->assertTrue(
            Storage::disk('local')->exists($export->file_path),
        );
    }

    public function test_export_without_permission_is_rejected_even_with_forged_flag(): void
    {
        $this->seedPermissions(['report.read']); // no export.create
        $account = $this->makeStaffAccount();

        Livewire::actingAs($account, 'staff')
            ->test(StaffReportCentre::class)
            ->call('selectDefinition', 'export_job_history')
            ->set('canExport', true) // forged client-side flag
            ->call('exportReport')
            ->assertStatus(403);
    }
}

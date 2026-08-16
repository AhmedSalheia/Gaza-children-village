<?php

declare(strict_types=1);

namespace Tests\Feature\Attendance;

use App\Livewire\Staff\Attendance\ScanEventQueue;
use App\Livewire\Staff\Attendance\StaffAttendanceDashboard;
use App\Livewire\Staff\Attendance\StaffAttendanceEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Accounts\Models\StaffAccount;
use Tests\TestCase;

/**
 * Livewire authorization tests for staff attendance components.
 *
 * Verifies that all three components enforce institution-semester and period-grant
 * isolation when a client tampers public Livewire properties or action arguments.
 *
 * Tests cover:
 *   1. Dashboard::attendanceRows  — foreign institution period → empty (no disclosure)
 *   2. Dashboard::attendanceRows  — ungranted local period (secretary) → empty
 *   3. Dashboard::periodSummaries — secretary only sees granted periods
 *   4. Entry::saveRow             — foreign institution period → 403
 *   5. Entry::saveRow             — ungranted local period (secretary) → 403
 *   6. Entry::staffRows           — foreign institution period → empty (no disclosure)
 *   7. ScanQueue::startReview     — foreign institution event → 403
 *   8. ScanQueue::startReview     — ungranted local period event → 403 (secretary)
 *   9. ScanQueue render           — directly-set foreign reviewingEventId → cleared, no disclosure
 *
 * Note on dashboard reads vs. mutations:
 *   attendanceRows() and staffRows() are called from render() — aborting inside
 *   render() breaks the Livewire lifecycle. These reads return empty (no disclosure)
 *   on scope violation. Mutations (saveRow, startReview, etc.) abort 403.
 *
 * NOTE: The HasStaffAuth trait uses a per-instance property cache (not a function-level
 * static) so there is no inter-test cache leakage even when RefreshDatabase reuses
 * the same auto-increment IDs after rolling back transactions.
 */
final class StaffAttendanceAuthTest extends TestCase
{
    use RefreshDatabase;

    // ── Shared org + institution_type ─────────────────────────────────────────

    private int $orgId = 0;

    private int $typeId = 0;

    /** Track per-semester period sequence numbers to satisfy the unique index */
    private array $periodSequence = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgId = (int) DB::table('organizations')->insertGetId([
            'code' => 'ORG-AUTH',
            'name_en' => 'Auth Test Org',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->typeId = (int) DB::table('institution_types')->insertGetId([
            'code' => 'TYPE-AUTH',
            'name_en' => 'School',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->seedMinimalPermissions();
    }

    // ── 1. Dashboard: foreign-institution period returns empty (no disclosure) ─

    public function test_dashboard_attendance_rows_empty_for_foreign_institution_period(): void
    {
        $instA = $this->makeInstitution();
        $semA = $this->makeSemester($instA);
        $account = $this->makeStaffAccount('principal', $instA, $semA);

        $instB = $this->makeInstitution();
        $semB = $this->makeSemester($instB);
        $periodB = $this->makePeriod($semB);

        $component = Livewire::actingAs($account, 'staff')
            ->test(StaffAttendanceDashboard::class)
            ->set('selectedPeriodId', $periodB);  // Tamper to institution B's period

        $rows = $component->instance()->attendanceRows();

        $this->assertCount(0, $rows, 'Foreign-institution period must not disclose any attendance rows');
    }

    // ── 2. Dashboard: secretary tampers to ungranted period → empty ───────────

    public function test_dashboard_attendance_rows_empty_when_secretary_targets_ungranted_period(): void
    {
        $inst = $this->makeInstitution();
        $sem = $this->makeSemester($inst);
        $grantedPeriod = $this->makePeriod($sem);
        $otherPeriod = $this->makePeriod($sem);   // Same institution, not granted

        $account = $this->makeStaffAccount('secretary', $inst, $sem, $grantedPeriod);

        $component = Livewire::actingAs($account, 'staff')
            ->test(StaffAttendanceDashboard::class)
            ->set('selectedPeriodId', $otherPeriod);  // Tamper to ungranted period

        $rows = $component->instance()->attendanceRows();

        $this->assertCount(0, $rows, 'Ungranted period must not disclose any attendance rows to secretary');
    }

    // ── 3. Dashboard: secretary periodSummaries only shows granted periods ────

    public function test_dashboard_period_summaries_excludes_ungranted_periods_for_secretary(): void
    {
        $inst = $this->makeInstitution();
        $sem = $this->makeSemester($inst);
        $grantedPeriod = $this->makePeriod($sem);
        $this->makePeriod($sem); // ungrantedPeriod — must NOT appear in summaries

        $account = $this->makeStaffAccount('secretary', $inst, $sem, $grantedPeriod);

        $component = Livewire::actingAs($account, 'staff')
            ->test(StaffAttendanceDashboard::class);

        $summaries = $component->instance()->periodSummaries();

        $this->assertCount(1, $summaries, 'Secretary should only see their one granted period in summaries');
        $this->assertEquals($grantedPeriod, $summaries[0]->period_id);
    }

    // ── 4. Entry: saveRow blocked on foreign-institution period ───────────────

    public function test_entry_save_row_aborts_on_foreign_institution_period(): void
    {
        $instA = $this->makeInstitution();
        $semA = $this->makeSemester($instA);
        $account = $this->makeStaffAccount('principal', $instA, $semA);

        $instB = $this->makeInstitution();
        $semB = $this->makeSemester($instB);
        $periodB = $this->makePeriod($semB);

        Livewire::actingAs($account, 'staff')
            ->test(StaffAttendanceEntry::class)
            ->set('selectedPeriodId', $periodB)      // Tamper to institution B's period
            ->set('rowStatus', [9999 => 'present'])  // Arbitrary staff profile id
            ->call('saveRow', 9999)
            ->assertForbidden();
    }

    // ── 5. Entry: secretary tampers to ungranted period ───────────────────────

    public function test_entry_save_row_aborts_when_secretary_targets_ungranted_period(): void
    {
        $inst = $this->makeInstitution();
        $sem = $this->makeSemester($inst);
        $grantedPeriod = $this->makePeriod($sem);
        $otherPeriod = $this->makePeriod($sem);

        $account = $this->makeStaffAccount('secretary', $inst, $sem, $grantedPeriod);

        Livewire::actingAs($account, 'staff')
            ->test(StaffAttendanceEntry::class)
            ->set('selectedPeriodId', $otherPeriod)  // Tamper to ungranted period
            ->set('rowStatus', [9999 => 'present'])
            ->call('saveRow', 9999)
            ->assertForbidden();
    }

    // ── 6. Entry: staffRows returns empty (no disclosure) on foreign period ───

    public function test_entry_staff_rows_returns_empty_for_foreign_institution_period(): void
    {
        $instA = $this->makeInstitution();
        $semA = $this->makeSemester($instA);
        $account = $this->makeStaffAccount('principal', $instA, $semA);

        // Create a staff member at institution B with an attendance record
        $instB = $this->makeInstitution();
        $semB = $this->makeSemester($instB);
        $periodB = $this->makePeriod($semB);

        $personB = (int) DB::table('people')->insertGetId([
            'full_name_ar' => 'Staff B',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $profileB = (int) DB::table('staff_profiles')->insertGetId([
            'person_id' => $personB,
            'staff_code' => 'STF-B-'.uniqid(),
            'employment_status' => 'active',
            'hired_on' => '2024-01-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('staff_attendance_records')->insert([
            'staff_profile_id' => $profileB,
            'operational_period_id' => $periodB,
            'institution_semester_id' => $semB,
            'record_date' => now()->toDateString(),
            'status_code' => 'present',
            'correction_cycle' => 0,
            'is_verified' => false,
            'source' => 'staff',
            'creator_staff_profile_id' => $profileB,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $component = Livewire::actingAs($account, 'staff')
            ->test(StaffAttendanceEntry::class)
            ->set('selectedPeriodId', $periodB)  // Tamper to institution B's period
            ->set('selectedDate', now()->toDateString());

        $rows = $component->instance()->staffRows();

        $this->assertCount(0, $rows, 'No rows should be disclosed for a foreign-institution period');
    }

    // ── 7. ScanQueue: startReview blocked on foreign-institution event ─────────

    public function test_scan_queue_start_review_aborts_on_foreign_institution_event(): void
    {
        $instA = $this->makeInstitution();
        $semA = $this->makeSemester($instA);
        $periodA = $this->makePeriod($semA);
        $accountA = $this->makeStaffAccount('secretary', $instA, $semA, $periodA);

        $instB = $this->makeInstitution();
        $semB = $this->makeSemester($instB);
        $periodB = $this->makePeriod($semB);

        // Create a staff profile + credential in institution B for the scan event
        $credId = $this->makeCredential($this->makeProfileId('Staff-B'));
        $eventId = (int) DB::table('attendance_scan_events')->insertGetId([
            'qr_credential_id' => $credId,
            'staff_profile_id' => 1,
            'institution_semester_id' => $semB,
            'operational_period_id' => $periodB,
            'scanned_at' => now(),
            'scan_date' => now()->toDateString(),
            'direction' => 'arrival',
            'device_fingerprint' => 'device-b',
            'processing_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::actingAs($accountA, 'staff')
            ->test(ScanEventQueue::class)
            ->call('startReview', $eventId)
            ->assertForbidden();
    }

    // ── 8. ScanQueue: startReview blocked when event is in ungranted period ───

    public function test_scan_queue_start_review_aborts_for_secretary_ungranted_period_event(): void
    {
        $inst = $this->makeInstitution();
        $sem = $this->makeSemester($inst);
        $grantedPeriod = $this->makePeriod($sem);
        $otherPeriod = $this->makePeriod($sem);  // Same institution, not granted

        $account = $this->makeStaffAccount('secretary', $inst, $sem, $grantedPeriod);

        // Scan event in same semester but ungranted period
        $credId = $this->makeCredential($this->makeProfileId('Staff-C'));
        $eventId = (int) DB::table('attendance_scan_events')->insertGetId([
            'qr_credential_id' => $credId,
            'staff_profile_id' => 1,
            'institution_semester_id' => $sem,
            'operational_period_id' => $otherPeriod,
            'scanned_at' => now(),
            'scan_date' => now()->toDateString(),
            'direction' => 'arrival',
            'device_fingerprint' => 'device-c',
            'processing_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::actingAs($account, 'staff')
            ->test(ScanEventQueue::class)
            ->call('startReview', $eventId)
            ->assertForbidden();
    }

    // ── 9. ScanQueue render: tampered reviewingEventId is cleared silently ────

    public function test_scan_queue_render_clears_tampered_foreign_reviewing_event_id(): void
    {
        $instA = $this->makeInstitution();
        $semA = $this->makeSemester($instA);
        $periodA = $this->makePeriod($semA);
        $account = $this->makeStaffAccount('secretary', $instA, $semA, $periodA);

        $instB = $this->makeInstitution();
        $semB = $this->makeSemester($instB);
        $periodB = $this->makePeriod($semB);

        // Scan event in institution B
        $credId = $this->makeCredential($this->makeProfileId('Staff-D'));
        $foreignEventId = (int) DB::table('attendance_scan_events')->insertGetId([
            'qr_credential_id' => $credId,
            'staff_profile_id' => 1,
            'institution_semester_id' => $semB,
            'operational_period_id' => $periodB,
            'scanned_at' => now(),
            'scan_date' => now()->toDateString(),
            'direction' => 'arrival',
            'device_fingerprint' => 'device-d',
            'processing_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Directly set reviewingEventId (bypassing startReview which would abort).
        // After render(), scopedReviewingEvent() must clear the ID and return null
        // to prevent metadata disclosure without crashing the component.
        Livewire::actingAs($account, 'staff')
            ->test(ScanEventQueue::class)
            ->set('reviewingEventId', $foreignEventId)
            // After render(), the out-of-scope ID must be cleared
            ->assertSet('reviewingEventId', null);
    }

    // ── Fixture helpers ───────────────────────────────────────────────────────

    /**
     * Seed the minimal permission/role tree required for component mount() calls.
     *
     * Principal and secretary both receive all four attendance permissions so that
     * the tests exercise the SCOPE guards without being stopped at the permission gate.
     */
    private function seedMinimalPermissions(): void
    {
        $permKeys = [
            'staff_attendance.read',
            'staff_attendance.enter',
            'staff_attendance.verify',
            'attendance_scan.review',
        ];

        $principalRoleId = (int) DB::table('roles')->insertGetId([
            'code' => 'PRINCIPAL_TEST_ROLE',
            'label' => 'Principal',
            'is_protected' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $secretaryRoleId = (int) DB::table('roles')->insertGetId([
            'code' => 'SECRETARY_TEST_ROLE',
            'label' => 'Secretary',
            'is_protected' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($permKeys as $key) {
            $permId = (int) DB::table('permissions')->insertGetId([
                'key' => $key,
                'description' => $key,
                'group' => 'attendance',
                'is_system' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('role_permissions')->insert([
                ['role_id' => $principalRoleId, 'permission_id' => $permId, 'created_at' => now(), 'updated_at' => now()],
                ['role_id' => $secretaryRoleId, 'permission_id' => $permId, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // granted_by is NOT NULL; use 0 as a sentinel (SQLite does not enforce FKs for 0)
        DB::table('position_role_grants')->insert([
            ['position_definition' => 'principal', 'role_id' => $principalRoleId, 'granted_by' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['position_definition' => 'secretary', 'role_id' => $secretaryRoleId, 'granted_by' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function makeInstitution(): int
    {
        return (int) DB::table('institutions')->insertGetId([
            'organization_id' => $this->orgId,
            'institution_type_id' => $this->typeId,
            'code' => 'INST-'.uniqid(),
            'name_en' => 'Test Institution',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeSemester(int $institutionId): int
    {
        $yearId = (int) DB::table('academic_years')->insertGetId([
            'organization_id' => $this->orgId,
            'code' => 'AY-'.uniqid(),
            'name_en' => 'Test Year',
            'starts_on' => '2025-09-01',
            'ends_on' => '2026-06-30',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $semId = (int) DB::table('semesters')->insertGetId([
            'code' => 'SEM-'.uniqid(),
            'name_en' => 'First Semester',
            'name_ar' => 'First Semester',
            'sequence' => 1,
            'status' => 'open',
            'academic_year_id' => $yearId,
            'starts_on' => '2025-09-01',
            'ends_on' => '2026-01-31',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('institution_semesters')->insertGetId([
            'institution_id' => $institutionId,
            'semester_id' => $semId,
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** Periods within the same semester need distinct sequence numbers */
    private function makePeriod(int $semesterId): int
    {
        $seq = ($this->periodSequence[$semesterId] ?? 0) + 1;
        $this->periodSequence[$semesterId] = $seq;

        return (int) DB::table('operational_periods')->insertGetId([
            'institution_semester_id' => $semesterId,
            'code' => 'OP-'.uniqid(),
            'name_en' => 'Period-'.uniqid(),
            'name_ar' => 'Period',
            'sequence' => $seq,
            'starts_at' => '08:00:00',
            'ends_at' => '13:00:00',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Create a minimal staff_profiles row and return its id.
     * Used to satisfy FK constraints when building scan events.
     */
    private function makeProfileId(string $label = 'Staff'): int
    {
        $personId = (int) DB::table('people')->insertGetId([
            'full_name_ar' => $label,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('staff_profiles')->insertGetId([
            'person_id' => $personId,
            'staff_code' => 'STF-'.uniqid(),
            'employment_status' => 'active',
            'hired_on' => '2024-01-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Create a staff_qr_credentials row and return its id.
     * Required by the FK on attendance_scan_events.qr_credential_id.
     */
    private function makeCredential(int $staffProfileId): int
    {
        return (int) DB::table('staff_qr_credentials')->insertGetId([
            'staff_profile_id' => $staffProfileId,
            'token_hash' => hash('sha256', uniqid('test-token-', true)),
            'is_active' => true,
            'issued_at' => now(),
            'issued_by_staff_profile_id' => $staffProfileId,
            'revoked_at' => null,
            'revoked_by_staff_profile_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Create a staff account with an active position at the given institution semester.
     *
     * For period-restricted positions (secretary), an explicit period grant is added
     * when $grantedPeriodId is provided.
     */
    private function makeStaffAccount(
        string $positionDef,
        int $institutionId,
        int $semesterId,
        ?int $grantedPeriodId = null,
    ): StaffAccount {
        $account = StaffAccount::factory()->active()->create();

        $profileId = $this->makeProfileId(ucfirst($positionDef).' Staff');

        // Link account → profile
        DB::table('staff_accounts')
            ->where('id', $account->getKey())
            ->update(['staff_profile_id' => $profileId]);

        // Force Eloquent model to reflect the updated profile id
        $account->setAttribute('staff_profile_id', $profileId);

        // Institution assignment (needed by domain actions and institution guards)
        $assignmentId = (int) DB::table('staff_institution_assignments')->insertGetId([
            'staff_profile_id' => $profileId,
            'institution_id' => $institutionId,
            'started_on' => '2024-01-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Position — created_by is NOT NULL; use 0 as sentinel (FK not enforced in SQLite by default)
        $positionId = (int) DB::table('staff_positions')->insertGetId([
            'staff_profile_id' => $profileId,
            'staff_institution_assignment_id' => $assignmentId,
            'institution_id' => $institutionId,
            'institution_semester_id' => $semesterId,
            'position_definition' => $positionDef,
            'started_on' => '2024-01-01',
            'ended_on' => null,
            'created_by' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Period grant for restricted positions
        if ($grantedPeriodId !== null && ! in_array($positionDef, ['principal', 'deputy_principal', 'counselor'], true)) {
            DB::table('staff_position_periods')->insert([
                'staff_position_id' => $positionId,
                'operational_period_id' => $grantedPeriodId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $account;
    }

    /**
     * Create a staff account with a full-scope position that has
     * staff_attendance.enter + staff_attendance.verify but NOT staff_attendance.correct.
     *
     * Used to test that correction operations are blocked for verify-only actors.
     */
    private function makeVerifyOnlyAccount(string $positionDef, int $institutionId, int $semesterId): StaffAccount
    {
        // Create a role with enter + verify (and read so mount passes), but NOT correct
        $roleId = (int) DB::table('roles')->insertGetId([
            'code' => 'VERIFY_ONLY_'.uniqid(),
            'label' => 'Verify-Only',
            'is_protected' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['staff_attendance.enter', 'staff_attendance.verify', 'staff_attendance.read'] as $key) {
            $permId = DB::table('permissions')->where('key', $key)->value('id');

            if ($permId) {
                DB::table('role_permissions')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Map the chosen position_definition to this verify-only role
        DB::table('position_role_grants')->insert([
            'position_definition' => $positionDef,
            'role_id' => $roleId,
            'granted_by' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->makeStaffAccount($positionDef, $institutionId, $semesterId);
    }
}

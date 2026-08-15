<?php

declare(strict_types=1);

namespace Tests\Feature\Attendance;

use App\Livewire\Staff\Attendance\StaffAttendanceEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Accounts\Models\StaffAccount;
use Tests\TestCase;

/**
 * Livewire authorization test: staff_attendance.correct is a distinct permission gate.
 *
 * A counselor (full-scope) with enter+verify but WITHOUT correct must be blocked
 * by both startCorrection() and submitCorrection().  Kept in a separate file so
 * position_role_grants for 'counselor' don't bleed into StaffAttendanceAuthTest's
 * setUp() shared across 9 tests.
 */
final class StaffCorrectionPermissionTest extends TestCase
{
    use RefreshDatabase;

    private int $orgId  = 0;
    private int $typeId = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgId = (int) DB::table('organizations')->insertGetId([
            'code'       => 'ORG-CORRECT',
            'name_en'    => 'Correct Perm Test Org',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->typeId = (int) DB::table('institution_types')->insertGetId([
            'code'       => 'TYPE-CORRECT',
            'name_en'    => 'School',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * A counselor with enter+verify (but NOT correct) must be blocked from
     * startCorrection() and submitCorrection() on the StaffAttendanceEntry component.
     */
    public function test_verify_only_actor_cannot_start_or_submit_correction(): void
    {
        $inst = $this->makeInstitution();
        $sem  = $this->makeSemester($inst);

        // Seed all four attendance permissions
        $permMap = [];

        foreach (['staff_attendance.enter', 'staff_attendance.verify', 'staff_attendance.read', 'staff_attendance.correct'] as $key) {
            $permMap[$key] = (int) DB::table('permissions')->insertGetId([
                'key'         => $key,
                'description' => $key,
                'group'       => 'attendance',
                'is_system'   => false,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // Verify-only role: enter + verify + read — deliberately excludes 'correct'
        $roleId = (int) DB::table('roles')->insertGetId([
            'code'         => 'VERIFY_ONLY_COUNSELOR',
            'label'        => 'Verify Only Counselor',
            'is_protected' => false,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        foreach (['staff_attendance.enter', 'staff_attendance.verify', 'staff_attendance.read'] as $key) {
            DB::table('role_permissions')->insert([
                'role_id'      => $roleId,
                'permission_id' => $permMap[$key],
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        // Counselor → verify-only role (no correct permission granted)
        DB::table('position_role_grants')->insert([
            'position_definition' => 'counselor',
            'role_id'             => $roleId,
            'granted_by'          => 0,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $account = $this->makeStaffAccount('counselor', $inst, $sem);

        // startCorrection — must abort 403 (correct permission missing)
        Livewire::actingAs($account, 'staff')
            ->test(StaffAttendanceEntry::class)
            ->call('startCorrection', 9999)
            ->assertForbidden();

        // submitCorrection — must also abort 403 (same gate)
        Livewire::actingAs($account, 'staff')
            ->test(StaffAttendanceEntry::class)
            ->call('submitCorrection')
            ->assertForbidden();
    }

    // ── Fixture helpers ───────────────────────────────────────────────────────

    private function makeInstitution(): int
    {
        return (int) DB::table('institutions')->insertGetId([
            'organization_id'     => $this->orgId,
            'institution_type_id' => $this->typeId,
            'code'                => 'INST-'.uniqid(),
            'name_en'             => 'Test Institution',
            'is_active'           => true,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    private function makeSemester(int $institutionId): int
    {
        $yearId = (int) DB::table('academic_years')->insertGetId([
            'organization_id' => $this->orgId,
            'code'            => 'AY-'.uniqid(),
            'name_en'         => 'Year',
            'starts_on'       => '2025-09-01',
            'ends_on'         => '2026-06-30',
            'status'          => 'open',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $semId = (int) DB::table('semesters')->insertGetId([
            'code'             => 'SEM-'.uniqid(),
            'name_en'          => 'First',
            'name_ar'          => 'First',
            'sequence'         => 1,
            'status'           => 'open',
            'academic_year_id' => $yearId,
            'starts_on'        => '2025-09-01',
            'ends_on'          => '2026-01-31',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return (int) DB::table('institution_semesters')->insertGetId([
            'institution_id' => $institutionId,
            'semester_id'    => $semId,
            'status'         => 'open',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    private function makeStaffAccount(string $positionDef, int $institutionId, int $semesterId): StaffAccount
    {
        $account = StaffAccount::factory()->active()->create();

        $personId = (int) DB::table('people')->insertGetId([
            'full_name_ar' => ucfirst($positionDef),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $profileId = (int) DB::table('staff_profiles')->insertGetId([
            'person_id'         => $personId,
            'staff_code'        => 'STF-'.uniqid(),
            'employment_status' => 'active',
            'hired_on'          => '2024-01-01',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        DB::table('staff_accounts')
            ->where('id', $account->getKey())
            ->update(['staff_profile_id' => $profileId]);

        $account->setAttribute('staff_profile_id', $profileId);

        $assignmentId = (int) DB::table('staff_institution_assignments')->insertGetId([
            'staff_profile_id' => $profileId,
            'institution_id'   => $institutionId,
            'started_on'       => '2024-01-01',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        DB::table('staff_positions')->insertGetId([
            'staff_profile_id'               => $profileId,
            'staff_institution_assignment_id' => $assignmentId,
            'institution_id'                 => $institutionId,
            'institution_semester_id'        => $semesterId,
            'position_definition'            => $positionDef,
            'started_on'                     => '2024-01-01',
            'ended_on'                       => null,
            'created_by'                     => 0,
            'created_at'                     => now(),
            'updated_at'                     => now(),
        ]);

        return $account;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Attendance;

use App\Livewire\Staff\Attendance\DailyAttendanceSheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Accounts\Models\StaffAccount;
use Tests\TestCase;

/**
 * Livewire component tests for DailyAttendanceSheet homeroom guard.
 *
 * Verifies that:
 *  1. A teacher WITH an active homeroom assignment can mount the component
 *     (sheet is opened successfully via classGroupId + date).
 *  2. A teacher WITHOUT a homeroom assignment receives 403 on mount.
 *  3. A staff member with STUDENT_ATTENDANCE_VERIFY permission (e.g. principal)
 *     can mount the component for any in-scope class regardless of homeroom.
 *
 * The homeroom guard lives in assertHomeroomIfTeacher() called from mount().
 * It skips staff who hold STUDENT_ATTENDANCE_VERIFY; everyone else must have
 * an active homeroom_assignments row for the target class group and semester.
 */
final class DailyAttendanceSheetComponentTest extends TestCase
{
    use RefreshDatabase;

    // ── Shared fixtures ───────────────────────────────────────────────────────

    private int $orgId = 0;

    private int $typeId = 0;

    private array $periodSeq = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgId = (int) DB::table('organizations')->insertGetId([
            'code' => 'ORG-HRM',
            'name_en' => 'Homeroom Test Org',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->typeId = (int) DB::table('institution_types')->insertGetId([
            'code' => 'TYPE-HRM',
            'name_en' => 'School',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->seedPermissions();
    }

    // ── Test 1: teacher with homeroom assignment can open a sheet ─────────────

    public function test_teacher_with_homeroom_assignment_can_mount_and_open_sheet(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeSemester($instId);
        $period = $this->makePeriod($semId);
        $levelId = $this->makeAcademicLevel();
        $classId = $this->makeClassGroup($semId, $period, $levelId);

        $account = $this->makeTeacherAccount($instId, $semId, $period);
        $profile = (int) $account->staff_profile_id;

        // Grant an active homeroom assignment for this class.
        $this->makeHomeroomAssignment($profile, $semId, $classId, $this->resolvePositionId($profile, $semId));

        $component = Livewire::actingAs($account, 'staff')
            ->test(DailyAttendanceSheet::class, [
                'classGroupId' => $classId,
                'date' => '2026-01-15',
            ]);

        // Must render without 403 and the sheet should be opened (sheetId set).
        $component->assertOk();
        $this->assertNotNull(
            $component->get('sheetId'),
            'Teacher with a homeroom assignment must have sheetId set after mount.',
        );
    }

    // ── Test 2: teacher without homeroom assignment receives 403 ──────────────

    public function test_teacher_without_homeroom_assignment_receives_403_on_mount(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeSemester($instId);
        $period = $this->makePeriod($semId);
        $levelId = $this->makeAcademicLevel();
        $classId = $this->makeClassGroup($semId, $period, $levelId);

        // Teacher has scope but NO homeroom_assignments row for this class.
        $account = $this->makeTeacherAccount($instId, $semId, $period);

        Livewire::actingAs($account, 'staff')
            ->test(DailyAttendanceSheet::class, [
                'classGroupId' => $classId,
                'date' => '2026-01-15',
            ])
            ->assertForbidden();
    }

    // ── Test 3: principal (VERIFY permission) bypasses homeroom check ─────────

    public function test_principal_with_verify_permission_can_mount_without_homeroom(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeSemester($instId);
        $levelId = $this->makeAcademicLevel();

        // Principals are full-scope — no period grant or homeroom assignment needed.
        $account = $this->makePrincipalAccount($instId, $semId);

        // Create a class group in ANY period (principal sees all periods).
        $period = $this->makePeriod($semId);
        $classId = $this->makeClassGroup($semId, $period, $levelId);

        // Principal has no homeroom assignment for this class — must still succeed.
        $component = Livewire::actingAs($account, 'staff')
            ->test(DailyAttendanceSheet::class, [
                'classGroupId' => $classId,
                'date' => '2026-01-16',
            ]);

        $component->assertOk();
        $this->assertNotNull(
            $component->get('sheetId'),
            'Principal with VERIFY permission must be able to open any in-scope sheet without a homeroom assignment.',
        );
    }

    // ── Fixture helpers ───────────────────────────────────────────────────────

    /**
     * Seed two roles:
     *  - teacher_test_role  → student_attendance.enter (no verify)
     *  - principal_test_role → student_attendance.enter + student_attendance.verify
     *
     * Tied to position_definitions 'teacher' and 'principal' respectively.
     */
    private function seedPermissions(): void
    {
        $enterPermId = (int) DB::table('permissions')->insertGetId([
            'key' => 'student_attendance.enter',
            'description' => 'Enter student attendance',
            'group' => 'student_attendance',
            'is_system' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $verifyPermId = (int) DB::table('permissions')->insertGetId([
            'key' => 'student_attendance.verify',
            'description' => 'Verify student attendance',
            'group' => 'student_attendance',
            'is_system' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Teacher role: enter only (no verify → homeroom guard is enforced)
        $teacherRoleId = (int) DB::table('roles')->insertGetId([
            'code' => 'TEACHER_HRM_ROLE',
            'label' => 'Teacher',
            'is_protected' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('role_permissions')->insert([
            ['role_id' => $teacherRoleId, 'permission_id' => $enterPermId,  'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('position_role_grants')->insert([
            'position_definition' => 'teacher',
            'role_id' => $teacherRoleId,
            'granted_by' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Principal role: enter + verify (homeroom guard is skipped)
        $principalRoleId = (int) DB::table('roles')->insertGetId([
            'code' => 'PRINCIPAL_HRM_ROLE',
            'label' => 'Principal',
            'is_protected' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('role_permissions')->insert([
            ['role_id' => $principalRoleId, 'permission_id' => $enterPermId,  'created_at' => now(), 'updated_at' => now()],
            ['role_id' => $principalRoleId, 'permission_id' => $verifyPermId, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('position_role_grants')->insert([
            'position_definition' => 'principal',
            'role_id' => $principalRoleId,
            'granted_by' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeInstitution(): int
    {
        return (int) DB::table('institutions')->insertGetId([
            'organization_id' => $this->orgId,
            'institution_type_id' => $this->typeId,
            'code' => 'INST-HRM-'.uniqid(),
            'name_en' => 'Homeroom Test Institution',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeSemester(int $institutionId): int
    {
        $yearId = (int) DB::table('academic_years')->insertGetId([
            'organization_id' => $this->orgId,
            'code' => 'AY-HRM-'.uniqid(),
            'name_en' => 'Test Year',
            'starts_on' => '2025-09-01',
            'ends_on' => '2026-06-30',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $semId = (int) DB::table('semesters')->insertGetId([
            'code' => 'SEM-HRM-'.uniqid(),
            'name_en' => 'First Semester',
            'name_ar' => 'الفصل الأول',
            'sequence' => 1,
            'status' => 'open',
            'academic_year_id' => $yearId,
            'starts_on' => '2025-09-01',
            'ends_on' => '2026-06-30',
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

    private function makePeriod(int $semesterId): int
    {
        $seq = ($this->periodSeq[$semesterId] ?? 0) + 1;
        $this->periodSeq[$semesterId] = $seq;

        return (int) DB::table('operational_periods')->insertGetId([
            'institution_semester_id' => $semesterId,
            'code' => 'OP-HRM-'.uniqid(),
            'name_en' => 'Morning',
            'name_ar' => 'الصباح',
            'sequence' => $seq,
            'starts_at' => '08:00:00',
            'ends_at' => '13:00:00',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeAcademicLevel(): int
    {
        return (int) DB::table('academic_levels')->insertGetId([
            'code' => 'LVL-HRM-'.uniqid(),
            'name_en' => 'Grade 1',
            'name_ar' => 'الصف الأول',
            'sequence' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeClassGroup(int $semesterId, int $periodId, int $levelId): int
    {
        return (int) DB::table('class_groups')->insertGetId([
            'institution_semester_id' => $semesterId,
            'operational_period_id' => $periodId,
            'academic_level_id' => $levelId,
            'code' => 'CG-HRM-'.uniqid(),
            'name_en' => 'Class A',
            'name_ar' => 'الفصل أ',
            'lifecycle_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Create a staff profile and return its ID.
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
            'staff_code' => 'STF-HRM-'.uniqid(),
            'employment_status' => 'active',
            'hired_on' => '2024-01-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Build a StaffAccount for a teacher.
     *
     * Teachers are period-restricted, so they need:
     *  - a staff_institution_assignment
     *  - a staff_position with position_definition = 'teacher'
     *  - a staff_position_periods grant for the given period
     *
     * Returns the account with staff_profile_id set.
     */
    private function makeTeacherAccount(int $institutionId, int $semesterId, int $periodId): StaffAccount
    {
        $account = StaffAccount::factory()->active()->create();
        $profileId = $this->makeProfileId('Teacher');

        DB::table('staff_accounts')
            ->where('id', $account->getKey())
            ->update(['staff_profile_id' => $profileId]);
        $account->setAttribute('staff_profile_id', $profileId);

        $assignmentId = (int) DB::table('staff_institution_assignments')->insertGetId([
            'staff_profile_id' => $profileId,
            'institution_id' => $institutionId,
            'started_on' => '2024-01-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $positionId = (int) DB::table('staff_positions')->insertGetId([
            'staff_profile_id' => $profileId,
            'staff_institution_assignment_id' => $assignmentId,
            'institution_id' => $institutionId,
            'institution_semester_id' => $semesterId,
            'position_definition' => 'teacher',
            'started_on' => '2024-01-01',
            'ended_on' => null,
            'created_by' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Period grant — required for period-restricted positions.
        DB::table('staff_position_periods')->insert([
            'staff_position_id' => $positionId,
            'operational_period_id' => $periodId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $account;
    }

    /**
     * Build a StaffAccount for a principal.
     *
     * Principals are full-scope (FULL_SCOPE_POSITIONS) and hold VERIFY permission,
     * so neither period grants nor homeroom assignments are required.
     */
    private function makePrincipalAccount(int $institutionId, int $semesterId): StaffAccount
    {
        $account = StaffAccount::factory()->active()->create();
        $profileId = $this->makeProfileId('Principal');

        DB::table('staff_accounts')
            ->where('id', $account->getKey())
            ->update(['staff_profile_id' => $profileId]);
        $account->setAttribute('staff_profile_id', $profileId);

        $assignmentId = (int) DB::table('staff_institution_assignments')->insertGetId([
            'staff_profile_id' => $profileId,
            'institution_id' => $institutionId,
            'started_on' => '2024-01-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('staff_positions')->insertGetId([
            'staff_profile_id' => $profileId,
            'staff_institution_assignment_id' => $assignmentId,
            'institution_id' => $institutionId,
            'institution_semester_id' => $semesterId,
            'position_definition' => 'principal',
            'started_on' => '2024-01-01',
            'ended_on' => null,
            'created_by' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // No period grant needed — principals are full-scope.

        return $account;
    }

    /**
     * Insert a homeroom_assignments row marking the teacher as active homeroom lead.
     */
    private function makeHomeroomAssignment(
        int $profileId,
        int $semesterId,
        int $classGroupId,
        int $positionId,
    ): void {
        DB::table('homeroom_assignments')->insert([
            'staff_profile_id' => $profileId,
            'institution_semester_id' => $semesterId,
            'staff_position_id' => $positionId,
            'class_group_id' => $classGroupId,
            'is_co_lead' => false,
            'starts_on' => '2024-01-01',
            'ends_on' => null,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Look up the staff_position id for a given profile + semester (created by makeTeacherAccount).
     */
    private function resolvePositionId(int $profileId, int $semesterId): int
    {
        return (int) DB::table('staff_positions')
            ->where('staff_profile_id', $profileId)
            ->where('institution_semester_id', $semesterId)
            ->value('id');
    }
}

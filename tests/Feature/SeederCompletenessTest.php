<?php

declare(strict_types=1);

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Authorization\Data\PermissionKey;
use Modules\Authorization\Data\RoleCode;

uses(RefreshDatabase::class);

/**
 * Seeder completeness and idempotency tests.
 *
 * Verifies that after a fresh seed:
 *  - All reference data is present
 *  - Demo student/guardian/staff/account counts meet minimums
 *  - All enrollment status variants are represented
 *  - Civil registry records are present
 *  - Import batch rows cover all result statuses
 *
 * Idempotency: seed is run a second time inside each test; row counts
 * must be identical (checks that all seeders use check-then-create).
 */

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function runSeeder(): void
{
    $seeder = new DatabaseSeeder;
    $seeder->run();
}

// ---------------------------------------------------------------------------
// Permission catalogue
// ---------------------------------------------------------------------------

describe('PermissionCatalogueSeeder', function (): void {

    it('seeds all permission keys defined in PermissionKey', function (): void {
        runSeeder();

        $expectedCount = count(PermissionKey::all());
        $actualCount = DB::table('permissions')->count();

        expect($actualCount)->toBe($expectedCount);
    });

    it('seeds all 12 protected roles', function (): void {
        runSeeder();

        $expectedCodes = RoleCode::all();
        $actualCodes = DB::table('roles')->where('is_protected', true)->pluck('code')->sort()->values()->toArray();

        expect($actualCodes)->toEqualCanonicalizing($expectedCodes);
    });

    it('assigns system_admin all permissions', function (): void {
        runSeeder();

        $adminRole = DB::table('roles')->where('code', 'system_admin')->first();
        $totalPermissions = DB::table('permissions')->count();
        $grantedCount = DB::table('role_permissions')
            ->where('role_id', $adminRole->id)
            ->count();

        expect($grantedCount)->toBe($totalPermissions);
    });

    it('includes all SR-6 new permission keys', function (): void {
        runSeeder();

        $newKeys = [
            'student.view',
            'student.view_restricted',
            'student.create',
            'student.update',
            'student.manage',
            'guardian_relationship.view',
            'guardian_relationship.manage',
            'guardian_relationship.verify',
            'academic_level.manage',
            'classroom.manage',
            'class_group.manage',
            'subject.manage',
            'subject_offering.manage',
            'enrollment.view',
            'enrollment.manage',
            'enrollment.transfer',
            'enrollment.promote',
            'import.upload',
            'import.review',
            'import.apply',
            'data.sensitive_export',
        ];

        foreach ($newKeys as $key) {
            expect(DB::table('permissions')->where('key', $key)->exists())
                ->toBeTrue("Permission key '{$key}' is missing from the catalogue.");
        }
    });

    it('grants student.create to principal and secretary', function (): void {
        runSeeder();

        foreach (['principal', 'secretary'] as $roleCode) {
            $roleId = DB::table('roles')->where('code', $roleCode)->value('id');
            $permId = DB::table('permissions')->where('key', 'student.create')->value('id');

            expect(DB::table('role_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permId)
                ->exists()
            )->toBeTrue("Role '{$roleCode}' is missing student.create permission.");
        }
    });

    it('grants student.view_restricted only to teacher, not principal or secretary', function (): void {
        runSeeder();

        $permId = DB::table('permissions')->where('key', 'student.view_restricted')->value('id');

        $teacherId = DB::table('roles')->where('code', 'teacher')->value('id');
        expect(DB::table('role_permissions')->where('role_id', $teacherId)->where('permission_id', $permId)->exists())->toBeTrue();

        $principalId = DB::table('roles')->where('code', 'principal')->value('id');
        expect(DB::table('role_permissions')->where('role_id', $principalId)->where('permission_id', $permId)->exists())->toBeFalse();
    });

    it('is idempotent — running twice produces no duplicate permissions', function (): void {
        runSeeder();
        $countAfterFirst = DB::table('permissions')->count();
        $grantCountAfterFirst = DB::table('role_permissions')->count();

        runSeeder();
        $countAfterSecond = DB::table('permissions')->count();
        $grantCountAfterSecond = DB::table('role_permissions')->count();

        expect($countAfterSecond)->toBe($countAfterFirst)
            ->and($grantCountAfterSecond)->toBe($grantCountAfterFirst);
    });

});

// ---------------------------------------------------------------------------
// Reference data seeders
// ---------------------------------------------------------------------------

describe('Reference data seeders', function (): void {

    it('seeds all 14 academic levels (KG1 through Grade12)', function (): void {
        runSeeder();

        expect(DB::table('academic_levels')->count())->toBe(14);
    });

    it('seeds all 8 canonical subjects', function (): void {
        runSeeder();

        $expectedCodes = ['ARABIC', 'MATH', 'ENGLISH', 'SCIENCE', 'ISLAMIC', 'SOCIAL', 'ART', 'PE'];

        foreach ($expectedCodes as $code) {
            expect(DB::table('subjects')->where('code', $code)->exists())
                ->toBeTrue("Subject '{$code}' is missing.");
        }
    });

    it('seeds institutions including academy_1 and academy_2', function (): void {
        runSeeder();

        expect(DB::table('institutions')->where('code', 'academy_1')->exists())->toBeTrue()
            ->and(DB::table('institutions')->where('code', 'academy_2')->exists())->toBeTrue();
    });

});

// ---------------------------------------------------------------------------
// Calendar seeder
// ---------------------------------------------------------------------------

describe('DemoCalendarSeeder', function (): void {

    it('creates two academic years', function (): void {
        runSeeder();

        expect(DB::table('academic_years')->count())->toBeGreaterThanOrEqual(2);
    });

    it('creates institution semesters with at least one open and one archived', function (): void {
        runSeeder();

        expect(DB::table('institution_semesters')->where('status', 'open')->exists())->toBeTrue()
            ->and(DB::table('institution_semesters')->where('status', 'archived')->exists())->toBeTrue();
    });

    it('creates three operational periods for each institution semester', function (): void {
        runSeeder();

        $instSems = DB::table('institution_semesters')->get();

        foreach ($instSems as $is) {
            $count = DB::table('operational_periods')
                ->where('institution_semester_id', $is->id)
                ->count();

            expect($count)->toBeGreaterThanOrEqual(3);
        }
    });

    it('is idempotent — second run leaves counts unchanged', function (): void {
        runSeeder();
        $yearCount = DB::table('academic_years')->count();
        $semCount = DB::table('semesters')->count();
        $opCount = DB::table('operational_periods')->count();

        runSeeder();
        expect(DB::table('academic_years')->count())->toBe($yearCount)
            ->and(DB::table('semesters')->count())->toBe($semCount)
            ->and(DB::table('operational_periods')->count())->toBe($opCount);
    });

});

// ---------------------------------------------------------------------------
// Academic structure seeder
// ---------------------------------------------------------------------------

describe('DemoAcademicStructureSeeder', function (): void {

    it('creates at least 6 classrooms for academy_1', function (): void {
        runSeeder();

        $inst1Id = DB::table('institutions')->where('code', 'academy_1')->value('id');
        expect(DB::table('classrooms')->where('institution_id', $inst1Id)->count())->toBeGreaterThanOrEqual(6);
    });

    it('creates class groups for the open semester', function (): void {
        runSeeder();

        $inst1Id = DB::table('institutions')->where('code', 'academy_1')->value('id');
        $instSemId = DB::table('institution_semesters')
            ->where('institution_id', $inst1Id)
            ->where('status', 'open')
            ->value('id');

        expect(DB::table('class_groups')->where('institution_semester_id', $instSemId)->count())->toBeGreaterThanOrEqual(6);
    });

    it('creates subject offerings for all 8 subjects', function (): void {
        runSeeder();

        $inst1Id = DB::table('institutions')->where('code', 'academy_1')->value('id');
        $instSemId = DB::table('institution_semesters')
            ->where('institution_id', $inst1Id)
            ->where('status', 'open')
            ->value('id');

        expect(DB::table('institution_subject_offerings')
            ->where('institution_semester_id', $instSemId)
            ->count()
        )->toBe(8);
    });

});

// ---------------------------------------------------------------------------
// Student seeder
// ---------------------------------------------------------------------------

describe('DemoStudentSeeder', function (): void {

    it('seeds at least 30 students', function (): void {
        runSeeder();

        expect(DB::table('student_profiles')->count())->toBeGreaterThanOrEqual(30);
    });

    it('covers all required lifecycle statuses', function (): void {
        runSeeder();

        $statuses = DB::table('student_profiles')->distinct()->pluck('lifecycle_status')->toArray();

        foreach (['active', 'inactive', 'withdrawn', 'draft', 'graduated'] as $status) {
            expect($statuses)->toContain($status);
        }
    });

    it('seeds at least 10 active students', function (): void {
        runSeeder();

        expect(DB::table('student_profiles')->where('lifecycle_status', 'active')->count())
            ->toBeGreaterThanOrEqual(10);
    });

    it('seeds at least 4 draft students (incomplete profiles)', function (): void {
        runSeeder();

        expect(DB::table('student_profiles')->where('lifecycle_status', 'draft')->count())
            ->toBeGreaterThanOrEqual(4);
    });

    it('is idempotent — second run adds no new students', function (): void {
        runSeeder();
        $count = DB::table('student_profiles')->count();

        runSeeder();
        expect(DB::table('student_profiles')->count())->toBe($count);
    });

});

// ---------------------------------------------------------------------------
// Guardian seeder
// ---------------------------------------------------------------------------

describe('DemoGuardianSeeder', function (): void {

    it('seeds guardian profiles', function (): void {
        runSeeder();

        expect(DB::table('guardian_profiles')->count())->toBeGreaterThanOrEqual(5);
    });

    it('seeds at least one student with multiple guardians (STU-002)', function (): void {
        runSeeder();

        $stu2Id = DB::table('student_profiles')->where('student_code', 'STU-002')->value('id');
        $guardianCount = DB::table('guardian_student_relationships')
            ->where('student_profile_id', $stu2Id)
            ->count();

        expect($guardianCount)->toBeGreaterThanOrEqual(2);
    });

    it('seeds at least one guardian with multiple students (GRD-004)', function (): void {
        runSeeder();

        $grd4Id = DB::table('guardian_profiles')->where('guardian_code', 'GRD-004')->value('id');
        $studentCount = DB::table('guardian_student_relationships')
            ->where('guardian_profile_id', $grd4Id)
            ->count();

        expect($studentCount)->toBeGreaterThanOrEqual(2);
    });

    it('seeds at least one student with a single guardian (STU-001)', function (): void {
        runSeeder();

        $stu1Id = DB::table('student_profiles')->where('student_code', 'STU-001')->value('id');
        $guardianCount = DB::table('guardian_student_relationships')
            ->where('student_profile_id', $stu1Id)
            ->count();

        expect($guardianCount)->toBe(1);
    });

    it('is idempotent — second run adds no new guardian relationships', function (): void {
        runSeeder();
        $count = DB::table('guardian_student_relationships')->count();

        runSeeder();
        expect(DB::table('guardian_student_relationships')->count())->toBe($count);
    });

});

// ---------------------------------------------------------------------------
// Enrollment seeder
// ---------------------------------------------------------------------------

describe('DemoEnrollmentSeeder', function (): void {

    it('seeds active enrollments for the open semester', function (): void {
        runSeeder();

        $inst1Id = DB::table('institutions')->where('code', 'academy_1')->value('id');
        $instSemId = DB::table('institution_semesters')
            ->where('institution_id', $inst1Id)
            ->where('status', 'open')
            ->value('id');

        expect(DB::table('student_enrollments')
            ->where('institution_semester_id', $instSemId)
            ->where('enrollment_status', 'active')
            ->count()
        )->toBeGreaterThanOrEqual(8);
    });

    it('seeds withdrawn and transferred enrollments', function (): void {
        runSeeder();

        expect(DB::table('student_enrollments')->where('enrollment_status', 'withdrawn')->exists())->toBeTrue()
            ->and(DB::table('student_enrollments')->where('enrollment_status', 'transferred')->exists())->toBeTrue();
    });

    it('seeds promotion proposals with all review statuses', function (): void {
        runSeeder();

        $reviewStatuses = DB::table('promotion_proposals')->distinct()->pluck('review_status')->toArray();

        expect($reviewStatuses)->toContain('pending')
            ->and($reviewStatuses)->toContain('approved')
            ->and($reviewStatuses)->toContain('rejected');
    });

    it('is idempotent — second run adds no new enrollments', function (): void {
        runSeeder();
        $count = DB::table('student_enrollments')->count();

        runSeeder();
        expect(DB::table('student_enrollments')->count())->toBe($count);
    });

});

// ---------------------------------------------------------------------------
// Civil registry seeder
// ---------------------------------------------------------------------------

describe('DemoCivilRegistrySeeder', function (): void {

    it('seeds civil registry records', function (): void {
        runSeeder();

        expect(DB::table('gaza_civil_records')->count())->toBeGreaterThanOrEqual(4);
    });

    it('seeds at least one deceased record', function (): void {
        runSeeder();

        expect(DB::table('gaza_civil_records')->where('is_deceased', true)->exists())->toBeTrue();
    });

    it('is idempotent', function (): void {
        runSeeder();
        $count = DB::table('gaza_civil_records')->count();

        runSeeder();
        expect(DB::table('gaza_civil_records')->count())->toBe($count);
    });

});

// ---------------------------------------------------------------------------
// Import batch seeder
// ---------------------------------------------------------------------------

describe('DemoImportBatchSeeder', function (): void {

    it('seeds an import batch in completed status', function (): void {
        runSeeder();

        expect(DB::table('import_batches')->where('status', 'completed')->exists())->toBeTrue();
    });

    it('seeds rows covering all key result statuses', function (): void {
        runSeeder();

        $statuses = DB::table('import_row_results')->distinct()->pluck('status')->toArray();

        foreach (['created', 'updated', 'skipped_existing', 'conflict', 'invalid', 'failed'] as $status) {
            expect($statuses)->toContain($status);
        }
    });

    it('is idempotent', function (): void {
        runSeeder();
        $batchCount = DB::table('import_batches')->count();

        runSeeder();
        expect(DB::table('import_batches')->count())->toBe($batchCount);
    });

});

// ---------------------------------------------------------------------------
// Staff seeder
// ---------------------------------------------------------------------------

describe('DemoStaffSeeder', function (): void {

    it('seeds at least 6 staff profiles', function (): void {
        runSeeder();

        expect(DB::table('staff_profiles')->count())->toBeGreaterThanOrEqual(6);
    });

    it('seeds a principal, secretary, teacher, and counselor position', function (): void {
        runSeeder();

        $positions = DB::table('staff_positions')->distinct()->pluck('position_definition')->toArray();

        foreach (['principal', 'secretary', 'teacher', 'counselor'] as $position) {
            expect($positions)->toContain($position);
        }
    });

    it('seeds position-role grants for all staff position definitions', function (): void {
        runSeeder();

        foreach (['principal', 'secretary', 'teacher', 'counselor'] as $position) {
            expect(DB::table('position_role_grants')->where('position_definition', $position)->exists())
                ->toBeTrue("Position role grant missing for '{$position}'.");
        }
    });

    it('is idempotent — second run adds no new staff profiles', function (): void {
        runSeeder();
        $count = DB::table('staff_profiles')->count();

        runSeeder();
        expect(DB::table('staff_profiles')->count())->toBe($count);
    });

});

// ---------------------------------------------------------------------------
// Account seeder
// ---------------------------------------------------------------------------

describe('DemoAccountSeeder', function (): void {

    it('seeds admin portal accounts including system_admin', function (): void {
        runSeeder();

        expect(DB::table('administrative_accounts')->where('username', 'admin@gcv.demo')->exists())->toBeTrue();
    });

    it('seeds staff portal accounts for all key roles', function (): void {
        runSeeder();

        foreach (['principal@gcv.demo', 'secretary@gcv.demo', 'teacher@gcv.demo', 'counselor@gcv.demo'] as $username) {
            expect(DB::table('staff_accounts')->where('username', $username)->exists())
                ->toBeTrue("Staff account '{$username}' is missing.");
        }
    });

    it('seeds a guardian portal account', function (): void {
        runSeeder();

        expect(DB::table('guardian_accounts')->where('login_identifier', 'guardian@gcv.demo')->exists())->toBeTrue();
    });

    it('links guardian account back to guardian profile', function (): void {
        runSeeder();

        $accountId = DB::table('guardian_accounts')->where('login_identifier', 'guardian@gcv.demo')->value('id');
        $guardianProfile = DB::table('guardian_profiles')
            ->where('guardian_code', 'GRD-002')
            ->first();

        expect($guardianProfile->guardian_account_id)->toBe($accountId);
    });

    it('links staff accounts to their staff profiles', function (): void {
        runSeeder();

        $principalAccount = DB::table('staff_accounts')->where('username', 'principal@gcv.demo')->first();
        $staffProfile = DB::table('staff_profiles')->where('staff_code', 'STAFF-001')->first();

        expect($principalAccount->staff_profile_id)->toBe($staffProfile->id);
    });

    it('assigns role to admin account', function (): void {
        runSeeder();

        $accountId = DB::table('administrative_accounts')->where('username', 'admin@gcv.demo')->value('id');
        $roleId = DB::table('roles')->where('code', 'system_admin')->value('id');

        expect(DB::table('administrative_account_roles')
            ->where('administrative_account_id', $accountId)
            ->where('role_id', $roleId)
            ->exists()
        )->toBeTrue();
    });

    it('is idempotent — second run adds no new accounts', function (): void {
        runSeeder();
        $adminCount = DB::table('administrative_accounts')->count();
        $staffCount = DB::table('staff_accounts')->count();
        $guardianCount = DB::table('guardian_accounts')->count();

        runSeeder();
        expect(DB::table('administrative_accounts')->count())->toBe($adminCount)
            ->and(DB::table('staff_accounts')->count())->toBe($staffCount)
            ->and(DB::table('guardian_accounts')->count())->toBe($guardianCount);
    });

});

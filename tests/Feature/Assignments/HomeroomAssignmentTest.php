<?php

declare(strict_types=1);

namespace Tests\Feature\Assignments;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\AcademicManagement\Actions\CreateHomeroomAssignment;
use Modules\AcademicManagement\Actions\EndHomeroomAssignment;
use Modules\AcademicManagement\Actions\ReplaceHomeroomAssignment;
use Modules\AcademicManagement\Enums\AssignmentStatus;
use Modules\AcademicManagement\Exceptions\AssignmentException;
use Modules\AcademicManagement\Models\HomeroomAssignment;
use Tests\TestCase;

/**
 * Domain tests for CreateHomeroomAssignment, EndHomeroomAssignment,
 * and ReplaceHomeroomAssignment.
 */
class HomeroomAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private int $orgId  = 0;
    private int $typeId = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgId = (int) DB::table('organizations')->insertGetId([
            'code'       => 'ORG-HRM',
            'name_en'    => 'HR Test Org',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->typeId = (int) DB::table('institution_types')->insertGetId([
            'code'       => 'TYPE-HRM',
            'name_en'    => 'School',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ── Insert helpers ────────────────────────────────────────────────────

    private function makeInstitution(): int
    {
        return (int) DB::table('institutions')->insertGetId([
            'organization_id'    => $this->orgId,
            'institution_type_id' => $this->typeId,
            'code'               => 'HRI-'.uniqid(),
            'name_en'            => 'HR Institution',
            'is_active'          => true,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    private function makeInstitutionSemester(int $institutionId, string $status = 'open'): int
    {
        $yearId = (int) DB::table('academic_years')->insertGetId([
            'organization_id' => $this->orgId,
            'code'       => 'AY-HRM-'.uniqid(),
            'name_en'    => 'Year',
            'starts_on'  => '2025-09-01',
            'ends_on'    => '2026-06-30',
            'status'     => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $semId = (int) DB::table('semesters')->insertGetId([
            'code'            => 'SEM-HRM-'.uniqid(),
            'name_en'         => 'Semester',
            'name_ar'         => 'Semester',
            'sequence'        => 1,
            'status'          => 'open',
            'academic_year_id' => $yearId,
            'starts_on'       => '2025-09-01',
            'ends_on'         => '2026-01-31',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return (int) DB::table('institution_semesters')->insertGetId([
            'institution_id' => $institutionId,
            'semester_id'    => $semId,
            'status'         => $status,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    private function makeClassGroup(int $instSemId): int
    {
        $opId = (int) DB::table('operational_periods')->insertGetId([
            'institution_semester_id' => $instSemId,
            'code'       => 'OP-HRM-'.uniqid(),
            'name_en'    => 'Period',
            'sequence'   => 1,
            'starts_at'  => '08:00:00',
            'ends_at'    => '13:00:00',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $levelId = (int) DB::table('academic_levels')->insertGetId([
            'code'       => 'LVL-HRM-'.uniqid(),
            'name_en'    => 'Level',
            'name_ar'    => 'Level',
            'sequence'   => 1,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('class_groups')->insertGetId([
            'institution_semester_id' => $instSemId,
            'operational_period_id'   => $opId,
            'academic_level_id'       => $levelId,
            'code'                    => 'CG-HRM-'.uniqid(),
            'name_ar'                 => 'Class',
            'lifecycle_status'        => 'active',
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);
    }

    private function makeTeacherPosition(
        int $institutionId,
        int $instSemId,
        string $definition = 'teacher',
        ?string $endedOn = null,
    ): array {
        $personId = (int) DB::table('people')->insertGetId([
            'full_name_ar' => 'HR Staff '.uniqid(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $profileId = (int) DB::table('staff_profiles')->insertGetId([
            'person_id'         => $personId,
            'staff_code'        => 'HR-'.uniqid(),
            'employment_status' => 'active',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $assignId = (int) DB::table('staff_institution_assignments')->insertGetId([
            'staff_profile_id' => $profileId,
            'institution_id'   => $institutionId,
            'started_on'       => now()->toDateString(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $posId = (int) DB::table('staff_positions')->insertGetId([
            'staff_profile_id'               => $profileId,
            'staff_institution_assignment_id' => $assignId,
            'institution_id'                 => $institutionId,
            'institution_semester_id'        => $instSemId,
            'position_definition'            => $definition,
            'started_on'                     => now()->toDateString(),
            'ended_on'                       => $endedOn,
            'created_by'                     => 'test',
            'created_at'                     => now(),
            'updated_at'                     => now(),
        ]);

        return ['positionId' => $posId, 'profileId' => $profileId];
    }

    // ── Tests ─────────────────────────────────────────────────────────────

    public function test_creates_a_lead_homeroom_assignment(): void
    {
        $instId    = $this->makeInstitution();
        $instSemId = $this->makeInstitutionSemester($instId);
        $classId   = $this->makeClassGroup($instSemId);
        $teacher   = $this->makeTeacherPosition($instId, $instSemId);

        $assignment = app(CreateHomeroomAssignment::class)(
            staffPositionId: $teacher['positionId'],
            classGroupId: $classId,
            startsOn: now(),
            isCoLead: false,
        );

        $this->assertInstanceOf(HomeroomAssignment::class, $assignment);
        $this->assertEquals(AssignmentStatus::Active, $assignment->status);
        $this->assertFalse($assignment->is_co_lead);
    }

    public function test_creates_co_lead_alongside_lead(): void
    {
        $instId    = $this->makeInstitution();
        $instSemId = $this->makeInstitutionSemester($instId);
        $classId   = $this->makeClassGroup($instSemId);
        $lead      = $this->makeTeacherPosition($instId, $instSemId);
        $coLead    = $this->makeTeacherPosition($instId, $instSemId);

        app(CreateHomeroomAssignment::class)(
            staffPositionId: $lead['positionId'],
            classGroupId: $classId,
            startsOn: now(),
            isCoLead: false,
        );

        $coLeadAssignment = app(CreateHomeroomAssignment::class)(
            staffPositionId: $coLead['positionId'],
            classGroupId: $classId,
            startsOn: now(),
            isCoLead: true,
        );

        $this->assertTrue($coLeadAssignment->is_co_lead);
        $this->assertEquals(AssignmentStatus::Active, $coLeadAssignment->status);
    }

    public function test_rejects_duplicate_lead_for_same_class_group(): void
    {
        $instId   = $this->makeInstitution();
        $semId    = $this->makeInstitutionSemester($instId);
        $classId  = $this->makeClassGroup($semId);
        $teacherA = $this->makeTeacherPosition($instId, $semId);
        $teacherB = $this->makeTeacherPosition($instId, $semId);

        app(CreateHomeroomAssignment::class)(
            staffPositionId: $teacherA['positionId'],
            classGroupId: $classId,
            startsOn: now(),
            isCoLead: false,
        );

        $this->expectException(AssignmentException::class);
        $this->expectExceptionMessageMatches('/already has an active lead/');

        app(CreateHomeroomAssignment::class)(
            staffPositionId: $teacherB['positionId'],
            classGroupId: $classId,
            startsOn: now(),
            isCoLead: false,
        );
    }

    public function test_rejects_non_teacher_position_for_homeroom(): void
    {
        $instId    = $this->makeInstitution();
        $semId     = $this->makeInstitutionSemester($instId);
        $classId   = $this->makeClassGroup($semId);
        $counselor = $this->makeTeacherPosition($instId, $semId, 'counselor');

        $this->expectException(AssignmentException::class);
        $this->expectExceptionMessageMatches('/not eligible/');

        app(CreateHomeroomAssignment::class)(
            staffPositionId: $counselor['positionId'],
            classGroupId: $classId,
            startsOn: now(),
        );
    }

    public function test_rejects_homeroom_for_closed_semester(): void
    {
        $instId  = $this->makeInstitution();
        $semId   = $this->makeInstitutionSemester($instId, 'closed');
        $classId = $this->makeClassGroup($semId);
        $teacher = $this->makeTeacherPosition($instId, $semId);

        $this->expectException(AssignmentException::class);

        app(CreateHomeroomAssignment::class)(
            staffPositionId: $teacher['positionId'],
            classGroupId: $classId,
            startsOn: now(),
        );
    }

    public function test_ends_an_active_homeroom_assignment(): void
    {
        $instId  = $this->makeInstitution();
        $semId   = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $teacher = $this->makeTeacherPosition($instId, $semId);

        $assignment = app(CreateHomeroomAssignment::class)(
            staffPositionId: $teacher['positionId'],
            classGroupId: $classId,
            startsOn: now(),
        );

        $ended = app(EndHomeroomAssignment::class)(
            $assignment,
            endsOn: now(),
            reason: 'End of term.',
        );

        $this->assertEquals(AssignmentStatus::Ended, $ended->status);
        $this->assertNotNull($ended->ends_on);
        $this->assertEquals('End of term.', $ended->ends_reason);
    }

    public function test_replaces_a_homeroom_assignment(): void
    {
        $instId   = $this->makeInstitution();
        $semId    = $this->makeInstitutionSemester($instId);
        $classId  = $this->makeClassGroup($semId);
        $teacherA = $this->makeTeacherPosition($instId, $semId);
        $teacherB = $this->makeTeacherPosition($instId, $semId);

        $original = app(CreateHomeroomAssignment::class)(
            staffPositionId: $teacherA['positionId'],
            classGroupId: $classId,
            startsOn: now(),
        );

        $replacement = app(ReplaceHomeroomAssignment::class)(
            old: $original,
            newStaffPositionId: $teacherB['positionId'],
            replacedOn: now(),
            reason: 'Reassigned.',
        );

        $original->refresh();

        $this->assertEquals(AssignmentStatus::Superseded, $original->status);
        $this->assertEquals(AssignmentStatus::Active, $replacement->status);
        $this->assertEquals($teacherB['positionId'], $replacement->staff_position_id);
    }

    public function test_rejects_duplicate_active_co_lead_for_same_position_and_class(): void
    {
        // The partial unique index on (staff_position_id, class_group_id) WHERE status='active'
        // must prevent the same position from holding two active homeroom rows for the same class,
        // even when both are co-leads (the lead-only index alone would not catch this).
        $instId  = $this->makeInstitution();
        $semId   = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $teacher = $this->makeTeacherPosition($instId, $semId);

        app(CreateHomeroomAssignment::class)(
            staffPositionId: $teacher['positionId'],
            classGroupId: $classId,
            startsOn: now(),
            isCoLead: true,
        );

        $this->expectException(AssignmentException::class);

        // Second attempt — same position, same class, also co-lead.
        // Must be rejected either by app-level duplicate check (lockForUpdate)
        // or by the DB-level partial unique constraint.
        app(CreateHomeroomAssignment::class)(
            staffPositionId: $teacher['positionId'],
            classGroupId: $classId,
            startsOn: now(),
            isCoLead: true,
        );
    }

    public function test_rejects_wrong_semester_for_homeroom(): void
    {
        $instId  = $this->makeInstitution();
        $semAId  = $this->makeInstitutionSemester($instId);
        $semBId  = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semBId);
        // teacher in semesterA but class is in semesterB
        $teacher = $this->makeTeacherPosition($instId, $semAId);

        $this->expectException(AssignmentException::class);
        $this->expectExceptionMessageMatches('/semester/');

        app(CreateHomeroomAssignment::class)(
            staffPositionId: $teacher['positionId'],
            classGroupId: $classId,
            startsOn: now(),
        );
    }

    public function test_rejects_ending_homeroom_before_starts_on(): void
    {
        $instId  = $this->makeInstitution();
        $semId   = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $teacher = $this->makeTeacherPosition($instId, $semId);

        $assignment = app(CreateHomeroomAssignment::class)(
            staffPositionId: $teacher['positionId'],
            classGroupId: $classId,
            startsOn: now(),
        );

        $this->expectException(AssignmentException::class);
        $this->expectExceptionMessageMatches('/before.*starts_on|before the assignment/i');

        app(EndHomeroomAssignment::class)(
            $assignment,
            endsOn: now()->subDay(),
            reason: 'Should fail.',
        );
    }

    public function test_rejects_replacing_homeroom_with_date_before_starts_on(): void
    {
        $instId   = $this->makeInstitution();
        $semId    = $this->makeInstitutionSemester($instId);
        $classId  = $this->makeClassGroup($semId);
        $teacherA = $this->makeTeacherPosition($instId, $semId);
        $teacherB = $this->makeTeacherPosition($instId, $semId);

        $assignment = app(CreateHomeroomAssignment::class)(
            staffPositionId: $teacherA['positionId'],
            classGroupId: $classId,
            startsOn: now(),
        );

        $this->expectException(AssignmentException::class);
        $this->expectExceptionMessageMatches('/before.*starts_on|before the assignment/i');

        app(ReplaceHomeroomAssignment::class)(
            old: $assignment,
            newStaffPositionId: $teacherB['positionId'],
            replacedOn: now()->subDay(),
            reason: 'Should fail.',
        );
    }

    public function test_rejects_replacing_a_terminal_homeroom_assignment(): void
    {
        $instId   = $this->makeInstitution();
        $semId    = $this->makeInstitutionSemester($instId);
        $classId  = $this->makeClassGroup($semId);
        $teacherA = $this->makeTeacherPosition($instId, $semId);
        $teacherB = $this->makeTeacherPosition($instId, $semId);

        $original = app(CreateHomeroomAssignment::class)(
            staffPositionId: $teacherA['positionId'],
            classGroupId: $classId,
            startsOn: now(),
        );

        app(EndHomeroomAssignment::class)($original, now(), 'Ended before replace attempt.');

        $this->expectException(AssignmentException::class);
        $this->expectExceptionMessageMatches('/terminal/');

        app(ReplaceHomeroomAssignment::class)(
            old: $original,
            newStaffPositionId: $teacherB['positionId'],
            replacedOn: now(),
            reason: 'Should not succeed on a terminal row.',
        );
    }

    public function test_rejects_ending_homeroom_assignment_in_closed_semester(): void
    {
        $instId  = $this->makeInstitution();
        $semId   = $this->makeInstitutionSemester($instId, 'open');
        $classId = $this->makeClassGroup($semId);
        $teacher = $this->makeTeacherPosition($instId, $semId);

        $assignment = app(CreateHomeroomAssignment::class)(
            staffPositionId: $teacher['positionId'],
            classGroupId: $classId,
            startsOn: now(),
        );

        \Illuminate\Support\Facades\DB::table('institution_semesters')
            ->where('id', $semId)
            ->update(['status' => 'closed']);

        $this->expectException(AssignmentException::class);
        $this->expectExceptionMessageMatches('/does not accept/');

        app(EndHomeroomAssignment::class)($assignment, now(), 'Attempt in closed semester.');
    }

    public function test_rejects_replacing_homeroom_assignment_in_closed_semester(): void
    {
        $instId   = $this->makeInstitution();
        $semId    = $this->makeInstitutionSemester($instId, 'open');
        $classId  = $this->makeClassGroup($semId);
        $teacherA = $this->makeTeacherPosition($instId, $semId);
        $teacherB = $this->makeTeacherPosition($instId, $semId);

        $assignment = app(CreateHomeroomAssignment::class)(
            staffPositionId: $teacherA['positionId'],
            classGroupId: $classId,
            startsOn: now(),
        );

        \Illuminate\Support\Facades\DB::table('institution_semesters')
            ->where('id', $semId)
            ->update(['status' => 'closed']);

        $this->expectException(AssignmentException::class);
        $this->expectExceptionMessageMatches('/does not accept/');

        app(ReplaceHomeroomAssignment::class)(
            old: $assignment,
            newStaffPositionId: $teacherB['positionId'],
            replacedOn: now(),
            reason: 'Attempt in closed semester.',
        );
    }

    public function test_end_vs_replace_concurrency_second_terminal_transition_is_rejected(): void
    {
        $instId   = $this->makeInstitution();
        $semId    = $this->makeInstitutionSemester($instId);
        $classId  = $this->makeClassGroup($semId);
        $teacherA = $this->makeTeacherPosition($instId, $semId);
        $teacherB = $this->makeTeacherPosition($instId, $semId);

        $assignment = app(CreateHomeroomAssignment::class)(
            staffPositionId: $teacherA['positionId'],
            classGroupId: $classId,
            startsOn: now(),
        );

        // First transition: End wins.
        app(EndHomeroomAssignment::class)($assignment, now(), 'End wins in the race.');

        // Second transition: Replace must fail.
        $this->expectException(AssignmentException::class);
        $this->expectExceptionMessageMatches('/terminal/');

        app(ReplaceHomeroomAssignment::class)(
            old: $assignment,
            newStaffPositionId: $teacherB['positionId'],
            replacedOn: now(),
            reason: 'Should fail — assignment already ended.',
        );
    }

    public function test_homeroom_does_not_grant_subject_access(): void
    {
        $this->assertFalse(
            \Illuminate\Support\Facades\Schema::hasColumn('homeroom_assignments', 'subject_offering_id'),
            'homeroom_assignments must not reference a subject — marks require a separate TeachingAssignment.'
        );
    }
}

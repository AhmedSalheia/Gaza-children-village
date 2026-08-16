<?php

declare(strict_types=1);

namespace Tests\Feature\Assignments;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\AcademicManagement\Actions\CreateTeachingAssignment;
use Modules\AcademicManagement\Actions\EndTeachingAssignment;
use Modules\AcademicManagement\Actions\ReplaceTeachingAssignment;
use Modules\AcademicManagement\Enums\AssignmentStatus;
use Modules\AcademicManagement\Exceptions\AssignmentException;
use Modules\AcademicManagement\Models\TeachingAssignment;
use Tests\TestCase;

/**
 * Domain tests for CreateTeachingAssignment, EndTeachingAssignment,
 * and ReplaceTeachingAssignment actions.
 *
 * Data setup uses raw DB inserts so the tests are isolated from factory changes.
 * Each test gets a fresh DB (RefreshDatabase), so we rebuild the scaffold per test
 * via setUp() to avoid static-variable/FK mismatches.
 */
class TeachingAssignmentTest extends TestCase
{
    use RefreshDatabase;

    // ── Per-test shared fixtures ──────────────────────────────────────────

    private int $orgId = 0;

    private int $typeId = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgId = (int) DB::table('organizations')->insertGetId([
            'code' => 'ORG-TEACH',
            'name_en' => 'Test Org',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->typeId = (int) DB::table('institution_types')->insertGetId([
            'code' => 'TYPE-TEACH',
            'name_en' => 'School',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ── Insert helpers ────────────────────────────────────────────────────

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

    private function makeInstitutionSemester(int $institutionId, string $status = 'open'): int
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
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeClassGroup(int $instSemId): int
    {
        $opId = (int) DB::table('operational_periods')->insertGetId([
            'institution_semester_id' => $instSemId,
            'code' => 'OP-'.uniqid(),
            'name_en' => 'Period',
            'sequence' => 1,
            'starts_at' => '08:00:00',
            'ends_at' => '13:00:00',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $levelId = (int) DB::table('academic_levels')->insertGetId([
            'code' => 'LVL-'.uniqid(),
            'name_en' => 'Level',
            'name_ar' => 'Level',
            'sequence' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('class_groups')->insertGetId([
            'institution_semester_id' => $instSemId,
            'operational_period_id' => $opId,
            'academic_level_id' => $levelId,
            'code' => 'CG-'.uniqid(),
            'name_ar' => 'Class Group',
            'lifecycle_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeSubjectOffering(int $instSemId): int
    {
        $subjectId = (int) DB::table('subjects')->insertGetId([
            'code' => 'SUB-'.uniqid(),
            'name_en' => 'Subject',
            'name_ar' => 'Subject',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('institution_subject_offerings')->insertGetId([
            'institution_semester_id' => $instSemId,
            'subject_id' => $subjectId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeStaffPosition(
        int $institutionId,
        int $instSemId,
        string $definition = 'teacher',
        ?string $endedOn = null,
    ): array {
        $personId = (int) DB::table('people')->insertGetId([
            'full_name_ar' => 'Staff '.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $profileId = (int) DB::table('staff_profiles')->insertGetId([
            'person_id' => $personId,
            'staff_code' => 'SC-'.uniqid(),
            'employment_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $assignId = (int) DB::table('staff_institution_assignments')->insertGetId([
            'staff_profile_id' => $profileId,
            'institution_id' => $institutionId,
            'started_on' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $positionId = (int) DB::table('staff_positions')->insertGetId([
            'staff_profile_id' => $profileId,
            'staff_institution_assignment_id' => $assignId,
            'institution_id' => $institutionId,
            'institution_semester_id' => $instSemId,
            'position_definition' => $definition,
            'started_on' => now()->toDateString(),
            'ended_on' => $endedOn,
            'created_by' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['positionId' => $positionId, 'profileId' => $profileId];
    }

    /** Wire up a complete valid context for happy-path tests. */
    private function makeValidContext(string $semStatus = 'open'): array
    {
        $institutionId = $this->makeInstitution();
        $instSemId = $this->makeInstitutionSemester($institutionId, $semStatus);
        $classGroupId = $this->makeClassGroup($instSemId);
        $offeringId = $this->makeSubjectOffering($instSemId);
        $teacher = $this->makeStaffPosition($institutionId, $instSemId);

        return array_merge(compact('institutionId', 'instSemId', 'classGroupId', 'offeringId'), $teacher);
    }

    // ── Tests ─────────────────────────────────────────────────────────────

    public function test_creates_a_valid_teaching_assignment(): void
    {
        $ctx = $this->makeValidContext();

        $assignment = app(CreateTeachingAssignment::class)(
            staffPositionId: $ctx['positionId'],
            classGroupId: $ctx['classGroupId'],
            subjectOfferingId: $ctx['offeringId'],
            startsOn: now(),
        );

        $this->assertInstanceOf(TeachingAssignment::class, $assignment);
        $this->assertEquals(AssignmentStatus::Active, $assignment->status);
        $this->assertEquals($ctx['positionId'], $assignment->staff_position_id);
        $this->assertEquals($ctx['classGroupId'], $assignment->class_group_id);
        $this->assertEquals($ctx['offeringId'], $assignment->subject_offering_id);
    }

    public function test_rejects_non_teacher_position(): void
    {
        $institutionId = $this->makeInstitution();
        $instSemId = $this->makeInstitutionSemester($institutionId);
        $classGroupId = $this->makeClassGroup($instSemId);
        $offeringId = $this->makeSubjectOffering($instSemId);
        $secretary = $this->makeStaffPosition($institutionId, $instSemId, 'secretary');

        $this->expectException(AssignmentException::class);
        $this->expectExceptionMessageMatches('/not eligible/');

        app(CreateTeachingAssignment::class)(
            staffPositionId: $secretary['positionId'],
            classGroupId: $classGroupId,
            subjectOfferingId: $offeringId,
            startsOn: now(),
        );
    }

    public function test_rejects_ended_position(): void
    {
        $ctx = $this->makeValidContext();

        DB::table('staff_positions')
            ->where('id', $ctx['positionId'])
            ->update(['ended_on' => now()->subDay()->toDateString()]);

        $this->expectException(AssignmentException::class);
        $this->expectExceptionMessageMatches('/has ended/');

        app(CreateTeachingAssignment::class)(
            staffPositionId: $ctx['positionId'],
            classGroupId: $ctx['classGroupId'],
            subjectOfferingId: $ctx['offeringId'],
            startsOn: now(),
        );
    }

    public function test_rejects_wrong_institution_semester(): void
    {
        $institutionId = $this->makeInstitution();
        $instSemA = $this->makeInstitutionSemester($institutionId);
        $instSemB = $this->makeInstitutionSemester($institutionId);
        $classGroupId = $this->makeClassGroup($instSemB);
        $offeringId = $this->makeSubjectOffering($instSemB);
        // position is in semesterA, class group in semesterB
        $teacher = $this->makeStaffPosition($institutionId, $instSemA);

        $this->expectException(AssignmentException::class);
        $this->expectExceptionMessageMatches('/same institution semester/');

        app(CreateTeachingAssignment::class)(
            staffPositionId: $teacher['positionId'],
            classGroupId: $classGroupId,
            subjectOfferingId: $offeringId,
            startsOn: now(),
        );
    }

    public function test_rejects_duplicate_active_assignment(): void
    {
        $ctx = $this->makeValidContext();

        app(CreateTeachingAssignment::class)(
            staffPositionId: $ctx['positionId'],
            classGroupId: $ctx['classGroupId'],
            subjectOfferingId: $ctx['offeringId'],
            startsOn: now(),
        );

        $this->expectException(AssignmentException::class);
        $this->expectExceptionMessageMatches('/already exists/');

        app(CreateTeachingAssignment::class)(
            staffPositionId: $ctx['positionId'],
            classGroupId: $ctx['classGroupId'],
            subjectOfferingId: $ctx['offeringId'],
            startsOn: now(),
        );
    }

    public function test_rejects_assignment_when_semester_is_closed(): void
    {
        $ctx = $this->makeValidContext(semStatus: 'closed');

        $this->expectException(AssignmentException::class);
        $this->expectExceptionMessageMatches('/does not accept/');

        app(CreateTeachingAssignment::class)(
            staffPositionId: $ctx['positionId'],
            classGroupId: $ctx['classGroupId'],
            subjectOfferingId: $ctx['offeringId'],
            startsOn: now(),
        );
    }

    public function test_ends_an_active_assignment(): void
    {
        $ctx = $this->makeValidContext();

        $assignment = app(CreateTeachingAssignment::class)(
            staffPositionId: $ctx['positionId'],
            classGroupId: $ctx['classGroupId'],
            subjectOfferingId: $ctx['offeringId'],
            startsOn: now(),
        );

        $ended = app(EndTeachingAssignment::class)(
            $assignment,
            endsOn: now(),
            reason: 'Teacher transferred mid-semester.',
        );

        $this->assertEquals(AssignmentStatus::Ended, $ended->status);
        $this->assertNotNull($ended->ends_on);
        $this->assertEquals('Teacher transferred mid-semester.', $ended->ends_reason);
    }

    public function test_rejects_ending_an_already_terminal_assignment(): void
    {
        $ctx = $this->makeValidContext();

        $assignment = app(CreateTeachingAssignment::class)(
            staffPositionId: $ctx['positionId'],
            classGroupId: $ctx['classGroupId'],
            subjectOfferingId: $ctx['offeringId'],
            startsOn: now(),
        );

        app(EndTeachingAssignment::class)($assignment, now(), 'First end.');

        $this->expectException(AssignmentException::class);
        $this->expectExceptionMessageMatches('/terminal/');

        app(EndTeachingAssignment::class)($assignment, now(), 'Second end — should fail.');
    }

    public function test_replaces_an_assignment_atomically(): void
    {
        $ctx = $this->makeValidContext();
        $teacher2 = $this->makeStaffPosition($ctx['institutionId'], $ctx['instSemId']);

        $original = app(CreateTeachingAssignment::class)(
            staffPositionId: $ctx['positionId'],
            classGroupId: $ctx['classGroupId'],
            subjectOfferingId: $ctx['offeringId'],
            startsOn: now(),
        );

        $replacement = app(ReplaceTeachingAssignment::class)(
            old: $original,
            newStaffPositionId: $teacher2['positionId'],
            replacedOn: now(),
            reason: 'Teacher replaced.',
        );

        $original->refresh();

        $this->assertEquals(AssignmentStatus::Superseded, $original->status);
        $this->assertEquals(AssignmentStatus::Active, $replacement->status);
        $this->assertEquals($teacher2['positionId'], $replacement->staff_position_id);
        $this->assertEquals($ctx['classGroupId'], $replacement->class_group_id);
        $this->assertEquals($ctx['offeringId'], $replacement->subject_offering_id);
    }

    public function test_allows_trainer_position_for_teaching_assignment(): void
    {
        $institutionId = $this->makeInstitution();
        $instSemId = $this->makeInstitutionSemester($institutionId);
        $classGroupId = $this->makeClassGroup($instSemId);
        $offeringId = $this->makeSubjectOffering($instSemId);
        $trainer = $this->makeStaffPosition($institutionId, $instSemId, 'trainer');

        $assignment = app(CreateTeachingAssignment::class)(
            staffPositionId: $trainer['positionId'],
            classGroupId: $classGroupId,
            subjectOfferingId: $offeringId,
            startsOn: now(),
        );

        $this->assertEquals(AssignmentStatus::Active, $assignment->status);
    }

    public function test_rejects_ending_assignment_before_starts_on(): void
    {
        $ctx = $this->makeValidContext();

        $assignment = app(CreateTeachingAssignment::class)(
            staffPositionId: $ctx['positionId'],
            classGroupId: $ctx['classGroupId'],
            subjectOfferingId: $ctx['offeringId'],
            startsOn: now(),
        );

        $this->expectException(AssignmentException::class);
        $this->expectExceptionMessageMatches('/before.*starts_on|before the assignment/i');

        app(EndTeachingAssignment::class)(
            $assignment,
            endsOn: now()->subDay(),
            reason: 'Should fail — date precedes starts_on.',
        );
    }

    public function test_rejects_replacing_with_date_before_starts_on(): void
    {
        $ctx = $this->makeValidContext();
        $teacher2 = $this->makeStaffPosition($ctx['institutionId'], $ctx['instSemId']);

        $assignment = app(CreateTeachingAssignment::class)(
            staffPositionId: $ctx['positionId'],
            classGroupId: $ctx['classGroupId'],
            subjectOfferingId: $ctx['offeringId'],
            startsOn: now(),
        );

        $this->expectException(AssignmentException::class);
        $this->expectExceptionMessageMatches('/before.*starts_on|before the assignment/i');

        app(ReplaceTeachingAssignment::class)(
            old: $assignment,
            newStaffPositionId: $teacher2['positionId'],
            replacedOn: now()->subDay(),
            reason: 'Should fail — replacement date precedes starts_on.',
        );
    }

    public function test_rejects_replacing_a_terminal_teaching_assignment(): void
    {
        $ctx = $this->makeValidContext();
        $teacher2 = $this->makeStaffPosition($ctx['institutionId'], $ctx['instSemId']);

        $original = app(CreateTeachingAssignment::class)(
            staffPositionId: $ctx['positionId'],
            classGroupId: $ctx['classGroupId'],
            subjectOfferingId: $ctx['offeringId'],
            startsOn: now(),
        );

        // End it — now it is terminal (status = ended).
        app(EndTeachingAssignment::class)($original, now(), 'Ended before replace attempt.');

        $this->expectException(AssignmentException::class);
        $this->expectExceptionMessageMatches('/terminal/');

        app(ReplaceTeachingAssignment::class)(
            old: $original,
            newStaffPositionId: $teacher2['positionId'],
            replacedOn: now(),
            reason: 'Should not succeed on a terminal row.',
        );
    }

    public function test_rejects_ending_assignment_in_closed_semester(): void
    {
        // Create in open semester, then close it — End must then be rejected.
        $ctx = $this->makeValidContext(semStatus: 'open');

        $assignment = app(CreateTeachingAssignment::class)(
            staffPositionId: $ctx['positionId'],
            classGroupId: $ctx['classGroupId'],
            subjectOfferingId: $ctx['offeringId'],
            startsOn: now(),
        );

        // Close the semester after creation.
        DB::table('institution_semesters')
            ->where('id', $ctx['instSemId'])
            ->update(['status' => 'closed']);

        $this->expectException(AssignmentException::class);
        $this->expectExceptionMessageMatches('/does not accept/');

        app(EndTeachingAssignment::class)($assignment, now(), 'Attempt to end in closed semester.');
    }

    public function test_rejects_replacing_assignment_in_closed_semester(): void
    {
        $ctx = $this->makeValidContext(semStatus: 'open');
        $teacher2 = $this->makeStaffPosition($ctx['institutionId'], $ctx['instSemId']);

        $assignment = app(CreateTeachingAssignment::class)(
            staffPositionId: $ctx['positionId'],
            classGroupId: $ctx['classGroupId'],
            subjectOfferingId: $ctx['offeringId'],
            startsOn: now(),
        );

        DB::table('institution_semesters')
            ->where('id', $ctx['instSemId'])
            ->update(['status' => 'closed']);

        $this->expectException(AssignmentException::class);
        $this->expectExceptionMessageMatches('/does not accept/');

        app(ReplaceTeachingAssignment::class)(
            old: $assignment,
            newStaffPositionId: $teacher2['positionId'],
            replacedOn: now(),
            reason: 'Attempt to replace in closed semester.',
        );
    }

    public function test_end_vs_replace_concurrency_second_terminal_transition_is_rejected(): void
    {
        // Simulates the end-wins scenario: End acquires the row-lock first,
        // marks the assignment Ended, then Replace must see the terminal status
        // and throw rather than silently creating an inconsistent superseded row.
        $ctx = $this->makeValidContext();
        $teacher2 = $this->makeStaffPosition($ctx['institutionId'], $ctx['instSemId']);

        $assignment = app(CreateTeachingAssignment::class)(
            staffPositionId: $ctx['positionId'],
            classGroupId: $ctx['classGroupId'],
            subjectOfferingId: $ctx['offeringId'],
            startsOn: now(),
        );

        // First transition: End succeeds.
        app(EndTeachingAssignment::class)($assignment, now(), 'First operation wins.');

        // Second transition: Replace must fail — the row is now terminal.
        $this->expectException(AssignmentException::class);
        $this->expectExceptionMessageMatches('/terminal/');

        app(ReplaceTeachingAssignment::class)(
            old: $assignment,
            newStaffPositionId: $teacher2['positionId'],
            replacedOn: now(),
            reason: 'Should fail — assignment already ended.',
        );
    }

    public function test_homeroom_assignment_has_no_subject_offering_column(): void
    {
        // Homeroom grants attendance entry only; marks require a TeachingAssignment.
        $this->assertFalse(
            Schema::hasColumn('homeroom_assignments', 'subject_offering_id'),
            'homeroom_assignments must not have a subject_offering_id — homeroom grants attendance only, not marks.'
        );
    }
}

<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AcademicManagement\Actions\ActivateEnrollment;
use Modules\AcademicManagement\Actions\ChangeDraftPlacement;
use Modules\AcademicManagement\Actions\CompleteEnrollment;
use Modules\AcademicManagement\Actions\CreateDraftEnrollment;
use Modules\AcademicManagement\Actions\ListEnrollmentHistory;
use Modules\AcademicManagement\Actions\ResolveCurrentPlacement;
use Modules\AcademicManagement\Actions\SuspendEnrollment;
use Modules\AcademicManagement\Actions\TransferStudent;
use Modules\AcademicManagement\Actions\WithdrawEnrollment;
use Modules\AcademicManagement\Enums\EnrollmentStatus;
use Modules\AcademicManagement\Exceptions\CapacityExceededException;
use Modules\AcademicManagement\Exceptions\EnrollmentMutationDeniedException;
use Modules\AcademicManagement\Models\AcademicLevel;
use Modules\AcademicManagement\Models\ClassGroup;
use Modules\AcademicManagement\Models\StudentEnrollment;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Cross-module helpers — double-backslash string-variable pattern.
// (amInstitution, amSemester, amPeriod, amLevel already defined in
//  F_AcademicStructuresTest.php; Pest shares the global function namespace
//  within a single suite run, but we redefine here for self-contained tests.)
// ---------------------------------------------------------------------------

function enrInstitution(): object
{
    $cls = 'Modules\\Organization\\Models\\Institution';

    return $cls::factory()->create(['is_active' => true]);
}

function enrSemester(int $institutionId, string $status = 'open'): object
{
    $yearCls = 'Modules\\AcademicCalendar\\Models\\AcademicYear';
    $semCls = 'Modules\\AcademicCalendar\\Models\\Semester';
    $isCls = 'Modules\\AcademicCalendar\\Models\\InstitutionSemester';

    $year = $yearCls::factory()->create(['status' => 'open']);
    $sem = $semCls::factory()->create(['academic_year_id' => $year->id, 'status' => 'open']);

    return $isCls::factory()->create([
        'institution_id' => $institutionId,
        'semester_id' => $sem->id,
        'status' => $status,
    ]);
}

function enrActiveGroup(int $institutionSemesterId): ClassGroup
{
    $period = enrPeriod($institutionSemesterId);
    $level = AcademicLevel::factory()->create();

    return ClassGroup::factory()->active()->create([
        'institution_semester_id' => $institutionSemesterId,
        'operational_period_id' => $period->id,
        'academic_level_id' => $level->id,
    ]);
}

function enrPeriod(int $institutionSemesterId): object
{
    $cls = 'Modules\\AcademicCalendar\\Models\\OperationalPeriod';

    return $cls::factory()->create([
        'institution_semester_id' => $institutionSemesterId,
        'is_active' => true,
    ]);
}

function enrStudent(): object
{
    $personCls = 'Modules\\People\\Models\\Person';
    $studentCls = 'Modules\\Students\\Models\\StudentProfile';

    $person = $personCls::factory()->create();
    $student = $studentCls::factory()->create([
        'person_id' => $person->id,
        'lifecycle_status' => 'active',
    ]);

    return $student;
}

// ---------------------------------------------------------------------------
// CreateDraftEnrollment
// ---------------------------------------------------------------------------

describe('CreateDraftEnrollment', function (): void {

    it('creates a draft enrollment for an active student in an open semester', function (): void {
        $inst = enrInstitution();
        $sem = enrSemester($inst->id);
        $group = enrActiveGroup($sem->id);
        $student = enrStudent();

        $enrollment = app(CreateDraftEnrollment::class)(
            $student->id,
            $sem->id,
            $group,
            now(),
        );

        expect($enrollment->enrollment_status)->toBe(EnrollmentStatus::Draft)
            ->and($enrollment->student_profile_id)->toBe($student->id)
            ->and($enrollment->class_group_id)->toBe($group->id)
            ->and($enrollment->institution_semester_id)->toBe($sem->id);
    });

    it('rejects a nonexistent student id', function (): void {
        $inst = enrInstitution();
        $sem = enrSemester($inst->id);
        $group = enrActiveGroup($sem->id);

        expect(fn () => app(CreateDraftEnrollment::class)(99999, $sem->id, $group, now()))
            ->toThrow(InvalidArgumentException::class);
    });

    it('rejects an inactive student', function (): void {
        $personCls = 'Modules\\People\\Models\\Person';
        $studentCls = 'Modules\\Students\\Models\\StudentProfile';
        $person = $personCls::factory()->create();
        $student = $studentCls::factory()->create([
            'person_id' => $person->id,
            'lifecycle_status' => 'inactive', // 'registered' was removed; use 'inactive' to test non-active rejection
        ]);
        $inst = enrInstitution();
        $sem = enrSemester($inst->id);
        $group = enrActiveGroup($sem->id);

        expect(fn () => app(CreateDraftEnrollment::class)($student->id, $sem->id, $group, now()))
            ->toThrow(EnrollmentMutationDeniedException::class);
    });

    it('rejects a nonexistent institution semester id', function (): void {
        $student = enrStudent();
        $inst = enrInstitution();
        $sem = enrSemester($inst->id);
        $group = enrActiveGroup($sem->id);

        expect(fn () => app(CreateDraftEnrollment::class)($student->id, 99999, $group, now()))
            ->toThrow(InvalidArgumentException::class);
    });

    it('rejects an archived semester', function (): void {
        $student = enrStudent();
        $inst = enrInstitution();
        $sem = enrSemester($inst->id, 'archived');
        $group = enrActiveGroup($sem->id);

        expect(fn () => app(CreateDraftEnrollment::class)($student->id, $sem->id, $group, now()))
            ->toThrow(EnrollmentMutationDeniedException::class);
    });

    it('rejects a class group belonging to a different semester', function (): void {
        $student = enrStudent();
        $inst = enrInstitution();
        $sem1 = enrSemester($inst->id);
        $sem2 = enrSemester($inst->id);
        $groupFromSem2 = enrActiveGroup($sem2->id);

        expect(fn () => app(CreateDraftEnrollment::class)($student->id, $sem1->id, $groupFromSem2, now()))
            ->toThrow(EnrollmentMutationDeniedException::class);
    });

    it('rejects a non-active class group', function (): void {
        $student = enrStudent();
        $inst = enrInstitution();
        $sem = enrSemester($inst->id);
        $period = enrPeriod($sem->id);
        $level = AcademicLevel::factory()->create();
        $draftGroup = ClassGroup::factory()->create([
            'institution_semester_id' => $sem->id,
            'operational_period_id' => $period->id,
            'academic_level_id' => $level->id,
            'lifecycle_status' => 'draft',
        ]);

        expect(fn () => app(CreateDraftEnrollment::class)($student->id, $sem->id, $draftGroup, now()))
            ->toThrow(EnrollmentMutationDeniedException::class);
    });

    it('rejects a duplicate draft enrollment in the same semester', function (): void {
        $student = enrStudent();
        $inst = enrInstitution();
        $sem = enrSemester($inst->id);
        $group = enrActiveGroup($sem->id);

        app(CreateDraftEnrollment::class)($student->id, $sem->id, $group, now());

        expect(fn () => app(CreateDraftEnrollment::class)($student->id, $sem->id, $group, now()))
            ->toThrow(EnrollmentMutationDeniedException::class);
    });

    it('rejects enrollment when capacity is reached without override', function (): void {
        $student1 = enrStudent();
        $student2 = enrStudent();
        $inst = enrInstitution();
        $sem = enrSemester($inst->id);
        $period = enrPeriod($sem->id);
        $level = AcademicLevel::factory()->create();
        $group = ClassGroup::factory()->active()->create([
            'institution_semester_id' => $sem->id,
            'operational_period_id' => $period->id,
            'academic_level_id' => $level->id,
            'capacity' => 1,
        ]);

        app(CreateDraftEnrollment::class)($student1->id, $sem->id, $group, now());

        expect(fn () => app(CreateDraftEnrollment::class)($student2->id, $sem->id, $group, now()))
            ->toThrow(CapacityExceededException::class);
    });

    it('allows capacity override with a reason', function (): void {
        $student1 = enrStudent();
        $student2 = enrStudent();
        $inst = enrInstitution();
        $sem = enrSemester($inst->id);
        $period = enrPeriod($sem->id);
        $level = AcademicLevel::factory()->create();
        $group = ClassGroup::factory()->active()->create([
            'institution_semester_id' => $sem->id,
            'operational_period_id' => $period->id,
            'academic_level_id' => $level->id,
            'capacity' => 1,
        ]);

        app(CreateDraftEnrollment::class)($student1->id, $sem->id, $group, now());
        $enrollment = app(CreateDraftEnrollment::class)(
            $student2->id, $sem->id, $group, now(),
            null, true, 'Emergency placement approved by principal'
        );

        expect($enrollment)->toBeInstanceOf(StudentEnrollment::class)
            ->and($enrollment->enrollment_status)->toBe(EnrollmentStatus::Draft);
    });

    it('rejects capacity override without a reason', function (): void {
        $student1 = enrStudent();
        $student2 = enrStudent();
        $inst = enrInstitution();
        $sem = enrSemester($inst->id);
        $period = enrPeriod($sem->id);
        $level = AcademicLevel::factory()->create();
        $group = ClassGroup::factory()->active()->create([
            'institution_semester_id' => $sem->id,
            'operational_period_id' => $period->id,
            'academic_level_id' => $level->id,
            'capacity' => 1,
        ]);

        app(CreateDraftEnrollment::class)($student1->id, $sem->id, $group, now());

        expect(fn () => app(CreateDraftEnrollment::class)(
            $student2->id, $sem->id, $group, now(), null, true, null
        ))->toThrow(CapacityExceededException::class);
    });

});

// ---------------------------------------------------------------------------
// ChangeDraftPlacement
// ---------------------------------------------------------------------------

describe('ChangeDraftPlacement', function (): void {

    it('moves a draft enrollment to a different class group in the same semester', function (): void {
        $student = enrStudent();
        $inst = enrInstitution();
        $sem = enrSemester($inst->id);
        $group1 = enrActiveGroup($sem->id);
        $group2 = enrActiveGroup($sem->id);

        $enrollment = app(CreateDraftEnrollment::class)($student->id, $sem->id, $group1, now());
        app(ChangeDraftPlacement::class)($enrollment, $group2);

        expect($enrollment->fresh()->class_group_id)->toBe($group2->id);
    });

    it('rejects placement change on a non-draft enrollment', function (): void {
        $student = enrStudent();
        $inst = enrInstitution();
        $sem = enrSemester($inst->id);
        $group1 = enrActiveGroup($sem->id);
        $group2 = enrActiveGroup($sem->id);

        $enrollment = app(CreateDraftEnrollment::class)($student->id, $sem->id, $group1, now());
        app(ActivateEnrollment::class)($enrollment->fresh(), now());

        expect(fn () => app(ChangeDraftPlacement::class)($enrollment->fresh(), $group2))
            ->toThrow(EnrollmentMutationDeniedException::class);
    });

    it('rejects placement to a class group in a different semester', function (): void {
        $student = enrStudent();
        $inst = enrInstitution();
        $sem1 = enrSemester($inst->id);
        $sem2 = enrSemester($inst->id);
        $group1 = enrActiveGroup($sem1->id);
        $groupSem2 = enrActiveGroup($sem2->id);

        $enrollment = app(CreateDraftEnrollment::class)($student->id, $sem1->id, $group1, now());

        expect(fn () => app(ChangeDraftPlacement::class)($enrollment, $groupSem2))
            ->toThrow(EnrollmentMutationDeniedException::class);
    });

});

// ---------------------------------------------------------------------------
// ActivateEnrollment
// ---------------------------------------------------------------------------

describe('ActivateEnrollment', function (): void {

    it('activates a draft enrollment in an open semester', function (): void {
        $student = enrStudent();
        $inst = enrInstitution();
        $sem = enrSemester($inst->id);
        $group = enrActiveGroup($sem->id);

        $enrollment = app(CreateDraftEnrollment::class)($student->id, $sem->id, $group, now());
        app(ActivateEnrollment::class)($enrollment, now());

        $fresh = $enrollment->fresh();
        expect($fresh->enrollment_status)->toBe(EnrollmentStatus::Active)
            ->and($fresh->activated_on)->not->toBeNull();
    });

    it('rejects activation of a non-draft enrollment', function (): void {
        $student = enrStudent();
        $inst = enrInstitution();
        $sem = enrSemester($inst->id);
        $group = enrActiveGroup($sem->id);

        $enrollment = app(CreateDraftEnrollment::class)($student->id, $sem->id, $group, now());
        app(ActivateEnrollment::class)($enrollment->fresh(), now());

        // Try to activate again.
        expect(fn () => app(ActivateEnrollment::class)($enrollment->fresh(), now()))
            ->toThrow(EnrollmentMutationDeniedException::class);
    });

    it('rejects activation when semester is not open (closed)', function (): void {
        $student = enrStudent();
        $inst = enrInstitution();
        $sem = enrSemester($inst->id, 'open');
        $group = enrActiveGroup($sem->id);

        $enrollment = app(CreateDraftEnrollment::class)($student->id, $sem->id, $group, now());

        // Close the semester.
        $sem->status = 'closed';
        $sem->save();

        expect(fn () => app(ActivateEnrollment::class)($enrollment->fresh(), now()))
            ->toThrow(EnrollmentMutationDeniedException::class);
    });

    it('rejects activation when student already has active enrollment in same semester', function (): void {
        $student = enrStudent();
        $inst = enrInstitution();
        $sem = enrSemester($inst->id);
        $group1 = enrActiveGroup($sem->id);
        $group2 = enrActiveGroup($sem->id);

        // Create and activate first enrollment.
        $enrollment1 = app(CreateDraftEnrollment::class)($student->id, $sem->id, $group1, now());
        app(ActivateEnrollment::class)($enrollment1->fresh(), now());

        // Force-create a second draft bypassing action guards for this test.
        $enrollment2 = StudentEnrollment::create([
            'student_profile_id' => $student->id,
            'institution_semester_id' => $sem->id,
            'class_group_id' => $group2->id,
            'enrollment_status' => EnrollmentStatus::Draft->value,
            'enrolled_on' => now()->toDateString(),
        ]);

        expect(fn () => app(ActivateEnrollment::class)($enrollment2, now()))
            ->toThrow(EnrollmentMutationDeniedException::class);
    });

    it('rejects activation when student is already active at a different institution', function (): void {
        $student = enrStudent();
        $instA = enrInstitution();
        $instB = enrInstitution();
        $semA = enrSemester($instA->id);
        $semB = enrSemester($instB->id);
        $groupA = enrActiveGroup($semA->id);
        $groupB = enrActiveGroup($semB->id);

        // Activate enrollment at institution A.
        $enrollmentA = app(CreateDraftEnrollment::class)($student->id, $semA->id, $groupA, now());
        app(ActivateEnrollment::class)($enrollmentA->fresh(), now());

        // Create draft enrollment at institution B.
        $enrollmentB = StudentEnrollment::create([
            'student_profile_id' => $student->id,
            'institution_semester_id' => $semB->id,
            'class_group_id' => $groupB->id,
            'enrollment_status' => EnrollmentStatus::Draft->value,
            'enrolled_on' => now()->toDateString(),
        ]);

        expect(fn () => app(ActivateEnrollment::class)($enrollmentB, now()))
            ->toThrow(EnrollmentMutationDeniedException::class);
    });

    it('allows activation at the same institution in a different semester', function (): void {
        $student = enrStudent();
        $inst = enrInstitution();
        $sem1 = enrSemester($inst->id);
        $sem2 = enrSemester($inst->id);
        $group1 = enrActiveGroup($sem1->id);
        $group2 = enrActiveGroup($sem2->id);

        // Activate in semester 1.
        $enrollment1 = app(CreateDraftEnrollment::class)($student->id, $sem1->id, $group1, now());
        app(ActivateEnrollment::class)($enrollment1->fresh(), now());

        // Draft enrollment in semester 2 of same institution.
        $enrollment2 = StudentEnrollment::create([
            'student_profile_id' => $student->id,
            'institution_semester_id' => $sem2->id,
            'class_group_id' => $group2->id,
            'enrollment_status' => EnrollmentStatus::Draft->value,
            'enrolled_on' => now()->toDateString(),
        ]);

        // Same institution — should be allowed.
        $activated = app(ActivateEnrollment::class)($enrollment2, now());
        expect($activated->enrollment_status)->toBe(EnrollmentStatus::Active);
    });

});

// ---------------------------------------------------------------------------
// CompleteEnrollment
// ---------------------------------------------------------------------------

describe('CompleteEnrollment', function (): void {

    it('completes an active enrollment', function (): void {
        $student = enrStudent();
        $inst = enrInstitution();
        $sem = enrSemester($inst->id);
        $group = enrActiveGroup($sem->id);

        $enrollment = app(CreateDraftEnrollment::class)($student->id, $sem->id, $group, now());
        app(ActivateEnrollment::class)($enrollment->fresh(), now());
        app(CompleteEnrollment::class)($enrollment->fresh(), now());

        $fresh = $enrollment->fresh();
        expect($fresh->enrollment_status)->toBe(EnrollmentStatus::Completed)
            ->and($fresh->completed_on)->not->toBeNull();
    });

    it('rejects completion of a draft enrollment', function (): void {
        $student = enrStudent();
        $inst = enrInstitution();
        $sem = enrSemester($inst->id);
        $group = enrActiveGroup($sem->id);

        $enrollment = app(CreateDraftEnrollment::class)($student->id, $sem->id, $group, now());

        expect(fn () => app(CompleteEnrollment::class)($enrollment, now()))
            ->toThrow(EnrollmentMutationDeniedException::class);
    });

});

// ---------------------------------------------------------------------------
// WithdrawEnrollment
// ---------------------------------------------------------------------------

describe('WithdrawEnrollment', function (): void {

    it('withdraws a draft enrollment', function (): void {
        $student = enrStudent();
        $inst = enrInstitution();
        $sem = enrSemester($inst->id);
        $group = enrActiveGroup($sem->id);

        $enrollment = app(CreateDraftEnrollment::class)($student->id, $sem->id, $group, now());
        app(WithdrawEnrollment::class)($enrollment, 'Parent request');

        expect($enrollment->fresh()->enrollment_status)->toBe(EnrollmentStatus::Withdrawn);
    });

    it('withdraws an active enrollment', function (): void {
        $student = enrStudent();
        $inst = enrInstitution();
        $sem = enrSemester($inst->id);
        $group = enrActiveGroup($sem->id);

        $enrollment = app(CreateDraftEnrollment::class)($student->id, $sem->id, $group, now());
        app(ActivateEnrollment::class)($enrollment->fresh(), now());
        app(WithdrawEnrollment::class)($enrollment->fresh());

        expect($enrollment->fresh()->enrollment_status)->toBe(EnrollmentStatus::Withdrawn);
    });

    it('rejects withdrawal of a terminal enrollment', function (): void {
        $student = enrStudent();
        $inst = enrInstitution();
        $sem = enrSemester($inst->id);
        $group = enrActiveGroup($sem->id);

        $enrollment = StudentEnrollment::factory()
            ->forStudent($student->id)
            ->forSemester($sem->id)
            ->promoted()
            ->create(['class_group_id' => $group->id]);

        expect(fn () => app(WithdrawEnrollment::class)($enrollment))
            ->toThrow(EnrollmentMutationDeniedException::class);
    });

});

// ---------------------------------------------------------------------------
// SuspendEnrollment
// ---------------------------------------------------------------------------

describe('SuspendEnrollment', function (): void {

    it('suspends an active enrollment', function (): void {
        $student = enrStudent();
        $inst = enrInstitution();
        $sem = enrSemester($inst->id);
        $group = enrActiveGroup($sem->id);

        $enrollment = app(CreateDraftEnrollment::class)($student->id, $sem->id, $group, now());
        app(ActivateEnrollment::class)($enrollment->fresh(), now());
        app(SuspendEnrollment::class)($enrollment->fresh(), 'Medical leave');

        expect($enrollment->fresh()->enrollment_status)->toBe(EnrollmentStatus::Suspended);
    });

    it('rejects suspension of a draft enrollment', function (): void {
        $student = enrStudent();
        $inst = enrInstitution();
        $sem = enrSemester($inst->id);
        $group = enrActiveGroup($sem->id);

        $enrollment = app(CreateDraftEnrollment::class)($student->id, $sem->id, $group, now());

        expect(fn () => app(SuspendEnrollment::class)($enrollment))
            ->toThrow(EnrollmentMutationDeniedException::class);
    });

});

// ---------------------------------------------------------------------------
// TransferStudent
// ---------------------------------------------------------------------------

describe('TransferStudent', function (): void {

    it('atomically transfers a student to a new class group', function (): void {
        $student = enrStudent();
        $instA = enrInstitution();
        $instB = enrInstitution();
        $semA = enrSemester($instA->id);
        $semB = enrSemester($instB->id);
        $groupA = enrActiveGroup($semA->id);
        $groupB = enrActiveGroup($semB->id);

        // Activate enrollment at institution A.
        $enrollmentA = app(CreateDraftEnrollment::class)($student->id, $semA->id, $groupA, now());
        app(ActivateEnrollment::class)($enrollmentA->fresh(), now());

        // Transfer to institution B.
        $newEnrollment = app(TransferStudent::class)(
            $student->id, $groupB, now(), 'Family relocation'
        );

        expect($enrollmentA->fresh()->enrollment_status)->toBe(EnrollmentStatus::Transferred)
            ->and($newEnrollment->enrollment_status)->toBe(EnrollmentStatus::Draft)
            ->and($newEnrollment->class_group_id)->toBe($groupB->id)
            ->and($newEnrollment->institution_semester_id)->toBe($semB->id);
    });

    it('preserves history after transfer — original enrollment row is retained', function (): void {
        $student = enrStudent();
        $inst = enrInstitution();
        $sem = enrSemester($inst->id);
        $groupA = enrActiveGroup($sem->id);
        $groupB = enrActiveGroup($sem->id);

        $enrollmentA = app(CreateDraftEnrollment::class)($student->id, $sem->id, $groupA, now());
        app(ActivateEnrollment::class)($enrollmentA->fresh(), now());
        app(TransferStudent::class)($student->id, $groupB, now());

        $history = app(ListEnrollmentHistory::class)($student->id);

        expect($history)->toHaveCount(2)
            ->and($history->first()->enrollment_status)->toBe(EnrollmentStatus::Draft) // new draft
            ->and($history->last()->enrollment_status)->toBe(EnrollmentStatus::Transferred); // old
    });

    it('rejects transfer when student has no active enrollment', function (): void {
        $student = enrStudent();
        $inst = enrInstitution();
        $sem = enrSemester($inst->id);
        $group = enrActiveGroup($sem->id);

        expect(fn () => app(TransferStudent::class)($student->id, $group, now()))
            ->toThrow(EnrollmentMutationDeniedException::class);
    });

    it('rejects transfer to an archived semester', function (): void {
        $student = enrStudent();
        $instA = enrInstitution();
        $instB = enrInstitution();
        $semA = enrSemester($instA->id);
        $semB = enrSemester($instB->id, 'archived');
        $groupA = enrActiveGroup($semA->id);
        $groupB = enrActiveGroup($semB->id);

        $enrollment = app(CreateDraftEnrollment::class)($student->id, $semA->id, $groupA, now());
        app(ActivateEnrollment::class)($enrollment->fresh(), now());

        expect(fn () => app(TransferStudent::class)($student->id, $groupB, now()))
            ->toThrow(EnrollmentMutationDeniedException::class);
    });

    it('rejects transfer when capacity is reached without override', function (): void {
        $student1 = enrStudent();
        $student2 = enrStudent();
        $inst = enrInstitution();
        $sem = enrSemester($inst->id);
        $period = enrPeriod($sem->id);
        $level = AcademicLevel::factory()->create();
        $groupA = enrActiveGroup($sem->id);
        $groupB = ClassGroup::factory()->active()->create([
            'institution_semester_id' => $sem->id,
            'operational_period_id' => $period->id,
            'academic_level_id' => $level->id,
            'capacity' => 1,
        ]);

        // Fill group B with student1.
        app(CreateDraftEnrollment::class)($student1->id, $sem->id, $groupB, now());

        // student2 tries to transfer into full group B.
        $enrollmentA = app(CreateDraftEnrollment::class)($student2->id, $sem->id, $groupA, now());
        app(ActivateEnrollment::class)($enrollmentA->fresh(), now());

        expect(fn () => app(TransferStudent::class)($student2->id, $groupB, now()))
            ->toThrow(CapacityExceededException::class);
    });

});

// ---------------------------------------------------------------------------
// ResolveCurrentPlacement
// ---------------------------------------------------------------------------

describe('ResolveCurrentPlacement', function (): void {

    it('returns the active enrollment with eager-loaded class group', function (): void {
        $student = enrStudent();
        $inst = enrInstitution();
        $sem = enrSemester($inst->id);
        $group = enrActiveGroup($sem->id);

        $enrollment = app(CreateDraftEnrollment::class)($student->id, $sem->id, $group, now());
        app(ActivateEnrollment::class)($enrollment->fresh(), now());

        $placement = app(ResolveCurrentPlacement::class)($student->id);

        expect($placement)->not->toBeNull()
            ->and($placement->id)->toBe($enrollment->id)
            ->and($placement->classGroup)->not->toBeNull();
    });

    it('returns null when student has no active enrollment', function (): void {
        $student = enrStudent();

        expect(app(ResolveCurrentPlacement::class)($student->id))->toBeNull();
    });

});

// ---------------------------------------------------------------------------
// ListEnrollmentHistory
// ---------------------------------------------------------------------------

describe('ListEnrollmentHistory', function (): void {

    it('returns all enrollments for a student in reverse chronological order', function (): void {
        $student = enrStudent();
        $inst = enrInstitution();
        $sem1 = enrSemester($inst->id);
        $sem2 = enrSemester($inst->id);
        $group1 = enrActiveGroup($sem1->id);
        $group2 = enrActiveGroup($sem2->id);

        $e1 = StudentEnrollment::factory()
            ->forStudent($student->id)
            ->forSemester($sem1->id)
            ->withdrawn()
            ->create(['class_group_id' => $group1->id, 'enrolled_on' => now()->subYear()]);

        $e2 = StudentEnrollment::factory()
            ->forStudent($student->id)
            ->forSemester($sem2->id)
            ->active()
            ->create(['class_group_id' => $group2->id, 'enrolled_on' => now()]);

        $history = app(ListEnrollmentHistory::class)($student->id);

        expect($history)->toHaveCount(2)
            ->and($history->first()->id)->toBe($e2->id); // most recent first
    });

});

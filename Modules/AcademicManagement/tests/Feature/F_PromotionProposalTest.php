<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AcademicManagement\Actions\ActivateEnrollment;
use Modules\AcademicManagement\Actions\ApplyApprovedProposal;
use Modules\AcademicManagement\Actions\CompleteEnrollment;
use Modules\AcademicManagement\Actions\CreateDraftEnrollment;
use Modules\AcademicManagement\Actions\CreatePromotionProposal;
use Modules\AcademicManagement\Actions\ReviewPromotionProposal;
use Modules\AcademicManagement\Enums\EnrollmentStatus;
use Modules\AcademicManagement\Enums\ProposalReviewStatus;
use Modules\AcademicManagement\Enums\ProposalStatus;
use Modules\AcademicManagement\Exceptions\CapacityExceededException;
use Modules\AcademicManagement\Exceptions\EnrollmentMutationDeniedException;
use Modules\AcademicManagement\Models\AcademicLevel;
use Modules\AcademicManagement\Models\ClassGroup;
use Modules\AcademicManagement\Models\PromotionProposal;
use Modules\AcademicManagement\Models\StudentEnrollment;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Cross-module helpers
// ---------------------------------------------------------------------------

function ppInstitution(): object
{
    $cls = 'Modules\\Organization\\Models\\Institution';

    return $cls::factory()->create(['is_active' => true]);
}

function ppSemester(int $institutionId, string $status = 'open'): object
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

function ppActiveGroup(int $institutionSemesterId): ClassGroup
{
    $periodCls = 'Modules\\AcademicCalendar\\Models\\OperationalPeriod';
    $period = $periodCls::factory()->create([
        'institution_semester_id' => $institutionSemesterId,
        'is_active' => true,
    ]);
    $level = AcademicLevel::factory()->create();

    return ClassGroup::factory()->active()->create([
        'institution_semester_id' => $institutionSemesterId,
        'operational_period_id' => $period->id,
        'academic_level_id' => $level->id,
    ]);
}

function ppStudent(): object
{
    $personCls = 'Modules\\People\\Models\\Person';
    $studentCls = 'Modules\\Students\\Models\\StudentProfile';
    $person = $personCls::factory()->create();

    return $studentCls::factory()->create([
        'person_id' => $person->id,
        'lifecycle_status' => 'active',
    ]);
}

/** Create an active enrollment, returned as fresh model. */
function ppActiveEnrollment(object $student, int $semId, ClassGroup $group): StudentEnrollment
{
    $enrollment = app(CreateDraftEnrollment::class)($student->id, $semId, $group, now());
    app(ActivateEnrollment::class)($enrollment->fresh(), now());

    return $enrollment->fresh();
}

/** Create a completed enrollment, returned as fresh model. */
function ppCompletedEnrollment(object $student, int $semId, ClassGroup $group): StudentEnrollment
{
    $enrollment = ppActiveEnrollment($student, $semId, $group);
    app(CompleteEnrollment::class)($enrollment, now());

    return $enrollment->fresh();
}

// ---------------------------------------------------------------------------
// CreatePromotionProposal
// ---------------------------------------------------------------------------

describe('CreatePromotionProposal', function (): void {

    it('creates a pending proposal for a completed enrollment', function (): void {
        $student = ppStudent();
        $inst = ppInstitution();
        $sem = ppSemester($inst->id);
        $group = ppActiveGroup($sem->id);

        $enrollment = ppCompletedEnrollment($student, $sem->id, $group);
        $proposal = app(CreatePromotionProposal::class)($enrollment, ProposalStatus::Promoted, null, 'Excellent performance');

        expect($proposal->review_status)->toBe(ProposalReviewStatus::Pending)
            ->and($proposal->proposed_status)->toBe(ProposalStatus::Promoted)
            ->and($proposal->source_enrollment_id)->toBe($enrollment->id)
            ->and($proposal->proposed_class_group_id)->toBeNull();
    });

    it('creates a proposal for an active enrollment', function (): void {
        $student = ppStudent();
        $inst = ppInstitution();
        $sem = ppSemester($inst->id);
        $group = ppActiveGroup($sem->id);

        $enrollment = ppActiveEnrollment($student, $sem->id, $group);
        $proposal = app(CreatePromotionProposal::class)($enrollment, ProposalStatus::Promoted);

        expect($proposal->review_status)->toBe(ProposalReviewStatus::Pending);
    });

    it('creates a proposal with a proposed target class group', function (): void {
        $student = ppStudent();
        $inst = ppInstitution();
        $sem = ppSemester($inst->id);
        $nextSem = ppSemester($inst->id);
        $group = ppActiveGroup($sem->id);
        $nextGroup = ppActiveGroup($nextSem->id);

        $enrollment = ppCompletedEnrollment($student, $sem->id, $group);
        $proposal = app(CreatePromotionProposal::class)($enrollment, ProposalStatus::Promoted, $nextGroup);

        expect($proposal->proposed_class_group_id)->toBe($nextGroup->id);
    });

    it('rejects proposal creation for a draft enrollment', function (): void {
        $student = ppStudent();
        $inst = ppInstitution();
        $sem = ppSemester($inst->id);
        $group = ppActiveGroup($sem->id);
        $enrollment = app(CreateDraftEnrollment::class)($student->id, $sem->id, $group, now());

        expect(fn () => app(CreatePromotionProposal::class)($enrollment, ProposalStatus::Promoted))
            ->toThrow(EnrollmentMutationDeniedException::class);
    });

    it('rejects proposal creation for a terminal enrollment', function (): void {
        $student = ppStudent();
        $inst = ppInstitution();
        $sem = ppSemester($inst->id);
        $group = ppActiveGroup($sem->id);
        $enrollment = StudentEnrollment::factory()
            ->forStudent($student->id)
            ->forSemester($sem->id)
            ->withdrawn()
            ->create(['class_group_id' => $group->id]);

        expect(fn () => app(CreatePromotionProposal::class)($enrollment, ProposalStatus::Promoted))
            ->toThrow(EnrollmentMutationDeniedException::class);
    });

    it('proposals do not auto-activate a new enrollment', function (): void {
        $student = ppStudent();
        $inst = ppInstitution();
        $sem = ppSemester($inst->id);
        $nextSem = ppSemester($inst->id);
        $group = ppActiveGroup($sem->id);
        $nextGroup = ppActiveGroup($nextSem->id);

        $enrollment = ppCompletedEnrollment($student, $sem->id, $group);
        app(CreatePromotionProposal::class)($enrollment, ProposalStatus::Promoted, $nextGroup);

        // No active enrollment should have been created in nextSem.
        $activeInNextSem = StudentEnrollment::where('student_profile_id', $student->id)
            ->where('institution_semester_id', $nextSem->id)
            ->where('enrollment_status', EnrollmentStatus::Active->value)
            ->exists();

        expect($activeInNextSem)->toBeFalse();
    });

});

// ---------------------------------------------------------------------------
// ReviewPromotionProposal
// ---------------------------------------------------------------------------

describe('ReviewPromotionProposal', function (): void {

    it('approves a pending proposal', function (): void {
        $proposal = PromotionProposal::factory()->create();
        app(ReviewPromotionProposal::class)($proposal, ProposalReviewStatus::Approved, 'principal-001');

        expect($proposal->fresh()->review_status)->toBe(ProposalReviewStatus::Approved)
            ->and($proposal->fresh()->reviewed_by)->toBe('principal-001')
            ->and($proposal->fresh()->reviewed_at)->not->toBeNull();
    });

    it('rejects a pending proposal', function (): void {
        $proposal = PromotionProposal::factory()->create();
        app(ReviewPromotionProposal::class)($proposal, ProposalReviewStatus::Rejected, 'principal-001', 'Missing evidence');

        expect($proposal->fresh()->review_status)->toBe(ProposalReviewStatus::Rejected);
    });

    it('cannot review an already-reviewed proposal', function (): void {
        $proposal = PromotionProposal::factory()->approved()->create();

        expect(fn () => app(ReviewPromotionProposal::class)($proposal, ProposalReviewStatus::Rejected, 'principal-001'))
            ->toThrow(EnrollmentMutationDeniedException::class);
    });

    it('rejects an empty reviewed_by actor reference', function (): void {
        $proposal = PromotionProposal::factory()->create();

        expect(fn () => app(ReviewPromotionProposal::class)($proposal, ProposalReviewStatus::Approved, ''))
            ->toThrow(InvalidArgumentException::class);
    });

    it('rejects passing pending as a review decision', function (): void {
        $proposal = PromotionProposal::factory()->create();

        expect(fn () => app(ReviewPromotionProposal::class)($proposal, ProposalReviewStatus::Pending, 'principal-001'))
            ->toThrow(InvalidArgumentException::class);
    });

});

// ---------------------------------------------------------------------------
// ApplyApprovedProposal
// ---------------------------------------------------------------------------

describe('ApplyApprovedProposal', function (): void {

    it('applies a promoted proposal and closes source enrollment as promoted', function (): void {
        $student = ppStudent();
        $inst = ppInstitution();
        $sem = ppSemester($inst->id);
        $nextSem = ppSemester($inst->id);
        $group = ppActiveGroup($sem->id);
        $nextGroup = ppActiveGroup($nextSem->id);

        $enrollment = ppCompletedEnrollment($student, $sem->id, $group);
        $proposal = app(CreatePromotionProposal::class)($enrollment, ProposalStatus::Promoted, $nextGroup);
        app(ReviewPromotionProposal::class)($proposal, ProposalReviewStatus::Approved, 'principal-001');

        $newEnrollment = app(ApplyApprovedProposal::class)($proposal->fresh());

        expect($enrollment->fresh()->enrollment_status)->toBe(EnrollmentStatus::Promoted)
            ->and($newEnrollment)->not->toBeNull()
            ->and($newEnrollment->enrollment_status)->toBe(EnrollmentStatus::Draft)
            ->and($newEnrollment->class_group_id)->toBe($nextGroup->id)
            ->and($newEnrollment->institution_semester_id)->toBe($nextSem->id);
    });

    it('applies a graduated proposal with no new enrollment created', function (): void {
        $student = ppStudent();
        $inst = ppInstitution();
        $sem = ppSemester($inst->id);
        $group = ppActiveGroup($sem->id);

        $enrollment = ppCompletedEnrollment($student, $sem->id, $group);
        $proposal = app(CreatePromotionProposal::class)($enrollment, ProposalStatus::Graduated);
        app(ReviewPromotionProposal::class)($proposal, ProposalReviewStatus::Approved, 'principal-001');

        $result = app(ApplyApprovedProposal::class)($proposal->fresh());

        expect($enrollment->fresh()->enrollment_status)->toBe(EnrollmentStatus::Graduated)
            ->and($result)->toBeNull();
    });

    it('applies a repeating proposal and creates a new draft enrollment in proposed group', function (): void {
        $student = ppStudent();
        $inst = ppInstitution();
        $sem = ppSemester($inst->id);
        $nextSem = ppSemester($inst->id);
        $group = ppActiveGroup($sem->id);
        $sameGroup = ppActiveGroup($nextSem->id);

        $enrollment = ppCompletedEnrollment($student, $sem->id, $group);
        $proposal = app(CreatePromotionProposal::class)($enrollment, ProposalStatus::Repeating, $sameGroup);
        app(ReviewPromotionProposal::class)($proposal, ProposalReviewStatus::Approved, 'principal-001');

        $newEnrollment = app(ApplyApprovedProposal::class)($proposal->fresh());

        expect($enrollment->fresh()->enrollment_status)->toBe(EnrollmentStatus::Repeating)
            ->and($newEnrollment->enrollment_status)->toBe(EnrollmentStatus::Draft);
    });

    it('does not auto-activate the new enrollment on apply', function (): void {
        $student = ppStudent();
        $inst = ppInstitution();
        $sem = ppSemester($inst->id);
        $nextSem = ppSemester($inst->id);
        $group = ppActiveGroup($sem->id);
        $nextGroup = ppActiveGroup($nextSem->id);

        $enrollment = ppCompletedEnrollment($student, $sem->id, $group);
        $proposal = app(CreatePromotionProposal::class)($enrollment, ProposalStatus::Promoted, $nextGroup);
        app(ReviewPromotionProposal::class)($proposal, ProposalReviewStatus::Approved, 'principal-001');

        $newEnrollment = app(ApplyApprovedProposal::class)($proposal->fresh());

        expect($newEnrollment->enrollment_status)->toBe(EnrollmentStatus::Draft);
    });

    it('rejects applying a non-approved proposal', function (): void {
        $proposal = PromotionProposal::factory()->create(); // pending

        expect(fn () => app(ApplyApprovedProposal::class)($proposal))
            ->toThrow(EnrollmentMutationDeniedException::class);
    });

    it('rejects applying a rejected proposal', function (): void {
        $proposal = PromotionProposal::factory()->rejected()->create();

        expect(fn () => app(ApplyApprovedProposal::class)($proposal))
            ->toThrow(EnrollmentMutationDeniedException::class);
    });

    it('rejects applying to an enrollment that is already terminal', function (): void {
        $student = ppStudent();
        $inst = ppInstitution();
        $sem = ppSemester($inst->id);
        $group = ppActiveGroup($sem->id);

        $enrollment = ppCompletedEnrollment($student, $sem->id, $group);

        // First application.
        $proposal1 = app(CreatePromotionProposal::class)($enrollment, ProposalStatus::Graduated);
        app(ReviewPromotionProposal::class)($proposal1, ProposalReviewStatus::Approved, 'principal-001');
        app(ApplyApprovedProposal::class)($proposal1->fresh());

        // Second proposal on the same (now terminal) enrollment.
        $proposal2 = PromotionProposal::factory()
            ->approved()
            ->create(['source_enrollment_id' => $enrollment->id]);

        expect(fn () => app(ApplyApprovedProposal::class)($proposal2->fresh()))
            ->toThrow(EnrollmentMutationDeniedException::class);
    });

    it('rejects applying when proposed class group is no longer active at apply-time', function (): void {
        $student = ppStudent();
        $inst = ppInstitution();
        $sem = ppSemester($inst->id);
        $nextSem = ppSemester($inst->id);
        $group = ppActiveGroup($sem->id);
        $nextGroup = ppActiveGroup($nextSem->id);

        $enrollment = ppCompletedEnrollment($student, $sem->id, $group);
        $proposal = app(CreatePromotionProposal::class)($enrollment, ProposalStatus::Promoted, $nextGroup);
        app(ReviewPromotionProposal::class)($proposal, ProposalReviewStatus::Approved, 'principal-001');

        // Deactivate the target group before applying.
        $nextGroup->lifecycle_status = 'archived';
        $nextGroup->save();

        expect(fn () => app(ApplyApprovedProposal::class)($proposal->fresh()))
            ->toThrow(EnrollmentMutationDeniedException::class);
    });

    it('rejects applying when target semester is archived at apply-time', function (): void {
        $student = ppStudent();
        $inst = ppInstitution();
        $sem = ppSemester($inst->id);
        $nextSem = ppSemester($inst->id);
        $group = ppActiveGroup($sem->id);
        $nextGroup = ppActiveGroup($nextSem->id);

        $enrollment = ppCompletedEnrollment($student, $sem->id, $group);
        $proposal = app(CreatePromotionProposal::class)($enrollment, ProposalStatus::Promoted, $nextGroup);
        app(ReviewPromotionProposal::class)($proposal, ProposalReviewStatus::Approved, 'principal-001');

        // Archive the target semester after approval.
        $nextSem->status = 'archived';
        $nextSem->save();

        expect(fn () => app(ApplyApprovedProposal::class)($proposal->fresh()))
            ->toThrow(EnrollmentMutationDeniedException::class);
    });

    it('rejects applying when student already has a draft enrollment in the target semester', function (): void {
        $student = ppStudent();
        $inst = ppInstitution();
        $sem = ppSemester($inst->id);
        $nextSem = ppSemester($inst->id);
        $group = ppActiveGroup($sem->id);
        $nextGroup = ppActiveGroup($nextSem->id);

        $enrollment = ppCompletedEnrollment($student, $sem->id, $group);
        $proposal = app(CreatePromotionProposal::class)($enrollment, ProposalStatus::Promoted, $nextGroup);
        app(ReviewPromotionProposal::class)($proposal, ProposalReviewStatus::Approved, 'principal-001');

        // Student already has a draft enrollment in the target semester.
        StudentEnrollment::create([
            'student_profile_id' => $student->id,
            'institution_semester_id' => $nextSem->id,
            'class_group_id' => $nextGroup->id,
            'enrollment_status' => EnrollmentStatus::Draft->value,
            'enrolled_on' => now()->toDateString(),
        ]);

        expect(fn () => app(ApplyApprovedProposal::class)($proposal->fresh()))
            ->toThrow(EnrollmentMutationDeniedException::class);
    });

    it('rejects applying when target class group is at capacity without override', function (): void {
        $student1 = ppStudent();
        $student2 = ppStudent();
        $inst = ppInstitution();
        $sem = ppSemester($inst->id);
        $nextSem = ppSemester($inst->id);
        $group = ppActiveGroup($sem->id);
        $periodCls = 'Modules\\AcademicCalendar\\Models\\OperationalPeriod';
        $period = $periodCls::factory()->create([
            'institution_semester_id' => $nextSem->id,
            'is_active' => true,
        ]);
        $level = AcademicLevel::factory()->create();
        $nextGroup = ClassGroup::factory()->active()->create([
            'institution_semester_id' => $nextSem->id,
            'operational_period_id' => $period->id,
            'academic_level_id' => $level->id,
            'capacity' => 1,
        ]);

        // Fill capacity with student1.
        StudentEnrollment::create([
            'student_profile_id' => $student1->id,
            'institution_semester_id' => $nextSem->id,
            'class_group_id' => $nextGroup->id,
            'enrollment_status' => EnrollmentStatus::Active->value,
            'enrolled_on' => now()->toDateString(),
        ]);

        // student2's proposal pointing to full group.
        $enrollment = ppCompletedEnrollment($student2, $sem->id, $group);
        $proposal = app(CreatePromotionProposal::class)($enrollment, ProposalStatus::Promoted, $nextGroup);
        app(ReviewPromotionProposal::class)($proposal, ProposalReviewStatus::Approved, 'principal-001');

        expect(fn () => app(ApplyApprovedProposal::class)($proposal->fresh()))
            ->toThrow(CapacityExceededException::class);
    });

    it('allows applying with capacity override and reason when target is full', function (): void {
        $student1 = ppStudent();
        $student2 = ppStudent();
        $inst = ppInstitution();
        $sem = ppSemester($inst->id);
        $nextSem = ppSemester($inst->id);
        $group = ppActiveGroup($sem->id);
        $periodCls = 'Modules\\AcademicCalendar\\Models\\OperationalPeriod';
        $period = $periodCls::factory()->create([
            'institution_semester_id' => $nextSem->id,
            'is_active' => true,
        ]);
        $level = AcademicLevel::factory()->create();
        $nextGroup = ClassGroup::factory()->active()->create([
            'institution_semester_id' => $nextSem->id,
            'operational_period_id' => $period->id,
            'academic_level_id' => $level->id,
            'capacity' => 1,
        ]);

        StudentEnrollment::create([
            'student_profile_id' => $student1->id,
            'institution_semester_id' => $nextSem->id,
            'class_group_id' => $nextGroup->id,
            'enrollment_status' => EnrollmentStatus::Active->value,
            'enrolled_on' => now()->toDateString(),
        ]);

        $enrollment = ppCompletedEnrollment($student2, $sem->id, $group);
        $proposal = app(CreatePromotionProposal::class)($enrollment, ProposalStatus::Promoted, $nextGroup);
        app(ReviewPromotionProposal::class)($proposal, ProposalReviewStatus::Approved, 'principal-001');

        $newEnrollment = app(ApplyApprovedProposal::class)($proposal->fresh(), true, 'Principal approved exception');

        expect($newEnrollment)->not->toBeNull()
            ->and($newEnrollment->enrollment_status)->toBe(EnrollmentStatus::Draft);
    });

});

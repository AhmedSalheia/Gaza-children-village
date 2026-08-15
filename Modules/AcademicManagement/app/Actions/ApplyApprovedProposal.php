<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Illuminate\Support\Facades\DB;
use Modules\AcademicManagement\Enums\EnrollmentStatus;
use Modules\AcademicManagement\Enums\ProposalStatus;
use Modules\AcademicManagement\Exceptions\CapacityExceededException;
use Modules\AcademicManagement\Exceptions\EnrollmentMutationDeniedException;
use Modules\AcademicManagement\Models\ClassGroup;
use Modules\AcademicManagement\Models\PromotionProposal;
use Modules\AcademicManagement\Models\StudentEnrollment;

/**
 * Atomically apply an approved promotion proposal to the source enrollment.
 *
 * Steps (all within a DB transaction with lockForUpdate):
 *  1. lockForUpdate on the proposal and source enrollment.
 *  2. Proposal must be approved; source enrollment must be active or completed.
 *  3. Apply the proposed_status to the source enrollment (closing it terminally).
 *  4. If proposed_class_group_id is set, validate current state of the target:
 *       a. Proposed class group must still be lifecycle-active.
 *       b. Target InstitutionSemester must not be archived.
 *       c. Student must not already have a draft/active enrollment in the target semester.
 *       d. Capacity must not be exceeded (or an override must be explicitly set).
 *  5. Create a new draft enrollment if all guards pass.
 *
 * The new enrollment is created as draft — it must be explicitly activated.
 * Approved proposals never auto-activate a new enrollment.
 *
 * Mapping proposal → enrollment terminal status:
 *   promoted    → EnrollmentStatus::Promoted
 *   repeating   → EnrollmentStatus::Repeating
 *   graduated   → EnrollmentStatus::Graduated
 *   transferred → EnrollmentStatus::Transferred
 *   unresolved  → (no enrollment status change; leaves enrollment as completed)
 */
final class ApplyApprovedProposal
{
    public function __invoke(
        PromotionProposal $proposal,
        bool $capacityOverride = false,
        ?string $capacityOverrideReason = null,
    ): ?StudentEnrollment {
        return DB::transaction(function () use ($proposal, $capacityOverride, $capacityOverrideReason): ?StudentEnrollment {
            // Lock proposal and source enrollment for concurrency safety.
            $lockedProposal = PromotionProposal::lockForUpdate()->findOrFail($proposal->id);
            $lockedEnrollment = StudentEnrollment::lockForUpdate()->findOrFail($lockedProposal->source_enrollment_id);

            if (! $lockedProposal->isApproved()) {
                throw new EnrollmentMutationDeniedException(
                    "Proposal #{$proposal->id} is not approved and cannot be applied."
                );
            }

            $allowedStatuses = [EnrollmentStatus::Active, EnrollmentStatus::Completed];
            if (! in_array($lockedEnrollment->enrollment_status, $allowedStatuses, true)) {
                throw new EnrollmentMutationDeniedException(
                    "Source enrollment #{$lockedEnrollment->id} has status '{$lockedEnrollment->enrollment_status->value}' ".
                    'and is not eligible to have a proposal applied.'
                );
            }

            // Map proposal status to enrollment terminal status.
            $newEnrollmentStatus = match ($lockedProposal->proposed_status) {
                ProposalStatus::Promoted => EnrollmentStatus::Promoted,
                ProposalStatus::Repeating => EnrollmentStatus::Repeating,
                ProposalStatus::Graduated => EnrollmentStatus::Graduated,
                ProposalStatus::Transferred => EnrollmentStatus::Transferred,
                ProposalStatus::Unresolved => null, // No enrollment change for unresolved.
            };

            if ($newEnrollmentStatus !== null) {
                $lockedEnrollment->enrollment_status = $newEnrollmentStatus->value;
                $lockedEnrollment->save();
            }

            // Create a new draft enrollment if a target class group was proposed.
            if ($lockedProposal->proposed_class_group_id === null) {
                return null;
            }

            // Re-validate the proposed class group's current state at apply-time.
            $targetGroup = ClassGroup::lockForUpdate()->findOrFail($lockedProposal->proposed_class_group_id);
            $targetSemesterId = (int) $targetGroup->institution_semester_id;

            // Guard a: class group must still be active.
            if (! $targetGroup->isActive()) {
                throw new EnrollmentMutationDeniedException(
                    "Proposed ClassGroup #{$targetGroup->id} is no longer active and cannot accept new enrollments."
                );
            }

            // Guard b: target semester must not be archived.
            $this->guardSemesterNotArchived($targetSemesterId);

            // Guard c: student must not already have a draft/active enrollment in target semester.
            $existingInTarget = StudentEnrollment::where('student_profile_id', $lockedEnrollment->student_profile_id)
                ->where('institution_semester_id', $targetSemesterId)
                ->whereIn('enrollment_status', [
                    EnrollmentStatus::Draft->value,
                    EnrollmentStatus::Active->value,
                ])
                ->lockForUpdate()
                ->exists();

            if ($existingInTarget) {
                throw new EnrollmentMutationDeniedException(
                    "Student #{$lockedEnrollment->student_profile_id} already has a draft or active enrollment ".
                    "in target semester #{$targetSemesterId}."
                );
            }

            // Guard d: capacity check on target class group.
            $this->guardCapacity($targetGroup, $capacityOverride, $capacityOverrideReason);

            $newEnrollment = new StudentEnrollment;
            $newEnrollment->student_profile_id = $lockedEnrollment->student_profile_id;
            $newEnrollment->institution_semester_id = $targetSemesterId;
            $newEnrollment->class_group_id = $targetGroup->id;
            $newEnrollment->enrollment_status = EnrollmentStatus::Draft->value;
            $newEnrollment->enrolled_on = now()->toDateString();
            $newEnrollment->save();

            return $newEnrollment;
        });
    }

    private function guardSemesterNotArchived(int $institutionSemesterId): void
    {
        $semClass = 'Modules\\AcademicCalendar\\Models\\InstitutionSemester';
        $semester = $semClass::find($institutionSemesterId);

        if ($semester === null) {
            throw new \InvalidArgumentException("InstitutionSemester #{$institutionSemesterId} not found.");
        }

        $statusClass = 'Modules\\AcademicCalendar\\Enums\\AcademicStatus';
        if ($semester->status->value === $statusClass::Archived->value) {
            throw new EnrollmentMutationDeniedException(
                "Target InstitutionSemester #{$institutionSemesterId} is archived and cannot accept new enrollments."
            );
        }
    }

    private function guardCapacity(ClassGroup $group, bool $override, ?string $reason): void
    {
        if ($group->capacity === null) {
            return;
        }

        $count = StudentEnrollment::where('class_group_id', $group->id)
            ->whereIn('enrollment_status', [
                EnrollmentStatus::Draft->value,
                EnrollmentStatus::Active->value,
            ])
            ->count();

        if ($count >= $group->capacity) {
            if (! $override || blank($reason)) {
                throw new CapacityExceededException(
                    "Target ClassGroup #{$group->id} is at capacity ({$group->capacity}). ".
                    'Set capacityOverride=true and supply a reason to proceed.'
                );
            }
        }
    }
}

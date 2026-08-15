<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Enums\EnrollmentStatus;
use Modules\AcademicManagement\Enums\ProposalReviewStatus;
use Modules\AcademicManagement\Enums\ProposalStatus;
use Modules\AcademicManagement\Exceptions\EnrollmentMutationDeniedException;
use Modules\AcademicManagement\Models\ClassGroup;
use Modules\AcademicManagement\Models\PromotionProposal;
use Modules\AcademicManagement\Models\StudentEnrollment;

/**
 * Create a promotion proposal for a student at semester end.
 *
 * Enforces:
 *  1. Source enrollment must be active or completed (not draft/terminal/suspended).
 *  2. A proposed_class_group (if supplied) must belong to a different (typically
 *     next-year) InstitutionSemester than the source enrollment's semester.
 *     Same-semester proposals are not blocked here — the UI/policy layer controls
 *     the valid target semester.
 *  3. Proposals never auto-apply; they require an explicit ApplyApprovedProposal call.
 */
final class CreatePromotionProposal
{
    public function __invoke(
        StudentEnrollment $sourceEnrollment,
        ProposalStatus $proposedStatus,
        ?ClassGroup $proposedClassGroup = null,
        ?string $reason = null,
    ): PromotionProposal {
        $allowed = [EnrollmentStatus::Active, EnrollmentStatus::Completed];

        if (! in_array($sourceEnrollment->enrollment_status, $allowed, true)) {
            throw new EnrollmentMutationDeniedException(
                "Enrollment #{$sourceEnrollment->id} has status '{$sourceEnrollment->enrollment_status->value}'. ".
                'Promotion proposals may only be created for active or completed enrollments.'
            );
        }

        $proposal = new PromotionProposal;
        $proposal->source_enrollment_id = $sourceEnrollment->id;
        $proposal->proposed_status = $proposedStatus->value;
        $proposal->proposed_class_group_id = $proposedClassGroup?->id;
        $proposal->review_status = ProposalReviewStatus::Pending->value;
        $proposal->reason = $reason;
        $proposal->save();

        return $proposal;
    }
}

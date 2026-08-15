<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Enums\ProposalReviewStatus;
use Modules\AcademicManagement\Exceptions\EnrollmentMutationDeniedException;
use Modules\AcademicManagement\Models\PromotionProposal;

/**
 * Approve or reject a pending promotion proposal.
 *
 * Enforces:
 *  1. Proposal must be in pending status.
 *  2. reviewedBy must be a non-empty actor reference.
 *
 * Approved proposals may then be applied via ApplyApprovedProposal.
 * Rejected proposals are terminal for that proposal; a new proposal may be created.
 */
final class ReviewPromotionProposal
{
    public function __invoke(
        PromotionProposal $proposal,
        ProposalReviewStatus $decision,
        string $reviewedBy,
        ?string $reason = null,
    ): PromotionProposal {
        if (! $proposal->isPending()) {
            throw new EnrollmentMutationDeniedException(
                "Proposal #{$proposal->id} has status '{$proposal->review_status->value}' and cannot be reviewed again."
            );
        }

        if ($decision === ProposalReviewStatus::Pending) {
            throw new \InvalidArgumentException('Review decision must be approved or rejected, not pending.');
        }

        if (blank($reviewedBy)) {
            throw new \InvalidArgumentException('reviewedBy must be a non-empty actor reference.');
        }

        $proposal->review_status = $decision->value;
        $proposal->reviewed_by = $reviewedBy;
        $proposal->reviewed_at = now();

        if ($reason !== null) {
            $proposal->reason = $reason;
        }

        $proposal->save();

        return $proposal;
    }
}

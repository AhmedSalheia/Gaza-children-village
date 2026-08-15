<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Enums;

/**
 * Review status for a PromotionProposal.
 *
 * pending   → Awaiting review by authorized staff.
 * approved  → Approved; may now be applied.
 * rejected  → Rejected; a new proposal may be created.
 */
enum ProposalReviewStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function canBeApplied(): bool
    {
        return $this === self::Approved;
    }
}

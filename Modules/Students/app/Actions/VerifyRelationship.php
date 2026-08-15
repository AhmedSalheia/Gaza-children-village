<?php

declare(strict_types=1);

namespace Modules\Students\Actions;

use Modules\Students\Enums\EvidenceStatus;
use Modules\Students\Enums\VerificationStatus;
use Modules\Students\Exceptions\RelationshipMutationDeniedException;
use Modules\Students\Models\GuardianStudentRelationship;

/**
 * Mark a guardian–student relationship as verified.
 *
 * Verification is a prerequisite for portal eligibility. It does NOT
 * automatically enable portal access; that requires ActivateRelationship.
 *
 * Only unverified or pending relationships may be verified. Rejected
 * relationships must be recreated.
 */
final class VerifyRelationship
{
    public function __invoke(
        GuardianStudentRelationship $relationship,
        string $actorReference,
        ?string $evidenceReference = null,
    ): GuardianStudentRelationship {
        if ($relationship->ends_on !== null && $relationship->ends_on->lt(now())) {
            throw new RelationshipMutationDeniedException(
                'Cannot verify an ended relationship.'
            );
        }

        if (! in_array(
            $relationship->verification_status,
            [VerificationStatus::Unverified, VerificationStatus::Pending],
            true
        )) {
            throw new RelationshipMutationDeniedException(
                "Cannot verify a relationship with status '{$relationship->verification_status->value}'."
            );
        }

        $relationship->verification_status = VerificationStatus::Verified->value;

        if ($evidenceReference !== null) {
            $relationship->evidence_reference = $evidenceReference;
            $relationship->evidence_status = EvidenceStatus::Verified->value;
        }

        $relationship->save();

        return $relationship;
    }
}

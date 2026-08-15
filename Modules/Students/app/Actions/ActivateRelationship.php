<?php

declare(strict_types=1);

namespace Modules\Students\Actions;

use Modules\Students\Enums\VerificationStatus;
use Modules\Students\Exceptions\RelationshipMutationDeniedException;
use Modules\Students\Models\GuardianStudentRelationship;

/**
 * Enable portal access for a guardian–student relationship.
 *
 * Requires: verification_status = 'verified' AND the relationship must be active.
 * Sets portal_eligible = true.
 *
 * Portal access is granted explicitly by authorized staff — it is never granted
 * automatically by verification alone.
 */
final class ActivateRelationship
{
    public function __invoke(
        GuardianStudentRelationship $relationship,
        string $actorReference,
    ): GuardianStudentRelationship {
        if ($relationship->ends_on !== null && $relationship->ends_on->lt(now())) {
            throw new RelationshipMutationDeniedException(
                'Cannot activate portal access on an ended relationship.'
            );
        }

        if ($relationship->verification_status !== VerificationStatus::Verified) {
            throw new RelationshipMutationDeniedException(
                'Relationship must be verified before portal access can be enabled.'
            );
        }

        $relationship->portal_eligible = true;
        $relationship->save();

        return $relationship;
    }
}

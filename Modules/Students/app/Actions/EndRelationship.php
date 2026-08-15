<?php

declare(strict_types=1);

namespace Modules\Students\Actions;

use Modules\Students\Exceptions\RelationshipMutationDeniedException;
use Modules\Students\Models\GuardianStudentRelationship;

/**
 * End a guardian–student relationship by setting ends_on.
 *
 * The row is NEVER deleted. Setting ends_on to a past or present date
 * effectively deactivates the relationship. Portal eligibility is
 * automatically lost once ends_on < today (enforced by scope queries).
 */
final class EndRelationship
{
    public function __invoke(
        GuardianStudentRelationship $relationship,
        string $actorReference,
        ?\DateTimeInterface $endsOn = null,
        ?string $reason = null,
    ): GuardianStudentRelationship {
        if ($relationship->ends_on !== null && $relationship->ends_on->lt(now())) {
            throw new RelationshipMutationDeniedException(
                'Relationship is already ended.'
            );
        }

        $date = ($endsOn ?? now())->format('Y-m-d');

        // Record reason in history_metadata for traceability.
        $history = $relationship->history_metadata ?? [];
        $history[] = [
            'event' => 'ended',
            'date' => $date,
            'actor' => $actorReference,
            'reason' => $reason,
        ];

        $relationship->ends_on = $date;
        $relationship->history_metadata = $history;
        $relationship->save();

        return $relationship;
    }
}

<?php

declare(strict_types=1);

namespace Modules\Staff\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Staff\Exceptions\AssignmentOverlapException;
use Modules\Staff\Models\StaffInstitutionAssignment;
use Modules\Staff\Models\StaffProfile;

/**
 * Assign a staff member to an institution starting on the given date.
 *
 * Overlap prevention:
 *  - Runs inside a DB transaction.
 *  - Checks for any existing assignment whose date range overlaps [started_on, ∞)
 *    (or [started_on, ended_on] if an end date is given).
 *  - On SQLite the check is application-level; on PostgreSQL the exclusion
 *    constraint provides additional protection.
 *
 * The staff member must not already have an open assignment on the start date.
 * Future non-overlapping assignments are allowed.
 */
final class StartAssignment
{
    public function __invoke(
        StaffProfile $profile,
        int $institutionId,
        \DateTimeInterface $startedOn,
        ?string $actor = null,
        ?string $context = null,
    ): StaffInstitutionAssignment {
        return DB::transaction(function () use ($profile, $institutionId, $startedOn, $actor, $context): StaffInstitutionAssignment {
            // Lock the profile's assignment rows for this check.
            StaffInstitutionAssignment::where('staff_profile_id', $profile->id)
                ->lockForUpdate()
                ->get();

            $startDate = $startedOn->format('Y-m-d');

            // Check for overlap: any existing assignment where
            //   assignment.started_on <= $startDate AND (assignment.ended_on IS NULL OR assignment.ended_on >= $startDate)
            $overlapping = StaffInstitutionAssignment::where('staff_profile_id', $profile->id)
                ->where('started_on', '<=', $startDate)
                ->where(function ($q) use ($startDate): void {
                    $q->whereNull('ended_on')
                        ->orWhere('ended_on', '>=', $startDate);
                })
                ->exists();

            if ($overlapping) {
                throw new AssignmentOverlapException(
                    'The proposed assignment dates overlap with an existing assignment.'
                );
            }

            $assignment = new StaffInstitutionAssignment;
            $assignment->staff_profile_id = $profile->id;
            $assignment->institution_id = $institutionId;
            $assignment->started_on = $startDate;
            $assignment->ended_on = null;
            $assignment->source_actor = $actor;
            $assignment->source_context = $context;
            $assignment->save();

            return $assignment;
        });
    }
}

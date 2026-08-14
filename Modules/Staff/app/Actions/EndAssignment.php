<?php

declare(strict_types=1);

namespace Modules\Staff\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Staff\Models\StaffInstitutionAssignment;
use Modules\Staff\Models\StaffProfile;

/**
 * Close the current open assignment for a staff member.
 *
 * Requires an active (open-ended) assignment. Requires an actor and reason.
 * The historical row is preserved; it is not deleted.
 */
final class EndAssignment
{
    public function __invoke(
        StaffProfile $profile,
        \DateTimeInterface $endedOn,
        string $closureReason,
        string $actor,
    ): void {
        DB::transaction(function () use ($profile, $endedOn, $closureReason, $actor): void {
            $current = StaffInstitutionAssignment::where('staff_profile_id', $profile->id)
                ->whereNull('ended_on')
                ->lockForUpdate()
                ->first();

            if ($current === null) {
                throw new \InvalidArgumentException(
                    'No active assignment found for this staff profile.'
                );
            }

            $endDate = $endedOn->format('Y-m-d');

            if ($endDate < $current->started_on->format('Y-m-d')) {
                throw new \InvalidArgumentException(
                    'End date must not be before the assignment start date.'
                );
            }

            $current->ended_on = $endDate;
            $current->closure_reason = $closureReason;
            $current->source_actor = $actor;
            $current->save();
        });
    }
}

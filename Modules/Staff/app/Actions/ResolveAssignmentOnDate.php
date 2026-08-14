<?php

declare(strict_types=1);

namespace Modules\Staff\Actions;

use Modules\Staff\Models\StaffInstitutionAssignment;
use Modules\Staff\Models\StaffProfile;

/**
 * Resolve which institution assignment was active for a staff member on a given date.
 *
 * Returns null if the staff member had no assignment on that date.
 */
final class ResolveAssignmentOnDate
{
    public function __invoke(
        StaffProfile $profile,
        \DateTimeInterface $date,
    ): ?StaffInstitutionAssignment {
        $dateStr = $date->format('Y-m-d');

        return StaffInstitutionAssignment::where('staff_profile_id', $profile->id)
            ->where('started_on', '<=', $dateStr)
            ->where(function ($q) use ($dateStr): void {
                $q->whereNull('ended_on')
                    ->orWhere('ended_on', '>=', $dateStr);
            })
            ->first();
    }
}

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

        // Use whereDate() so SQLite date columns (stored as 'Y-m-d H:i:s' by Carbon) compare
        // correctly against plain 'Y-m-d' strings.
        return StaffInstitutionAssignment::where('staff_profile_id', $profile->id)
            ->whereDate('started_on', '<=', $dateStr)
            ->where(function ($q) use ($dateStr): void {
                $q->whereNull('ended_on')
                    ->orWhereDate('ended_on', '>=', $dateStr);
            })
            ->first();
    }
}

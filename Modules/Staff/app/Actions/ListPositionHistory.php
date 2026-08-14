<?php

declare(strict_types=1);

namespace Modules\Staff\Actions;

use Illuminate\Support\Collection;
use Modules\Staff\Models\StaffPosition;
use Modules\Staff\Models\StaffProfile;

/**
 * Return the full position history for a staff member, ordered by started_on.
 *
 * Returns all positions including ended ones — historical records are always
 * readable (F16 requirement). Optionally filtered to a specific institution.
 *
 * @return Collection<int, StaffPosition>
 */
final class ListPositionHistory
{
    /**
     * @return Collection<int, StaffPosition>
     */
    public function __invoke(StaffProfile $profile, ?int $institutionId = null): Collection
    {
        return StaffPosition::where('staff_profile_id', $profile->id)
            ->when($institutionId !== null, fn ($q) => $q->where('institution_id', $institutionId))
            ->orderBy('started_on')
            ->orderBy('id')
            ->get();
    }
}

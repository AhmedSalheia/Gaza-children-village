<?php

declare(strict_types=1);

namespace Modules\Staff\Actions;

use Illuminate\Support\Collection;
use Modules\Staff\Models\StaffPosition;
use Modules\Staff\Models\StaffProfile;

/**
 * Resolve all active positions for a staff member on a given date.
 *
 * Optionally scoped to a specific institution and/or institution semester.
 *
 * Returns an empty Collection if the profile has no matching positions on the
 * given date. Never throws for a legitimate "no positions" state.
 *
 * @return Collection<int, StaffPosition>
 */
final class ResolveActivePositionsForDate
{
    /**
     * @return Collection<int, StaffPosition>
     */
    public function __invoke(
        StaffProfile $profile,
        \DateTimeInterface $date,
        ?int $institutionId = null,
        ?int $institutionSemesterId = null,
    ): Collection {
        return StaffPosition::where('staff_profile_id', $profile->id)
            ->effectiveOn($date)
            ->when($institutionId !== null, fn ($q) => $q->where('institution_id', $institutionId))
            ->when(
                $institutionSemesterId !== null,
                fn ($q) => $q->where('institution_semester_id', $institutionSemesterId),
            )
            ->get();
    }
}

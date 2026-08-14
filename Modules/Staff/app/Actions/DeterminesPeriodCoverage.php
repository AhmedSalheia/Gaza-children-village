<?php

declare(strict_types=1);

namespace Modules\Staff\Actions;

use Modules\Staff\Models\StaffPosition;
use Modules\Staff\Models\StaffPositionPeriod;

/**
 * Determine whether a staff position explicitly covers a requested operational period.
 *
 * Rules (F16):
 *  - If the position has no InstitutionSemester (non-academic), period checks
 *    are not applicable; returns false (call site should not be asking).
 *  - If the position has an InstitutionSemester and NO period links, it is
 *    unrestricted by period (returns true for any period in the semester).
 *    NOTE: this default applies only to positions whose spec does not mandate
 *    explicit links (e.g. principal, teacher). Secretaries are always period-
 *    restricted by the explicit links set via ReplacePositionScopes.
 *  - If the position has period links, returns true only when the requested
 *    period is among them.
 *
 * The distinction "always restricted" vs "unrestricted-by-default" is
 * encoded by the caller (F17/F19 policy) rather than this low-level query.
 * This action only checks whether an explicit link exists.
 */
final class DeterminesPeriodCoverage
{
    /**
     * Returns true if the position has an explicit link to the given period.
     * Returns false if the position has period links but this period is not among them.
     * Returns null if the position has no period links at all (caller decides default).
     */
    public function __invoke(StaffPosition $position, int $operationalPeriodId): ?bool
    {
        if ($position->institution_semester_id === null) {
            return false;
        }

        $hasPeriodLinks = StaffPositionPeriod::where('staff_position_id', $position->id)->exists();

        if (! $hasPeriodLinks) {
            return null; // unrestricted-by-default; caller applies position-type rules
        }

        return StaffPositionPeriod::where('staff_position_id', $position->id)
            ->where('operational_period_id', $operationalPeriodId)
            ->exists();
    }
}

<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Actions;

use Illuminate\Database\Eloquent\Collection;
use Modules\AcademicCalendar\Models\InstitutionSemester;
use Modules\AcademicCalendar\Models\OperationalPeriod;

/**
 * List operational periods for an institution semester.
 *
 * Returns periods ordered by sequence. Includes inactive periods by default
 * since they remain readable for historical reference.
 *
 * Authorization boundary: this action does not check actor permissions.
 * Future callers must pass through the F17/F19 policy kernel.
 */
final readonly class ListOperationalPeriods
{
    /**
     * @param  bool  $activeOnly  When true, exclude deactivated periods.
     * @return Collection<int, OperationalPeriod>
     */
    public function execute(InstitutionSemester $is, bool $activeOnly = false): Collection
    {
        $query = $is->operationalPeriods()->ordered();

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get();
    }
}

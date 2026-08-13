<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Actions;

use Modules\AcademicCalendar\Data\ChangeAcademicYearNamesData;
use Modules\AcademicCalendar\Models\AcademicYear;
use RuntimeException;

/**
 * Change the display names of an academic year.
 *
 * Name changes are permitted while the academic year is not archived.
 * Stable codes are immutable through ordinary application behavior.
 *
 * Authorization boundary: this action does not check actor permissions.
 * Future callers must pass through the F17/F19 policy kernel.
 */
final readonly class ChangeAcademicYearNames
{
    public function execute(AcademicYear $year, ChangeAcademicYearNamesData $data): AcademicYear
    {
        if ($year->status->isTerminal()) {
            throw new RuntimeException(
                "Cannot change names of archived academic year '{$year->code}'."
            );
        }

        $year->name_en = $data->nameEn;
        $year->name_ar = $data->nameAr;
        $year->save();

        return $year;
    }
}

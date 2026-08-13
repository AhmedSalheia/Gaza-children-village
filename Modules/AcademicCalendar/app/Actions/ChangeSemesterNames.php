<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Actions;

use Modules\AcademicCalendar\Data\ChangeSemesterNamesData;
use Modules\AcademicCalendar\Models\Semester;
use RuntimeException;

/**
 * Change the display names of a semester.
 *
 * Permitted while the semester itself is not archived and its parent academic
 * year is not archived. Stable codes are immutable through ordinary behavior.
 *
 * Authorization boundary: this action does not check actor permissions.
 * Future callers must pass through the F17/F19 policy kernel.
 */
final readonly class ChangeSemesterNames
{
    public function execute(Semester $semester, ChangeSemesterNamesData $data): Semester
    {
        if ($semester->status->isTerminal()) {
            throw new RuntimeException(
                "Cannot change names of archived semester '{$semester->code}'."
            );
        }

        // Load parent year status to enforce the archived-year restriction.
        $year = $semester->academicYear;

        if ($year->status->isTerminal()) {
            throw new RuntimeException(
                "Cannot change semester names while the parent academic year '{$year->code}' is archived."
            );
        }

        $semester->name_en = $data->nameEn;
        $semester->name_ar = $data->nameAr;
        $semester->save();

        return $semester;
    }
}

<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Actions;

use Illuminate\Support\Facades\DB;
use Modules\AcademicCalendar\Data\ChangeAcademicYearDatesData;
use Modules\AcademicCalendar\Models\AcademicYear;
use Modules\AcademicCalendar\Models\Semester;
use RuntimeException;

/**
 * Change the dates of an academic year.
 *
 * Only permitted while the academic year is in Draft status. Date corrections
 * for opened or closed records require a future controlled correction workflow
 * and are outside F07 scope.
 *
 * Also validates that all existing semesters remain contained within the
 * new dates. Callers must resolve any conflicting semesters before narrowing
 * year dates.
 *
 * Runs in a DB transaction to keep the year and its semesters consistent.
 *
 * Authorization boundary: this action does not check actor permissions.
 * Future callers must pass through the F17/F19 policy kernel.
 */
final readonly class ChangeAcademicYearDates
{
    public function execute(AcademicYear $year, ChangeAcademicYearDatesData $data): AcademicYear
    {
        if (! $year->status->allowsDateChange()) {
            throw new RuntimeException(
                "Dates can only be changed while the academic year is draft. '{$year->code}' is {$year->status->value}."
            );
        }

        if ($data->startsOn >= $data->endsOn) {
            throw new RuntimeException(
                'Academic year start date must precede end date.'
            );
        }

        return DB::transaction(function () use ($year, $data): AcademicYear {
            // Ensure existing semesters still fit within the new year dates.
            $incompatible = Semester::where('academic_year_id', $year->id)
                ->where(function ($q) use ($data): void {
                    $q->where('starts_on', '<', $data->startsOn)
                        ->orWhere('ends_on', '>', $data->endsOn);
                })
                ->exists();

            if ($incompatible) {
                throw new RuntimeException(
                    'Cannot change year dates: one or more semesters fall outside the new date range.'
                );
            }

            $year->starts_on = $data->startsOn;
            $year->ends_on = $data->endsOn;
            $year->save();

            return $year;
        });
    }
}

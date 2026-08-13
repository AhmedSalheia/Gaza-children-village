<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Actions;

use Illuminate\Support\Facades\DB;
use Modules\AcademicCalendar\Data\ChangeSemesterDatesData;
use Modules\AcademicCalendar\Models\Semester;
use RuntimeException;

/**
 * Change the dates and sequence of a semester.
 *
 * Only permitted while the semester is in Draft status. Corrections after
 * opening require a future controlled correction workflow outside F07 scope.
 *
 * Also validates:
 *   - starts_on < ends_on
 *   - Dates fall within the parent academic year dates
 *   - No overlap with sibling semesters (excluding self)
 *   - Sequence is positive and unique within the year (excluding self)
 *   - Parent year is not archived
 *
 * Runs in a DB transaction to keep overlap and uniqueness checks atomic.
 *
 * Authorization boundary: this action does not check actor permissions.
 * Future callers must pass through the F17/F19 policy kernel.
 */
final readonly class ChangeSemesterDates
{
    public function execute(Semester $semester, ChangeSemesterDatesData $data): Semester
    {
        if (! $semester->status->allowsDateChange()) {
            throw new RuntimeException(
                "Dates and sequence can only be changed while the semester is draft. '{$semester->code}' is {$semester->status->value}."
            );
        }

        $year = $semester->academicYear;

        if ($year->status->isTerminal()) {
            throw new RuntimeException(
                "Cannot change semester dates while the parent academic year '{$year->code}' is archived."
            );
        }

        if ($data->sequence <= 0) {
            throw new RuntimeException(
                "Semester sequence must be a positive integer; got {$data->sequence}."
            );
        }

        if ($data->startsOn >= $data->endsOn) {
            throw new RuntimeException(
                'Semester start date must precede end date.'
            );
        }

        $yearStart = $year->starts_on->format('Y-m-d');
        $yearEnd = $year->ends_on->format('Y-m-d');

        if ($data->startsOn < $yearStart || $data->endsOn > $yearEnd) {
            throw new RuntimeException(
                "Semester dates must fall within the academic year dates ({$yearStart} – {$yearEnd})."
            );
        }

        return DB::transaction(function () use ($semester, $year, $data): Semester {
            $sequenceConflict = Semester::where('academic_year_id', $year->id)
                ->where('id', '!=', $semester->id)
                ->where('sequence', $data->sequence)
                ->exists();

            if ($sequenceConflict) {
                throw new RuntimeException(
                    "Sequence {$data->sequence} is already used by another semester in this academic year."
                );
            }

            $overlapping = Semester::where('academic_year_id', $year->id)
                ->where('id', '!=', $semester->id)
                ->where('starts_on', '<', $data->endsOn)
                ->where('ends_on', '>', $data->startsOn)
                ->exists();

            if ($overlapping) {
                throw new RuntimeException(
                    "New dates overlap with an existing semester in academic year '{$year->code}'."
                );
            }

            $semester->sequence = $data->sequence;
            $semester->starts_on = $data->startsOn;
            $semester->ends_on = $data->endsOn;
            $semester->save();

            return $semester;
        });
    }
}

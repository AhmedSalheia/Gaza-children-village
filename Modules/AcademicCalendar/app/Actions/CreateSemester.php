<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Actions;

use Illuminate\Support\Facades\DB;
use Modules\AcademicCalendar\Data\CreateSemesterData;
use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\AcademicYear;
use Modules\AcademicCalendar\Models\Semester;
use RuntimeException;

/**
 * Create a new semester within an academic year.
 *
 * Any positive number of semesters is allowed. There is no two-semester
 * assumption. Summer semesters and exceptional semesters are representable.
 *
 * Validates:
 *   - Academic year is not Archived.
 *   - Sequence is a positive integer.
 *   - starts_on < ends_on.
 *   - Semester dates fall within the academic year dates.
 *   - No overlap with existing semesters in the same year.
 *   - Code is unique within the academic year.
 *   - Sequence is unique within the academic year.
 *
 * New semesters start in Draft status.
 *
 * Runs in a DB transaction to keep the overlap and uniqueness checks atomic.
 *
 * Authorization boundary: this action does not check actor permissions.
 * Future callers must pass through the F17/F19 policy kernel.
 */
final readonly class CreateSemester
{
    public function execute(AcademicYear $year, CreateSemesterData $data): Semester
    {
        if ($year->status->isTerminal()) {
            throw new RuntimeException(
                "Cannot add a semester to archived academic year '{$year->code}'."
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

        return DB::transaction(function () use ($year, $data): Semester {
            if (Semester::where('academic_year_id', $year->id)
                ->where('code', $data->code)
                ->exists()) {
                throw new RuntimeException(
                    "Semester code '{$data->code}' already exists within academic year '{$year->code}'."
                );
            }

            if (Semester::where('academic_year_id', $year->id)
                ->where('sequence', $data->sequence)
                ->exists()) {
                throw new RuntimeException(
                    "Semester sequence {$data->sequence} already exists within academic year '{$year->code}'."
                );
            }

            $overlapping = Semester::where('academic_year_id', $year->id)
                ->where('starts_on', '<', $data->endsOn)
                ->where('ends_on', '>', $data->startsOn)
                ->exists();

            if ($overlapping) {
                throw new RuntimeException(
                    "Semester dates overlap with an existing semester in academic year '{$year->code}'."
                );
            }

            $semester = new Semester;
            $semester->academic_year_id = $year->id;
            $semester->code = $data->code;
            $semester->name_en = $data->nameEn;
            $semester->name_ar = $data->nameAr;
            $semester->sequence = $data->sequence;
            $semester->starts_on = $data->startsOn;
            $semester->ends_on = $data->endsOn;
            $semester->status = AcademicStatus::Draft;
            $semester->save();

            return $semester;
        });
    }
}

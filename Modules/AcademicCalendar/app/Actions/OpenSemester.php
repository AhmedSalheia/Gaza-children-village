<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Actions;

use Illuminate\Support\Facades\DB;
use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\Semester;
use RuntimeException;

/**
 * Transition a semester from Draft to Open.
 *
 * Validates:
 *   - Semester is currently Draft.
 *   - Parent academic year is Open.
 *   - No other semester in the same academic year is already Open.
 *
 * Opening a year does not automatically open its semesters; each semester
 * must be opened explicitly.
 *
 * Runs in a DB transaction with the one-open-semester check to prevent races.
 *
 * Authorization boundary: this action does not check actor permissions.
 * Future callers must pass through the F17/F19 policy kernel.
 */
final readonly class OpenSemester
{
    public function execute(Semester $semester): Semester
    {
        if ($semester->status !== AcademicStatus::Draft) {
            throw new RuntimeException(
                "Only a draft semester can be opened. '{$semester->code}' is {$semester->status->value}."
            );
        }

        $year = $semester->academicYear;

        if ($year->status !== AcademicStatus::Open) {
            throw new RuntimeException(
                "Semester '{$semester->code}' can only be opened while its academic year is open. Year '{$year->code}' is {$year->status->value}."
            );
        }

        return DB::transaction(function () use ($semester, $year): Semester {
            $alreadyOpen = Semester::where('academic_year_id', $year->id)
                ->where('status', AcademicStatus::Open->value)
                ->exists();

            if ($alreadyOpen) {
                throw new RuntimeException(
                    "Another semester is already open in academic year '{$year->code}'. Close it before opening a new one."
                );
            }

            $semester->status = AcademicStatus::Open;
            $semester->save();

            return $semester;
        });
    }
}

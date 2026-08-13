<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Actions;

use Illuminate\Support\Facades\DB;
use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\AcademicYear;
use Modules\AcademicCalendar\Models\Semester;
use RuntimeException;

/**
 * Transition an academic year from Open to Closed.
 *
 * Validates:
 *   - Year is currently Open.
 *   - All semesters of the year are Closed or Archived (none in Draft or Open).
 *
 * Closing a year does not cascade to semesters. No child records are deleted
 * or modified by this action.
 *
 * Runs in a DB transaction to keep the semester check and the status update
 * atomic.
 *
 * Authorization boundary: this action does not check actor permissions.
 * Future callers must pass through the F17/F19 policy kernel.
 */
final readonly class CloseAcademicYear
{
    public function execute(AcademicYear $year): AcademicYear
    {
        if ($year->status !== AcademicStatus::Open) {
            throw new RuntimeException(
                "Only an open academic year can be closed. '{$year->code}' is {$year->status->value}."
            );
        }

        return DB::transaction(function () use ($year): AcademicYear {
            $blockingSemesters = Semester::where('academic_year_id', $year->id)
                ->whereIn('status', [AcademicStatus::Draft->value, AcademicStatus::Open->value])
                ->exists();

            if ($blockingSemesters) {
                throw new RuntimeException(
                    "Cannot close academic year '{$year->code}': all semesters must be closed or archived first."
                );
            }

            $year->status = AcademicStatus::Closed;
            $year->save();

            return $year;
        });
    }
}

<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Actions;

use Illuminate\Support\Facades\DB;
use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\AcademicYear;
use Modules\AcademicCalendar\Models\Semester;
use RuntimeException;

/**
 * Transition an academic year from Draft to Open.
 *
 * Validates:
 *   - Year is currently Draft.
 *   - Year has at least one semester (any status).
 *   - No other academic year is already Open for the same organization.
 *
 * Opening a year does not automatically open its semesters. Semesters must
 * be opened individually through OpenSemester after the year is open.
 *
 * Runs in a DB transaction with the one-open-year check to prevent races.
 * (SQLite acquires a database-level write lock for the transaction duration.)
 *
 * Authorization boundary: this action does not check actor permissions.
 * Future callers must pass through the F17/F19 policy kernel.
 */
final readonly class OpenAcademicYear
{
    public function execute(AcademicYear $year): AcademicYear
    {
        if ($year->status !== AcademicStatus::Draft) {
            throw new RuntimeException(
                "Only a draft academic year can be opened. '{$year->code}' is {$year->status->value}."
            );
        }

        if (! Semester::where('academic_year_id', $year->id)->exists()) {
            throw new RuntimeException(
                "Academic year '{$year->code}' must contain at least one semester before opening."
            );
        }

        return DB::transaction(function () use ($year): AcademicYear {
            $alreadyOpen = AcademicYear::where('organization_id', $year->organization_id)
                ->where('status', AcademicStatus::Open->value)
                ->exists();

            if ($alreadyOpen) {
                throw new RuntimeException(
                    'Another academic year is already open for this organization. Close it before opening a new one.'
                );
            }

            $year->status = AcademicStatus::Open;
            $year->save();

            return $year;
        });
    }
}

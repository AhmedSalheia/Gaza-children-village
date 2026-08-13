<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Actions;

use Illuminate\Support\Facades\DB;
use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\Semester;
use RuntimeException;

/**
 * Transition a semester from Closed back to Open.
 *
 * Requires a non-empty reason for audit purposes. F18 will persist the
 * actor-aware audit history; F07 accepts and passes the reason through.
 *
 * Validates:
 *   - Semester is currently Closed.
 *   - A non-empty reason is provided.
 *   - Parent academic year is Open.
 *   - No other semester in the same year is already Open.
 *
 * Runs in a DB transaction with the one-open-semester check to prevent races.
 *
 * Authorization boundary: this action does not check actor permissions.
 * Future callers must pass through the F17/F19 policy kernel.
 */
final readonly class ReopenSemester
{
    public function execute(Semester $semester, string $reason): Semester
    {
        if ($semester->status !== AcademicStatus::Closed) {
            throw new RuntimeException(
                "Only a closed semester can be reopened. '{$semester->code}' is {$semester->status->value}."
            );
        }

        if (trim($reason) === '') {
            throw new RuntimeException(
                "A non-empty reason is required to reopen semester '{$semester->code}'."
            );
        }

        // Fresh query to bypass any cached relationship from prior actions in the
        // same request cycle (e.g. OpenSemester cached the year as Open).
        $year = $semester->academicYear()->first();

        if ($year->status !== AcademicStatus::Open) {
            throw new RuntimeException(
                "Semester '{$semester->code}' can only be reopened while its academic year is open. Year '{$year->code}' is {$year->status->value}."
            );
        }

        return DB::transaction(function () use ($semester, $year): Semester {
            $alreadyOpen = Semester::where('academic_year_id', $year->id)
                ->where('status', AcademicStatus::Open->value)
                ->exists();

            if ($alreadyOpen) {
                throw new RuntimeException(
                    "Another semester is already open in academic year '{$year->code}'. Close it before reopening this one."
                );
            }

            $semester->status = AcademicStatus::Open;
            $semester->save();

            return $semester;
        });
    }
}

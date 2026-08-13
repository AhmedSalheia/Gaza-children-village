<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Actions;

use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\Semester;
use RuntimeException;

/**
 * Transition a semester from Closed to Archived.
 *
 * Archived is a terminal status for ordinary actions. Archived semesters
 * remain readable but cannot be mutated or transitioned further.
 *
 * An archived academic year prevents ordinary mutation of its semesters.
 * This action enforces that guard: archiving is blocked if the parent year
 * is itself archived (because archiving constitutes a status mutation).
 *
 * Wait — archiving a semester when the year is archived is the edge case:
 * the year being archived already prevents name/date changes. For archiving
 * the semester itself: the semester can only be archived from Closed, and
 * if the year is archived the semester is blocked from ordinary mutation.
 * We therefore reject this case for consistency.
 *
 * Authorization boundary: this action does not check actor permissions.
 * Future callers must pass through the F17/F19 policy kernel.
 */
final readonly class ArchiveSemester
{
    public function execute(Semester $semester): Semester
    {
        if ($semester->status !== AcademicStatus::Closed) {
            throw new RuntimeException(
                "Only a closed semester can be archived. '{$semester->code}' is {$semester->status->value}."
            );
        }

        $year = $semester->academicYear;

        if ($year->status->isTerminal()) {
            throw new RuntimeException(
                "Cannot archive semester '{$semester->code}': the parent academic year '{$year->code}' is archived and prevents ordinary mutation."
            );
        }

        $semester->status = AcademicStatus::Archived;
        $semester->save();

        return $semester;
    }
}

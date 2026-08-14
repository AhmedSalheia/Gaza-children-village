<?php

declare(strict_types=1);

namespace Modules\Staff\Actions;

use Modules\Staff\Exceptions\PositionMutationDeniedException;
use Modules\Staff\Models\StaffPosition;

/**
 * End an active staff position.
 *
 * Rules (F16):
 *  - The position must currently be open (ended_on is null).
 *  - Closed or archived institution semesters reject ordinary ending (same rule
 *    as AssignPosition).
 *  - A reason and actor are required.
 *  - Historical positions remain readable after ending.
 */
final class EndPosition
{
    public function __invoke(
        StaffPosition $position,
        \DateTimeInterface $endedOn,
        string $closureReason,
        string $endedBy,
        string $closureSource = 'manual',
    ): StaffPosition {
        if (! $position->isOpen()) {
            throw new \InvalidArgumentException('This position has already been ended.');
        }

        $endStr = $endedOn->format('Y-m-d');

        if ($endStr < $position->started_on->format('Y-m-d')) {
            throw new \InvalidArgumentException('ended_on cannot be before started_on.');
        }

        if ($position->institution_semester_id !== null) {
            $this->guardSemesterMutable($position->institution_semester_id);
        }

        $position->ended_on = $endStr;
        $position->closure_reason = $closureReason;
        $position->ended_by = $endedBy;
        $position->closure_source = $closureSource;
        $position->save();

        return $position;
    }

    private function guardSemesterMutable(int $institutionSemesterId): void
    {
        $semesterClass = 'Modules\\AcademicCalendar\\Models\\InstitutionSemester';
        $semester = $semesterClass::find($institutionSemesterId);

        if ($semester === null) {
            return; // position exists without a valid semester — allow ending
        }

        $statusClass = 'Modules\\AcademicCalendar\\Enums\\AcademicStatus';

        if (in_array($semester->status->value, [
            $statusClass::Closed->value,
            $statusClass::Archived->value,
        ], true)) {
            throw new PositionMutationDeniedException(
                'Cannot end a position when the institution semester is closed or archived.'
            );
        }
    }
}

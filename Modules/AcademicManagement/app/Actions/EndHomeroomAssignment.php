<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Illuminate\Support\Facades\DB;
use Modules\AcademicManagement\Enums\AssignmentStatus;
use Modules\AcademicManagement\Exceptions\AssignmentException;
use Modules\AcademicManagement\Models\HomeroomAssignment;

/**
 * End an active homeroom assignment.
 *
 * Rules enforced inside a serialised transaction:
 *  1. The assignment must be in Active status (row-locked to prevent End-vs-Replace race).
 *  2. The assignment's institution semester must be open or draft (mutable).
 *     Closed/archived semesters deny all mutations — historical records are read-only.
 *  3. The ends_on date must be on or after the assignment's starts_on date.
 */
final class EndHomeroomAssignment
{
    private const MUTABLE_STATUSES = ['open', 'draft'];

    public function __invoke(
        HomeroomAssignment $assignment,
        \DateTimeInterface $endsOn,
        string $reason,
        ?string $actorRef = null,
    ): HomeroomAssignment {
        return DB::transaction(function () use ($assignment, $endsOn, $reason): HomeroomAssignment {
            $locked = HomeroomAssignment::lockForUpdate()->findOrFail($assignment->id);

            $lockedStatus = $locked->status instanceof AssignmentStatus
                ? $locked->status
                : AssignmentStatus::from((string) $locked->status);

            if ($lockedStatus !== AssignmentStatus::Active) {
                throw new AssignmentException(
                    "HomeroomAssignment #{$assignment->id} is already terminal and cannot be ended."
                );
            }

            // Semester mutability guard — mirrors the Create action's rule.
            $this->guardSemesterMutable((int) $locked->institution_semester_id);

            $startsOn   = $locked->starts_on->format('Y-m-d');
            $endsOnDate = $endsOn->format('Y-m-d');

            if ($endsOnDate < $startsOn) {
                throw new AssignmentException(
                    "End date ({$endsOnDate}) is before the assignment's starts_on ({$startsOn}). ".
                    'The effective interval must be valid.'
                );
            }

            $locked->ends_on     = $endsOnDate;
            $locked->ends_reason = $reason;
            $locked->status      = AssignmentStatus::Ended->value;
            $locked->save();

            return $locked;
        });
    }

    private function guardSemesterMutable(int $semesterId): void
    {
        $semClass = 'Modules\\AcademicCalendar\\Models\\InstitutionSemester';
        $semester = $semClass::find($semesterId);

        if ($semester === null) {
            throw new \InvalidArgumentException("InstitutionSemester #{$semesterId} not found.");
        }

        $status = $semester->status instanceof \BackedEnum
            ? $semester->status->value
            : (string) $semester->status;

        if (! in_array($status, self::MUTABLE_STATUSES, true)) {
            throw new AssignmentException(
                "InstitutionSemester #{$semesterId} has status '{$status}' and does not ".
                'accept mutations. End/replace is only permitted in open or draft semesters.'
            );
        }
    }
}

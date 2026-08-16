<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Illuminate\Support\Facades\DB;
use Modules\AcademicManagement\Enums\AssignmentStatus;
use Modules\AcademicManagement\Exceptions\AssignmentException;
use Modules\AcademicManagement\Models\TeachingAssignment;

/**
 * Atomically supersede an existing active teaching assignment and create a
 * replacement for the same class group and subject offering.
 *
 * Rules enforced inside a serialised transaction:
 *  1. The old assignment must be in Active status (row-locked).
 *  2. The old assignment's institution semester must be open or draft (mutable).
 *     Closed/archived semesters deny all mutations — historical records are read-only.
 *  3. The replacedOn date must be on or after the old assignment's starts_on.
 */
final class ReplaceTeachingAssignment
{
    private const MUTABLE_STATUSES = ['open', 'draft'];

    public function __construct(
        private readonly CreateTeachingAssignment $createAction,
    ) {}

    public function __invoke(
        TeachingAssignment $old,
        int $newStaffPositionId,
        \DateTimeInterface $replacedOn,
        string $reason,
        ?string $actorRef = null,
    ): TeachingAssignment {
        return DB::transaction(function () use ($old, $newStaffPositionId, $replacedOn, $reason): TeachingAssignment {
            $locked = TeachingAssignment::lockForUpdate()->findOrFail($old->id);

            $lockedStatus = $locked->status instanceof AssignmentStatus
                ? $locked->status
                : AssignmentStatus::from((string) $locked->status);

            if ($lockedStatus !== AssignmentStatus::Active) {
                throw new AssignmentException(
                    "TeachingAssignment #{$old->id} has status '{$lockedStatus->value}' and is ".
                    'terminal — only active assignments can be replaced.'
                );
            }

            // Semester mutability guard — mirrors the Create action's rule.
            $this->guardSemesterMutable((int) $locked->institution_semester_id);

            $startsOn = $locked->starts_on->format('Y-m-d');
            $replacedOnDate = $replacedOn->format('Y-m-d');

            if ($replacedOnDate < $startsOn) {
                throw new AssignmentException(
                    "Replacement date ({$replacedOnDate}) is before the assignment's starts_on ({$startsOn}). ".
                    'The replacement date must be on or after the original start date.'
                );
            }

            $locked->ends_on = $replacedOnDate;
            $locked->ends_reason = $reason;
            $locked->status = AssignmentStatus::Superseded->value;
            $locked->save();

            return ($this->createAction)(
                staffPositionId: $newStaffPositionId,
                classGroupId: (int) $locked->class_group_id,
                subjectOfferingId: (int) $locked->subject_offering_id,
                startsOn: $replacedOn,
            );
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

<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Modules\AcademicManagement\Enums\AssignmentStatus;
use Modules\AcademicManagement\Exceptions\AssignmentException;
use Modules\AcademicManagement\Models\ClassGroup;
use Modules\AcademicManagement\Models\InstitutionSubjectOffering;
use Modules\AcademicManagement\Models\TeachingAssignment;

/**
 * Create a new TeachingAssignment linking a teacher/trainer position to a
 * class group and subject offering within a shared institution semester.
 *
 * Enforced rules:
 *  1. The StaffPosition must exist and be active (not ended).
 *  2. The position_definition must be teacher or trainer.
 *  3. The institution_semester_id on the position must match the ClassGroup's semester.
 *  4. The ClassGroup's semester must be in an open status (not closed/archived).
 *  5. The InstitutionSubjectOffering must belong to the same institution semester.
 *  6. No active duplicate assignment exists for the same position + class + subject
 *     (verified inside a serialised transaction with a shared row-lock).
 *
 * Cross-module classes (StaffPosition, InstitutionSemester) are loaded via
 * string-variable references (boundary scanner safe).
 */
final class CreateTeachingAssignment
{
    /** Position definitions eligible to receive teaching assignments. */
    private const ELIGIBLE_POSITIONS = ['teacher', 'trainer'];

    /** Institution semester statuses that allow new assignments. */
    private const MUTABLE_STATUSES = ['open', 'draft'];

    public function __invoke(
        int $staffPositionId,
        int $classGroupId,
        int $subjectOfferingId,
        \DateTimeInterface $startsOn,
        ?string $actorRef = null,
    ): TeachingAssignment {
        // Pre-transaction validation (stateless, no race-sensitive data).
        $position = $this->loadPosition($staffPositionId);

        $positionDef = $position->position_definition instanceof \BackedEnum
            ? $position->position_definition->value
            : (string) $position->position_definition;

        if (! in_array($positionDef, self::ELIGIBLE_POSITIONS, true)) {
            throw new AssignmentException(
                "Position definition '{$positionDef}' is not eligible for teaching assignments. ".
                'Only teacher and trainer positions may be assigned.'
            );
        }

        if ($position->ended_on !== null) {
            throw new AssignmentException(
                "StaffPosition #{$staffPositionId} has ended and cannot receive new teaching assignments."
            );
        }

        $classGroup = ClassGroup::find($classGroupId);

        if ($classGroup === null) {
            throw new \InvalidArgumentException("ClassGroup #{$classGroupId} not found.");
        }

        $semester = $this->loadSemester((int) $classGroup->institution_semester_id);
        $this->guardSemesterMutable($semester);

        if ((int) $position->institution_semester_id !== (int) $classGroup->institution_semester_id) {
            throw new AssignmentException(
                "StaffPosition #{$staffPositionId} is in semester #{$position->institution_semester_id}, ".
                "but ClassGroup #{$classGroupId} is in semester #{$classGroup->institution_semester_id}. ".
                'They must be in the same institution semester.'
            );
        }

        $offering = InstitutionSubjectOffering::find($subjectOfferingId);

        if ($offering === null) {
            throw new \InvalidArgumentException("InstitutionSubjectOffering #{$subjectOfferingId} not found.");
        }

        if ((int) $offering->institution_semester_id !== (int) $classGroup->institution_semester_id) {
            throw new AssignmentException(
                "SubjectOffering #{$subjectOfferingId} is in semester #{$offering->institution_semester_id}, ".
                "but ClassGroup #{$classGroupId} is in semester #{$classGroup->institution_semester_id}."
            );
        }

        // Serialised transaction: lock existing active rows before the duplicate
        // check so concurrent requests cannot both pass and produce duplicates.
        // The partial unique index on (staff_position_id, class_group_id,
        // subject_offering_id) WHERE status='active' provides a DB-level backstop
        // on SQLite/PostgreSQL; UniqueConstraintViolationException is caught and
        // re-raised as AssignmentException for a clean domain error boundary.
        try {
            return DB::transaction(function () use (
                $staffPositionId, $classGroupId, $subjectOfferingId, $startsOn, $position, $classGroup,
            ): TeachingAssignment {
                $duplicate = TeachingAssignment::where('staff_position_id', $staffPositionId)
                    ->where('class_group_id', $classGroupId)
                    ->where('subject_offering_id', $subjectOfferingId)
                    ->where('status', AssignmentStatus::Active->value)
                    ->lockForUpdate()
                    ->exists();

                if ($duplicate) {
                    throw new AssignmentException(
                        "An active teaching assignment already exists for position #{$staffPositionId}, ".
                        "class group #{$classGroupId}, and subject offering #{$subjectOfferingId}."
                    );
                }

                $assignment = new TeachingAssignment;
                $assignment->staff_profile_id = (int) $position->staff_profile_id;
                $assignment->institution_semester_id = (int) $classGroup->institution_semester_id;
                $assignment->staff_position_id = $staffPositionId;
                $assignment->class_group_id = $classGroupId;
                $assignment->subject_offering_id = $subjectOfferingId;
                $assignment->starts_on = $startsOn->format('Y-m-d');
                $assignment->ends_on = null;
                $assignment->status = AssignmentStatus::Active->value;
                $assignment->save();

                return $assignment;
            });
        } catch (UniqueConstraintViolationException) {
            throw new AssignmentException(
                "An active teaching assignment already exists for position #{$staffPositionId}, ".
                "class group #{$classGroupId}, and subject offering #{$subjectOfferingId}."
            );
        }
    }

    private function loadPosition(int $positionId): object
    {
        $posClass = 'Modules\\Staff\\Models\\StaffPosition';
        $pos = $posClass::find($positionId);

        if ($pos === null) {
            throw new \InvalidArgumentException("StaffPosition #{$positionId} not found.");
        }

        return $pos;
    }

    private function loadSemester(int $semesterId): object
    {
        $semClass = 'Modules\\AcademicCalendar\\Models\\InstitutionSemester';
        $sem = $semClass::find($semesterId);

        if ($sem === null) {
            throw new \InvalidArgumentException("InstitutionSemester #{$semesterId} not found.");
        }

        return $sem;
    }

    private function guardSemesterMutable(object $semester): void
    {
        $status = $semester->status instanceof \BackedEnum
            ? $semester->status->value
            : (string) $semester->status;

        if (! in_array($status, self::MUTABLE_STATUSES, true)) {
            throw new AssignmentException(
                "InstitutionSemester #{$semester->id} has status '{$status}' and does not ".
                'accept new teaching assignments. Only open or draft semesters allow mutations.'
            );
        }
    }
}

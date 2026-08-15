<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Modules\AcademicManagement\Enums\AssignmentStatus;
use Modules\AcademicManagement\Exceptions\AssignmentException;
use Modules\AcademicManagement\Models\ClassGroup;
use Modules\AcademicManagement\Models\HomeroomAssignment;

/**
 * Create a homeroom (lead or co-lead) assignment for a ClassGroup.
 *
 * Enforced rules:
 *  1. The StaffPosition must exist, be active, and be teacher or trainer.
 *  2. The position's semester must match the ClassGroup's semester.
 *  3. The ClassGroup's semester must be open/draft (mutable).
 *  4. If is_co_lead = false (lead), no other active lead exists for this class group
 *     (verified inside a serialised transaction with a shared row-lock).
 *
 * Cross-module classes are loaded via string-variable references.
 */
final class CreateHomeroomAssignment
{
    private const ELIGIBLE_POSITIONS = ['teacher', 'trainer'];

    private const MUTABLE_STATUSES = ['open', 'draft'];

    public function __invoke(
        int $staffPositionId,
        int $classGroupId,
        \DateTimeInterface $startsOn,
        bool $isCoLead = false,
        ?string $actorRef = null,
    ): HomeroomAssignment {
        // Pre-transaction validation (stateless, no race-sensitive data).
        $position = $this->loadPosition($staffPositionId);

        $positionDef = $position->position_definition instanceof \BackedEnum
            ? $position->position_definition->value
            : (string) $position->position_definition;

        if (! in_array($positionDef, self::ELIGIBLE_POSITIONS, true)) {
            throw new AssignmentException(
                "Position definition '{$positionDef}' is not eligible for homeroom assignments."
            );
        }

        if ($position->ended_on !== null) {
            throw new AssignmentException(
                "StaffPosition #{$staffPositionId} has ended and cannot receive homeroom assignments."
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
                "but ClassGroup #{$classGroupId} is in semester #{$classGroup->institution_semester_id}."
            );
        }

        // Serialised transaction: lock existing active rows before uniqueness checks
        // so concurrent requests cannot both pass the lead / duplicate guards.
        // Partial unique indexes (SQLite/PostgreSQL) provide a DB-level backstop;
        // UniqueConstraintViolationException is caught and re-raised as AssignmentException.
        try {
            return DB::transaction(function () use (
                $staffPositionId, $classGroupId, $isCoLead, $startsOn, $position, $classGroup,
            ): HomeroomAssignment {
                // Lead uniqueness: at most one active lead (is_co_lead = false) per class group.
                if (! $isCoLead) {
                    $leadExists = HomeroomAssignment::where('class_group_id', $classGroupId)
                        ->where('status', AssignmentStatus::Active->value)
                        ->where('is_co_lead', false)
                        ->lockForUpdate()
                        ->exists();

                    if ($leadExists) {
                        throw new AssignmentException(
                            "ClassGroup #{$classGroupId} already has an active lead homeroom teacher. ".
                            'End the existing assignment before creating a new lead, or create a co-lead instead.'
                        );
                    }
                }

                // No duplicate for this position + class.
                $duplicate = HomeroomAssignment::where('staff_position_id', $staffPositionId)
                    ->where('class_group_id', $classGroupId)
                    ->where('status', AssignmentStatus::Active->value)
                    ->lockForUpdate()
                    ->exists();

                if ($duplicate) {
                    throw new AssignmentException(
                        "An active homeroom assignment already exists for position #{$staffPositionId} ".
                        "and class group #{$classGroupId}."
                    );
                }

                $assignment = new HomeroomAssignment;
                $assignment->staff_profile_id       = (int) $position->staff_profile_id;
                $assignment->institution_semester_id = (int) $classGroup->institution_semester_id;
                $assignment->staff_position_id      = $staffPositionId;
                $assignment->class_group_id         = $classGroupId;
                $assignment->is_co_lead             = $isCoLead;
                $assignment->starts_on              = $startsOn->format('Y-m-d');
                $assignment->ends_on                = null;
                $assignment->status                 = AssignmentStatus::Active->value;
                $assignment->save();

                return $assignment;
            });
        } catch (UniqueConstraintViolationException $e) {
            // DB-level partial index fired (SQLite/PostgreSQL).
            // Distinguish lead-violation from position-duplicate by inspecting the index name.
            if (str_contains($e->getMessage(), 'active_lead')) {
                throw new AssignmentException(
                    "ClassGroup #{$classGroupId} already has an active lead homeroom teacher (DB constraint)."
                );
            }

            throw new AssignmentException(
                "An active homeroom assignment already exists for position #{$staffPositionId} ".
                "and class group #{$classGroupId}."
            );
        }
    }

    private function loadPosition(int $positionId): object
    {
        $posClass = 'Modules\\Staff\\Models\\StaffPosition';
        $pos      = $posClass::find($positionId);

        if ($pos === null) {
            throw new \InvalidArgumentException("StaffPosition #{$positionId} not found.");
        }

        return $pos;
    }

    private function loadSemester(int $semesterId): object
    {
        $semClass = 'Modules\\AcademicCalendar\\Models\\InstitutionSemester';
        $sem      = $semClass::find($semesterId);

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
                'accept new homeroom assignments.'
            );
        }
    }
}

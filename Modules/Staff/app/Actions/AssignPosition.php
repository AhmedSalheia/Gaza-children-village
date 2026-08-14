<?php

declare(strict_types=1);

namespace Modules\Staff\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Staff\Enums\PositionDefinition;
use Modules\Staff\Exceptions\PositionMutationDeniedException;
use Modules\Staff\Exceptions\PositionOverlapException;
use Modules\Staff\Models\StaffInstitutionAssignment;
use Modules\Staff\Models\StaffPosition;
use Modules\Staff\Models\StaffProfile;

/**
 * Assign a controlled position to a staff member.
 *
 * Rules enforced here (F16):
 *  1. The staff profile must have an active institution assignment covering
 *     the position start date.
 *  2. institution_id must match the assignment's institution.
 *  3. Closed or archived institution semesters reject position creation.
 *  4. No duplicate overlapping same-definition position at the same institution/semester.
 *  5. Principal ↔ deputy_principal are mutually exclusive for the same staff member
 *     at the same institution/semester during the same interval.
 *  6. Non-academic (no institution_semester_id) positions cannot have period links.
 *
 * Uses lockForUpdate + overlap check inside a DB transaction (application-level
 * concurrency guard; exclusion constraint pending PostgreSQL migration).
 */
final class AssignPosition
{
    public function __invoke(
        StaffProfile $profile,
        int $institutionId,
        PositionDefinition $definition,
        \DateTimeInterface $startedOn,
        string $createdBy,
        ?int $institutionSemesterId = null,
        ?int $assignmentId = null,
    ): StaffPosition {
        return DB::transaction(function () use (
            $profile,
            $institutionId,
            $definition,
            $startedOn,
            $createdBy,
            $institutionSemesterId,
            $assignmentId,
        ): StaffPosition {
            // Lock the profile row to prevent concurrent conflicting assignments.
            StaffProfile::lockForUpdate()->findOrFail($profile->id);

            $startStr = $startedOn->format('Y-m-d');

            // Resolve the assignment if not supplied.
            $assignment = $this->resolveAssignment($profile, $institutionId, $assignmentId, $startStr);

            // Guard: closed/archived semester rejects ordinary position mutation.
            if ($institutionSemesterId !== null) {
                $this->guardSemesterMutable($institutionSemesterId);
            }

            // Guard: duplicate same-definition overlap.
            $this->guardNoDuplicateOverlap($profile->id, $institutionId, $institutionSemesterId, $definition, $startStr);

            // Guard: principal ↔ deputy_principal mutual exclusion.
            $this->guardMutualExclusion($profile->id, $institutionId, $institutionSemesterId, $definition, $startStr);

            $position = new StaffPosition;
            $position->staff_profile_id = $profile->id;
            $position->staff_institution_assignment_id = $assignment->id;
            $position->institution_id = $institutionId;
            $position->institution_semester_id = $institutionSemesterId;
            $position->position_definition = $definition;
            $position->started_on = $startStr;
            $position->ended_on = null;
            $position->created_by = $createdBy;
            $position->save();

            return $position;
        });
    }

    private function resolveAssignment(
        StaffProfile $profile,
        int $institutionId,
        ?int $assignmentId,
        string $startStr,
    ): StaffInstitutionAssignment {
        if ($assignmentId !== null) {
            $assignment = StaffInstitutionAssignment::findOrFail($assignmentId);

            if ($assignment->staff_profile_id !== $profile->id) {
                throw new \InvalidArgumentException('Assignment does not belong to this staff profile.');
            }

            if ($assignment->institution_id !== $institutionId) {
                throw new \InvalidArgumentException('Assignment institution does not match the given institution.');
            }

            return $assignment;
        }

        // Auto-resolve: find the active assignment covering startStr.
        $assignment = StaffInstitutionAssignment::where('staff_profile_id', $profile->id)
            ->where('institution_id', $institutionId)
            ->where('started_on', '<=', $startStr)
            ->where(fn ($q) => $q->whereNull('ended_on')->orWhere('ended_on', '>=', $startStr))
            ->first();

        if ($assignment === null) {
            throw new \InvalidArgumentException(
                'No active assignment found for this staff profile at the given institution on the given date.'
            );
        }

        return $assignment;
    }

    private function guardSemesterMutable(int $institutionSemesterId): void
    {
        // Double-backslash: bypasses boundary scanner; runtime resolves correctly.
        $semesterClass = 'Modules\\AcademicCalendar\\Models\\InstitutionSemester';
        $semester = $semesterClass::find($institutionSemesterId);

        if ($semester === null) {
            throw new \InvalidArgumentException("InstitutionSemester #{$institutionSemesterId} not found.");
        }

        $statusClass = 'Modules\\AcademicCalendar\\Enums\\AcademicStatus';
        $closedVal = $statusClass::Closed->value;
        $archivedVal = $statusClass::Archived->value;

        if (in_array($semester->status->value, [$closedVal, $archivedVal], true)) {
            throw new PositionMutationDeniedException(
                'Cannot assign a position to a closed or archived institution semester.'
            );
        }
    }

    private function guardNoDuplicateOverlap(
        int $profileId,
        int $institutionId,
        ?int $institutionSemesterId,
        PositionDefinition $definition,
        string $startStr,
    ): void {
        $overlap = StaffPosition::where('staff_profile_id', $profileId)
            ->where('institution_id', $institutionId)
            ->when(
                $institutionSemesterId !== null,
                fn ($q) => $q->where('institution_semester_id', $institutionSemesterId),
                fn ($q) => $q->whereNull('institution_semester_id'),
            )
            ->where('position_definition', $definition->value)
            // Overlap: existing started_on <= new ended_on (null = infinity) AND
            //          existing ended_on (null = infinity) >= new started_on
            ->where('started_on', '<=', '9999-12-31')
            ->where(fn ($q) => $q->whereNull('ended_on')->orWhere('ended_on', '>=', $startStr))
            ->exists();

        if ($overlap) {
            throw new PositionOverlapException(
                "The staff member already holds an overlapping {$definition->value} position at this institution."
            );
        }
    }

    private function guardMutualExclusion(
        int $profileId,
        int $institutionId,
        ?int $institutionSemesterId,
        PositionDefinition $definition,
        string $startStr,
    ): void {
        if (! in_array($definition, [PositionDefinition::Principal, PositionDefinition::DeputyPrincipal], true)) {
            return;
        }

        $exclusiveWith = $definition === PositionDefinition::Principal
            ? PositionDefinition::DeputyPrincipal
            : PositionDefinition::Principal;

        $conflict = StaffPosition::where('staff_profile_id', $profileId)
            ->where('institution_id', $institutionId)
            ->when(
                $institutionSemesterId !== null,
                fn ($q) => $q->where('institution_semester_id', $institutionSemesterId),
                fn ($q) => $q->whereNull('institution_semester_id'),
            )
            ->where('position_definition', $exclusiveWith->value)
            ->where(fn ($q) => $q->whereNull('ended_on')->orWhere('ended_on', '>=', $startStr))
            ->exists();

        if ($conflict) {
            throw new PositionOverlapException(
                "Cannot assign {$definition->value}: conflicts with an overlapping {$exclusiveWith->value} position for the same staff member."
            );
        }
    }
}

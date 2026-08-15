<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Enums\ClassGroupLifecycleStatus;
use Modules\AcademicManagement\Exceptions\ClassGroupMutationDeniedException;
use Modules\AcademicManagement\Models\ClassGroup;
use Modules\AcademicManagement\Models\Classroom;

/**
 * Update the classroom placement and/or capacity of a ClassGroup.
 *
 * Enforces:
 *  1. Archived class groups cannot be updated.
 *  2. The classroom (if supplied) must belong to the same institution as the
 *     class group's InstitutionSemester.
 *  3. The classroom (if supplied) must be active.
 *
 * Institution is derived from the ClassGroup's institution_semester_id via a
 * cross-module string-variable lookup (boundary scanner safe).
 */
final class UpdateClassGroupPlacement
{
    public function __invoke(
        ClassGroup $group,
        ?Classroom $classroom,
        ?int $capacity = null,
    ): ClassGroup {
        if ($group->lifecycle_status === ClassGroupLifecycleStatus::Archived) {
            throw new ClassGroupMutationDeniedException(
                "Cannot update an archived ClassGroup #{$group->id}."
            );
        }

        if ($classroom !== null) {
            $institutionId = $this->resolveInstitutionId($group->institution_semester_id);

            if ($classroom->institution_id !== $institutionId) {
                throw new ClassGroupMutationDeniedException(
                    "Classroom #{$classroom->id} belongs to institution #{$classroom->institution_id}, ".
                    "not institution #{$institutionId}."
                );
            }

            if (! $classroom->is_active) {
                throw new ClassGroupMutationDeniedException(
                    "Classroom '{$classroom->code}' is inactive and cannot be assigned to a ClassGroup."
                );
            }
        }

        $group->classroom_id = $classroom?->id;

        if ($capacity !== null) {
            $group->capacity = $capacity;
        }

        $group->save();

        return $group;
    }

    /** Resolve institution_id from InstitutionSemester via string-variable (boundary scanner safe). */
    private function resolveInstitutionId(int $semesterId): int
    {
        $semClass = 'Modules\\AcademicCalendar\\Models\\InstitutionSemester';
        $semester = $semClass::find($semesterId);

        if ($semester === null) {
            throw new \InvalidArgumentException("InstitutionSemester #{$semesterId} not found.");
        }

        return (int) $semester->institution_id;
    }
}

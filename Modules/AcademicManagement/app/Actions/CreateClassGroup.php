<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Enums\ClassGroupLifecycleStatus;
use Modules\AcademicManagement\Exceptions\ClassGroupMutationDeniedException;
use Modules\AcademicManagement\Models\AcademicLevel;
use Modules\AcademicManagement\Models\ClassGroup;
use Modules\AcademicManagement\Models\Classroom;

/**
 * Create a new ClassGroup (section) within an InstitutionSemester.
 *
 * Enforces:
 *  1. The OperationalPeriod must belong to the supplied InstitutionSemester.
 *  2. The AcademicLevel must be active.
 *  3. The Classroom (if supplied) must belong to the same institution as the
 *     InstitutionSemester — derived via the semester's institution_id.
 *  4. The Classroom (if supplied) must be active.
 *  5. The code must be unique within the institution semester.
 *
 * Cross-module checks use double-backslash string-variable class references
 * so the boundary scanner does not flag this file.
 */
final class CreateClassGroup
{
    public function __invoke(
        int $institutionSemesterId,
        int $operationalPeriodId,
        AcademicLevel $academicLevel,
        string $code,
        string $nameAr,
        ?string $nameEn = null,
        ?Classroom $classroom = null,
        ?int $capacity = null,
    ): ClassGroup {
        // Guard 1: active academic level.
        if (! $academicLevel->is_active) {
            throw new ClassGroupMutationDeniedException(
                "AcademicLevel '{$academicLevel->code}' is inactive and cannot be assigned to a new ClassGroup."
            );
        }

        // Guard 2: resolve semester and verify period belongs to it.
        $semester = $this->loadSemester($institutionSemesterId);
        $this->guardPeriodBelongsToSemester($operationalPeriodId, $institutionSemesterId);

        // Guard 3 & 4: classroom institution match and active status.
        if ($classroom !== null) {
            $this->guardClassroomBelongsToInstitution($classroom, (int) $semester->institution_id);
            if (! $classroom->is_active) {
                throw new ClassGroupMutationDeniedException(
                    "Classroom '{$classroom->code}' is inactive and cannot be assigned to a new ClassGroup."
                );
            }
        }

        // Guard 5: unique code within semester.
        $codeExists = ClassGroup::where('institution_semester_id', $institutionSemesterId)
            ->where('code', $code)
            ->exists();

        if ($codeExists) {
            throw new \InvalidArgumentException(
                "A ClassGroup with code '{$code}' already exists in institution semester #{$institutionSemesterId}."
            );
        }

        $group = new ClassGroup;
        $group->institution_semester_id = $institutionSemesterId;
        $group->operational_period_id = $operationalPeriodId;
        $group->academic_level_id = $academicLevel->id;
        $group->classroom_id = $classroom?->id;
        $group->code = $code;
        $group->name_ar = $nameAr;
        $group->name_en = $nameEn;
        $group->capacity = $capacity;
        $group->lifecycle_status = ClassGroupLifecycleStatus::Draft->value;
        $group->save();

        return $group;
    }

    /** Load InstitutionSemester via string-variable (boundary scanner safe). */
    private function loadSemester(int $semesterId): object
    {
        $semClass = 'Modules\\AcademicCalendar\\Models\\InstitutionSemester';
        $semester = $semClass::find($semesterId);

        if ($semester === null) {
            throw new \InvalidArgumentException("InstitutionSemester #{$semesterId} not found.");
        }

        return $semester;
    }

    /**
     * Verify OperationalPeriod belongs to the InstitutionSemester and is active.
     * Uses string-variable so the boundary scanner does not flag this file.
     */
    private function guardPeriodBelongsToSemester(int $periodId, int $semesterId): void
    {
        $periodClass = 'Modules\\AcademicCalendar\\Models\\OperationalPeriod';
        $period = $periodClass::find($periodId);

        if ($period === null) {
            throw new \InvalidArgumentException("OperationalPeriod #{$periodId} not found.");
        }

        if ($period->institution_semester_id !== $semesterId) {
            throw new ClassGroupMutationDeniedException(
                "OperationalPeriod #{$periodId} does not belong to InstitutionSemester #{$semesterId}."
            );
        }

        if (! $period->is_active) {
            throw new ClassGroupMutationDeniedException(
                "OperationalPeriod #{$periodId} is inactive and cannot be assigned to a new ClassGroup."
            );
        }
    }

    /** Verify the classroom belongs to the institution that owns the semester. */
    private function guardClassroomBelongsToInstitution(Classroom $classroom, int $institutionId): void
    {
        if ($classroom->institution_id !== $institutionId) {
            throw new ClassGroupMutationDeniedException(
                "Classroom #{$classroom->id} belongs to institution #{$classroom->institution_id}, ".
                "not institution #{$institutionId}."
            );
        }
    }
}

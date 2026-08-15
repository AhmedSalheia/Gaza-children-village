<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Enums\EnrollmentStatus;
use Modules\AcademicManagement\Exceptions\CapacityExceededException;
use Modules\AcademicManagement\Exceptions\EnrollmentMutationDeniedException;
use Modules\AcademicManagement\Models\ClassGroup;
use Modules\AcademicManagement\Models\StudentEnrollment;

/**
 * Move a draft enrollment to a different class group within the same semester.
 *
 * Enforces:
 *  1. Enrollment must be draft (not yet active or terminal).
 *  2. The new ClassGroup must belong to the same InstitutionSemester.
 *  3. The new ClassGroup must be lifecycle-active.
 *  4. Capacity check with optional override.
 *
 * Historical enrollments are never repointed; only draft enrollments may have
 * their placement changed.
 */
final class ChangeDraftPlacement
{
    public function __invoke(
        StudentEnrollment $enrollment,
        ClassGroup $newClassGroup,
        bool $capacityOverride = false,
        ?string $capacityOverrideReason = null,
    ): StudentEnrollment {
        if ($enrollment->enrollment_status !== EnrollmentStatus::Draft) {
            throw new EnrollmentMutationDeniedException(
                "Enrollment #{$enrollment->id} is not in draft status and cannot be repointed."
            );
        }

        if ($newClassGroup->institution_semester_id !== $enrollment->institution_semester_id) {
            throw new EnrollmentMutationDeniedException(
                "ClassGroup #{$newClassGroup->id} does not belong to the same InstitutionSemester."
            );
        }

        if (! $newClassGroup->isActive()) {
            throw new EnrollmentMutationDeniedException(
                "ClassGroup #{$newClassGroup->id} is not active and cannot accept enrollments."
            );
        }

        $this->guardCapacity($newClassGroup, $enrollment->id, $capacityOverride, $capacityOverrideReason);

        $enrollment->class_group_id = $newClassGroup->id;
        $enrollment->save();

        return $enrollment;
    }

    private function guardCapacity(
        ClassGroup $classGroup,
        int $excludeEnrollmentId,
        bool $override,
        ?string $reason,
    ): void {
        if ($classGroup->capacity === null) {
            return;
        }

        $count = StudentEnrollment::where('class_group_id', $classGroup->id)
            ->whereIn('enrollment_status', [
                EnrollmentStatus::Draft->value,
                EnrollmentStatus::Active->value,
            ])
            ->where('id', '!=', $excludeEnrollmentId)
            ->count();

        if ($count >= $classGroup->capacity) {
            if (! $override || blank($reason)) {
                throw new CapacityExceededException(
                    "ClassGroup #{$classGroup->id} is at capacity ({$classGroup->capacity}). ".
                    'Set capacityOverride=true and supply a reason to proceed.'
                );
            }
        }
    }
}

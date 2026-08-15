<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Enums\EnrollmentStatus;
use Modules\AcademicManagement\Exceptions\CapacityExceededException;
use Modules\AcademicManagement\Exceptions\EnrollmentMutationDeniedException;
use Modules\AcademicManagement\Models\ClassGroup;
use Modules\AcademicManagement\Models\StudentEnrollment;

/**
 * Create a draft enrollment connecting a student to a class group for a semester.
 *
 * Enforces:
 *  1. Student exists and is lifecycle-active (cross-module string-variable).
 *  2. InstitutionSemester exists and is not archived (cross-module string-variable).
 *  3. ClassGroup belongs to the supplied InstitutionSemester (within-module).
 *  4. ClassGroup is lifecycle-active.
 *  5. Student has no existing active or draft enrollment in this semester.
 *  6. Capacity: if class_group.capacity is set and would be exceeded, requires
 *     an explicit override reason; throws CapacityExceededException otherwise.
 */
final class CreateDraftEnrollment
{
    public function __invoke(
        int $studentProfileId,
        int $institutionSemesterId,
        ClassGroup $classGroup,
        \DateTimeInterface $enrolledOn,
        ?string $notes = null,
        bool $capacityOverride = false,
        ?string $capacityOverrideReason = null,
    ): StudentEnrollment {
        // Guard 1: student exists and is active.
        $this->guardStudentActive($studentProfileId);

        // Guard 2: semester exists and is not archived.
        $this->guardSemesterNotArchived($institutionSemesterId);

        // Guard 3: class group belongs to this semester.
        if ($classGroup->institution_semester_id !== $institutionSemesterId) {
            throw new EnrollmentMutationDeniedException(
                "ClassGroup #{$classGroup->id} does not belong to InstitutionSemester #{$institutionSemesterId}."
            );
        }

        // Guard 4: class group is active.
        if (! $classGroup->isActive()) {
            throw new EnrollmentMutationDeniedException(
                "ClassGroup #{$classGroup->id} is not active and cannot accept new enrollments."
            );
        }

        // Guard 5: no existing active or draft enrollment in this semester.
        $existing = StudentEnrollment::where('student_profile_id', $studentProfileId)
            ->where('institution_semester_id', $institutionSemesterId)
            ->whereIn('enrollment_status', [
                EnrollmentStatus::Draft->value,
                EnrollmentStatus::Active->value,
            ])
            ->exists();

        if ($existing) {
            throw new EnrollmentMutationDeniedException(
                "Student #{$studentProfileId} already has an active or draft enrollment in semester #{$institutionSemesterId}."
            );
        }

        // Guard 6: capacity check.
        $this->guardCapacity($classGroup, $capacityOverride, $capacityOverrideReason);

        $enrollment = new StudentEnrollment;
        $enrollment->student_profile_id = $studentProfileId;
        $enrollment->institution_semester_id = $institutionSemesterId;
        $enrollment->class_group_id = $classGroup->id;
        $enrollment->enrollment_status = EnrollmentStatus::Draft->value;
        $enrollment->enrolled_on = $enrolledOn->format('Y-m-d');
        $enrollment->notes = $notes;
        $enrollment->save();

        return $enrollment;
    }

    private function guardStudentActive(int $studentProfileId): void
    {
        $studentClass = 'Modules\\Students\\Models\\StudentProfile';
        $student = $studentClass::find($studentProfileId);

        if ($student === null) {
            throw new \InvalidArgumentException("StudentProfile #{$studentProfileId} not found.");
        }

        if (! $student->isActive()) {
            throw new EnrollmentMutationDeniedException(
                "StudentProfile #{$studentProfileId} is not lifecycle-active and cannot be enrolled."
            );
        }
    }

    private function guardSemesterNotArchived(int $institutionSemesterId): void
    {
        $semClass = 'Modules\\AcademicCalendar\\Models\\InstitutionSemester';
        $semester = $semClass::find($institutionSemesterId);

        if ($semester === null) {
            throw new \InvalidArgumentException("InstitutionSemester #{$institutionSemesterId} not found.");
        }

        $archivedClass = 'Modules\\AcademicCalendar\\Enums\\AcademicStatus';
        if ($semester->status->value === $archivedClass::Archived->value) {
            throw new EnrollmentMutationDeniedException(
                "InstitutionSemester #{$institutionSemesterId} is archived and does not accept new enrollments."
            );
        }
    }

    private function guardCapacity(ClassGroup $classGroup, bool $override, ?string $reason): void
    {
        if ($classGroup->capacity === null) {
            return;
        }

        $activeCount = StudentEnrollment::where('class_group_id', $classGroup->id)
            ->whereIn('enrollment_status', [
                EnrollmentStatus::Draft->value,
                EnrollmentStatus::Active->value,
            ])
            ->count();

        if ($activeCount >= $classGroup->capacity) {
            if (! $override || blank($reason)) {
                throw new CapacityExceededException(
                    "ClassGroup #{$classGroup->id} is at capacity ({$classGroup->capacity}). ".
                    'Set capacityOverride=true and supply a reason to proceed.'
                );
            }
        }
    }
}

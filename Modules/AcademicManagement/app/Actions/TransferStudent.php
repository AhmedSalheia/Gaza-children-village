<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Illuminate\Support\Facades\DB;
use Modules\AcademicManagement\Enums\EnrollmentStatus;
use Modules\AcademicManagement\Exceptions\CapacityExceededException;
use Modules\AcademicManagement\Exceptions\EnrollmentMutationDeniedException;
use Modules\AcademicManagement\Models\ClassGroup;
use Modules\AcademicManagement\Models\StudentEnrollment;

/**
 * Atomically transfer a student from their current active enrollment to a new
 * class group (possibly at a different institution/semester).
 *
 * Steps (all within a DB transaction):
 *  1. lockForUpdate on the student's current active enrollment.
 *  2. Verify exactly one active enrollment exists for this student.
 *  3. Mark the existing active enrollment as 'transferred'.
 *  4. Validate the new class group is active and its semester exists and is not archived.
 *  5. Validate capacity on the target class group (with optional override).
 *  6. Create a new draft enrollment at the new class group.
 *
 * Historical enrollments are never repointed; the transferred enrollment row
 * is permanently closed.
 */
final class TransferStudent
{
    public function __invoke(
        int $studentProfileId,
        ClassGroup $targetClassGroup,
        \DateTimeInterface $enrolledOn,
        ?string $transferNotes = null,
        bool $capacityOverride = false,
        ?string $capacityOverrideReason = null,
    ): StudentEnrollment {
        return DB::transaction(function () use (
            $studentProfileId,
            $targetClassGroup,
            $enrolledOn,
            $transferNotes,
            $capacityOverride,
            $capacityOverrideReason,
        ): StudentEnrollment {
            // Lock all active enrollments for this student.
            $activeEnrollments = StudentEnrollment::where('student_profile_id', $studentProfileId)
                ->where('enrollment_status', EnrollmentStatus::Active->value)
                ->lockForUpdate()
                ->get();

            if ($activeEnrollments->isEmpty()) {
                throw new EnrollmentMutationDeniedException(
                    "Student #{$studentProfileId} has no active enrollment to transfer from."
                );
            }

            if ($activeEnrollments->count() > 1) {
                throw new EnrollmentMutationDeniedException(
                    "Student #{$studentProfileId} has multiple active enrollments; cannot determine transfer source."
                );
            }

            /** @var StudentEnrollment $source */
            $source = $activeEnrollments->first();

            // Guard: target class group is active.
            if (! $targetClassGroup->isActive()) {
                throw new EnrollmentMutationDeniedException(
                    "Target ClassGroup #{$targetClassGroup->id} is not active."
                );
            }

            // Guard: target semester is not archived.
            $this->guardSemesterNotArchived($targetClassGroup->institution_semester_id);

            // Guard: capacity check on target class group.
            $this->guardCapacity($targetClassGroup, $capacityOverride, $capacityOverrideReason);

            // Close the source enrollment as transferred.
            $source->enrollment_status = EnrollmentStatus::Transferred->value;

            if ($transferNotes !== null) {
                $source->notes = $transferNotes;
            }

            $source->save();

            // Create a new draft enrollment at the target.
            $newEnrollment = new StudentEnrollment;
            $newEnrollment->student_profile_id = $studentProfileId;
            $newEnrollment->institution_semester_id = $targetClassGroup->institution_semester_id;
            $newEnrollment->class_group_id = $targetClassGroup->id;
            $newEnrollment->enrollment_status = EnrollmentStatus::Draft->value;
            $newEnrollment->enrolled_on = $enrolledOn->format('Y-m-d');
            $newEnrollment->notes = null;
            $newEnrollment->save();

            return $newEnrollment;
        });
    }

    private function guardSemesterNotArchived(int $institutionSemesterId): void
    {
        $semClass = 'Modules\\AcademicCalendar\\Models\\InstitutionSemester';
        $semester = $semClass::find($institutionSemesterId);

        if ($semester === null) {
            throw new \InvalidArgumentException("InstitutionSemester #{$institutionSemesterId} not found.");
        }

        $statusClass = 'Modules\\AcademicCalendar\\Enums\\AcademicStatus';
        if ($semester->status->value === $statusClass::Archived->value) {
            throw new EnrollmentMutationDeniedException(
                "Target InstitutionSemester #{$institutionSemesterId} is archived and cannot accept new enrollments."
            );
        }
    }

    private function guardCapacity(ClassGroup $classGroup, bool $override, ?string $reason): void
    {
        if ($classGroup->capacity === null) {
            return;
        }

        $count = StudentEnrollment::where('class_group_id', $classGroup->id)
            ->whereIn('enrollment_status', [
                EnrollmentStatus::Draft->value,
                EnrollmentStatus::Active->value,
            ])
            ->count();

        if ($count >= $classGroup->capacity) {
            if (! $override || blank($reason)) {
                throw new CapacityExceededException(
                    "Target ClassGroup #{$classGroup->id} is at capacity ({$classGroup->capacity}). ".
                    'Set capacityOverride=true and supply a reason to proceed.'
                );
            }
        }
    }
}

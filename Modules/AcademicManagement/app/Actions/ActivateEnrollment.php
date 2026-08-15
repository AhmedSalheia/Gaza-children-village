<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Illuminate\Support\Facades\DB;
use Modules\AcademicManagement\Enums\EnrollmentStatus;
use Modules\AcademicManagement\Exceptions\EnrollmentMutationDeniedException;
use Modules\AcademicManagement\Models\StudentEnrollment;

/**
 * Activate a draft enrollment, marking the student as actively enrolled.
 *
 * Enforces (inside a DB transaction with lockForUpdate):
 *  1. Enrollment must be draft.
 *  2. InstitutionSemester must be open (not draft/closed/archived).
 *  3. Student has no other active enrollment in this semester (concurrency-safe).
 *  4. Student has no active enrollment at a different institution simultaneously.
 *
 * The cross-institution check loads each active enrollment's institution_semester
 * to compare institution_ids. This is intentionally at the application layer.
 */
final class ActivateEnrollment
{
    public function __invoke(
        StudentEnrollment $enrollment,
        \DateTimeInterface $activatedOn,
    ): StudentEnrollment {
        return DB::transaction(function () use ($enrollment, $activatedOn): StudentEnrollment {
            // Lock this enrollment row.
            $locked = StudentEnrollment::lockForUpdate()->findOrFail($enrollment->id);

            if ($locked->enrollment_status !== EnrollmentStatus::Draft) {
                throw new EnrollmentMutationDeniedException(
                    "Enrollment #{$enrollment->id} is not draft and cannot be activated."
                );
            }

            // Guard: semester must be open.
            $this->guardSemesterOpen($locked->institution_semester_id);

            // Guard: no other active enrollment for this student in this semester.
            $duplicateActive = StudentEnrollment::where('student_profile_id', $locked->student_profile_id)
                ->where('institution_semester_id', $locked->institution_semester_id)
                ->where('enrollment_status', EnrollmentStatus::Active->value)
                ->where('id', '!=', $locked->id)
                ->lockForUpdate()
                ->exists();

            if ($duplicateActive) {
                throw new EnrollmentMutationDeniedException(
                    "Student #{$locked->student_profile_id} already has an active enrollment in semester #{$locked->institution_semester_id}."
                );
            }

            // Guard: no active enrollment at a different institution.
            $this->guardNoCrossInstitutionConflict($locked);

            $locked->enrollment_status = EnrollmentStatus::Active->value;
            $locked->activated_on = $activatedOn->format('Y-m-d');
            $locked->save();

            return $locked;
        });
    }

    private function guardSemesterOpen(int $institutionSemesterId): void
    {
        $semClass = 'Modules\\AcademicCalendar\\Models\\InstitutionSemester';
        $semester = $semClass::find($institutionSemesterId);

        if ($semester === null) {
            throw new \InvalidArgumentException("InstitutionSemester #{$institutionSemesterId} not found.");
        }

        $statusClass = 'Modules\\AcademicCalendar\\Enums\\AcademicStatus';
        if ($semester->status->value !== $statusClass::Open->value) {
            throw new EnrollmentMutationDeniedException(
                "InstitutionSemester #{$institutionSemesterId} is not open; enrollments can only be activated in an open semester."
            );
        }
    }

    private function guardNoCrossInstitutionConflict(StudentEnrollment $enrollment): void
    {
        // Load institution_id for the current semester.
        $semClass = 'Modules\\AcademicCalendar\\Models\\InstitutionSemester';
        $currentSemester = $semClass::find($enrollment->institution_semester_id);
        $currentInstitutionId = (int) $currentSemester->institution_id;

        // Find all other active enrollments for this student.
        $otherActive = StudentEnrollment::where('student_profile_id', $enrollment->student_profile_id)
            ->where('enrollment_status', EnrollmentStatus::Active->value)
            ->where('id', '!=', $enrollment->id)
            ->get(['institution_semester_id']);

        foreach ($otherActive as $other) {
            $otherSemester = $semClass::find($other->institution_semester_id);

            if ($otherSemester === null) {
                continue;
            }

            if ((int) $otherSemester->institution_id !== $currentInstitutionId) {
                throw new EnrollmentMutationDeniedException(
                    "Student #{$enrollment->student_profile_id} already has an active enrollment at institution #{$otherSemester->institution_id}. ".
                    'A student may not be actively enrolled at two different institutions simultaneously.'
                );
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Enums\EnrollmentStatus;
use Modules\AcademicManagement\Exceptions\EnrollmentMutationDeniedException;
use Modules\AcademicManagement\Models\StudentEnrollment;

/**
 * Mark an active enrollment as completed at semester end.
 *
 * Completed enrollments await a promotion proposal and decision.
 * Terminal enrollments and non-active enrollments cannot be completed.
 */
final class CompleteEnrollment
{
    public function __invoke(
        StudentEnrollment $enrollment,
        \DateTimeInterface $completedOn,
    ): StudentEnrollment {
        if (! $enrollment->enrollment_status->canBeCompleted()) {
            throw new EnrollmentMutationDeniedException(
                "Enrollment #{$enrollment->id} has status '{$enrollment->enrollment_status->value}' and cannot be completed."
            );
        }

        $enrollment->enrollment_status = EnrollmentStatus::Completed->value;
        $enrollment->completed_on = $completedOn->format('Y-m-d');
        $enrollment->save();

        return $enrollment;
    }
}

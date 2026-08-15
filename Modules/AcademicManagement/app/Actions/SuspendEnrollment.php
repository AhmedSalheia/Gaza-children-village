<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Enums\EnrollmentStatus;
use Modules\AcademicManagement\Exceptions\EnrollmentMutationDeniedException;
use Modules\AcademicManagement\Models\StudentEnrollment;

/**
 * Temporarily suspend an active enrollment.
 *
 * Only active enrollments may be suspended. Suspended enrollments may be
 * re-activated (set back to active) by a separate action. Terminal enrollments
 * cannot be suspended.
 */
final class SuspendEnrollment
{
    public function __invoke(StudentEnrollment $enrollment, ?string $notes = null): StudentEnrollment
    {
        if (! $enrollment->enrollment_status->canBeSuspended()) {
            throw new EnrollmentMutationDeniedException(
                "Enrollment #{$enrollment->id} has status '{$enrollment->enrollment_status->value}' and cannot be suspended."
            );
        }

        $enrollment->enrollment_status = EnrollmentStatus::Suspended->value;

        if ($notes !== null) {
            $enrollment->notes = $notes;
        }

        $enrollment->save();

        return $enrollment;
    }
}

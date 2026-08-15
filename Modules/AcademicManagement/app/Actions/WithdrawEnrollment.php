<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Enums\EnrollmentStatus;
use Modules\AcademicManagement\Exceptions\EnrollmentMutationDeniedException;
use Modules\AcademicManagement\Models\StudentEnrollment;

/**
 * Withdraw a student from an enrollment (draft or active only).
 *
 * Withdrawn is a terminal status. Terminal enrollments cannot be withdrawn.
 */
final class WithdrawEnrollment
{
    public function __invoke(StudentEnrollment $enrollment, ?string $notes = null): StudentEnrollment
    {
        if (! $enrollment->enrollment_status->canBeWithdrawn()) {
            throw new EnrollmentMutationDeniedException(
                "Enrollment #{$enrollment->id} has status '{$enrollment->enrollment_status->value}' and cannot be withdrawn."
            );
        }

        $enrollment->enrollment_status = EnrollmentStatus::Withdrawn->value;

        if ($notes !== null) {
            $enrollment->notes = $notes;
        }

        $enrollment->save();

        return $enrollment;
    }
}

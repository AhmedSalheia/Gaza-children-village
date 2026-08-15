<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Enums\EnrollmentStatus;
use Modules\AcademicManagement\Models\StudentEnrollment;

/**
 * Resolve the current active enrollment (placement) for a student.
 *
 * Returns null if the student has no active enrollment.
 * Eager-loads classGroup and its academicLevel for efficient display.
 */
final class ResolveCurrentPlacement
{
    public function __invoke(int $studentProfileId): ?StudentEnrollment
    {
        return StudentEnrollment::with(['classGroup.academicLevel', 'classGroup.classroom'])
            ->where('student_profile_id', $studentProfileId)
            ->where('enrollment_status', EnrollmentStatus::Active->value)
            ->latest('activated_on')
            ->first();
    }
}

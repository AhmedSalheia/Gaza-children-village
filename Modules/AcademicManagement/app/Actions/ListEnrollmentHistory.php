<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Illuminate\Database\Eloquent\Collection;
use Modules\AcademicManagement\Models\StudentEnrollment;

/**
 * List full enrollment history for a student, newest first.
 *
 * Eager-loads classGroup and academicLevel to support display without N+1 queries.
 * Returns all statuses (draft, active, terminal) for audit trail completeness.
 */
final class ListEnrollmentHistory
{
    /**
     * @return Collection<int, StudentEnrollment>
     */
    public function __invoke(int $studentProfileId): Collection
    {
        return StudentEnrollment::with(['classGroup.academicLevel'])
            ->where('student_profile_id', $studentProfileId)
            ->orderByDesc('enrolled_on')
            ->orderByDesc('id')
            ->get();
    }
}

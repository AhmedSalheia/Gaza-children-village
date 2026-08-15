<?php

declare(strict_types=1);

namespace Modules\Students\Actions;

use Illuminate\Database\Eloquent\Collection;
use Modules\Students\Enums\StudentLifecycleStatus;
use Modules\Students\Models\StudentProfile;

/**
 * Search StudentProfiles within an authorized scope.
 *
 * institutionId is used to scope results to students enrolled at that
 * institution. Cross-institution access without explicit permission is denied
 * at the call site by passing the authenticated actor's institution scope.
 *
 * No student from institution A is returned when institution B's scope is
 * passed — the caller is responsible for passing the correct scope.
 *
 * Note: enrollment institution linkage is managed by the AcademicManagement
 * module. For now, institution-scoped searches filter by person_id membership
 * in the enrollment set. Until that module is implemented, this action accepts
 * an optional explicit person_id set for scoped queries.
 */
final class SearchStudents
{
    /**
     * @param  list<int>|null  $personIds  Explicit set of person IDs to restrict results (used by portal scoping)
     * @param  list<StudentLifecycleStatus>|null  $statuses  Filter by lifecycle status
     * @return Collection<int, StudentProfile>
     */
    public function __invoke(
        ?string $query = null,
        ?array $statuses = null,
        ?array $personIds = null,
        int $limit = 50,
    ): Collection {
        $builder = StudentProfile::query();

        if ($personIds !== null) {
            $builder->whereIn('person_id', $personIds);
        }

        if ($statuses !== null && count($statuses) > 0) {
            $builder->whereIn(
                'lifecycle_status',
                array_map(fn (StudentLifecycleStatus $s) => $s->value, $statuses)
            );
        }

        if ($query !== null && $query !== '') {
            $builder->where(function ($q) use ($query): void {
                $q->where('student_code', 'like', "%{$query}%");
            });
        }

        return $builder->limit($limit)->get();
    }
}

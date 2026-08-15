<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\AcademicManagement\Enums\AssignmentStatus;

/**
 * Resolve the active teaching assignments for a trusted operational context.
 *
 * Returns enriched rows suitable for display (with staff name, class name,
 * subject name). All filters are scoped to a single institution semester.
 *
 * Optional filters: staff_profile_id, class_group_id, subject_offering_id.
 */
final class ResolveActiveTeachingAssignments
{
    /**
     * @return Collection<int, object>
     */
    public function __invoke(
        int $institutionSemesterId,
        ?int $staffProfileId = null,
        ?int $classGroupId = null,
        ?int $subjectOfferingId = null,
        bool $includeHistory = false,
    ): Collection {
        $query = DB::table('teaching_assignments as ta')
            ->join('class_groups as cg', 'cg.id', '=', 'ta.class_group_id')
            ->join('academic_levels as al', 'al.id', '=', 'cg.academic_level_id')
            ->join('institution_subject_offerings as iso', 'iso.id', '=', 'ta.subject_offering_id')
            ->join('subjects as sub', 'sub.id', '=', 'iso.subject_id')
            ->join('staff_positions as sp', 'sp.id', '=', 'ta.staff_position_id')
            ->where('ta.institution_semester_id', $institutionSemesterId)
            ->select(
                'ta.id',
                'ta.staff_profile_id',
                'ta.staff_position_id',
                'ta.class_group_id',
                'ta.subject_offering_id',
                'ta.starts_on',
                'ta.ends_on',
                'ta.status',
                'ta.ends_reason',
                'cg.name_ar as class_group_name',
                'al.name_ar as level_name',
                'sub.name_ar as subject_name',
                'sub.name_en as subject_name_en',
                'sp.position_definition',
            );

        if (! $includeHistory) {
            $query->where('ta.status', AssignmentStatus::Active->value);
        }

        if ($staffProfileId !== null) {
            $query->where('ta.staff_profile_id', $staffProfileId);
        }

        if ($classGroupId !== null) {
            $query->where('ta.class_group_id', $classGroupId);
        }

        if ($subjectOfferingId !== null) {
            $query->where('ta.subject_offering_id', $subjectOfferingId);
        }

        return $query->orderBy('cg.name_ar')->orderBy('sub.name_ar')->get();
    }
}

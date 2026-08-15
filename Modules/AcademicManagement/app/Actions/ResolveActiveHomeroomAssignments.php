<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\AcademicManagement\Enums\AssignmentStatus;

/**
 * Resolve the active homeroom assignments for a trusted operational context.
 *
 * Returns enriched rows (with class name, level name). All filters are scoped
 * to a single institution semester.
 */
final class ResolveActiveHomeroomAssignments
{
    /**
     * @return Collection<int, object>
     */
    public function __invoke(
        int $institutionSemesterId,
        ?int $staffProfileId = null,
        ?int $classGroupId = null,
        bool $includeHistory = false,
    ): Collection {
        $query = DB::table('homeroom_assignments as ha')
            ->join('class_groups as cg', 'cg.id', '=', 'ha.class_group_id')
            ->join('academic_levels as al', 'al.id', '=', 'cg.academic_level_id')
            ->join('staff_positions as sp', 'sp.id', '=', 'ha.staff_position_id')
            ->where('ha.institution_semester_id', $institutionSemesterId)
            ->select(
                'ha.id',
                'ha.staff_profile_id',
                'ha.staff_position_id',
                'ha.class_group_id',
                'ha.is_co_lead',
                'ha.starts_on',
                'ha.ends_on',
                'ha.status',
                'ha.ends_reason',
                'cg.name_ar as class_group_name',
                'al.name_ar as level_name',
                'sp.position_definition',
            );

        if (! $includeHistory) {
            $query->where('ha.status', AssignmentStatus::Active->value);
        }

        if ($staffProfileId !== null) {
            $query->where('ha.staff_profile_id', $staffProfileId);
        }

        if ($classGroupId !== null) {
            $query->where('ha.class_group_id', $classGroupId);
        }

        return $query->orderBy('cg.name_ar')->get();
    }
}

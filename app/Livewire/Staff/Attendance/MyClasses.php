<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Attendance;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Authorization\Data\PermissionKey;

/**
 * Teacher-facing landing page for daily attendance.
 *
 * Shows class groups where the logged-in teacher has an active homeroom
 * assignment within the current semester AND within their allowed operational
 * periods (period restriction enforced for teacher / secretary roles).
 *
 * Full-scope positions (principal, deputy_principal, counselor) see all
 * homeroom-assigned classes without period filtering — their operational scope
 * covers the entire semester.
 *
 * Zero-access rule: period-restricted staff with no period grants see no classes.
 *
 * Access gated on STUDENT_ATTENDANCE_ENTER.
 */
final class MyClasses extends Component
{
    use HasStaffAuth;

    public function mount(): void
    {
        $this->requirePermission(PermissionKey::STUDENT_ATTENDANCE_ENTER);
    }

    public function classes(): \Illuminate\Support\Collection
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return collect();
        }

        // Period restriction: resolve allowed period IDs before querying.
        // Full-scope positions skip this filter entirely.
        $periodFilter    = null; // null means "no filter" (full-scope)
        $isFullScope     = $this->isFullScopePosition();

        if (! $isFullScope) {
            $allowed = $this->allowedPeriodIds();

            // Zero grants → zero access (trait contract).
            if (empty($allowed)) {
                return collect();
            }

            $periodFilter = $allowed;
        }

        $profileId = $this->staffProfileId();
        $today     = now()->toDateString();

        $query = DB::table('homeroom_assignments as ha')
            ->join('class_groups as cg', 'cg.id', '=', 'ha.class_group_id')
            ->join('academic_levels as al', 'al.id', '=', 'cg.academic_level_id')
            ->where('ha.staff_profile_id', $profileId)
            ->where('ha.institution_semester_id', $scope['institution_semester_id'])
            ->where('ha.status', 'active')
            ->select(
                'cg.id as class_group_id',
                'cg.name_ar as class_name',
                'cg.operational_period_id',
                'al.name_ar as level_name',
                'ha.is_co_lead',
            );

        // Apply period filter for period-restricted positions.
        if ($periodFilter !== null) {
            $query->whereIn('cg.operational_period_id', $periodFilter);
        }

        $assignments = $query->get();

        if ($assignments->isEmpty()) {
            return collect();
        }

        $classGroupIds = $assignments->pluck('class_group_id')->all();

        // Load today's sheet for each class group (one sheet per class per day).
        $todaySheets = DB::table('student_attendance_sheets')
            ->whereIn('class_group_id', $classGroupIds)
            ->whereDate('attendance_date', $today)
            ->select('id', 'class_group_id', 'status')
            ->get()
            ->keyBy('class_group_id');

        return $assignments->map(function (object $row) use ($todaySheets, $today): object {
            $sheet = $todaySheets->get($row->class_group_id);

            return (object) [
                'class_group_id' => $row->class_group_id,
                'class_name'     => $row->class_name,
                'level_name'     => $row->level_name,
                'is_co_lead'     => (bool) $row->is_co_lead,
                'sheet_id'       => $sheet?->id,
                'sheet_status'   => $sheet?->status,
                'today'          => $today,
            ];
        });
    }

    public function render(): View
    {
        return view('livewire.staff.attendance.my-classes', [
            'classes' => $this->classes(),
        ]);
    }
}

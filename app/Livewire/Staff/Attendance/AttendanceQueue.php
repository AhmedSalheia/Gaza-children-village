<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Attendance;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Authorization\Data\PermissionKey;

/**
 * Secretary queue of attendance sheets pending review.
 *
 * Shows sheets in 'submitted' status (initial teacher submission) and
 * 'reopened' status (previously verified, correction made, awaiting
 * re-verification) within the secretary's operational scope.
 *
 * Period restriction is enforced: secretaries only see sheets for class groups
 * in their explicitly granted operational periods. Full-scope positions
 * (principal, deputy_principal) see all sheets in the semester.
 *
 * Access gated on STUDENT_ATTENDANCE_RETURN.
 */
final class AttendanceQueue extends Component
{
    use HasStaffAuth;

    public function mount(): void
    {
        $this->requirePermission(PermissionKey::STUDENT_ATTENDANCE_RETURN);
    }

    public function pendingSheets(): Collection
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return collect();
        }

        $query = DB::table('student_attendance_sheets as sas')
            ->join('class_groups as cg', 'cg.id', '=', 'sas.class_group_id')
            ->join('academic_levels as al', 'al.id', '=', 'cg.academic_level_id')
            ->where('sas.institution_semester_id', $scope['institution_semester_id'])
            ->whereIn('sas.status', ['submitted', 'reopened'])
            ->select(
                'sas.id',
                'sas.attendance_date',
                'sas.submitted_at',
                'sas.status',
                'cg.name_ar as class_name',
                'al.name_ar as level_name',
                DB::raw('(SELECT COUNT(*) FROM student_attendance_records WHERE sheet_id = sas.id) as total_students'),
            )
            ->orderBy('sas.attendance_date')
            ->orderBy('cg.name_ar');

        // Period restriction for secretary / teacher roles
        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();

            if (empty($allowed)) {
                return collect();
            }

            $query->whereIn('cg.operational_period_id', $allowed);
        }

        return $query->get();
    }

    public function render(): View
    {
        return view('livewire.staff.attendance.queue', [
            'sheets' => $this->pendingSheets(),
        ]);
    }
}

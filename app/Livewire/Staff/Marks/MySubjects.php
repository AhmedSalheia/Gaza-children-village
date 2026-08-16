<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Marks;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Authorization\Data\PermissionKey;

/**
 * Teacher: list of my teaching assignments with their mark sheet status.
 *
 * Shows each class/subject assignment for the current semester, and the
 * status of the associated mark sheet (if any) for each open window.
 *
 * Teachers see only their own assignments (teaching_assignment.staff_profile_id
 * = current staff_profile_id). Secretary/principal see all.
 */
final class MySubjects extends Component
{
    use HasStaffAuth;

    public function mount(): void
    {
        $this->requirePermission(PermissionKey::MARKS_READ);
    }

    public function assignments(): Collection
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return collect();
        }

        $query = DB::table('teaching_assignments as ta')
            ->join('class_groups as cg', 'cg.id', '=', 'ta.class_group_id')
            ->join('institution_subject_offerings as iso', 'iso.id', '=', 'ta.subject_offering_id')
            ->join('subjects as s', 's.id', '=', 'iso.subject_id')
            ->leftJoin('mark_sheets as ms', function ($join): void {
                $join->on('ms.teaching_assignment_id', '=', 'ta.id')
                    ->where('ms.status', '!=', 'superseded');
            })
            ->where('ta.institution_semester_id', $scope['institution_semester_id'])
            ->where('ta.status', 'active');

        // Apply operational-period filter for period-restricted positions
        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();

            if (empty($allowed)) {
                return collect(); // No grants → no access
            }

            $query->whereIn('cg.operational_period_id', $allowed);
        }

        // Teachers see only their own assignments
        if (! $this->staffCan(PermissionKey::MARKS_VERIFY)) {
            $profileId = $this->staffProfileId();
            $query->where('ta.staff_profile_id', $profileId);
        }

        return $query
            ->orderBy('cg.name_ar')
            ->orderBy('s.name_ar')
            ->get([
                'ta.id as assignment_id',
                'ta.staff_profile_id',
                'cg.name_ar as class_name',
                's.name_ar as subject_name',
                's.code as subject_code',
                'ms.id as sheet_id',
                'ms.status as sheet_status',
                'ms.version as sheet_version',
            ]);
    }

    public function openWindows(): Collection
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return collect();
        }

        return DB::table('mark_entry_windows')
            ->where('institution_semester_id', $scope['institution_semester_id'])
            ->whereIn('status', ['open', 'extended'])
            ->where('closes_at', '>', now())
            ->orderBy('closes_at')
            ->get(['id', 'name_ar', 'closes_at', 'class_group_id', 'subject_offering_id']);
    }

    public function render(): View
    {
        return view('livewire.staff.marks.my-subjects', [
            'assignments' => $this->assignments(),
            'openWindows' => $this->openWindows(),
            'canVerify' => $this->staffCan(PermissionKey::MARKS_VERIFY),
            'canApprove' => $this->staffCan(PermissionKey::MARKS_APPROVE),
        ])->layout('layouts.staff');
    }
}

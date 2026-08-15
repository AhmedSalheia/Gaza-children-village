<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Assignments;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Authorization\Data\PermissionKey;

/**
 * Staff portal (principal/deputy only) — assignment overview for the active semester.
 *
 * Shows who teaches what subjects in which class groups, and which teachers
 * serve as homeroom/lead for each class. Read-only for staff portal;
 * mutations are handled through the admin portal.
 *
 * Access is gated on TEACHING_ASSIGNMENT_MANAGE, which the seeder grants only to
 * principal and deputy_principal. Secretary, teacher, and counselor hold only
 * TEACHING_ASSIGNMENT_READ and are blocked at mount().
 */
final class AssignmentOverview extends Component
{
    use HasStaffAuth;

    public string $tab = 'teaching'; // 'teaching' | 'homeroom'

    public function mount(): void
    {
        // MANAGE is granted to principal + deputy_principal only — not secretary,
        // teacher, or counselor — so this gate achieves the full-scope-only intent.
        $this->requirePermission(PermissionKey::TEACHING_ASSIGNMENT_MANAGE);
    }

    public function teachingAssignments(): \Illuminate\Support\Collection
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return collect();
        }

        return DB::table('teaching_assignments as ta')
            ->join('class_groups as cg', 'cg.id', '=', 'ta.class_group_id')
            ->join('academic_levels as al', 'al.id', '=', 'cg.academic_level_id')
            ->join('institution_subject_offerings as iso', 'iso.id', '=', 'ta.subject_offering_id')
            ->join('subjects as sub', 'sub.id', '=', 'iso.subject_id')
            ->join('staff_profiles as spf', 'spf.id', '=', 'ta.staff_profile_id')
            ->join('people as p', 'p.id', '=', 'spf.person_id')
            ->where('ta.institution_semester_id', $scope['institution_semester_id'])
            ->where('ta.status', 'active')
            ->orderBy('cg.name_ar')
            ->orderBy('sub.name_ar')
            ->select(
                'ta.id',
                'ta.starts_on',
                'cg.name_ar as class_group_name',
                'al.name_ar as level_name',
                'sub.name_ar as subject_name',
                'p.full_name_ar as staff_name',
            )
            ->get();
    }

    public function homeroomAssignments(): \Illuminate\Support\Collection
    {
        // Re-assert the homeroom-specific permission so the tab data is independently
        // gated even when switching tabs via Livewire without a full page reload.
        $this->requirePermission(PermissionKey::HOMEROOM_ASSIGNMENT_READ);

        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return collect();
        }

        return DB::table('homeroom_assignments as ha')
            ->join('class_groups as cg', 'cg.id', '=', 'ha.class_group_id')
            ->join('academic_levels as al', 'al.id', '=', 'cg.academic_level_id')
            ->join('staff_profiles as spf', 'spf.id', '=', 'ha.staff_profile_id')
            ->join('people as p', 'p.id', '=', 'spf.person_id')
            ->where('ha.institution_semester_id', $scope['institution_semester_id'])
            ->where('ha.status', 'active')
            ->orderBy('cg.name_ar')
            ->orderBy('ha.is_co_lead')
            ->select(
                'ha.id',
                'ha.is_co_lead',
                'ha.starts_on',
                'cg.name_ar as class_group_name',
                'al.name_ar as level_name',
                'p.full_name_ar as staff_name',
            )
            ->get();
    }

    public function render(): View
    {
        return view('livewire.staff.assignments.overview', [
            'teachingAssignments' => $this->tab === 'teaching' ? $this->teachingAssignments() : collect(),
            'homeroomAssignments' => $this->tab === 'homeroom' ? $this->homeroomAssignments() : collect(),
        ])->layout('layouts.staff');
    }
}

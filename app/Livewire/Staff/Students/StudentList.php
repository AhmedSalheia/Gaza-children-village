<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Students;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Institution-scoped student list for staff.
 *
 * Period restriction (F16 spec):
 *   - Full-scope positions (principal, deputy_principal, counselor): see all
 *     class groups in the semester — isFullScopePosition() returns true.
 *   - Period-restricted positions (secretary, teacher): see only students
 *     enrolled in class groups whose operational_period_id is in their explicit
 *     period grants. No grants → zero results (do not fall through to all).
 *
 * Cross-institution isolation: all queries scoped to institution_semester_id
 * from the staff's active position.
 */
final class StudentList extends Component
{
    use HasStaffAuth;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public int $classGroupFilter = 0;

    #[Url]
    public int $levelFilter = 0;

    public function mount(): void
    {
        if (! $this->staffCan('student.view') && ! $this->staffCan('student.view_restricted')) {
            abort(403);
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedClassGroupFilter(): void
    {
        $this->resetPage();
    }

    public function updatedLevelFilter(): void
    {
        $this->resetPage();
    }

    public function students(): LengthAwarePaginator
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return new LengthAwarePaginator([], 0, 25);
        }

        $query = DB::table('student_enrollments as se')
            ->join('student_profiles as sp', 'sp.id', '=', 'se.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->join('class_groups as cg', 'cg.id', '=', 'se.class_group_id')
            ->join('academic_levels as al', 'al.id', '=', 'cg.academic_level_id')
            ->where('se.institution_semester_id', $scope['institution_semester_id'])
            ->whereNotIn('se.enrollment_status', ['completed', 'withdrawn'])
            ->select(
                'sp.id as student_id',
                'p.full_name_ar as name_ar',
                'p.full_name_en as name_en',
                'sp.student_code',
                'sp.lifecycle_status',
                'se.id as enrollment_id',
                'se.enrollment_status',
                'cg.id as class_group_id',
                'cg.name_ar as class_group_name',
                'al.name_ar as level_name'
            );

        // Period restriction (F16): full-scope positions see all periods;
        // restricted positions (secretary, teacher) see only explicit grants.
        if (! $this->isFullScopePosition()) {
            $allowedPeriods = $this->allowedPeriodIds();

            if (empty($allowedPeriods)) {
                // No explicit period grants → no access.
                return new LengthAwarePaginator([], 0, 25);
            }

            $query->whereIn('cg.operational_period_id', $allowedPeriods);
        }

        if ($this->search !== '') {
            $query->where(fn ($q) => $q
                ->where('p.full_name_ar', 'like', '%'.$this->search.'%')
                ->orWhere('p.full_name_en', 'like', '%'.$this->search.'%')
                ->orWhere('sp.student_code', 'like', '%'.$this->search.'%')
            );
        }

        if ($this->statusFilter !== '') {
            $query->where('se.enrollment_status', $this->statusFilter);
        }

        if ($this->classGroupFilter > 0) {
            $query->where('se.class_group_id', $this->classGroupFilter);
        }

        if ($this->levelFilter > 0) {
            $query->where('cg.academic_level_id', $this->levelFilter);
        }

        return $query->orderBy('p.full_name_ar')->paginate(25);
    }

    public function classGroups(): Collection
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return collect();
        }

        $query = DB::table('class_groups as cg')
            ->join('academic_levels as al', 'al.id', '=', 'cg.academic_level_id')
            ->where('cg.institution_semester_id', $scope['institution_semester_id'])
            ->whereNotIn('cg.lifecycle_status', ['archived']);

        if (! $this->isFullScopePosition()) {
            $allowedPeriods = $this->allowedPeriodIds();

            if (empty($allowedPeriods)) {
                return collect();
            }

            $query->whereIn('cg.operational_period_id', $allowedPeriods);
        }

        return $query->orderBy('al.name_ar')->orderBy('cg.name_ar')->get(['cg.id', 'cg.name_ar', 'al.name_ar as level_name']);
    }

    public function academicLevels(): Collection
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return collect();
        }

        $query = DB::table('class_groups as cg')
            ->join('academic_levels as al', 'al.id', '=', 'cg.academic_level_id')
            ->where('cg.institution_semester_id', $scope['institution_semester_id'])
            ->distinct();

        if (! $this->isFullScopePosition()) {
            $allowedPeriods = $this->allowedPeriodIds();

            if (empty($allowedPeriods)) {
                return collect();
            }

            $query->whereIn('cg.operational_period_id', $allowedPeriods);
        }

        return $query->orderBy('al.name_ar')->get(['al.id', 'al.name_ar']);
    }

    public function render(): View
    {
        return view('livewire.staff.students.list', [
            'students' => $this->students(),
            'classGroups' => $this->classGroups(),
            'academicLevels' => $this->academicLevels(),
            'canCreateStudent' => $this->staffCan('student.create'),
            'canManageEnrollments' => $this->staffCan('enrollment.manage'),
        ])->layout('layouts.staff');
    }
}

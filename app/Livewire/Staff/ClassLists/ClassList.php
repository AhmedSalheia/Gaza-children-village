<?php

declare(strict_types=1);

namespace App\Livewire\Staff\ClassLists;

use App\Exports\ClassStudentsExport;
use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

final class ClassList extends Component
{
    use HasStaffAuth;

    #[Url]
    public int $classGroupId = 0;

    /**
     * Selected academic level / class.
     */
    #[Url]
    public int $academicLevelId = 0;

    public function mount(): void
    {
        $this->requirePermission('enrollment.view');
    }

    /**
     * Get class groups available to the current staff member.
     *
     * A class group represents a section and belongs to an academic level.
     */
    public function classGroups(): Collection
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return collect();
        }

        $query = DB::table('class_groups as cg')
            ->join(
                'academic_levels as al',
                'al.id',
                '=',
                'cg.academic_level_id'
            )
            ->leftJoin(
                'classrooms as cr',
                'cr.id',
                '=',
                'cg.classroom_id'
            )
            ->where(
                'cg.institution_semester_id',
                $scope['institution_semester_id']
            )
            ->whereNotIn(
                'cg.lifecycle_status',
                ['archived']
            );

        /*
         * Filter by selected academic level / class.
         */
        if ($this->academicLevelId > 0) {
            $query->where(
                'cg.academic_level_id',
                $this->academicLevelId
            );
        }

        /*
         * Period restriction:
         * full-scope positions see all class groups;
         * period-restricted positions see only explicit grants.
         */
        if (! $this->isFullScopePosition()) {
            $allowedPeriods = $this->allowedPeriodIds();

            if (empty($allowedPeriods)) {
                return collect();
            }

            $query->whereIn(
                'cg.operational_period_id',
                $allowedPeriods
            );
        }

        return $query
            ->orderBy('al.name_ar')
            ->orderBy('cg.name_ar')
            ->get([
                'cg.id',
                'cg.name_ar',
                'cg.name_en',
                'cg.code',
                'cg.capacity',
                'cg.lifecycle_status',
                'cg.academic_level_id',
                'cg.operational_period_id',
                'al.name_ar as level_name',
                'cr.name_ar as classroom_name',
            ]);
    }

    /**
     * Get available academic levels / classes
     * for the current staff scope.
     */
    public function academicLevels(): Collection
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return collect();
        }

        $query = DB::table('class_groups as cg')
            ->join(
                'academic_levels as al',
                'al.id',
                '=',
                'cg.academic_level_id'
            )
            ->where(
                'cg.institution_semester_id',
                $scope['institution_semester_id']
            )
            ->whereNotIn(
                'cg.lifecycle_status',
                ['archived']
            );

        /*
         * Period restriction.
         */
        if (! $this->isFullScopePosition()) {
            $allowedPeriods = $this->allowedPeriodIds();

            if (empty($allowedPeriods)) {
                return collect();
            }

            $query->whereIn(
                'cg.operational_period_id',
                $allowedPeriods
            );
        }

        return $query
            ->select([
                'al.id',
                'al.name_ar',
                'al.name_en',
            ])
            ->distinct()
            ->orderBy('al.name_ar')
            ->get();
    }

    /**
     * Get sections belonging to the selected class.
     *
     * class_groups = sections in this system.
     */
    public function sections(): Collection
    {
        if ($this->academicLevelId === 0) {
            return collect();
        }

        return $this->classGroups()
            ->where(
                'academic_level_id',
                $this->academicLevelId
            )
            ->values();
    }

    /**
     * Called whenever the class / academic level changes.
     */
    public function updatedAcademicLevelId(): void
    {
        $this->classGroupId = 0;

        /*
         * If a class was selected, automatically select
         * the first available section only when there is
         * exactly one section.
         */
        if ($this->academicLevelId > 0) {
            $sections = $this->sections();

            if ($sections->count() === 1) {
                $this->classGroupId = (int) $sections->first()->id;
            }
        }
    }

    /**
     * Called whenever the section changes.
     */
    public function updatedClassGroupId(): void
    {
        if ($this->classGroupId === 0) {
            return;
        }

        /*
         * Make sure the selected section actually belongs
         * to the selected class when a class filter is active.
         */
        if ($this->academicLevelId > 0) {
            $section = $this->sections()
                ->firstWhere(
                    'id',
                    $this->classGroupId
                );

            if (! $section) {
                $this->classGroupId = 0;
            }
        }
    }

    /**
     * Reset all filters.
     */
    public function resetFilters(): void
    {
        $this->academicLevelId = 0;
        $this->classGroupId = 0;
    }

    /**
     * Get students for the selected class group / section.
     */
    public function classStudents(): Collection
    {
        /*
         * If there is no selected section, don't automatically
         * select the first one.
         */
        if ($this->classGroupId === 0) {
            return collect();
        }

        /*
         * Verify the class group belongs to the staff's scope.
         */
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return collect();
        }

        $classGroup = DB::table('class_groups')
            ->where(
                'id',
                $this->classGroupId
            )
            ->where(
                'institution_semester_id',
                $scope['institution_semester_id']
            )
            ->first();

        if (! $classGroup) {
            return collect();
        }

        /*
         * If an academic level filter is active,
         * verify that the selected section belongs
         * to that academic level.
         */
        if (
            $this->academicLevelId > 0 &&
            (int) $classGroup->academic_level_id !==
                $this->academicLevelId
        ) {
            return collect();
        }

        /*
         * Period restriction.
         */
        if (! $this->isFullScopePosition()) {
            $allowedPeriods = $this->allowedPeriodIds();

            if (
                empty($allowedPeriods) ||
                ! in_array(
                    (int) $classGroup->operational_period_id,
                    $allowedPeriods,
                    true
                )
            ) {
                return collect();
            }
        }

        return DB::table('student_enrollments as se')
            ->join(
                'student_profiles as sp',
                'sp.id',
                '=',
                'se.student_profile_id'
            )
            ->join(
                'people as p',
                'p.id',
                '=',
                'sp.person_id'
            )
            ->where(
                'se.class_group_id',
                $this->classGroupId
            )
            ->whereIn(
                'se.enrollment_status',
                ['active', 'draft']
            )
            ->select(
                'p.national_id as national_id',
                'sp.id as student_id',
                'p.full_name_ar as name_ar',
                'p.full_name_en as name_en',
                'sp.student_code',
                'se.enrollment_status',
                'se.enrolled_on'
            )
            ->orderBy('p.full_name_ar')
            ->get();
    }

    /**
     * Download the selected class group's students as Excel.
     */
    public function downloadExcel()
    {
        $this->requirePermission('enrollment.view');

        if ($this->classGroupId === 0) {
            return;
        }

        $students = $this->classStudents();

        $classGroup = DB::table('class_groups')
            ->find($this->classGroupId);

        if (! $classGroup) {
            return;
        }

        $filename =
            'class-list-' .
            ($classGroup->code ?? $this->classGroupId) .
            '-' .
            now()->format('Y-m-d') .
            '.xlsx';

        return Excel::download(
            new ClassStudentsExport($students),
            $filename
        );
    }

    public function render(): View
    {
        return view(
            'livewire.staff.class-lists.index',
            [
                'classGroups' => $this->classGroups(),
                'academicLevels' => $this->academicLevels(),
                'sections' => $this->sections(),
                'classStudents' => $this->classStudents(),
                'canManageEnrollments' => $this->staffCan(
                    'enrollment.manage'
                ),
            ]
        )->layout('layouts.staff');
    }
}

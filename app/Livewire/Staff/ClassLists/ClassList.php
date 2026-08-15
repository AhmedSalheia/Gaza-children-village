<?php

declare(strict_types=1);

namespace App\Livewire\Staff\ClassLists;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Class list viewer — read-only for all staff roles.
 *
 * Teachers see this as their primary view (enrollment.view permission).
 * Secretaries and principals also see it but can navigate to enrollment
 * management from here.
 *
 * Period-restricted staff see only class groups for their assigned periods.
 */
final class ClassList extends Component
{
    use HasStaffAuth;

    #[Url]
    public int $classGroupId = 0;

    public function mount(): void
    {
        $this->requirePermission('enrollment.view');
    }

    public function classGroups(): Collection
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return collect();
        }

        $query = DB::table('class_groups as cg')
            ->join('academic_levels as al', 'al.id', '=', 'cg.academic_level_id')
            ->leftJoin('classrooms as cr', 'cr.id', '=', 'cg.classroom_id')
            ->where('cg.institution_semester_id', $scope['institution_semester_id'])
            ->whereNotIn('cg.lifecycle_status', ['archived']);

        // Period restriction: full-scope positions see all class groups;
        // period-restricted positions (secretary, teacher) see only explicit grants.
        if (! $this->isFullScopePosition()) {
            $allowedPeriods = $this->allowedPeriodIds();

            if (empty($allowedPeriods)) {
                return collect();
            }

            $query->whereIn('cg.operational_period_id', $allowedPeriods);
        }

        return $query->orderBy('al.name_ar')->orderBy('cg.name_ar')
            ->get(['cg.id', 'cg.name_ar', 'cg.code', 'cg.capacity', 'cg.lifecycle_status',
                'al.name_ar as level_name', 'cr.name_ar as classroom_name']);
    }

    public function classStudents(): Collection
    {
        if ($this->classGroupId === 0) {
            return collect();
        }

        // Verify the class group belongs to the staff's scope.
        $scope = $this->staffScope();

        $classGroup = DB::table('class_groups')
            ->where('id', $this->classGroupId)
            ->where('institution_semester_id', $scope['institution_semester_id'])
            ->first();

        if (! $classGroup) {
            return collect();
        }

        // Period restriction: verify this class group's period is accessible.
        if (! $this->isFullScopePosition()) {
            $allowedPeriods = $this->allowedPeriodIds();

            if (empty($allowedPeriods) || ! in_array((int) $classGroup->operational_period_id, $allowedPeriods, true)) {
                return collect();
            }
        }

        return DB::table('student_enrollments as se')
            ->join('student_profiles as sp', 'sp.id', '=', 'se.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->where('se.class_group_id', $this->classGroupId)
            ->whereIn('se.enrollment_status', ['active', 'draft'])
            ->select(
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

    public function downloadCsv(): void
    {
        $this->requirePermission('enrollment.view');

        if ($this->classGroupId === 0) {
            return;
        }

        $students = $this->classStudents();
        $classGroup = DB::table('class_groups')->find($this->classGroupId);

        $filename = 'class-list-'.($classGroup?->code ?? $this->classGroupId).'-'.now()->format('Y-m-d').'.csv';

        $csvLines = ['Student Code,Name (Arabic),Name (English),Status,Enrolled On'];

        foreach ($students as $s) {
            $csvLines[] = implode(',', [
                '"'.($s->student_code ?? '').'"',
                '"'.($s->name_ar ?? '').'"',
                '"'.($s->name_en ?? '').'"',
                '"'.($s->enrollment_status ?? '').'"',
                '"'.($s->enrolled_on ?? '').'"',
            ]);
        }

        $this->stream(
            content: implode("\n", $csvLines),
            headers: [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
    }

    public function render(): View
    {
        return view('livewire.staff.class-lists.index', [
            'classGroups' => $this->classGroups(),
            'classStudents' => $this->classStudents(),
            'canManageEnrollments' => $this->staffCan('enrollment.manage'),
        ])->layout('layouts.staff');
    }
}

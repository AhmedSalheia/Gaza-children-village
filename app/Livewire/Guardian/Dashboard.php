<?php

declare(strict_types=1);

namespace App\Livewire\Guardian;

use App\Livewire\Guardian\Concerns\HasGuardianAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Guardian portal dashboard — child selector.
 *
 * Displays all students connected to the authenticated guardian through
 * active, verified, portal-eligible relationships. Each child is shown as
 * a selectable card linking to their detail page.
 *
 * Access: every authenticated guardian. No student data is shown until
 * portal-eligible relationships exist; an informative empty state is
 * displayed instead (does not reveal whether any student exists).
 */
final class Dashboard extends Component
{
    use HasGuardianAuth;

    public function eligibleChildren(): Collection
    {
        $studentIds = $this->eligibleStudentIds();

        if (empty($studentIds)) {
            return collect();
        }

        return DB::table('student_profiles as sp')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->whereIn('sp.id', $studentIds)
            ->select(
                'sp.id',
                'sp.student_code',
                'sp.lifecycle_status',
                'p.full_name_ar',
                'p.full_name_en'
            )
            ->orderBy('p.full_name_ar')
            ->get();
    }

    /**
     * Return a summary of the current active placement for each eligible student.
     * Keyed by student_profile_id.
     *
     * @return array<int, object>
     */
    public function placementSummaries(): array
    {
        $studentIds = $this->eligibleStudentIds();

        if (empty($studentIds)) {
            return [];
        }

        $rows = DB::table('student_enrollments as se')
            ->join('class_groups as cg', 'cg.id', '=', 'se.class_group_id')
            ->join('academic_levels as al', 'al.id', '=', 'cg.academic_level_id')
            ->join('institution_semesters as is2', 'is2.id', '=', 'se.institution_semester_id')
            ->join('institutions as i', 'i.id', '=', 'is2.institution_id')
            ->whereIn('se.student_profile_id', $studentIds)
            ->where('se.enrollment_status', 'active')
            ->select(
                'se.student_profile_id',
                'al.name_ar as level_name',
                'cg.name_ar as class_group_name',
                'i.name_ar as institution_name'
            )
            ->get();

        $result = [];

        foreach ($rows as $row) {
            $result[(int) $row->student_profile_id] = $row;
        }

        return $result;
    }

    public function render(): View
    {
        $hasProfile = $this->hasGuardianProfile();
        $children = $hasProfile ? $this->eligibleChildren() : collect();
        $placements = $hasProfile ? $this->placementSummaries() : [];

        return view('livewire.guardian.dashboard', [
            'hasProfile' => $hasProfile,
            'children' => $children,
            'placements' => $placements,
            'hasChildren' => $children->isNotEmpty(),
        ])->layout('layouts.guardian');
    }
}

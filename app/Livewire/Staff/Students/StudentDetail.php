<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Students;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Student profile detail view for staff.
 *
 * Access:
 *   - student.view             → full profile (secretary, principal, counselor)
 *   - student.view_restricted  → basic profile (teacher)
 *
 * Both permissions are checked at mount(). Cross-institution and period
 * isolation are enforced via assertStudentAccessible() for ALL users —
 * secretaries included. A secretary with student.view but no period grants
 * cannot access any student detail.
 *
 * Sensitive welfare fields are visible only with person.view_sensitive.
 */
final class StudentDetail extends Component
{
    use HasStaffAuth;

    /** @var int Route-bound student profile ID; locked against browser mutation. */
    #[Locked]
    public int $studentProfileId;

    public function mount(int $studentProfileId): void
    {
        if (! $this->staffCan('student.view') && ! $this->staffCan('student.view_restricted')) {
            abort(403);
        }

        $this->studentProfileId = $studentProfileId;

        // assertStudentAccessible applies period restriction for ALL positions
        // (not only teacher). A secretary with no period grants cannot view
        // student detail even though they hold student.view.
        $this->assertStudentAccessible($this->studentProfileId);
    }

    public function student(): ?object
    {
        return DB::table('student_profiles as sp')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->where('sp.id', $this->studentProfileId)
            ->select(
                'sp.id',
                'sp.student_code',
                'sp.lifecycle_status',
                'sp.registered_on',
                'p.full_name_ar',
                'p.full_name_en',
                'p.birth_date',
                'p.birth_date_precision',
                'p.gender'
            )
            ->first();
    }

    public function sensitiveData(): ?object
    {
        if (! $this->staffCan('person.view_sensitive')) {
            return null;
        }

        return DB::table('student_profiles as sp')
            ->where('sp.id', $this->studentProfileId)
            ->select(
                'sp.orphan_status',
                'sp.displacement_status',
                'sp.evidence_status'
            )
            ->first();
    }

    public function enrollmentHistory(): Collection
    {
        $scope = $this->staffScope();

        if ($scope['institution_id'] === null) {
            return collect();
        }

        // Enrollment history is scoped to the staff member's institution only —
        // a secretary cannot see enrollments the student had at other institutions.
        $query = DB::table('student_enrollments as se')
            ->join('class_groups as cg', 'cg.id', '=', 'se.class_group_id')
            ->join('institution_semesters as is2', 'is2.id', '=', 'se.institution_semester_id')
            ->join('semesters as s', 's.id', '=', 'is2.semester_id')
            ->join('academic_levels as al', 'al.id', '=', 'cg.academic_level_id')
            ->where('se.student_profile_id', $this->studentProfileId)
            ->where('is2.institution_id', $scope['institution_id'])
            ->select(
                'se.id',
                'se.enrollment_status',
                'se.enrolled_on',
                'se.activated_on',
                'se.completed_on',
                'cg.name_ar as class_group_name',
                'al.name_ar as level_name',
                's.name_ar as semester_name'
            );

        // Period-restricted positions see only records within their granted
        // periods. Full-scope positions (principal, counselor) see all history
        // within the institution.
        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();

            if (empty($allowed)) {
                return collect();
            }

            $query->whereIn('cg.operational_period_id', $allowed);
        }

        return $query->orderByDesc('se.enrolled_on')->get();
    }

    public function guardianRelationships(): Collection
    {
        if (! $this->staffCan('guardian_relationship.view')) {
            return collect();
        }

        return DB::table('guardian_student_relationships as gsr')
            ->join('guardian_profiles as gp', 'gp.id', '=', 'gsr.guardian_profile_id')
            ->join('people as p', 'p.id', '=', 'gp.person_id')
            ->where('gsr.student_profile_id', $this->studentProfileId)
            ->select(
                'gsr.id',
                'gsr.relationship_type',
                'gsr.verification_status',
                'gsr.portal_eligible',
                'gsr.legal_authority',
                'gsr.ends_on',
                'p.full_name_ar as guardian_name'
            )
            ->orderBy('gsr.created_at')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.staff.students.detail', [
            'student' => $this->student(),
            'sensitiveData' => $this->sensitiveData(),
            'enrollmentHistory' => $this->enrollmentHistory(),
            'guardianRelationships' => $this->guardianRelationships(),
            'canViewSensitive' => $this->staffCan('person.view_sensitive'),
            'canManageRelationships' => $this->staffCan('guardian_relationship.manage'),
            'canManageEnrollments' => $this->staffCan('enrollment.manage'),
            'canTransfer' => $this->staffCan('enrollment.transfer'),
        ])->layout('layouts.staff');
    }
}

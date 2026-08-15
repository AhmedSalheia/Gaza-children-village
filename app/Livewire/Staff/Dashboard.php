<?php

declare(strict_types=1);

namespace App\Livewire\Staff;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Staff portal landing dashboard.
 *
 * Shows the staff member's institution and current semester at a glance,
 * with counts of students, pending enrollments, pending promotions, and
 * active imports — all scoped to the staff's active position.
 *
 * Access: every authenticated staff member with an active position.
 * No specific permission beyond auth:staff is required for the dashboard
 * itself; each metric section is gated by its corresponding permission.
 */
final class Dashboard extends Component
{
    use HasStaffAuth;

    public function mount(): void
    {
        if (auth('staff')->guest()) {
            abort(403);
        }
    }

    public function institutionInfo(): ?object
    {
        $scope = $this->staffScope();

        if ($scope['institution_id'] === null) {
            return null;
        }

        return DB::table('institutions')
            ->where('id', $scope['institution_id'])
            ->select('id', 'name_ar', 'name_en', 'is_active')
            ->first();
    }

    public function semesterInfo(): ?object
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return null;
        }

        return DB::table('institution_semesters as is')
            ->join('semesters as s', 's.id', '=', 'is.semester_id')
            ->where('is.id', $scope['institution_semester_id'])
            ->select('is.id', 's.name_ar as semester_name', 'is.status', 's.starts_on', 's.ends_on')
            ->first();
    }

    public function studentCount(): int
    {
        if (! $this->staffCan('student.view') && ! $this->staffCan('student.view_restricted')) {
            return 0;
        }

        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return 0;
        }

        $query = DB::table('student_enrollments as se')
            ->join('class_groups as cg', 'cg.id', '=', 'se.class_group_id')
            ->where('se.institution_semester_id', $scope['institution_semester_id'])
            ->whereIn('se.enrollment_status', ['active', 'draft']);

        // Period restriction: full-scope positions (principal, deputy_principal, counselor)
        // see the institution total; restricted positions see only their granted periods.
        // Zero grants → zero (consistent with list/detail components).
        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();

            if (empty($allowed)) {
                return 0;
            }

            $query->whereIn('cg.operational_period_id', $allowed);
        }

        return (int) $query->distinct('se.student_profile_id')->count('se.student_profile_id');
    }

    public function pendingEnrollmentCount(): int
    {
        if (! $this->staffCan('enrollment.view')) {
            return 0;
        }

        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return 0;
        }

        $query = DB::table('student_enrollments as se')
            ->join('class_groups as cg', 'cg.id', '=', 'se.class_group_id')
            ->where('se.institution_semester_id', $scope['institution_semester_id'])
            ->where('se.enrollment_status', 'draft');

        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();

            if (empty($allowed)) {
                return 0;
            }

            $query->whereIn('cg.operational_period_id', $allowed);
        }

        return (int) $query->count();
    }

    public function pendingPromotionCount(): int
    {
        if (! $this->staffCan('enrollment.promote')) {
            return 0;
        }

        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return 0;
        }

        return (int) DB::table('promotion_proposals as pp')
            ->join('student_enrollments as se', 'se.id', '=', 'pp.source_enrollment_id')
            ->where('se.institution_semester_id', $scope['institution_semester_id'])
            ->where('pp.review_status', 'pending')
            ->count();
    }

    public function activeImportCount(): int
    {
        if (! $this->staffCan('import.review')) {
            return 0;
        }

        $scope = $this->staffScope();

        if ($scope['institution_id'] === null) {
            return 0;
        }

        return (int) DB::table('import_batches')
            ->where('institution_id', $scope['institution_id'])
            ->whereNotIn('status', ['completed', 'completed_with_errors', 'cancelled'])
            ->count();
    }

    public function recentImports(): Collection
    {
        if (! $this->staffCan('import.review')) {
            return collect();
        }

        $scope = $this->staffScope();

        if ($scope['institution_id'] === null) {
            return collect();
        }

        return DB::table('import_batches')
            ->where('institution_id', $scope['institution_id'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'status', 'original_filename', 'created_at']);
    }

    public function render(): View
    {
        $scope = $this->staffScope();

        return view('livewire.staff.dashboard', [
            'institutionInfo' => $this->institutionInfo(),
            'semesterInfo' => $this->semesterInfo(),
            'hasScope' => $scope['institution_id'] !== null,
            'studentCount' => $this->studentCount(),
            'pendingEnrollmentCount' => $this->pendingEnrollmentCount(),
            'pendingPromotionCount' => $this->pendingPromotionCount(),
            'activeImportCount' => $this->activeImportCount(),
            'recentImports' => $this->recentImports(),
            'canViewStudents' => $this->staffCan('student.view') || $this->staffCan('student.view_restricted'),
            'canViewEnrollments' => $this->staffCan('enrollment.view'),
            'canManageEnrollments' => $this->staffCan('enrollment.manage'),
            'canPromote' => $this->staffCan('enrollment.promote'),
            'canViewImports' => $this->staffCan('import.review'),
            'canUploadImports' => $this->staffCan('import.upload'),
            'canCreateStudent' => $this->staffCan('student.create'),
        ])->layout('layouts.staff');
    }
}

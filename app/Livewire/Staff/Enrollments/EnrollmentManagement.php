<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Enrollments;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\AcademicManagement\Actions\ActivateEnrollment;
use Modules\AcademicManagement\Actions\ChangeDraftPlacement;
use Modules\AcademicManagement\Actions\CreateDraftEnrollment;
use Modules\AcademicManagement\Actions\SuspendEnrollment;
use Modules\AcademicManagement\Actions\WithdrawEnrollment;

/**
 * Enrollment management for secretary and principal.
 *
 * Supports: create draft enrollment, change placement, activate draft,
 * withdraw, and suspend. All mutations go through domain Actions.
 *
 * Authorization: enrollment.manage permission required. Every mutation
 * calls assertClassGroupInScope() or assertEnrollmentInScope() before
 * proceeding — Livewire actions are public HTTP endpoints; UI filtering
 * alone does not prevent out-of-scope submissions.
 *
 * Period restriction: isFullScopePosition() + allowedPeriodIds() govern
 * listing queries; assertEnrollmentInScope/assertClassGroupInScope enforce
 * the same restriction on writes.
 */
final class EnrollmentManagement extends Component
{
    use HasStaffAuth;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    // Create enrollment form
    public bool $showCreateForm = false;

    public int $createStudentId = 0;

    public int $createClassGroupId = 0;

    public string $createEnrolledOn = '';

    public string $createNotes = '';

    // Change placement
    public ?int $changingEnrollmentId = null;

    public int $newClassGroupId = 0;

    // Withdraw/suspend
    public ?int $withdrawingEnrollmentId = null;

    public ?int $suspendingEnrollmentId = null;

    public string $actionNotes = '';

    public function mount(): void
    {
        $this->requirePermission('enrollment.manage');
        $this->createEnrolledOn = now()->toDateString();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    // ── Listing queries ───────────────────────────────────────────────────

    public function enrollments(): LengthAwarePaginator
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
            ->select(
                'se.id',
                'se.enrollment_status',
                'se.enrolled_on',
                'se.activated_on',
                'se.notes',
                'sp.id as student_id',
                'sp.student_code',
                'p.full_name_ar as student_name',
                'cg.id as class_group_id',
                'cg.name_ar as class_group_name',
                'al.name_ar as level_name'
            );

        if (! $this->isFullScopePosition()) {
            $allowedPeriods = $this->allowedPeriodIds();

            if (empty($allowedPeriods)) {
                return new LengthAwarePaginator([], 0, 25);
            }

            $query->whereIn('cg.operational_period_id', $allowedPeriods);
        }

        if ($this->search !== '') {
            $query->where(fn ($q) => $q
                ->where('p.full_name_ar', 'like', '%'.$this->search.'%')
                ->orWhere('sp.student_code', 'like', '%'.$this->search.'%')
            );
        }

        if ($this->statusFilter !== '') {
            $query->where('se.enrollment_status', $this->statusFilter);
        }

        return $query->orderBy('se.enrollment_status')->orderBy('p.full_name_ar')->paginate(25);
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
            ->where('cg.lifecycle_status', 'active');

        if (! $this->isFullScopePosition()) {
            $allowedPeriods = $this->allowedPeriodIds();

            if (empty($allowedPeriods)) {
                return collect();
            }

            $query->whereIn('cg.operational_period_id', $allowedPeriods);
        }

        return $query->orderBy('al.name_ar')->orderBy('cg.name_ar')
            ->get(['cg.id', 'cg.name_ar', 'al.name_ar as level_name']);
    }

    // ── Mutations ─────────────────────────────────────────────────────────

    public function createDraftEnrollment(CreateDraftEnrollment $action): void
    {
        $this->requirePermission('enrollment.manage');

        $this->validate([
            'createStudentId' => ['required', 'integer', 'min:1'],
            'createClassGroupId' => ['required', 'integer', 'min:1'],
            'createEnrolledOn' => ['required', 'date'],
        ]);

        $scope = $this->staffScope();
        abort_if($scope['institution_semester_id'] === null, 422, 'No active semester scope.');

        // Guard class group against period + institution scope.
        $this->assertClassGroupInScope($this->createClassGroupId);

        $classGroupClass = 'Modules\\AcademicManagement\\Models\\ClassGroup';
        $classGroup = $classGroupClass::findOrFail($this->createClassGroupId);

        try {
            $action(
                studentProfileId: $this->createStudentId,
                institutionSemesterId: $scope['institution_semester_id'],
                classGroup: $classGroup,
                enrolledOn: new \DateTime($this->createEnrolledOn),
                notes: $this->createNotes ?: null,
            );

            session()->flash('success', __('ui.enrollment_created', [], null, 'Draft enrollment created.'));
            $this->showCreateForm = false;
            $this->reset(['createStudentId', 'createClassGroupId', 'createNotes']);
            $this->createEnrolledOn = now()->toDateString();
        } catch (\Throwable $e) {
            $this->addError('createEnrollment', $e->getMessage());
        }
    }

    public function startChangePlacement(int $enrollmentId): void
    {
        // Guard before exposing the change-placement form; prevents probing.
        $this->assertEnrollmentInScope($enrollmentId);
        $this->changingEnrollmentId = $enrollmentId;
        $this->newClassGroupId = 0;
    }

    public function changePlacement(ChangeDraftPlacement $action): void
    {
        $this->requirePermission('enrollment.manage');
        abort_if($this->changingEnrollmentId === null, 400);

        $this->validate(['newClassGroupId' => ['required', 'integer', 'min:1']]);

        // Guard both the source enrollment and target class group.
        $this->assertEnrollmentInScope($this->changingEnrollmentId);
        $this->assertClassGroupInScope($this->newClassGroupId);

        $enrollmentClass = 'Modules\\AcademicManagement\\Models\\StudentEnrollment';
        $classGroupClass = 'Modules\\AcademicManagement\\Models\\ClassGroup';

        $enrollment = $enrollmentClass::findOrFail($this->changingEnrollmentId);
        $newGroup = $classGroupClass::findOrFail($this->newClassGroupId);

        try {
            $action($enrollment, $newGroup);
            session()->flash('success', __('ui.placement_changed', [], null, 'Placement updated.'));
            $this->changingEnrollmentId = null;
        } catch (\Throwable $e) {
            $this->addError('changePlacement', $e->getMessage());
        }
    }

    public function activate(int $enrollmentId, ActivateEnrollment $action): void
    {
        $this->requirePermission('enrollment.manage');
        $this->assertEnrollmentInScope($enrollmentId);

        $enrollmentClass = 'Modules\\AcademicManagement\\Models\\StudentEnrollment';
        $enrollment = $enrollmentClass::findOrFail($enrollmentId);

        try {
            $action($enrollment, now());
            session()->flash('success', __('ui.enrollment_activated', [], null, 'Enrollment activated.'));
        } catch (\Throwable $e) {
            $this->addError('activate_'.$enrollmentId, $e->getMessage());
        }
    }

    public function withdraw(int $enrollmentId, WithdrawEnrollment $action): void
    {
        $this->requirePermission('enrollment.manage');
        $this->assertEnrollmentInScope($enrollmentId);

        $enrollmentClass = 'Modules\\AcademicManagement\\Models\\StudentEnrollment';
        $enrollment = $enrollmentClass::findOrFail($enrollmentId);

        try {
            $action($enrollment, $this->actionNotes ?: null);
            session()->flash('success', __('ui.enrollment_withdrawn', [], null, 'Enrollment withdrawn.'));
            $this->withdrawingEnrollmentId = null;
            $this->actionNotes = '';
        } catch (\Throwable $e) {
            $this->addError('withdraw', $e->getMessage());
        }
    }

    public function suspend(int $enrollmentId, SuspendEnrollment $action): void
    {
        $this->requirePermission('enrollment.manage');
        $this->assertEnrollmentInScope($enrollmentId);

        $enrollmentClass = 'Modules\\AcademicManagement\\Models\\StudentEnrollment';
        $enrollment = $enrollmentClass::findOrFail($enrollmentId);

        try {
            $action($enrollment, $this->actionNotes ?: null);
            session()->flash('success', __('ui.enrollment_suspended', [], null, 'Enrollment suspended.'));
            $this->suspendingEnrollmentId = null;
            $this->actionNotes = '';
        } catch (\Throwable $e) {
            $this->addError('suspend', $e->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.staff.enrollments.management', [
            'enrollments' => $this->enrollments(),
            'classGroups' => $this->classGroups(),
            'canTransfer' => $this->staffCan('enrollment.transfer'),
        ])->layout('layouts.staff');
    }
}

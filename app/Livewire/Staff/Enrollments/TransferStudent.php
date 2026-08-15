<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Enrollments;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\AcademicManagement\Actions\TransferStudent as TransferStudentAction;

/**
 * Two-step student transfer flow for staff.
 *
 * Step 1: confirm the student is accessible (assertStudentAccessible) and
 *         select a target class group.
 * Step 2: provide enrollment date / reason and confirm the transfer.
 *
 * ── Transfer scope ────────────────────────────────────────────────────────
 * Available target class groups are restricted to:
 *   (a) The staff member's own institution (institution_id from trusted position).
 *   (b) Semesters at that institution with status 'open' or 'active'.
 *   (c) For period-restricted positions: class groups whose operational_period_id
 *       is within the allowed period grants.
 *
 * confirmTransfer() re-validates the submitted target ID against the same
 * constraints before invoking the domain action, so a forged wire-call
 * submitting a foreign institution's class group ID is rejected server-side.
 *
 * Requires enrollment.transfer permission.
 */
final class TransferStudent extends Component
{
    use HasStaffAuth;

    /** @var int Route-bound; locked against browser mutation. */
    #[Locked]
    public int $studentProfileId;

    public int $step = 1;

    public int $targetClassGroupId = 0;

    public string $enrolledOn = '';

    public string $transferNotes = '';

    public bool $capacityOverride = false;

    public string $capacityOverrideReason = '';

    public function mount(int $studentProfileId): void
    {
        $this->requirePermission('enrollment.transfer');
        $this->studentProfileId = $studentProfileId;
        $this->assertStudentAccessible($this->studentProfileId);
        $this->enrolledOn = now()->toDateString();
    }

    public function student(): ?object
    {
        return DB::table('student_profiles as sp')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->where('sp.id', $this->studentProfileId)
            ->select('sp.id', 'p.full_name_ar')
            ->first();
    }

    public function currentEnrollment(): ?object
    {
        $scope = $this->staffScope();

        return DB::table('student_enrollments as se')
            ->join('class_groups as cg', 'cg.id', '=', 'se.class_group_id')
            ->join('academic_levels as al', 'al.id', '=', 'cg.academic_level_id')
            ->join('institution_semesters as is2', 'is2.id', '=', 'se.institution_semester_id')
            ->join('semesters as s', 's.id', '=', 'is2.semester_id')
            ->where('se.student_profile_id', $this->studentProfileId)
            ->where('se.institution_semester_id', $scope['institution_semester_id'])
            ->whereIn('se.enrollment_status', ['active', 'draft'])
            ->select(
                'se.id',
                'cg.name_ar as class_group_name',
                'al.name_ar as level_name',
                's.name_ar as semester_name',
                'se.enrollment_status'
            )
            ->first();
    }

    /**
     * Available target class groups — restricted to the staff member's own
     * institution and open/active semesters only.
     *
     * Period-restricted positions additionally see only class groups within
     * their allowed operational periods. This prevents selecting a target
     * outside the staff member's authorized scope.
     */
    public function availableGroups(): Collection
    {
        $scope = $this->staffScope();

        if ($scope['institution_id'] === null) {
            return collect();
        }

        $query = DB::table('class_groups as cg')
            ->join('institution_semesters as is2', 'is2.id', '=', 'cg.institution_semester_id')
            ->join('semesters as s', 's.id', '=', 'is2.semester_id')
            ->join('academic_levels as al', 'al.id', '=', 'cg.academic_level_id')
            ->where('is2.institution_id', $scope['institution_id'])
            ->where('cg.lifecycle_status', 'active')
            ->whereIn('is2.status', ['open', 'active'])
            ->orderBy('s.name_ar')
            ->orderBy('al.name_ar');

        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();

            if (empty($allowed)) {
                return collect();
            }

            $query->whereIn('cg.operational_period_id', $allowed);
        }

        return $query->get([
            'cg.id',
            'cg.name_ar',
            'al.name_ar as level_name',
            's.name_ar as semester_name',
        ]);
    }

    /**
     * Assert the submitted target class group ID is within the staff member's
     * authorized scope: same institution AND allowed period (if restricted).
     * Aborts 403 on any violation — never rely on availableGroups() filter alone.
     */
    private function assertTargetGroupInScope(int $classGroupId): void
    {
        $scope = $this->staffScope();

        if ($scope['institution_id'] === null) {
            abort(403, 'No institutional scope for your account.');
        }

        $cg = DB::table('class_groups as cg')
            ->join('institution_semesters as is2', 'is2.id', '=', 'cg.institution_semester_id')
            ->where('cg.id', $classGroupId)
            ->where('is2.institution_id', $scope['institution_id'])
            ->whereIn('is2.status', ['open', 'active'])
            ->select('cg.id', 'cg.operational_period_id')
            ->first();

        if (! $cg) {
            abort(403, 'Target class group is not in your authorized institution or semester.');
        }

        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();

            if (empty($allowed) || ! in_array((int) $cg->operational_period_id, $allowed, true)) {
                abort(403, 'Target class group is not in your authorized operational period.');
            }
        }
    }

    public function selectTarget(): void
    {
        $this->validate(['targetClassGroupId' => ['required', 'integer', 'min:1']]);
        // Early validation before step 2 — reject out-of-scope selections.
        $this->assertTargetGroupInScope($this->targetClassGroupId);
        $this->step = 2;
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function confirmTransfer(TransferStudentAction $action): void
    {
        $this->requirePermission('enrollment.transfer');

        // Re-assert source student scope on mutation.
        $this->assertStudentAccessible($this->studentProfileId);

        $this->validate([
            'targetClassGroupId' => ['required', 'integer', 'min:1'],
            'enrolledOn' => ['required', 'date'],
            'capacityOverrideReason' => $this->capacityOverride
                ? ['required', 'string', 'min:5']
                : ['nullable'],
        ]);

        // Re-assert target class group scope before invoking action — the
        // domain action has no UI actor authorization.
        $this->assertTargetGroupInScope($this->targetClassGroupId);

        $classGroupClass = 'Modules\\AcademicManagement\\Models\\ClassGroup';
        $targetGroup = $classGroupClass::findOrFail($this->targetClassGroupId);

        $currentEnrollment = $this->currentEnrollment();
        abort_if($currentEnrollment === null, 422, 'No active enrollment to transfer from.');

        try {
            $action(
                studentProfileId: $this->studentProfileId,
                targetClassGroup: $targetGroup,
                enrolledOn: new \DateTime($this->enrolledOn),
                transferNotes: $this->transferNotes ?: null,
                capacityOverride: $this->capacityOverride,
                capacityOverrideReason: $this->capacityOverride ? $this->capacityOverrideReason : null,
            );

            session()->flash('success', __('ui.transfer_completed', [], null, 'Transfer completed successfully.'));
            $this->redirectRoute('staff.students.index');
        } catch (\Throwable $e) {
            $this->addError('transfer', $e->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.staff.enrollments.transfer', [
            'student' => $this->student(),
            'currentEnrollment' => $this->currentEnrollment(),
            'availableGroups' => $this->availableGroups(),
        ])->layout('layouts.staff');
    }
}

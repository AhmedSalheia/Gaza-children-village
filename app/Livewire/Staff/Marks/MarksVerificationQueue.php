<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Marks;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Modules\AcademicManagement\Actions\ApproveMarkSheet;
use Modules\AcademicManagement\Actions\ReturnMarkSheet;
use Modules\AcademicManagement\Actions\VerifyMarkSheet;
use Modules\AcademicManagement\Exceptions\MarksException;
use Modules\AcademicManagement\Models\MarkSheet;
use Modules\Authorization\Data\PermissionKey;

/**
 * Secretary: mark-sheet verification queue.
 * Principal/Deputy: mark-sheet approval queue.
 *
 * Combines both queues in one component; display is permission-gated.
 */
final class MarksVerificationQueue extends Component
{
    use HasStaffAuth;

    public string $returnReason = '';
    public int    $returningId  = 0;
    public string $flashMessage = '';
    public string $flashType    = '';

    public function mount(): void
    {
        $this->requirePermission(PermissionKey::MARKS_VERIFY);
    }

    public function submittedSheets(): Collection
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return collect();
        }

        $query = DB::table('mark_sheets as ms')
            ->join('class_groups as cg', 'cg.id', '=', 'ms.class_group_id')
            ->join('institution_subject_offerings as iso', 'iso.id', '=', 'ms.subject_offering_id')
            ->join('subjects as s', 's.id', '=', 'iso.subject_id')
            ->join('teaching_assignments as ta', 'ta.id', '=', 'ms.teaching_assignment_id')
            ->join('staff_profiles as sp', 'sp.id', '=', 'ta.staff_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->where('ms.institution_semester_id', $scope['institution_semester_id'])
            ->where('ms.status', 'submitted')
            ->orderBy('ms.submitted_at');

        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();

            if (empty($allowed)) {
                return collect();
            }

            $query->whereIn('cg.operational_period_id', $allowed);
        }

        return $query->get([
            'ms.id', 'ms.submitted_at', 'ms.return_reason',
            'cg.name_ar as class_name',
            's.name_ar as subject_name',
            'p.full_name_ar as teacher_name',
        ]);
    }

    public function verifiedSheets(): Collection
    {
        $scope = $this->staffScope();

        if (! $this->staffCan(PermissionKey::MARKS_APPROVE) || $scope['institution_semester_id'] === null) {
            return collect();
        }

        $query = DB::table('mark_sheets as ms')
            ->join('class_groups as cg', 'cg.id', '=', 'ms.class_group_id')
            ->join('institution_subject_offerings as iso', 'iso.id', '=', 'ms.subject_offering_id')
            ->join('subjects as s', 's.id', '=', 'iso.subject_id')
            ->join('teaching_assignments as ta', 'ta.id', '=', 'ms.teaching_assignment_id')
            ->join('staff_profiles as sp', 'sp.id', '=', 'ta.staff_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->where('ms.institution_semester_id', $scope['institution_semester_id'])
            ->where('ms.status', 'verified')
            ->orderBy('ms.verified_at');

        // Principals are full-scope; no period filter needed.
        // If somehow a non-full-scope role has MARKS_APPROVE, still apply period guard.
        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();

            if (empty($allowed)) {
                return collect();
            }

            $query->whereIn('cg.operational_period_id', $allowed);
        }

        return $query->get([
            'ms.id', 'ms.verified_at',
            'cg.name_ar as class_name',
            's.name_ar as subject_name',
            'p.full_name_ar as teacher_name',
        ]);
    }

    public function verify(int $sheetId): void
    {
        $this->requirePermission(PermissionKey::MARKS_VERIFY);

        $sheet = $this->loadSheetInScope($sheetId);

        try {
            app(VerifyMarkSheet::class)($sheet, (int) $this->staffProfileId());
            $this->flash('Sheet verified.', 'success');
        } catch (MarksException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function approve(int $sheetId): void
    {
        $this->requirePermission(PermissionKey::MARKS_APPROVE);

        $sheet = $this->loadSheetInScope($sheetId);

        try {
            app(ApproveMarkSheet::class)($sheet, (int) $this->staffProfileId());
            $this->flash('Sheet approved.', 'success');
        } catch (MarksException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function startReturn(int $sheetId): void
    {
        $this->requirePermission(PermissionKey::MARKS_RETURN);
        $this->returningId  = $sheetId;
        $this->returnReason = '';
    }

    public function confirmReturn(): void
    {
        $this->requirePermission(PermissionKey::MARKS_RETURN);

        if ($this->returningId === 0) {
            return;
        }

        $sheet = $this->loadSheetInScope($this->returningId);

        try {
            app(ReturnMarkSheet::class)($sheet, $this->returnReason, (int) $this->staffProfileId());
            $this->returningId  = 0;
            $this->returnReason = '';
            $this->flash('Sheet returned to teacher.', 'success');
        } catch (MarksException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function cancelReturn(): void
    {
        $this->returningId  = 0;
        $this->returnReason = '';
    }

    public function render(): View
    {
        return view('livewire.staff.marks.verification-queue', [
            'submittedSheets' => $this->submittedSheets(),
            'verifiedSheets'  => $this->verifiedSheets(),
            'canApprove'      => $this->staffCan(PermissionKey::MARKS_APPROVE),
            'canReturn'       => $this->staffCan(PermissionKey::MARKS_RETURN),
        ])->layout('layouts.staff');
    }

    private function loadSheetInScope(int $sheetId): MarkSheet
    {
        $scope = $this->staffScope();

        // Join class_groups to resolve operational_period_id for period guard
        $row = DB::table('mark_sheets as ms')
            ->join('class_groups as cg', 'cg.id', '=', 'ms.class_group_id')
            ->where('ms.id', $sheetId)
            ->where('ms.institution_semester_id', $scope['institution_semester_id'])
            ->select('ms.id', 'cg.operational_period_id')
            ->first();

        if (! $row) {
            abort(404);
        }

        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();

            if (empty($allowed) || ! in_array((int) $row->operational_period_id, $allowed, true)) {
                abort(403, 'Mark sheet is not in your assigned operational period.');
            }
        }

        return MarkSheet::findOrFail($sheetId);
    }

    private function flash(string $message, string $type = 'success'): void
    {
        $this->flashMessage = $message;
        $this->flashType    = $type;
    }
}

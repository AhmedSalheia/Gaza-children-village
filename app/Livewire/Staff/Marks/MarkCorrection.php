<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Marks;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Modules\AcademicManagement\Actions\CorrectMark;
use Modules\AcademicManagement\Exceptions\MarksException;
use Modules\AcademicManagement\Models\MarkSheet;
use Modules\AcademicManagement\Models\StudentMark;
use Modules\Authorization\Data\PermissionKey;

/**
 * Staff mark correction interface.
 *
 * Allows authorized staff (marks.correct) to submit correction rows for
 * individual student marks on an approved or published sheet. Correction
 * rows are append-only; the original mark is never mutated.
 *
 * Authorization:
 *   - Requires marks.correct permission.
 *   - The sheet must belong to the staff member's institution semester.
 *   - Period-restricted positions must have an operational-period grant
 *     covering the sheet's class group.
 */
final class MarkCorrection extends Component
{
    use HasStaffAuth;

    public ?int $sheetId = null;

    public int    $selectedMarkId    = 0;
    public string $correctedScore    = '';
    public string $correctedExcept   = '';
    public string $correctionReason  = '';
    public string $flashMessage      = '';
    public string $flashType         = '';

    public function mount(int $sheetId): void
    {
        $this->requirePermission(PermissionKey::MARKS_CORRECT);
        $this->sheetId = $sheetId;
        $this->assertSheetCorrectableInScope($sheetId);
    }

    // ── Data accessors ────────────────────────────────────────────────────

    public function sheet(): ?MarkSheet
    {
        if ($this->sheetId === null) {
            return null;
        }

        return $this->loadMarkSheetInScope($this->sheetId);
    }

    /**
     * Return the original (non-correction) marks for this sheet, each with
     * the most recent correction if one exists.
     */
    public function marks(): Collection
    {
        $sheet = $this->sheet();

        if ($sheet === null) {
            return collect();
        }

        return DB::table('student_marks as sm')
            ->join('student_enrollments as se', 'se.id', '=', 'sm.enrollment_id')
            ->join('student_profiles as sp', 'sp.id', '=', 'se.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->join('assessment_definitions as ad', 'ad.id', '=', 'sm.assessment_definition_id')
            ->where('sm.mark_sheet_id', $sheet->id)
            ->whereNull('sm.correction_of_id')   // originals only
            ->orderBy('p.full_name_ar')
            ->orderBy('ad.name_ar')
            ->get([
                'sm.id as mark_id',
                'sm.score',
                'sm.exception_status',
                'p.full_name_ar as student_name',
                'ad.name_ar as assessment_name',
                'ad.max_score',
            ]);
    }

    public function corrections(): Collection
    {
        $sheet = $this->sheet();

        if ($sheet === null) {
            return collect();
        }

        // Latest correction per original mark
        return DB::table('student_marks as corr')
            ->join('student_marks as orig', 'orig.id', '=', 'corr.correction_of_id')
            ->join('student_enrollments as se', 'se.id', '=', 'orig.enrollment_id')
            ->join('student_profiles as sp', 'sp.id', '=', 'se.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->join('assessment_definitions as ad', 'ad.id', '=', 'orig.assessment_definition_id')
            ->where('orig.mark_sheet_id', $sheet->id)
            ->orderBy('corr.corrected_at', 'desc')
            ->get([
                'corr.id as correction_id',
                'corr.correction_of_id as original_mark_id',
                'corr.score as corrected_score',
                'corr.exception_status as corrected_exception',
                'corr.correction_reason',
                'corr.corrected_at',
                'p.full_name_ar as student_name',
                'ad.name_ar as assessment_name',
            ]);
    }

    // ── Mutations ─────────────────────────────────────────────────────────

    public function selectMark(int $markId): void
    {
        $this->requirePermission(PermissionKey::MARKS_CORRECT);
        $this->selectedMarkId  = $markId;
        $this->correctedScore  = '';
        $this->correctedExcept = '';
        $this->correctionReason = '';
    }

    public function submitCorrection(): void
    {
        $this->requirePermission(PermissionKey::MARKS_CORRECT);

        $this->validate([
            'correctionReason' => ['required', 'string', 'min:5'],
            'correctedScore'   => ['nullable', 'numeric', 'min:0'],
            'correctedExcept'  => ['nullable', 'string', 'in:absent,exempt,medical'],
        ]);

        // Require exactly one of score or exception
        if ($this->correctedScore === '' && $this->correctedExcept === '') {
            $this->addError('correctedScore', 'Provide either a corrected score or an exception status.');

            return;
        }

        if ($this->selectedMarkId === 0) {
            return;
        }

        // Re-assert sheet is still in scope (period guard)
        $this->assertSheetCorrectableInScope((int) $this->sheetId);

        $original = StudentMark::where('id', $this->selectedMarkId)
            ->where('mark_sheet_id', $this->sheetId)
            ->whereNull('correction_of_id')
            ->first();

        if (! $original) {
            $this->flash('Original mark not found on this sheet.', 'error');

            return;
        }

        $score     = $this->correctedScore !== '' ? (float) $this->correctedScore : null;
        $exception = $this->correctedExcept !== '' ? $this->correctedExcept : null;

        $staffProfileId = $this->staffProfileId();

        if ($staffProfileId === null) {
            abort(403, 'No staff profile found for this account.');
        }

        try {
            app(CorrectMark::class)(
                sheet: $original->markSheet,
                originalMarkId: $original->id,
                newScore: $score,
                newExceptionStatus: $exception,
                reason: $this->correctionReason,
                actorStaffProfileId: $staffProfileId,
            );
            $this->selectedMarkId  = 0;
            $this->correctedScore  = '';
            $this->correctedExcept = '';
            $this->correctionReason = '';
            $this->flash('Correction submitted.', 'success');
        } catch (MarksException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function cancelCorrection(): void
    {
        $this->selectedMarkId  = 0;
        $this->correctedScore  = '';
        $this->correctedExcept = '';
        $this->correctionReason = '';
    }

    public function render(): View
    {
        return view('livewire.staff.marks.mark-correction', [
            'sheet'       => $this->sheet(),
            'marks'       => $this->marks(),
            'corrections' => $this->corrections(),
        ])->layout('layouts.staff');
    }

    // ── Guards ────────────────────────────────────────────────────────────

    /**
     * Assert the sheet exists, belongs to the staff member's semester scope,
     * is in an approved or published status, and is within the allowed
     * operational period for period-restricted positions.
     */
    private function assertSheetCorrectableInScope(int $sheetId): void
    {
        $sheet = $this->loadMarkSheetInScope($sheetId);

        if (! in_array($sheet->status->value, ['approved', 'published'], true)) {
            abort(403, 'Corrections are only allowed on approved or published mark sheets.');
        }
    }

    private function loadMarkSheetInScope(int $sheetId): MarkSheet
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            abort(403, 'No active scope.');
        }

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

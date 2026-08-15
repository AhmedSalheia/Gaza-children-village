<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Marks;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Modules\AcademicManagement\Actions\OpenMarkSheet;
use Modules\AcademicManagement\Actions\ReturnMarkSheet;
use Modules\AcademicManagement\Actions\SaveDraftMarks;
use Modules\AcademicManagement\Actions\SubmitMarkSheet;
use Modules\AcademicManagement\Actions\VerifyMarkSheet;
use Modules\AcademicManagement\Exceptions\MarksException;
use Modules\AcademicManagement\Models\MarkSheet;
use Modules\AcademicManagement\Models\TeachingAssignment;
use Modules\Authorization\Data\PermissionKey;

/**
 * Spreadsheet-style mark entry for a single teaching assignment.
 *
 * Route params:
 *   - assignmentId: TeachingAssignment ID (required)
 *   - windowId: MarkEntryWindow ID (optional; null = windowless sheet)
 *
 * Authorization contract:
 *   - assignmentId is validated against the staff member's institution semester
 *     AND operational period on mount and re-verified on every mutation.
 *   - For period-restricted positions (secretary, teacher): the assignment's
 *     class group must fall within the staff member's granted operational periods.
 *   - All write operations require marks.enter AND teacher ownership
 *     (unless the actor holds marks.verify — secretary/principal may act on
 *     any in-scope sheet for verification/return).
 *   - The sheet is ALWAYS resolved from (teaching_assignment_id, window_id,
 *     institution_semester_id) — never from a client-supplied sheetId. This
 *     prevents Livewire forged-property attacks.
 */
final class MarkEntrySheet extends Component
{
    use HasStaffAuth;

    public ?int $assignmentId = null;
    public ?int $windowId     = null;

    public string $returnReason = '';
    public bool   $showReturn   = false;
    public string $flashMessage = '';
    public string $flashType    = '';

    /** Tracks whether a sheet was successfully opened on this visit (UI feedback only). */
    public bool $sheetOpened = false;

    /** Grading scale selection (populated from the UI; persisted to the sheet). */
    public string $selectedScaleId = '';

    public function mount(int $assignmentId, ?int $windowId = null): void
    {
        $this->requirePermission(PermissionKey::MARKS_READ);

        $this->assignmentId = $assignmentId;

        // Verify assignment is in the staff member's institution semester AND period
        $this->assertAssignmentInScope($assignmentId);

        $assignment = TeachingAssignment::find($assignmentId);

        if (! $assignment) {
            abort(404);
        }

        // If no window was given, auto-resolve the most specific open window
        $this->windowId = $windowId ?? $this->resolveWindowId($assignment);

        // Pre-populate scale selector from existing sheet
        $existing = $this->resolveSheet();

        if ($existing?->grading_scale_id !== null) {
            $this->selectedScaleId = (string) $existing->grading_scale_id;
        }

        // Auto-open on first visit: requires an open window AND teacher ownership.
        // A null windowId means no applicable open window was found — staff cannot
        // create or enter marks outside a configured window period.
        if ($this->staffCan(PermissionKey::MARKS_ENTER) && $existing === null && $this->windowId !== null) {
            $isOwner = $this->isAssignmentOwner((int) $assignmentId);

            if ($isOwner) {
                try {
                    app(OpenMarkSheet::class)($assignment, $this->windowId);
                    $this->sheetOpened = true;
                } catch (MarksException $e) {
                    $this->flash($e->getMessage(), 'error');
                }
            }
        }
    }

    // ── Authoritative sheet resolver ──────────────────────────────────────

    /**
     * Always resolve the sheet from (teaching_assignment_id, window_id, semester_id).
     * NEVER trust a client-supplied sheetId — this prevents forged-property mutations.
     */
    private function resolveSheet(): ?MarkSheet
    {
        if ($this->assignmentId === null) {
            return null;
        }

        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return null;
        }

        return MarkSheet::where('teaching_assignment_id', $this->assignmentId)
            ->where('mark_entry_window_id', $this->windowId)
            ->where('institution_semester_id', $scope['institution_semester_id'])
            ->where('status', '!=', 'superseded')
            ->first();
    }

    // ── Data accessors ────────────────────────────────────────────────────

    public function sheet(): ?MarkSheet
    {
        return $this->resolveSheet();
    }

    public function assessments(): Collection
    {
        $sheet = $this->resolveSheet();

        if ($sheet === null) {
            return collect();
        }

        // Use LEFT JOINs so semester-wide definitions (null subject_offering_id)
        // are included alongside class+subject and subject-only definitions.
        return DB::table('assessment_definitions as ad')
            ->leftJoin('institution_subject_offerings as iso', 'iso.id', '=', 'ad.subject_offering_id')
            ->leftJoin('subjects as s', 's.id', '=', 'iso.subject_id')
            ->where('ad.institution_semester_id', $sheet->institution_semester_id)
            ->where(function ($q) use ($sheet): void {
                $q->where(function ($inner) use ($sheet): void {
                    // Class + subject match
                    $inner->where('ad.class_group_id', $sheet->class_group_id)
                        ->where('ad.subject_offering_id', $sheet->subject_offering_id);
                })->orWhere(function ($inner) use ($sheet): void {
                    // Subject-only scope
                    $inner->whereNull('ad.class_group_id')
                        ->where('ad.subject_offering_id', $sheet->subject_offering_id);
                })->orWhere(function ($inner) use ($sheet): void {
                    // Class-only scope
                    $inner->where('ad.class_group_id', $sheet->class_group_id)
                        ->whereNull('ad.subject_offering_id');
                })->orWhere(function ($inner): void {
                    // Semester-wide
                    $inner->whereNull('ad.class_group_id')
                        ->whereNull('ad.subject_offering_id');
                });
            })
            ->where('ad.status', 'active')
            ->orderBy('ad.assessment_type')
            ->orderBy('ad.name_ar')
            ->get(['ad.id', 'ad.name_ar', 'ad.assessment_type', 'ad.max_score', 'ad.weight']);
    }

    public function marks(): Collection
    {
        $sheet = $this->resolveSheet();

        if ($sheet === null) {
            return collect();
        }

        return DB::table('student_marks as sm')
            ->join('student_enrollments as se', 'se.id', '=', 'sm.enrollment_id')
            ->join('student_profiles as sp', 'sp.id', '=', 'se.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->where('sm.mark_sheet_id', $sheet->id)
            ->whereNull('sm.correction_of_id')
            ->orderBy('p.full_name_ar')
            ->orderBy('sm.assessment_definition_id')
            ->get([
                'sm.id',
                'sm.enrollment_id',
                'sm.assessment_definition_id',
                'sm.score',
                'sm.exception_status',
                'sm.teacher_note',
                'p.full_name_ar as student_name',
            ]);
    }

    // ── Mutations ─────────────────────────────────────────────────────────

    public function saveMark(
        int $enrollmentId,
        int $assessmentDefinitionId,
        ?string $score,
        ?string $exceptionStatus,
        ?string $teacherNote,
    ): void {
        $this->requirePermission(PermissionKey::MARKS_ENTER);
        // Re-assert assignment scope (period guard included)
        $this->assertAssignmentInScope((int) $this->assignmentId);
        $this->assertTeacherOwnsAssignment();

        // Resolve sheet authoritatively — never trust public sheetId
        $sheet = $this->resolveSheet();

        if (! $sheet) {
            $this->flash('Mark sheet is not open.', 'error');

            return;
        }

        // Staff portal requires a window; windowless sheets are admin-only
        if ($sheet->mark_entry_window_id === null) {
            $this->flash('Mark entry requires an open mark-entry window.', 'error');

            return;
        }

        try {
            app(SaveDraftMarks::class)(
                sheet: $sheet,
                enrollmentId: $enrollmentId,
                assessmentDefinitionId: $assessmentDefinitionId,
                score: $score !== null && $score !== '' ? (float) $score : null,
                exceptionStatus: $exceptionStatus !== '' ? $exceptionStatus : null,
                teacherNote: $teacherNote,
            );
            $this->flash('Saved.', 'success');
        } catch (MarksException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function submit(): void
    {
        $this->requirePermission(PermissionKey::MARKS_SUBMIT);
        $this->assertAssignmentInScope((int) $this->assignmentId);
        $this->assertTeacherOwnsAssignment();

        $sheet = $this->resolveSheet();

        if (! $sheet) {
            return;
        }

        // Staff portal requires a window; windowless sheets are admin-only
        if ($sheet->mark_entry_window_id === null) {
            $this->flash('Submission requires an open mark-entry window.', 'error');

            return;
        }

        try {
            app(SubmitMarkSheet::class)($sheet, (int) $this->staffProfileId());
            $this->flash('Sheet submitted for review.', 'success');
        } catch (MarksException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function startReturn(): void
    {
        $this->requirePermission(PermissionKey::MARKS_RETURN);
        $this->showReturn   = true;
        $this->returnReason = '';
    }

    public function confirmReturn(): void
    {
        $this->requirePermission(PermissionKey::MARKS_RETURN);
        $this->assertAssignmentInScope((int) $this->assignmentId);

        $sheet = $this->resolveSheet();

        if (! $sheet) {
            return;
        }

        // Scope guard — already enforced by resolveSheet, but double-check semester
        $scope = $this->staffScope();

        if ((int) $sheet->institution_semester_id !== $scope['institution_semester_id']) {
            abort(403, 'Sheet is not in your assigned semester.');
        }

        try {
            app(ReturnMarkSheet::class)($sheet, $this->returnReason, (int) $this->staffProfileId());
            $this->showReturn = false;
            $this->flash('Sheet returned to teacher.', 'success');
        } catch (MarksException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function verify(): void
    {
        $this->requirePermission(PermissionKey::MARKS_VERIFY);
        $this->assertAssignmentInScope((int) $this->assignmentId);

        $sheet = $this->resolveSheet();

        if (! $sheet) {
            return;
        }

        try {
            app(VerifyMarkSheet::class)($sheet, (int) $this->staffProfileId());
            $this->flash('Sheet verified.', 'success');
        } catch (MarksException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function attachGradingScale(): void
    {
        $this->requirePermission(PermissionKey::MARKS_ENTER);
        $this->assertAssignmentInScope((int) $this->assignmentId);
        $this->assertTeacherOwnsAssignment();

        $sheet = $this->resolveSheet();

        if (! $sheet || ! $sheet->isEditable()) {
            return;
        }

        $scaleId = $this->selectedScaleId !== '' ? (int) $this->selectedScaleId : null;

        // Validate scale belongs to same institution (if provided)
        if ($scaleId !== null) {
            $scope   = $this->staffScope();
            $belongs = DB::table('grading_scales')
                ->where('id', $scaleId)
                ->where('institution_id', $scope['institution_id'])
                ->exists();

            if (! $belongs) {
                $this->flash('Selected grading scale is not valid for this semester.', 'error');

                return;
            }
        }

        $sheet->grading_scale_id = $scaleId;
        $sheet->save();

        $this->flash($scaleId ? 'Grading scale applied.' : 'Grading scale cleared.', 'success');
    }

    public function gradingScales(): Collection
    {
        $scope = $this->staffScope();

        if ($scope['institution_id'] === null) {
            return collect();
        }

        return DB::table('grading_scales')
            ->where('institution_id', $scope['institution_id'])
            ->orderBy('name_ar')
            ->get(['id', 'name_ar']);
    }

    public function render(): View
    {
        return view('livewire.staff.marks.mark-entry-sheet', [
            'sheet'         => $this->resolveSheet(),
            'assessments'   => $this->assessments(),
            'marks'         => $this->marks(),
            'gradingScales' => $this->gradingScales(),
            'canEnter'      => $this->staffCan(PermissionKey::MARKS_ENTER),
            'canVerify'     => $this->staffCan(PermissionKey::MARKS_VERIFY),
            'canReturn'     => $this->staffCan(PermissionKey::MARKS_RETURN),
            'canCorrect'    => $this->staffCan(PermissionKey::MARKS_CORRECT),
        ])->layout('layouts.staff');
    }

    // ── Guards ────────────────────────────────────────────────────────────

    /**
     * Assert the assignment belongs to the current staff member's semester scope
     * AND to an allowed operational period (for period-restricted positions).
     */
    private function assertAssignmentInScope(int $assignmentId): void
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            abort(403, 'No active scope.');
        }

        // Pull the class_group's operational_period_id via a join
        $row = DB::table('teaching_assignments as ta')
            ->join('class_groups as cg', 'cg.id', '=', 'ta.class_group_id')
            ->where('ta.id', $assignmentId)
            ->where('ta.institution_semester_id', $scope['institution_semester_id'])
            ->select('ta.id', 'cg.operational_period_id')
            ->first();

        if (! $row) {
            abort(404);
        }

        // Period restriction for secretary/teacher positions
        if (! $this->isFullScopePosition()) {
            $allowed = $this->allowedPeriodIds();

            if (empty($allowed) || ! in_array((int) $row->operational_period_id, $allowed, true)) {
                abort(403, 'This assignment is not in your assigned operational period.');
            }
        }
    }

    /**
     * Abort 403 if the current user is a teacher (no marks.verify) and does
     * not own the teaching assignment.
     */
    private function assertTeacherOwnsAssignment(): void
    {
        if (! $this->isAssignmentOwner((int) $this->assignmentId)) {
            abort(403, 'You do not have a teaching assignment for this class/subject.');
        }
    }

    /**
     * Return true if the actor owns the assignment OR holds a privileged role
     * (marks.verify — secretary/principal may act on any in-scope sheet).
     */
    private function isAssignmentOwner(int $assignmentId): bool
    {
        if ($this->staffCan(PermissionKey::MARKS_VERIFY)) {
            return true; // Secretary/principal may act on any in-scope sheet
        }

        $profileId = $this->staffProfileId();

        return DB::table('teaching_assignments')
            ->where('id', $assignmentId)
            ->where('staff_profile_id', $profileId)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Resolve the most specific open window covering this assignment.
     *
     * Priority: class+subject > subject-only > class-only > semester-wide.
     * Returns null if no applicable open window exists (windowless sheet allowed).
     */
    private function resolveWindowId(TeachingAssignment $assignment): ?int
    {
        $row = DB::table('mark_entry_windows')
            ->where('institution_semester_id', $assignment->institution_semester_id)
            ->whereIn('status', ['open', 'extended'])
            ->where('closes_at', '>', now())
            ->where(function ($q) use ($assignment): void {
                $q->where(function ($inner) use ($assignment): void {
                    $inner->where('class_group_id', $assignment->class_group_id)
                        ->where('subject_offering_id', $assignment->subject_offering_id);
                })->orWhere(function ($inner) use ($assignment): void {
                    $inner->whereNull('class_group_id')
                        ->where('subject_offering_id', $assignment->subject_offering_id);
                })->orWhere(function ($inner) use ($assignment): void {
                    $inner->where('class_group_id', $assignment->class_group_id)
                        ->whereNull('subject_offering_id');
                })->orWhere(function ($inner): void {
                    $inner->whereNull('class_group_id')
                        ->whereNull('subject_offering_id');
                });
            })
            ->orderByRaw("
                CASE
                    WHEN class_group_id IS NOT NULL AND subject_offering_id IS NOT NULL THEN 1
                    WHEN subject_offering_id IS NOT NULL THEN 2
                    WHEN class_group_id IS NOT NULL THEN 3
                    ELSE 4
                END
            ")
            ->first(['id']);

        return $row ? (int) $row->id : null;
    }

    private function flash(string $message, string $type = 'success'): void
    {
        $this->flashMessage = $message;
        $this->flashType    = $type;
    }
}

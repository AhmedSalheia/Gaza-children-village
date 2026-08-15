<?php

declare(strict_types=1);

namespace App\Livewire\Guardian\Students;

use App\Livewire\Guardian\Concerns\HasGuardianAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\AcademicManagement\Actions\ResolveCurrentPlacement;

/**
 * Per-child detail view for the guardian portal.
 *
 * Shows three sections on one page:
 *   1. Student identity — name, age range (not exact birth date), current
 *      institution/level. No marks, attendance, or documents.
 *   2. Current placement — active enrollment institution → semester → class
 *      group → operational period → academic level. Graceful "no active
 *      placement" empty state.
 *   3. Guardian's own relationship — relationship type, legal authority,
 *      contact priority, emergency contact flag.
 *   4. Correction-request form — lets the guardian propose changes to
 *      contact_priority and is_emergency_contact. Shows a "pending" indicator
 *      while a request is unresolved.
 *
 * $studentProfileId is locked against browser mutation; assertStudentAccessible()
 * re-runs on every render to prevent stale state from exposing data after a
 * relationship expires mid-session.
 */
final class StudentDetail extends Component
{
    use HasGuardianAuth;

    /** @var int Route-bound; locked against browser mutation. */
    #[Locked]
    public int $studentProfileId;

    // ── Correction-request form fields ────────────────────────────────────

    /** Proposed contact priority (null = no change). */
    public ?int $correctionPriority = null;

    /** Proposed emergency-contact flag (null = no change). */
    public ?bool $correctionIsEmergency = null;

    /** Optional free-text note from the guardian. */
    public string $correctionNote = '';

    /** Whether the correction form is open. */
    public bool $correctionFormOpen = false;

    /** Flash message after a successful submission. */
    public ?string $correctionSuccessMessage = null;

    public function mount(int $studentProfileId): void
    {
        $this->studentProfileId = $studentProfileId;
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
                'p.full_name_ar',
                'p.full_name_en',
                'p.birth_date',
                'p.birth_date_precision'
            )
            ->first();
    }

    /**
     * Returns the student's age range as a display string instead of the
     * exact birth date. Privacy: guardians see approximate age only.
     *
     * Returns null if no birth date is recorded.
     */
    public function ageRange(): ?string
    {
        $student = $this->student();

        if (! $student || ! $student->birth_date) {
            return null;
        }

        $age = now()->diffInYears(new \DateTime($student->birth_date));

        // Group into 3-year bands (e.g. "6–8 years")
        $lower = (int) (floor($age / 3) * 3);
        $upper = $lower + 2;

        return "{$lower}–{$upper}";
    }

    /**
     * Current active placement. Includes institution, semester, class group,
     * academic level, and operational period.
     */
    public function placement(ResolveCurrentPlacement $action): ?object
    {
        $enrollment = $action($this->studentProfileId);

        if (! $enrollment) {
            return null;
        }

        // Enrich with institution / semester / period names via DB query
        // to stay within module boundaries.
        $row = DB::table('student_enrollments as se')
            ->join('class_groups as cg', 'cg.id', '=', 'se.class_group_id')
            ->join('academic_levels as al', 'al.id', '=', 'cg.academic_level_id')
            ->join('institution_semesters as is2', 'is2.id', '=', 'se.institution_semester_id')
            ->join('institutions as i', 'i.id', '=', 'is2.institution_id')
            ->join('semesters as s', 's.id', '=', 'is2.semester_id')
            ->leftJoin('operational_periods as op', 'op.id', '=', 'cg.operational_period_id')
            ->where('se.id', $enrollment->id)
            ->select(
                'se.id',
                'se.enrolled_on',
                'se.activated_on',
                'i.name_ar as institution_name_ar',
                'i.name_en as institution_name_en',
                's.name_ar as semester_name_ar',
                's.name_en as semester_name_en',
                'cg.name_ar as class_group_name_ar',
                'cg.name_en as class_group_name_en',
                'al.name_ar as level_name_ar',
                'al.name_en as level_name_en',
                'op.name_ar as period_name_ar',
                'op.name_en as period_name_en'
            )
            ->first();

        return $row;
    }

    /**
     * The authenticated guardian's own relationship record to this student.
     */
    public function relationship(): ?object
    {
        return $this->relationshipTo($this->studentProfileId);
    }

    /**
     * Return the latest pending correction request for this guardian–student
     * relationship, or null if none exists.
     */
    public function pendingCorrection(): ?object
    {
        $rel = $this->relationship();

        if (! $rel) {
            return null;
        }

        return DB::table('guardian_correction_requests')
            ->where('guardian_student_relationship_id', $rel->id)
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->select(
                'id',
                'requested_contact_priority',
                'requested_is_emergency_contact',
                'note',
                'created_at',
            )
            ->first();
    }

    // ── Actions ───────────────────────────────────────────────────────────

    /**
     * Toggle the correction-request form open/closed.
     */
    public function openCorrectionForm(): void
    {
        $this->assertStudentAccessible($this->studentProfileId);
        $this->correctionFormOpen = true;
        $this->correctionSuccessMessage = null;
    }

    public function closeCorrectionForm(): void
    {
        $this->correctionFormOpen = false;
        $this->resetCorrectionForm();
    }

    /**
     * Submit a correction request for contact_priority and/or
     * is_emergency_contact.
     *
     * Validation:
     *   - At least one correction field must be provided.
     *   - Each provided value must differ from the current relationship value.
     *   - contact_priority, if given, must be a positive integer.
     *
     * Concurrency: the DB unique index on (guardian_student_relationship_id,
     * pending_lock) enforces at-most-one-pending at the database level, so
     * concurrent submissions race to the same unique slot and the second one
     * fails with a constraint violation rather than silently inserting.
     */
    public function submitCorrectionRequest(): void
    {
        $this->assertStudentAccessible($this->studentProfileId);

        $rel = $this->relationship();

        if (! $rel) {
            return;
        }

        // Validate that at least one correction field is set.
        $hasPriority   = $this->correctionPriority !== null;
        $hasEmergency  = $this->correctionIsEmergency !== null;

        if (! $hasPriority && ! $hasEmergency) {
            $this->addError('correctionPriority', __('ui.correction_no_change', [], null, 'Please select at least one field to correct.'));

            return;
        }

        // Validate priority range if provided (unsignedTinyInteger: 1–255).
        if ($hasPriority && ($this->correctionPriority < 1 || $this->correctionPriority > 255)) {
            $this->addError('correctionPriority', __('ui.correction_priority_invalid', [], null, 'Contact priority must be between 1 and 255.'));

            return;
        }

        // Fetch current values for the "differs" check.
        $current = DB::table('guardian_student_relationships')
            ->where('id', $rel->id)
            ->select('contact_priority', 'is_emergency_contact')
            ->first();

        if ($hasPriority && (int) $this->correctionPriority === (int) $current->contact_priority) {
            $this->addError('correctionPriority', __('ui.correction_same_value', [], null, 'The contact priority you entered matches the current value.'));

            return;
        }

        if ($hasEmergency && (bool) $this->correctionIsEmergency === (bool) $current->is_emergency_contact) {
            $this->addError('correctionIsEmergency', __('ui.correction_same_value', [], null, 'The emergency-contact flag you selected matches the current value.'));

            return;
        }

        // Attempt the insert; the unique index on (relationship_id, pending_lock)
        // will reject a duplicate pending row if a concurrent submission races in.
        try {
            DB::table('guardian_correction_requests')->insert([
                'guardian_student_relationship_id' => $rel->id,
                'requested_contact_priority'       => $this->correctionPriority,
                'requested_is_emergency_contact'   => $this->correctionIsEmergency,
                'note'                             => $this->correctionNote !== '' ? $this->correctionNote : null,
                'status'                           => 'pending',
                'pending_lock'                     => 1,
                'created_at'                       => now(),
                'updated_at'                       => now(),
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            // A concurrent submission beat us — treat as already-pending.
            $this->correctionFormOpen = false;

            return;
        }

        $this->correctionFormOpen = false;
        $this->correctionSuccessMessage = __('ui.correction_submitted', [], null, 'Your correction request has been submitted. Staff will review it shortly.');
        $this->resetCorrectionForm();
    }

    // ── Rendering ─────────────────────────────────────────────────────────

    public function render(): View
    {
        // Re-assert access on every render — a relationship may expire during
        // an active session; this prevents the page from serving stale data.
        $this->assertStudentAccessible($this->studentProfileId);

        return view('livewire.guardian.students.detail', [
            'student'           => $this->student(),
            'ageRange'          => $this->ageRange(),
            'relationship'      => $this->relationship(),
            'pendingCorrection' => $this->pendingCorrection(),
        ])->layout('layouts.guardian');
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function resetCorrectionForm(): void
    {
        $this->correctionPriority    = null;
        $this->correctionIsEmergency = null;
        $this->correctionNote        = '';
        $this->resetErrorBag();
    }
}

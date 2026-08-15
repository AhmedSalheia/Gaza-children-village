<?php

declare(strict_types=1);

namespace App\Livewire\Guardian\Students;

use App\Livewire\Guardian\Concerns\HasGuardianAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\AcademicManagement\Actions\ResolveCurrentPlacement;

/**
 * Per-child detail view for the guardian portal.
 *
 * Shows:
 *   1. Student identity (name, age range, not exact birth date)
 *   2. Current placement (institution → semester → class group)
 *   3. Guardian's own relationship + correction-request form
 *   4. Published attendance summary (if a non-revoked snapshot exists)
 *   5. Published results (if a non-revoked publication exists)
 *
 * Security contract (unchanged from original):
 *   - $studentProfileId is locked against browser mutation.
 *   - assertStudentAccessible() re-runs on every render.
 *   - Published data queries are scoped to the student's enrollment IDs derived
 *     from the relationship — URL ID manipulation is denied.
 *   - Only 'published' (non-revoked, non-superseded) snapshots are shown.
 *   - Draft marks, internal notes, and staff-only fields are never exposed.
 */
final class StudentDetail extends Component
{
    use HasGuardianAuth;

    #[Locked]
    public int $studentProfileId;

    // ── Correction-request form fields ────────────────────────────────────

    public ?int   $correctionPriority    = null;
    public ?bool  $correctionIsEmergency = null;
    public string $correctionNote        = '';
    public bool   $correctionFormOpen    = false;
    public ?string $correctionSuccessMessage = null;

    public function mount(int $studentProfileId): void
    {
        $this->studentProfileId = $studentProfileId;

        if (! $this->hasGuardianProfile()) {
            session()->flash('error', __('ui.guardian_profile_not_linked_flash', [], null, 'Your account is not yet fully set up. Please contact school administration.'));
            $this->redirectRoute('guardian.dashboard', navigate: true);

            return;
        }

        $this->assertStudentAccessible($this->studentProfileId);
    }

    // ── Data accessors ────────────────────────────────────────────────────

    public function student(): ?object
    {
        return DB::table('student_profiles as sp')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->where('sp.id', $this->studentProfileId)
            ->select('sp.id', 'sp.student_code', 'sp.lifecycle_status',
                'p.full_name_ar', 'p.full_name_en', 'p.birth_date', 'p.birth_date_precision')
            ->first();
    }

    public function ageRange(): ?string
    {
        $student = $this->student();

        if (! $student || ! $student->birth_date) {
            return null;
        }

        $age   = now()->diffInYears(new \DateTime($student->birth_date));
        $lower = (int) (floor($age / 3) * 3);
        $upper = $lower + 2;

        return "{$lower}–{$upper}";
    }

    public function placement(ResolveCurrentPlacement $action): ?object
    {
        $enrollment = $action($this->studentProfileId);

        if (! $enrollment) {
            return null;
        }

        return DB::table('student_enrollments as se')
            ->join('class_groups as cg', 'cg.id', '=', 'se.class_group_id')
            ->join('academic_levels as al', 'al.id', '=', 'cg.academic_level_id')
            ->join('institution_semesters as is2', 'is2.id', '=', 'se.institution_semester_id')
            ->join('institutions as i', 'i.id', '=', 'is2.institution_id')
            ->join('semesters as s', 's.id', '=', 'is2.semester_id')
            ->leftJoin('operational_periods as op', 'op.id', '=', 'cg.operational_period_id')
            ->where('se.id', $enrollment->id)
            ->select(
                'se.id', 'se.enrolled_on', 'se.activated_on',
                'i.name_ar as institution_name_ar', 'i.name_en as institution_name_en',
                's.name_ar as semester_name_ar', 's.name_en as semester_name_en',
                'cg.name_ar as class_group_name_ar', 'cg.name_en as class_group_name_en',
                'al.name_ar as level_name_ar', 'al.name_en as level_name_en',
                'op.name_ar as period_name_ar', 'op.name_en as period_name_en',
                'se.institution_semester_id', 'se.class_group_id',
            )
            ->first();
    }

    public function relationship(): ?object
    {
        return $this->relationshipTo($this->studentProfileId);
    }

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
            ->select('id', 'requested_contact_priority', 'requested_is_emergency_contact', 'note', 'created_at')
            ->first();
    }

    /**
     * Published result rows for this student from the current non-revoked publication.
     * Only approved published data is returned — no drafts or internal values.
     * Scoped by enrollment_id derived from the relationship (not from URL params).
     *
     * @return Collection<int, object>
     */
    public function publishedResults(): Collection
    {
        // Get the student's active enrollment IDs (derived from relationship scope)
        $enrollmentIds = $this->resolveStudentEnrollmentIds();

        if ($enrollmentIds->isEmpty()) {
            return collect();
        }

        // Find the current published (non-revoked) publication for the student's class group
        // Use the most recent non-superseded, non-revoked publication
        $pub = DB::table('result_publications')
            ->whereIn('id', function ($sub) use ($enrollmentIds): void {
                $sub->select('result_publication_id')
                    ->from('result_publication_rows')
                    ->whereIn('enrollment_id', $enrollmentIds->all());
            })
            ->where('status', 'published')
            ->whereNull('superseded_by_id')
            ->orderByDesc('published_at')
            ->first(['id', 'published_at', 'version']);

        if (! $pub) {
            return collect();
        }

        // Return the rows for this student from that publication
        return DB::table('result_publication_rows as rpr')
            ->join('institution_subject_offerings as iso', 'iso.id', '=', 'rpr.subject_offering_id')
            ->join('subjects as s', 's.id', '=', 'iso.subject_id')
            ->where('rpr.result_publication_id', $pub->id)
            ->whereIn('rpr.enrollment_id', $enrollmentIds->all())
            ->orderBy('s.name_ar')
            ->get([
                'rpr.subject_offering_id',
                'rpr.normalized_score',
                'rpr.grade_code',
                'rpr.grade_name_ar',
                'rpr.is_passing',
                'rpr.completeness_status',
                's.name_ar as subject_name_ar',
                's.name_en as subject_name_en',
            ])
            ->map(fn ($row) => (object) array_merge(
                (array) $row,
                ['published_at' => $pub->published_at, 'version' => $pub->version]
            ));
    }

    /**
     * Published attendance data for this student.
     * Respects the snapshot's detail_level, show_reason, show_arrival_departure flags.
     *
     * @return object{snapshot: object|null, rows: Collection<int, object>, summary: object|null}
     */
    public function publishedAttendance(): object
    {
        $enrollmentIds = $this->resolveStudentEnrollmentIds();

        if ($enrollmentIds->isEmpty()) {
            return (object) ['snapshot' => null, 'rows' => collect(), 'summary' => null];
        }

        // Find current non-revoked, non-superseded snapshot covering this student
        $snapshot = DB::table('attendance_publication_snapshots')
            ->whereIn('id', function ($sub) use ($enrollmentIds): void {
                $sub->select('snapshot_id')
                    ->from('attendance_snapshot_rows')
                    ->whereIn('enrollment_id', $enrollmentIds->all());
            })
            ->where('status', 'published')
            ->whereNull('superseded_by_id')
            ->orderByDesc('published_at')
            ->first(['id', 'published_at', 'version', 'detail_level', 'show_reason', 'show_arrival_departure', 'period_from', 'period_to']);

        if (! $snapshot) {
            return (object) ['snapshot' => null, 'rows' => collect(), 'summary' => null];
        }

        $cols = ['asr.attendance_date', 'asr.status_code'];

        if ($snapshot->show_reason) {
            $cols[] = 'asr.reason';
        }

        if ($snapshot->show_arrival_departure) {
            $cols[] = 'asr.arrived_at';
        }

        // Summary: total days, present, absent, late counts
        $rows = DB::table('attendance_snapshot_rows as asr')
            ->where('asr.snapshot_id', $snapshot->id)
            ->whereIn('asr.enrollment_id', $enrollmentIds->all())
            ->orderBy('asr.attendance_date')
            ->get($cols);

        $summary = (object) [
            'total'   => $rows->count(),
            'present' => $rows->where('status_code', 'present')->count(),
            'absent'  => $rows->where('status_code', 'absent')->count(),
            'late'    => $rows->where('status_code', 'late')->count(),
            'other'   => $rows->whereNotIn('status_code', ['present', 'absent', 'late', null])->count(),
        ];

        return (object) [
            'snapshot' => $snapshot,
            'rows'     => $snapshot->detail_level === 'daily_status' ? $rows : collect(),
            'summary'  => $summary,
        ];
    }

    // ── Actions ───────────────────────────────────────────────────────────

    public function openCorrectionForm(): void
    {
        $this->assertStudentAccessible($this->studentProfileId);
        $this->correctionFormOpen       = true;
        $this->correctionSuccessMessage = null;
    }

    public function closeCorrectionForm(): void
    {
        $this->correctionFormOpen = false;
        $this->resetCorrectionForm();
    }

    public function submitCorrectionRequest(): void
    {
        $this->assertStudentAccessible($this->studentProfileId);

        $rel = $this->relationship();

        if (! $rel) {
            return;
        }

        $hasPriority  = $this->correctionPriority !== null;
        $hasEmergency = $this->correctionIsEmergency !== null;

        if (! $hasPriority && ! $hasEmergency) {
            $this->addError('correctionPriority', __('ui.correction_no_change', [], null, 'Please select at least one field to correct.'));

            return;
        }

        if ($hasPriority && ($this->correctionPriority < 1 || $this->correctionPriority > 255)) {
            $this->addError('correctionPriority', __('ui.correction_priority_invalid', [], null, 'Contact priority must be between 1 and 255.'));

            return;
        }

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
            $this->correctionFormOpen = false;

            return;
        }

        $this->correctionFormOpen       = false;
        $this->correctionSuccessMessage = __('ui.correction_submitted', [], null, 'Your correction request has been submitted. Staff will review it shortly.');
        $this->resetCorrectionForm();
    }

    // ── Rendering ─────────────────────────────────────────────────────────

    public function render(): View
    {
        if (! $this->hasGuardianProfile()) {
            session()->flash('error', __('ui.guardian_profile_not_linked_flash', [], null, 'Your account is not yet fully set up. Please contact school administration.'));
            $this->redirectRoute('guardian.dashboard', navigate: true);

            return view('livewire.guardian.students.detail', [
                'student'           => null,
                'ageRange'          => null,
                'relationship'      => null,
                'pendingCorrection' => null,
                'publishedResults'  => collect(),
                'publishedAttendance' => (object) ['snapshot' => null, 'rows' => collect(), 'summary' => null],
            ])->layout('layouts.guardian');
        }

        $this->assertStudentAccessible($this->studentProfileId);

        return view('livewire.guardian.students.detail', [
            'student'             => $this->student(),
            'ageRange'            => $this->ageRange(),
            'relationship'        => $this->relationship(),
            'pendingCorrection'   => $this->pendingCorrection(),
            'publishedResults'    => $this->publishedResults(),
            'publishedAttendance' => $this->publishedAttendance(),
        ])->layout('layouts.guardian');
    }

    // ── Private helpers ───────────────────────────────────────────────────

    /**
     * Resolve enrollment IDs for this student that the guardian has a valid
     * relationship for. Scoped entirely from relationship — never from URL params.
     *
     * @return Collection<int, int>
     */
    private function resolveStudentEnrollmentIds(): Collection
    {
        return DB::table('student_enrollments')
            ->where('student_profile_id', $this->studentProfileId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id);
    }

    private function resetCorrectionForm(): void
    {
        $this->correctionPriority    = null;
        $this->correctionIsEmergency = null;
        $this->correctionNote        = '';
        $this->resetErrorBag();
    }
}

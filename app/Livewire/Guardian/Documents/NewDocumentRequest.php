<?php

declare(strict_types=1);

namespace App\Livewire\Guardian\Documents;

use App\Livewire\Guardian\Concerns\HasGuardianAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Documents\Models\StudentDocumentRequest;
use Modules\Documents\Services\DocumentRequestService;
use Modules\Documents\Services\DocumentTypeRegistry;

/**
 * Guardian portal: new document request form.
 *
 * Step 1: select a student
 * Step 2: choose document type + locale + purpose_notes
 * Step 3: confirm and submit
 *
 * Security: assertStudentAccessible() is called before every student-specific
 * action to prevent a guardian from submitting requests for other students.
 */
final class NewDocumentRequest extends Component
{
    use HasGuardianAuth;

    public int    $step             = 1;
    public ?int   $studentProfileId = null;
    public string $documentTypeCode = '';
    public string $locale           = 'ar';
    public string $purposeNotes     = '';

    public bool   $submitted        = false;
    public ?int   $createdRequestId = null;

    /** @var string[] */
    public array $errors = [];

    public function mount(?int $studentProfileId = null): void
    {
        if (! $this->hasGuardianProfile()) {
            abort(403, 'No guardian profile linked to this account.');
        }

        if ($studentProfileId !== null) {
            $this->assertStudentAccessible($studentProfileId);
            $this->studentProfileId = $studentProfileId;
            $this->step             = 2;
        }
    }

    public function selectStudent(int $studentProfileId): void
    {
        $this->assertStudentAccessible($studentProfileId);
        $this->studentProfileId = $studentProfileId;
        $this->step             = 2;
    }

    public function proceedToReview(): void
    {
        $this->errors = [];

        if ($this->documentTypeCode === '') {
            $this->errors[] = 'يرجى اختيار نوع الوثيقة.';

            return;
        }

        $this->step = 3;
    }

    public function backToStep(int $step): void
    {
        $this->step = $step;
    }

    public function submit(): void
    {
        $this->errors = [];

        if ($this->studentProfileId === null) {
            $this->errors[] = 'يرجى اختيار الطالب.';

            return;
        }

        // Re-assert access (prevents forged Livewire message)
        $this->assertStudentAccessible((int) $this->studentProfileId);

        // Resolve enrollment and institution for this student
        $enrollment = $this->resolveEnrollment((int) $this->studentProfileId);

        if (! $enrollment) {
            $this->errors[] = 'لا يوجد تسجيل نشط لهذا الطالب.';

            return;
        }

        // Validate document type
        $registry = app(DocumentTypeRegistry::class);

        if (! $registry->exists($this->documentTypeCode)) {
            $this->errors[] = 'نوع الوثيقة المختار غير صحيح.';

            return;
        }

        try {
            $request = app(DocumentRequestService::class)->createAndSubmit([
                'enrollment_id'          => (int) $enrollment->enrollment_id,
                'student_profile_id'     => (int) $this->studentProfileId,
                'institution_id'         => (int) $enrollment->institution_id,
                'institution_semester_id' => (int) $enrollment->institution_semester_id,
                'actor_type'             => StudentDocumentRequest::ACTOR_GUARDIAN,
                'actor_account_id'       => (int) auth('guardian')->id(),
                'portal'                 => 'guardian',
                'document_type_code'     => $this->documentTypeCode,
                'locale'                 => $this->locale,
                'purpose_notes'          => $this->purposeNotes !== '' ? $this->purposeNotes : null,
            ]);

            $this->submitted       = true;
            $this->createdRequestId = $request->id;
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function render(): View
    {
        $registry = app(DocumentTypeRegistry::class);
        $types    = $registry->all();

        $students = $this->step === 1
            ? $this->loadEligibleStudents()
            : collect();

        $selectedStudent = $this->studentProfileId !== null
            ? $this->loadStudentInfo((int) $this->studentProfileId)
            : null;

        return view('livewire.guardian.documents.new-document-request', [
            'students'        => $students,
            'documentTypes'   => $types,
            'selectedStudent' => $selectedStudent,
        ])->layout('layouts.guardian');
    }

    private function loadEligibleStudents(): \Illuminate\Support\Collection
    {
        $ids = $this->eligibleStudentIds();

        if (empty($ids)) {
            return collect();
        }

        return DB::table('student_profiles as sp')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->whereIn('sp.id', $ids)
            ->select('sp.id', 'p.full_name_ar', 'p.full_name_en')
            ->orderBy('p.full_name_ar')
            ->get();
    }

    private function loadStudentInfo(int $studentProfileId): ?object
    {
        return DB::table('student_profiles as sp')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->where('sp.id', $studentProfileId)
            ->select('sp.id', 'p.full_name_ar', 'p.full_name_en')
            ->first();
    }

    private function resolveEnrollment(int $studentProfileId): ?object
    {
        return DB::table('student_enrollments as se')
            ->join('class_groups as cg', 'cg.id', '=', 'se.class_group_id')
            ->join('institution_semesters as is', 'is.id', '=', 'se.institution_semester_id')
            ->where('se.student_profile_id', $studentProfileId)
            ->where('se.enrollment_status', 'active')
            ->orderByDesc('se.institution_semester_id')
            ->select('se.id as enrollment_id', 'is.institution_id', 'se.institution_semester_id')
            ->first();
    }
}

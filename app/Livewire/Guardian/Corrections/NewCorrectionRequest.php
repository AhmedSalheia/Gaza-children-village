<?php

declare(strict_types=1);

namespace App\Livewire\Guardian\Corrections;

use App\Livewire\Guardian\Concerns\HasGuardianAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Requests\Enums\CorrectionFieldCatalogue;
use Modules\Requests\Services\CorrectionRequestService;

/**
 * Guardian portal: multi-step new correction request form.
 *
 * Step 1 — Select student (from portal-eligible children)
 * Step 2 — Select field from catalogue + enter proposed value + explanation
 * Step 3 — Review and submit
 *
 * The component validates proposed values using the catalogue's validation rules
 * before calling CorrectionRequestService::createAndSubmit().
 */
final class NewCorrectionRequest extends Component
{
    use HasGuardianAuth;

    // -----------------------------------------------------------------
    // Form state
    // -----------------------------------------------------------------

    public int $step = 1;

    public ?int $studentProfileId = null;

    public string $fieldCode = '';

    public string $proposedValue = '';

    public string $explanation = '';

    public ?int $relationshipRefId = null;

    // -----------------------------------------------------------------
    // Computed
    // -----------------------------------------------------------------

    /** @var string[] */
    public array $validationErrors = [];

    public bool $submitted = false;

    public ?int $createdRequestId = null;

    // -----------------------------------------------------------------
    // Lifecycle
    // -----------------------------------------------------------------

    public function mount(?int $studentProfileId = null): void
    {
        if (! $this->hasGuardianProfile()) {
            abort(403, 'No guardian profile linked.');
        }

        if ($studentProfileId !== null) {
            $this->assertStudentAccessible($studentProfileId);
            $this->studentProfileId = $studentProfileId;
            $this->step = 2;
        }
    }

    // -----------------------------------------------------------------
    // Step navigation
    // -----------------------------------------------------------------

    public function selectStudent(int $studentProfileId): void
    {
        $this->assertStudentAccessible($studentProfileId);
        $this->studentProfileId = $studentProfileId;
        $this->step = 2;
    }

    public function proceedToReview(): void
    {
        $this->validationErrors = [];

        if ($this->fieldCode === '') {
            $this->validationErrors[] = __('requests.error_select_field', [], null, 'Please select a field to correct.');

            return;
        }

        $field = CorrectionFieldCatalogue::tryFrom($this->fieldCode);

        if ($field === null) {
            $this->validationErrors[] = __('requests.error_invalid_field', [], null, 'Invalid field selected.');

            return;
        }

        $rules = $field->validationRules();
        $errors = validator(['value' => $this->proposedValue], ['value' => $rules])->errors()->all();

        if (! empty($errors)) {
            $this->validationErrors = $errors;

            return;
        }

        $this->step = 3;
    }

    public function backToStep(int $targetStep): void
    {
        $this->step = max(1, min($targetStep, $this->step - 1));
        $this->validationErrors = [];
    }

    // -----------------------------------------------------------------
    // Submit
    // -----------------------------------------------------------------

    public function submit(): void
    {
        if ($this->studentProfileId === null) {
            abort(400);
        }

        $this->assertStudentAccessible($this->studentProfileId);

        $field = CorrectionFieldCatalogue::tryFrom($this->fieldCode);

        if ($field === null) {
            abort(400, 'Invalid field code.');
        }

        $guardianAccountId = (int) auth('guardian')->id();
        $guardianProfileId = $this->resolveGuardianProfileIdPublic();
        $institutionId = $this->studentInstitutionId($this->studentProfileId);

        try {
            $request = app(CorrectionRequestService::class)->createAndSubmit(
                studentProfileId: $this->studentProfileId,
                guardianAccountId: $guardianAccountId,
                guardianProfileId: $guardianProfileId,
                fieldCode: $this->fieldCode,
                proposedValue: $this->proposedValue,
                explanation: $this->explanation ?: null,
                relationshipRefId: $this->relationshipRefId,
                institutionId: $institutionId,
            );

            $this->submitted = true;
            $this->createdRequestId = $request->id;
        } catch (\InvalidArgumentException $e) {
            $this->validationErrors = [$e->getMessage()];
        } catch (\RuntimeException $e) {
            $this->validationErrors = [$e->getMessage()];
        }
    }

    // -----------------------------------------------------------------
    // Render
    // -----------------------------------------------------------------

    public function render(): View
    {
        $eligibleStudents = $this->loadEligibleStudents();
        $catalogueFields = CorrectionFieldCatalogue::cases();
        $selectedStudent = $this->studentProfileId
            ? $eligibleStudents->firstWhere('id', $this->studentProfileId)
            : null;

        // For relationship-type fields, load the guardian's verified relationships
        // to this student so the form can present a bounded ownership-verified selector.
        $selectedField = CorrectionFieldCatalogue::tryFrom($this->fieldCode);
        $isRelationshipField = $selectedField !== null && in_array($selectedField, [
            CorrectionFieldCatalogue::GuardianRelationshipType,
            CorrectionFieldCatalogue::GuardianLegalAuthority,
        ], true);

        $guardianRelationships = ($isRelationshipField && $this->studentProfileId !== null)
            ? $this->loadGuardianRelationships($this->studentProfileId)
            : collect();

        return view('livewire.guardian.corrections.new-correction-request', [
            'eligibleStudents' => $eligibleStudents,
            'catalogueFields' => $catalogueFields,
            'selectedStudent' => $selectedStudent,
            'guardianRelationships' => $guardianRelationships,
        ])->layout('layouts.guardian');
    }

    // -----------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------

    /** Public alias for use in this component (trait method is private). */
    public function resolveGuardianProfileIdPublic(): int
    {
        $profileId = $this->resolveGuardianProfileId();

        if ($profileId === null) {
            abort(403);
        }

        return $profileId;
    }

    private function loadEligibleStudents(): Collection
    {
        $profileId = $this->resolveGuardianProfileId();

        if ($profileId === null) {
            return collect();
        }

        return DB::table('guardian_student_relationships as gsr')
            ->join('student_profiles as sp', 'sp.id', '=', 'gsr.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->where('gsr.guardian_profile_id', $profileId)
            ->where('gsr.verification_status', 'verified')
            ->where('gsr.portal_eligible', true)
            ->where(fn ($q) => $q->whereNull('gsr.ends_on')->orWhere('gsr.ends_on', '>=', now()->toDateString()))
            ->select('sp.id', 'p.full_name_ar as name', 'p.full_name_en as name_en')
            ->get();
    }

    /**
     * Load the guardian's verified relationships to the given student.
     * Used to build the bounded relationship selector for GuardianRelationshipType
     * and GuardianLegalAuthority correction fields.
     *
     * Returns only rows that belong to this guardian and this student — the server
     * enforces ownership again in CorrectionRequestService::assertRelationshipOwnership().
     */
    private function loadGuardianRelationships(int $studentProfileId): Collection
    {
        $profileId = $this->resolveGuardianProfileId();

        if ($profileId === null) {
            return collect();
        }

        return DB::table('guardian_student_relationships')
            ->where('guardian_profile_id', $profileId)
            ->where('student_profile_id', $studentProfileId)
            ->where('verification_status', 'verified')
            ->select('id', 'relationship_type', 'legal_authority', 'starts_on')
            ->get();
    }

    private function studentInstitutionId(int $studentProfileId): ?int
    {
        $row = DB::table('student_enrollments as se')
            ->join('institution_semesters as is2', 'is2.id', '=', 'se.institution_semester_id')
            ->where('se.student_profile_id', $studentProfileId)
            ->where('se.enrollment_status', 'active')
            ->orderByDesc('se.id')
            ->select('is2.institution_id')
            ->first();

        return $row ? (int) $row->institution_id : null;
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Marks;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\AcademicManagement\Actions\CreateAssessmentDefinition;
use Modules\AcademicManagement\Enums\AssessmentType;
use Modules\AcademicManagement\Exceptions\MarksException;
use Modules\AcademicManagement\Models\AssessmentDefinition;
use Modules\Authorization\Data\PermissionKey;

/**
 * Admin UI for managing assessment definitions per institution semester.
 */
final class AssessmentDefinitionIndex extends Component
{
    use HasAdminAuth;

    #[Url]
    public int $semesterId = 0;

    public bool   $showForm          = false;
    public string $nameAr            = '';
    public string $nameEn            = '';
    public string $assessmentType    = '';
    public float  $maxScore          = 100.0;
    public float  $weight            = 0.0;
    public string $assessmentDate    = '';
    public int    $classGroupId      = 0;
    public int    $subjectOfferingId = 0;

    public string $flashMessage = '';
    public string $flashType    = '';

    public function mount(): void
    {
        $this->requirePermission(PermissionKey::ASSESSMENT_MANAGE);
    }

    public function openSemesters(): Collection
    {
        return DB::table('institution_semesters as is')
            ->join('institutions as i', 'i.id', '=', 'is.institution_id')
            ->join('semesters as s', 's.id', '=', 'is.semester_id')
            ->where('is.status', 'open')
            ->orderBy('i.name_ar')
            ->get(['is.id', 'i.name_ar as institution_name', 's.name_ar as semester_name']);
    }

    public function definitions(): Collection
    {
        if ($this->semesterId === 0) {
            return collect();
        }

        return DB::table('assessment_definitions as ad')
            ->leftJoin('class_groups as cg', 'cg.id', '=', 'ad.class_group_id')
            ->leftJoin('institution_subject_offerings as iso', 'iso.id', '=', 'ad.subject_offering_id')
            ->leftJoin('subjects as s', 's.id', '=', 'iso.subject_id')
            ->where('ad.institution_semester_id', $this->semesterId)
            ->orderBy('ad.created_at', 'desc')
            ->get([
                'ad.id', 'ad.name_ar', 'ad.assessment_type', 'ad.max_score', 'ad.weight', 'ad.status',
                'cg.name_ar as class_group_name',
                's.name_ar as subject_name',
            ]);
    }

    public function classGroups(): Collection
    {
        if ($this->semesterId === 0) {
            return collect();
        }

        return DB::table('class_groups')
            ->where('institution_semester_id', $this->semesterId)
            ->where('lifecycle_status', 'active')
            ->orderBy('name_ar')
            ->get(['id', 'name_ar']);
    }

    public function subjectOfferings(): Collection
    {
        if ($this->semesterId === 0) {
            return collect();
        }

        return DB::table('institution_subject_offerings as iso')
            ->join('subjects as s', 's.id', '=', 'iso.subject_id')
            ->where('iso.institution_semester_id', $this->semesterId)
            ->orderBy('s.name_ar')
            ->get(['iso.id', 's.name_ar']);
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function assessmentTypes(): array
    {
        return array_map(
            static fn (AssessmentType $t) => ['value' => $t->value, 'labelAr' => $t->labelAr()],
            AssessmentType::cases(),
        );
    }

    public function save(): void
    {
        $this->requirePermission(PermissionKey::ASSESSMENT_MANAGE);

        $this->validate([
            'semesterId'     => ['required', 'integer', 'min:1'],
            'nameAr'         => ['required', 'string', 'max:200'],
            'assessmentType' => ['required', 'string'],
            'maxScore'       => ['required', 'numeric', 'min:0.01'],
            'weight'         => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $type = AssessmentType::from($this->assessmentType);

            app(CreateAssessmentDefinition::class)(
                institutionSemesterId: $this->semesterId,
                classGroupId: $this->classGroupId > 0 ? $this->classGroupId : null,
                subjectOfferingId: $this->subjectOfferingId > 0 ? $this->subjectOfferingId : null,
                nameAr: $this->nameAr,
                nameEn: $this->nameEn ?: null,
                assessmentType: $type,
                maxScore: $this->maxScore,
                weight: $this->weight,
                assessmentDate: $this->assessmentDate !== '' ? $this->assessmentDate : null,
            );

            $this->reset(['nameAr', 'nameEn', 'assessmentType', 'maxScore', 'weight', 'assessmentDate', 'classGroupId', 'subjectOfferingId', 'showForm']);
            $this->maxScore = 100.0;
            $this->flash('success', 'Assessment definition created.');
        } catch (MarksException $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function archive(int $id): void
    {
        $this->requirePermission(PermissionKey::ASSESSMENT_MANAGE);

        AssessmentDefinition::where('id', $id)->update(['status' => 'archived']);
        $this->flash('success', 'Definition archived.');
    }

    public function restore(int $id): void
    {
        $this->requirePermission(PermissionKey::ASSESSMENT_MANAGE);

        AssessmentDefinition::where('id', $id)->update(['status' => 'active']);
        $this->flash('success', 'Definition restored.');
    }

    public function cancelForm(): void
    {
        $this->reset(['nameAr', 'nameEn', 'assessmentType', 'assessmentDate', 'classGroupId', 'subjectOfferingId', 'showForm']);
        $this->maxScore = 100.0;
        $this->weight   = 0.0;
    }

    public function render(): View
    {
        return view('livewire.admin.marks.assessment-definitions', [
            'openSemesters'    => $this->openSemesters(),
            'definitions'      => $this->definitions(),
            'classGroups'      => $this->classGroups(),
            'subjectOfferings' => $this->subjectOfferings(),
            'assessmentTypes'  => $this->assessmentTypes(),
        ])->layout('layouts.admin');
    }

    private function flash(string $type, string $message): void
    {
        $this->flashType    = $type;
        $this->flashMessage = $message;
    }
}

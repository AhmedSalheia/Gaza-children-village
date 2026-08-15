<?php

declare(strict_types=1);

namespace App\Livewire\Admin\AcademicStructure;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\AcademicManagement\Actions\CreateSubject;
use Modules\AcademicManagement\Actions\OfferSubject;
use Modules\AcademicManagement\Actions\RemoveSubjectOffering;
use Modules\AcademicManagement\Actions\ToggleSubject;
use Modules\AcademicManagement\Models\Subject;

/**
 * Subject catalogue and per-institution-semester subject offerings.
 */
final class SubjectIndex extends Component
{
    use HasAdminAuth;

    public bool $showForm = false;

    public string $nameAr = '';

    public string $nameEn = '';

    public string $code = '';

    #[Url]
    public int $offeringInstSemId = 0;

    public string $flashMessage = '';

    public string $flashType = '';

    public function mount(): void
    {
        $this->requirePermission('subject.manage');
    }

    public function subjects(): Collection
    {
        return Subject::orderBy('name_ar')->get();
    }

    public function openSemesters(): Collection
    {
        return DB::table('institution_semesters as is')
            ->join('institutions as i', 'i.id', '=', 'is.institution_id')
            ->join('semesters as s', 's.id', '=', 'is.semester_id')
            ->where('is.status', 'open')
            ->orderBy('i.name_ar')
            ->get([
                'is.id',
                'i.name_ar as institution_name',
                's.name_ar as semester_name',
            ]);
    }

    public function offerings(): Collection
    {
        if ($this->offeringInstSemId === 0) {
            return collect();
        }

        return DB::table('institution_subject_offerings as iso')
            ->join('subjects as sub', 'sub.id', '=', 'iso.subject_id')
            ->where('iso.institution_semester_id', $this->offeringInstSemId)
            ->orderBy('sub.name_ar')
            ->get(['iso.id', 'sub.name_ar', 'sub.name_en', 'sub.code', 'sub.id as subject_id']);
    }

    public function toggle(int $subjectId, bool $isActive): void
    {
        $this->requirePermission('subject.manage');

        $subject = Subject::findOrFail($subjectId);
        app(ToggleSubject::class)($subject, $isActive);
        $this->flash('success', __('ui.saved', [], null, 'Saved.'));
    }

    public function save(): void
    {
        $this->requirePermission('subject.manage');

        $this->validate([
            'nameAr' => ['required', 'string', 'max:150'],
            'nameEn' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:32'],
        ]);

        try {
            app(CreateSubject::class)(
                nameAr: $this->nameAr,
                nameEn: $this->nameEn ?: null,
                code: $this->code,
            );

            $this->reset(['nameAr', 'nameEn', 'code', 'showForm']);
            $this->flash('success', __('ui.created', [], null, 'Subject created.'));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function addOffering(int $subjectId): void
    {
        $this->requirePermission('subject_offering.manage');

        if ($this->offeringInstSemId === 0) {
            $this->flash('error', __('ui.select_semester', [], null, 'Please select a semester first.'));

            return;
        }

        try {
            $subject = Subject::findOrFail($subjectId);
            app(OfferSubject::class)($this->offeringInstSemId, $subject);
            $this->flash('success', __('ui.created', [], null, 'Offering added.'));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function removeOffering(int $offeringId): void
    {
        $this->requirePermission('subject_offering.manage');

        try {
            $offeringClass = 'Modules\\AcademicManagement\\Models\\InstitutionSubjectOffering';
            $offering = $offeringClass::findOrFail($offeringId);
            app(RemoveSubjectOffering::class)($offering);
            $this->flash('success', __('ui.deleted', [], null, 'Offering removed.'));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function cancelForm(): void
    {
        $this->reset(['nameAr', 'nameEn', 'code', 'showForm']);
    }

    private function flash(string $type, string $message): void
    {
        $this->flashType = $type;
        $this->flashMessage = $message;
    }

    public function render(): View
    {
        return view('livewire.admin.academic-structure.subjects', [
            'subjects' => $this->subjects(),
            'openSemesters' => $this->openSemesters(),
            'offerings' => $this->offerings(),
        ])->layout('layouts.admin');
    }
}

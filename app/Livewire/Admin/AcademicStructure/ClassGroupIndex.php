<?php

declare(strict_types=1);

namespace App\Livewire\Admin\AcademicStructure;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\AcademicManagement\Actions\ActivateClassGroup;
use Modules\AcademicManagement\Actions\ArchiveClassGroup;
use Modules\AcademicManagement\Actions\CreateClassGroup;
use Modules\AcademicManagement\Models\AcademicLevel;
use Modules\AcademicManagement\Models\ClassGroup;
use Modules\AcademicManagement\Models\Classroom;

final class ClassGroupIndex extends Component
{
    use HasAdminAuth;
    use WithPagination;

    #[Url]
    public int $instSemId = 0;

    #[Url]
    public string $lifecycleFilter = '';

    // Create form
    public bool $showForm = false;

    public string $formCode = '';

    public string $formNameAr = '';

    public string $formNameEn = '';

    public int $formAcademicLevelId = 0;

    public int $formClassroomId = 0;

    public int $formOperationalPeriodId = 0;

    public ?int $formCapacity = null;

    public string $flashMessage = '';

    public string $flashType = '';

    public function mount(): void
    {
        $this->requirePermission('class_group.manage');
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

    public function classGroups(): LengthAwarePaginator
    {
        return ClassGroup::query()
            ->when($this->instSemId > 0, fn ($q) => $q->where('institution_semester_id', $this->instSemId))
            ->when($this->lifecycleFilter !== '', fn ($q) => $q->where('lifecycle_status', $this->lifecycleFilter))
            ->with(['academicLevel', 'classroom'])
            ->orderBy('name_ar')
            ->paginate(25);
    }

    public function academicLevels(): Collection
    {
        return AcademicLevel::where('is_active', true)->orderBy('sequence')->get();
    }

    public function classrooms(): Collection
    {
        if ($this->instSemId === 0) {
            return collect();
        }

        $institutionId = DB::table('institution_semesters')->where('id', $this->instSemId)->value('institution_id');

        return Classroom::where('institution_id', $institutionId)
            ->where('is_active', true)
            ->orderBy('name_ar')
            ->get();
    }

    public function operationalPeriods(): Collection
    {
        if ($this->instSemId === 0) {
            return collect();
        }

        return DB::table('operational_periods')
            ->where('institution_semester_id', $this->instSemId)
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en', 'code']);
    }

    public function updatingInstSemId(): void
    {
        $this->resetPage();
        $this->reset(['formClassroomId', 'formOperationalPeriodId']);
    }

    public function activate(int $groupId): void
    {
        $this->requirePermission('class_group.manage');

        try {
            $group = ClassGroup::findOrFail($groupId);
            app(ActivateClassGroup::class)($group, 'admin:'.$this->adminId());
            $this->flash('success', __('ui.saved', [], null, 'Class group activated.'));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function archive(int $groupId): void
    {
        $this->requirePermission('class_group.manage');

        try {
            $group = ClassGroup::findOrFail($groupId);
            app(ArchiveClassGroup::class)($group, 'admin:'.$this->adminId());
            $this->flash('success', __('ui.saved', [], null, 'Class group archived.'));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function save(): void
    {
        $this->requirePermission('class_group.manage');

        $this->validate([
            'instSemId' => ['required', 'integer', 'min:1'],
            'formCode' => ['required', 'string', 'max:32'],
            'formNameAr' => ['required', 'string', 'max:150'],
            'formAcademicLevelId' => ['required', 'integer', 'min:1'],
            'formOperationalPeriodId' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $level = AcademicLevel::findOrFail($this->formAcademicLevelId);
            $classroom = $this->formClassroomId > 0 ? Classroom::find($this->formClassroomId) : null;

            app(CreateClassGroup::class)(
                institutionSemesterId: $this->instSemId,
                operationalPeriodId: $this->formOperationalPeriodId,
                academicLevel: $level,
                code: $this->formCode,
                nameAr: $this->formNameAr,
                nameEn: $this->formNameEn ?: null,
                classroom: $classroom,
                capacity: $this->formCapacity,
            );

            $this->reset(['formCode', 'formNameAr', 'formNameEn', 'formAcademicLevelId', 'formClassroomId', 'formOperationalPeriodId', 'formCapacity', 'showForm']);
            $this->flash('success', __('ui.created', [], null, 'Class group created.'));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function cancelForm(): void
    {
        $this->reset(['formCode', 'formNameAr', 'formNameEn', 'formAcademicLevelId', 'formClassroomId', 'formOperationalPeriodId', 'formCapacity', 'showForm']);
    }

    private function flash(string $type, string $message): void
    {
        $this->flashType = $type;
        $this->flashMessage = $message;
    }

    public function render(): View
    {
        return view('livewire.admin.academic-structure.class-groups', [
            'openSemesters' => $this->openSemesters(),
            'classGroups' => $this->classGroups(),
            'academicLevels' => $this->academicLevels(),
            'classrooms' => $this->classrooms(),
            'operationalPeriods' => $this->operationalPeriods(),
        ])->layout('layouts.admin');
    }
}

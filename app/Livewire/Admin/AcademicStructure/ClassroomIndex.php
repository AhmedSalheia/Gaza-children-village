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
use Modules\AcademicManagement\Actions\CreateClassroom;
use Modules\AcademicManagement\Actions\ToggleClassroom;
use Modules\AcademicManagement\Models\Classroom;

/**
 * Per-institution classroom list with create and toggle-active.
 */
final class ClassroomIndex extends Component
{
    use HasAdminAuth;
    use WithPagination;

    #[Url]
    public int $institutionId = 0;

    public bool $showForm = false;

    public string $nameAr = '';

    public string $nameEn = '';

    public string $roomCode = '';

    public ?int $capacity = null;

    public string $flashMessage = '';

    public string $flashType = '';

    public function mount(): void
    {
        $this->requirePermission('classroom.manage');
    }

    public function institutions(): Collection
    {
        return DB::table('institutions')
            ->where('is_active', true)
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en', 'code']);
    }

    public function classrooms(): LengthAwarePaginator
    {
        // Classroom has no Eloquent relation back to Institution (cross-module boundary).
        // Resolve institution name via a plain JOIN instead of eager-loading.
        return DB::table('classrooms as c')
            ->leftJoin('institutions as i', 'i.id', '=', 'c.institution_id')
            ->select([
                'c.id',
                'c.name_ar',
                'c.name_en',
                'c.code',
                'c.capacity',
                'c.is_active',
                'c.institution_id',
                'i.name_ar as institution_name_ar',
            ])
            ->when($this->institutionId > 0, fn ($q) => $q->where('c.institution_id', $this->institutionId))
            ->orderBy('c.name_ar')
            ->paginate(25);
    }

    public function updatingInstitutionId(): void
    {
        $this->resetPage();
    }

    public function toggle(int $classroomId, bool $isActive): void
    {
        $this->requirePermission('classroom.manage');

        $classroom = Classroom::findOrFail($classroomId);
        app(ToggleClassroom::class)($classroom, $isActive);

        $this->flash('success', __('ui.saved', [], null, 'Saved.'));
    }

    public function save(): void
    {
        $this->requirePermission('classroom.manage');

        $this->validate([
            'institutionId' => ['required', 'integer', 'min:1'],
            'nameAr' => ['required', 'string', 'max:150'],
            'nameEn' => ['nullable', 'string', 'max:150'],
            'roomCode' => ['required', 'string', 'max:32'],
        ]);

        try {
            app(CreateClassroom::class)(
                institutionId: $this->institutionId,
                nameAr: $this->nameAr,
                nameEn: $this->nameEn ?: null,
                code: $this->roomCode,
                capacity: $this->capacity,
            );

            $this->reset(['nameAr', 'nameEn', 'roomCode', 'capacity', 'showForm']);
            $this->flash('success', __('ui.created', [], null, 'Classroom created.'));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function cancelForm(): void
    {
        $this->reset(['nameAr', 'nameEn', 'roomCode', 'capacity', 'showForm']);
    }

    private function flash(string $type, string $message): void
    {
        $this->flashType = $type;
        $this->flashMessage = $message;
    }

    public function render(): View
    {
        return view('livewire.admin.academic-structure.classrooms', [
            'institutions' => $this->institutions(),
            'classrooms' => $this->classrooms(),
        ])->layout('layouts.admin');
    }
}

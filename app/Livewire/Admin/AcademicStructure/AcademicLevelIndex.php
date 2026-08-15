<?php

declare(strict_types=1);

namespace App\Livewire\Admin\AcademicStructure;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;
use Modules\AcademicManagement\Actions\CreateAcademicLevel;
use Modules\AcademicManagement\Actions\ToggleAcademicLevel;
use Modules\AcademicManagement\Models\AcademicLevel;

/**
 * Academic levels catalogue (KG1 → Grade12).
 * Supports create and toggle-active mutations.
 */
final class AcademicLevelIndex extends Component
{
    use HasAdminAuth;

    public bool $showForm = false;

    public string $nameAr = '';

    public string $nameEn = '';

    public int $sequence = 0;

    public string $code = '';

    public string $flashMessage = '';

    public string $flashType = '';

    public function mount(): void
    {
        $this->requirePermission('academic_level.manage');
    }

    public function levels(): Collection
    {
        return AcademicLevel::orderBy('sequence')->get();
    }

    public function toggle(int $levelId, bool $isActive): void
    {
        $this->requirePermission('academic_level.manage');

        $level = AcademicLevel::findOrFail($levelId);
        app(ToggleAcademicLevel::class)($level, $isActive);

        $this->flash('success', __('ui.saved', [], null, 'Saved.'));
    }

    public function save(): void
    {
        $this->requirePermission('academic_level.manage');

        $this->validate([
            'nameAr' => ['required', 'string', 'max:100'],
            'nameEn' => ['required', 'string', 'max:100'],
            'sequence' => ['required', 'integer', 'min:0'],
            'code' => ['required', 'string', 'max:32'],
        ]);

        try {
            app(CreateAcademicLevel::class)(
                nameAr: $this->nameAr,
                nameEn: $this->nameEn ?: null,
                sequence: $this->sequence,
                code: $this->code,
            );

            $this->reset(['nameAr', 'nameEn', 'sequence', 'code', 'showForm']);
            $this->flash('success', __('ui.created', [], null, 'Level created.'));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function cancelForm(): void
    {
        $this->reset(['nameAr', 'nameEn', 'sequence', 'code', 'showForm']);
    }

    private function flash(string $type, string $message): void
    {
        $this->flashType = $type;
        $this->flashMessage = $message;
    }

    public function render(): View
    {
        return view('livewire.admin.academic-structure.levels', [
            'levels' => $this->levels(),
        ])->layout('layouts.admin');
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Marks;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Modules\AcademicManagement\Actions\CreateGradingScale;
use Modules\AcademicManagement\Exceptions\MarksException;
use Modules\AcademicManagement\Models\GradingScale;
use Modules\Authorization\Data\PermissionKey;

/**
 * Admin UI for creating and managing institution grading scales.
 */
final class GradingScaleIndex extends Component
{
    use HasAdminAuth;

    public bool $showForm = false;

    public int $institutionId = 0;

    public string $code = '';

    public string $nameAr = '';

    /** @var array<int, array<string, mixed>> */
    public array $gradeRows = [];

    public string $flashMessage = '';

    public string $flashType = '';

    public function mount(): void
    {
        $this->requirePermission(PermissionKey::GRADING_SCALE_MANAGE);
        $this->resetGradeRows();
    }

    public function institutions(): Collection
    {
        return DB::table('institutions')
            ->where('is_active', true)
            ->orderBy('name_ar')
            ->get(['id', 'name_ar']);
    }

    public function scales(): Collection
    {
        return DB::table('grading_scales as gs')
            ->join('institutions as i', 'i.id', '=', 'gs.institution_id')
            ->orderBy('i.name_ar')
            ->orderBy('gs.code')
            ->get([
                'gs.id', 'gs.code', 'gs.name_ar', 'gs.is_active',
                'i.name_ar as institution_name',
            ]);
    }

    public function addGradeRow(): void
    {
        $this->gradeRows[] = [
            'code' => '',
            'name_ar' => '',
            'name_en' => '',
            'min_score' => '',
            'max_score' => '',
            'is_passing' => true,
            'sequence' => count($this->gradeRows) + 1,
        ];
    }

    public function removeGradeRow(int $index): void
    {
        array_splice($this->gradeRows, $index, 1);
        $this->gradeRows = array_values($this->gradeRows);
    }

    public function save(): void
    {
        $this->requirePermission(PermissionKey::GRADING_SCALE_MANAGE);

        $this->validate([
            'institutionId' => ['required', 'integer', 'min:1'],
            'code' => ['required', 'string', 'max:50'],
            'nameAr' => ['required', 'string', 'max:200'],
            'gradeRows' => ['required', 'array', 'min:1'],
            'gradeRows.*.code' => ['required', 'string'],
            'gradeRows.*.name_ar' => ['required', 'string'],
            'gradeRows.*.min_score' => ['required', 'numeric'],
            'gradeRows.*.max_score' => ['required', 'numeric'],
            'gradeRows.*.sequence' => ['required', 'integer', 'min:1'],
        ]);

        $grades = array_map(static fn ($r) => [
            'code' => $r['code'],
            'name_ar' => $r['name_ar'],
            'name_en' => $r['name_en'] ?: null,
            'min_score' => (float) $r['min_score'],
            'max_score' => (float) $r['max_score'],
            'is_passing' => (bool) ($r['is_passing'] ?? false),
            'sequence' => (int) $r['sequence'],
        ], $this->gradeRows);

        try {
            app(CreateGradingScale::class)(
                institutionId: $this->institutionId,
                code: $this->code,
                nameAr: $this->nameAr,
                nameEn: null,
                grades: $grades,
            );

            $this->reset(['code', 'nameAr', 'institutionId', 'showForm']);
            $this->resetGradeRows();
            $this->flash('success', 'Grading scale created successfully.');
        } catch (MarksException $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function toggle(int $id, bool $currentlyActive): void
    {
        $this->requirePermission(PermissionKey::GRADING_SCALE_MANAGE);

        GradingScale::where('id', $id)->update(['is_active' => ! $currentlyActive]);
        $this->flash('success', $currentlyActive ? 'Scale deactivated.' : 'Scale activated.');
    }

    public function cancelForm(): void
    {
        $this->reset(['code', 'nameAr', 'institutionId', 'showForm']);
        $this->resetGradeRows();
    }

    public function render(): View
    {
        return view('livewire.admin.marks.grading-scales', [
            'institutions' => $this->institutions(),
            'scales' => $this->scales(),
        ])->layout('layouts.admin');
    }

    private function resetGradeRows(): void
    {
        $this->gradeRows = [
            ['code' => '', 'name_ar' => '', 'name_en' => '', 'min_score' => '', 'max_score' => '', 'is_passing' => true,  'sequence' => 1],
            ['code' => '', 'name_ar' => '', 'name_en' => '', 'min_score' => '', 'max_score' => '', 'is_passing' => false, 'sequence' => 2],
        ];
    }

    private function flash(string $type, string $message): void
    {
        $this->flashType = $type;
        $this->flashMessage = $message;
    }
}

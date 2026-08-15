<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Imports;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Imports\Actions\ApplyImportBatch;
use Modules\Imports\Actions\CancelImportBatch;
use Modules\Imports\Actions\GenerateImportResultReport;
use Modules\Imports\Actions\MapColumns;
use Modules\Imports\Actions\ParseImportFile;
use Modules\Imports\Actions\PreviewRows;
use Modules\Imports\Actions\ValidateRows;

/**
 * Import batch processing detail view for staff.
 *
 * Mirrors the admin ImportBatchDetail but scoped to the staff's institution.
 *
 * ── IDOR prevention ───────────────────────────────────────────────────────
 * $batchId is a public Livewire property and can be mutated by the browser
 * between requests. guardBatchInScope() is therefore called inside EVERY
 * public mutation method (not only mount()) to verify the batch still belongs
 * to the staff member's trusted institution before any operation proceeds.
 *
 * ── Permissions ───────────────────────────────────────────────────────────
 * import.review  → view, parse, map, validate, preview, cancel, report
 * import.apply   → apply
 */
final class ImportBatchDetail extends Component
{
    use HasStaffAuth;

    /** @var int Route-bound batch ID; locked against browser mutation. */
    #[Locked]
    public int $batchId;

    /** Raw JSON string typed into the textarea; decoded before passing to MapColumns. */
    public string $columnMappingsJson = '';

    public bool $showMappingForm = false;

    public array $previewData = [];

    public int $previewPage = 1;

    public ?string $previewStatus = null;

    public function mount(int $batchId): void
    {
        $this->requirePermission('import.review');
        $this->batchId = $batchId;
        $this->guardBatchInScope();
    }

    // ── Scope guard ───────────────────────────────────────────────────────

    /**
     * Assert that $this->batchId belongs to the staff member's trusted
     * institution. Called at mount() and re-called inside every public
     * mutation so that a modified $batchId cannot open another institution's
     * import batch.
     */
    private function guardBatchInScope(): void
    {
        $scope = $this->staffScope();

        if ($scope['institution_id'] === null) {
            abort(403, 'No institutional scope for your account.');
        }

        $exists = DB::table('import_batches')
            ->where('id', $this->batchId)
            ->where('institution_id', $scope['institution_id'])
            ->exists();

        if (! $exists) {
            abort(404);
        }
    }

    // ── Computed properties ───────────────────────────────────────────────

    public function batch(): ?object
    {
        return DB::table('import_batches')->where('id', $this->batchId)->first();
    }

    public function files(): Collection
    {
        return DB::table('import_files')
            ->where('batch_id', $this->batchId)
            ->get(['id', 'storage_path', 'content_sha256']);
    }

    // ── Pipeline actions ──────────────────────────────────────────────────

    public function parseFile(int $fileId, ParseImportFile $action): void
    {
        $this->requirePermission('import.review');
        $this->guardBatchInScope();

        $file = DB::table('import_files')
            ->where('id', $fileId)
            ->where('batch_id', $this->batchId)
            ->firstOrFail();

        $resolvedPath = Storage::disk('local')->path($file->storage_path);

        try {
            $action($this->loadBatchModel(), $resolvedPath);
            session()->flash('success', __('ui.import_parsed', [], null, 'File parsed successfully.'));
        } catch (\Throwable $e) {
            $this->addError('parse', $e->getMessage());
        }
    }

    public function saveColumnMappings(MapColumns $action): void
    {
        $this->requirePermission('import.review');
        $this->guardBatchInScope();

        // Decode the JSON string from the textarea.
        // Blank / "{}" / "[]" → null (auto-resolve in MapColumns).
        $trimmed = trim($this->columnMappingsJson);
        if ($trimmed === '' || $trimmed === '{}' || $trimmed === '[]') {
            $mappings = null;
        } else {
            $decoded = json_decode($trimmed, true);
            $mappings = (is_array($decoded) && ! empty($decoded)) ? $decoded : null;
        }

        try {
            $action($this->loadBatchModel(), $mappings);
            session()->flash('success', __('ui.columns_mapped', [], null, 'Columns mapped.'));
            $this->showMappingForm = false;
        } catch (\Throwable $e) {
            $this->addError('mapping', $e->getMessage());
        }
    }

    public function validateRows(ValidateRows $action): void
    {
        $this->requirePermission('import.review');
        $this->guardBatchInScope();

        try {
            $action($this->loadBatchModel());
            session()->flash('success', __('ui.import_validated', [], null, 'Rows validated.'));
        } catch (\Throwable $e) {
            $this->addError('validate', $e->getMessage());
        }
    }

    public function loadPreview(PreviewRows $action): void
    {
        $this->requirePermission('import.review');
        $this->guardBatchInScope();

        try {
            $this->previewData = $action(
                $this->loadBatchModel(),
                $this->previewPage,
                50,
                $this->previewStatus ?: null
            );
        } catch (\Throwable $e) {
            $this->addError('preview', $e->getMessage());
        }
    }

    public function apply(ApplyImportBatch $action): void
    {
        $this->requirePermission('import.apply');
        $this->guardBatchInScope();

        try {
            $action($this->loadBatchModel(), $this->staffActorReference());
            session()->flash('success', __('ui.import_applied', [], null, 'Import applied successfully.'));
        } catch (\Throwable $e) {
            $this->addError('apply', $e->getMessage());
        }
    }

    public function cancel(CancelImportBatch $action): void
    {
        $this->requirePermission('import.review');
        $this->guardBatchInScope();

        try {
            $action($this->loadBatchModel());
            session()->flash('success', __('ui.import_cancelled', [], null, 'Import cancelled.'));
        } catch (\Throwable $e) {
            $this->addError('cancel', $e->getMessage());
        }
    }

    public function downloadReport(GenerateImportResultReport $action): mixed
    {
        $this->requirePermission('import.review');
        $this->guardBatchInScope();

        try {
            $tempPath = $action($this->loadBatchModel());
            $content = file_get_contents($tempPath);
            @unlink($tempPath);

            $filename = 'import-report-'.$this->batchId.'-'.now()->format('Y-m-d').'.csv';

            return response()->streamDownload(
                function () use ($content): void {
                    echo $content;
                },
                $filename,
                ['Content-Type' => 'text/csv; charset=utf-8']
            );
        } catch (\Throwable $e) {
            $this->addError('report', $e->getMessage());
        }

        return null;
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function loadBatchModel(): object
    {
        $batchClass = 'Modules\\Imports\\Models\\ImportBatch';

        return $batchClass::findOrFail($this->batchId);
    }

    public function render(): View
    {
        return view('livewire.staff.imports.detail', [
            'batch' => $this->batch(),
            'files' => $this->files(),
            'canApply' => $this->staffCan('import.apply'),
        ])->layout('layouts.staff');
    }
}

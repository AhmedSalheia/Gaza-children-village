<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Imports;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Imports\Actions\ApplyImportBatch;
use Modules\Imports\Actions\CancelImportBatch;
use Modules\Imports\Actions\GenerateImportResultReport;
use Modules\Imports\Actions\MapColumns;
use Modules\Imports\Actions\ParseImportFile;
use Modules\Imports\Actions\PreviewRows;
use Modules\Imports\Actions\ValidateRows;
use Modules\Imports\Models\ImportBatch;

/**
 * Import batch detail: column mapping, row preview, apply/cancel, result download.
 *
 * Table layout:
 *  - import_batches: id, status, original_filename, file_size_bytes, …
 *  - import_files:   id, batch_id, storage_path, content_sha256
 *  - import_rows:    id, batch_id, row_number, raw_data, mapped_data
 *
 * Action signatures (authoritative):
 *  ParseImportFile      (__invoke(ImportBatch, string $filePath, int $chunkSize=500))
 *  MapColumns           (__invoke(ImportBatch, ?array $mappings=null))  null → auto-resolve
 *  ValidateRows         (__invoke(ImportBatch))
 *  PreviewRows          (__invoke(ImportBatch, int $page, ?string $statusFilter))
 *  ApplyImportBatch     (__invoke(ImportBatch, string $actorReference='import'))
 *  CancelImportBatch    (__invoke(ImportBatch))
 *  GenerateImportResultReport (__invoke(ImportBatch): string)  returns temp-file PATH
 */
final class ImportBatchDetail extends Component
{
    use HasAdminAuth;

    public int $batchId;

    public ?ImportBatch $batch = null;

    /** User-supplied column mappings (empty array triggers auto-resolve). */
    public array $columnMappings = [];

    public int $previewPage = 1;

    public string $previewStatusFilter = '';

    public ?array $previewData = null;

    public string $flashMessage = '';

    public string $flashType = '';

    public function mount(int $batchId): void
    {
        $this->requirePermission('import.review');
        $this->batchId = $batchId;

        $this->batch = ImportBatch::find($batchId);

        if ($this->batch === null) {
            $this->redirectRoute('admin.imports.index', navigate: true);
        }
    }

    /**
     * Return the import_files record for this batch (holds storage_path).
     * Metadata like original_filename lives on import_batches, not here.
     */
    public function batchFile(): ?object
    {
        return DB::table('import_files')->where('batch_id', $this->batchId)->first();
    }

    /**
     * Parse the uploaded file into import_rows.
     *
     * Requires the storage_path from import_files so ParseImportFile can open
     * the actual file from disk.
     */
    public function parseFile(): void
    {
        $this->requirePermission('import.review');

        $fileRecord = DB::table('import_files')->where('batch_id', $this->batchId)->first();

        if ($fileRecord === null) {
            $this->flash('error', 'No import file found for this batch.');

            return;
        }

        try {
            // storage_path is disk-relative (e.g. imports/abc.xlsx).
            // Resolve to absolute path so the parser can open the file.
            $absolutePath = Storage::disk('local')->path($fileRecord->storage_path);
            app(ParseImportFile::class)($this->batch, $absolutePath);
            $this->batch->refresh();
            $this->flash('success', __('ui.saved', [], null, 'File parsed successfully.'));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    /**
     * Save column mappings and advance the batch to the validating stage.
     *
     * Passing null triggers the action's built-in alias-based auto-resolver.
     * Explicit user mappings (non-empty $columnMappings array) override auto-resolve.
     */
    public function saveColumnMappings(): void
    {
        $this->requirePermission('import.review');

        try {
            $mappings = ! empty($this->columnMappings) ? $this->columnMappings : null;
            app(MapColumns::class)($this->batch, $mappings);
            $this->batch->refresh();
            $this->flash('success', __('ui.saved', [], null, 'Column mappings saved.'));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function validateRows(): void
    {
        $this->requirePermission('import.review');

        try {
            app(ValidateRows::class)($this->batch);
            $this->batch->refresh();
            $this->flash('success', __('ui.saved', [], null, 'Rows validated.'));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function loadPreview(): void
    {
        $this->requirePermission('import.review');

        try {
            $this->previewData = app(PreviewRows::class)(
                $this->batch,
                page: $this->previewPage,
                statusFilter: $this->previewStatusFilter ?: null,
            );
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    /**
     * Apply the batch, recording the acting admin in the actor reference.
     *
     * ApplyImportBatch signature: __invoke(ImportBatch, string $actorReference = 'import')
     */
    public function apply(): void
    {
        $this->requirePermission('import.apply');

        try {
            app(ApplyImportBatch::class)($this->batch, 'admin:'.$this->adminId());
            $this->batch->refresh();
            $this->flash('success', __('ui.saved', [], null, 'Batch applied.'));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function cancel(): void
    {
        $this->requirePermission('import.upload');

        try {
            app(CancelImportBatch::class)($this->batch);
            $this->batch->refresh();
            $this->flash('success', __('ui.saved', [], null, 'Batch cancelled.'));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    /**
     * Stream the result report CSV to the browser.
     *
     * GenerateImportResultReport returns a temp file PATH (not CSV content).
     * We read the file, base64-encode it, dispatch to the x-data download
     * handler in the view, then delete the temp file.
     */
    public function downloadReport(): void
    {
        $this->requirePermission('import.review');

        try {
            $tempPath = app(GenerateImportResultReport::class)($this->batch);

            if (! file_exists($tempPath)) {
                $this->flash('error', 'Report file could not be generated.');

                return;
            }

            $csvContent = file_get_contents($tempPath);
            @unlink($tempPath);

            $filename = 'import-result-'.$this->batchId.'.csv';
            $this->dispatch('download-csv', filename: $filename, content: base64_encode($csvContent));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    private function flash(string $type, string $message): void
    {
        $this->flashType = $type;
        $this->flashMessage = $message;
    }

    public function render(): View
    {
        return view('livewire.admin.imports.detail', [
            'batch' => $this->batch,
        ])->layout('layouts.admin');
    }
}

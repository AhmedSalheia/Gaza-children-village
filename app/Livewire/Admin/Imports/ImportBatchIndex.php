<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Imports;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Modules\Imports\Actions\UploadImportFile;

/**
 * Import batch list with upload form.
 *
 * Uploaded files are stored privately via the UploadImportFile action.
 * No direct disk access in this component.
 */
final class ImportBatchIndex extends Component
{
    use HasAdminAuth;
    use WithFileUploads;
    use WithPagination;

    #[Url]
    public string $statusFilter = '';

    // Upload form
    public bool $showUploadForm = false;

    public $uploadFile = null;

    public int $uploadInstitutionId = 0;

    public string $uploadNotes = '';

    public string $flashMessage = '';

    public string $flashType = '';

    public function mount(): void
    {
        $this->requirePermission('import.upload');
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function batches(): LengthAwarePaginator
    {
        // original_filename lives on import_batches, not import_files
        return DB::table('import_batches as ib')
            ->select([
                'ib.id',
                'ib.status',
                'ib.created_at',
                'ib.applied_at',
                'ib.original_filename',
                'ib.institution_id',
            ])
            ->when($this->statusFilter !== '', fn ($q) => $q->where('ib.status', $this->statusFilter))
            ->orderByDesc('ib.created_at')
            ->paginate(20);
    }

    public function institutions(): Collection
    {
        return DB::table('institutions')->where('is_active', true)->orderBy('name_ar')->get(['id', 'name_ar']);
    }

    public function statusOptions(): array
    {
        return ['uploaded', 'parsing', 'ready_for_mapping', 'validating', 'ready_for_review', 'applying', 'completed', 'completed_with_errors', 'cancelled'];
    }

    public function upload(): void
    {
        $this->requirePermission('import.upload');

        $this->validate([
            // .xls is not supported by the spreadsheet parser (treated as CSV); accept only csv/xlsx.
            'uploadFile' => ['required', 'file', 'mimes:csv,xlsx', 'max:10240'],
            'uploadInstitutionId' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $result = app(UploadImportFile::class)(
                source: $this->uploadFile,
                actorAccountId: $this->adminId(),
                institutionId: $this->uploadInstitutionId,
                notes: $this->uploadNotes ?: null,
            );

            $batchId = $result['batch']->id;
            $this->reset(['uploadFile', 'uploadInstitutionId', 'uploadNotes', 'showUploadForm']);
            $this->flash('success', __('ui.created', [], null, 'File uploaded. Batch #'.$batchId.' created.'));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function cancelUpload(): void
    {
        $this->reset(['uploadFile', 'uploadInstitutionId', 'uploadNotes', 'showUploadForm']);
    }

    private function flash(string $type, string $message): void
    {
        $this->flashType = $type;
        $this->flashMessage = $message;
    }

    public function render(): View
    {
        return view('livewire.admin.imports.index', [
            'batches' => $this->batches(),
            'institutions' => $this->institutions(),
            'statusOptions' => $this->statusOptions(),
        ])->layout('layouts.admin');
    }
}

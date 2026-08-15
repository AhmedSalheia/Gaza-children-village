<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Imports;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Modules\Imports\Actions\UploadImportFile;

/**
 * Import batch list and file upload for staff.
 *
 * Upload is institution-scoped: the institution_id and operational_period_id
 * come from the staff member's active position.
 *
 * Only CSV and XLSX files are accepted (.xls is not supported by the parser).
 *
 * Requires import.upload permission to access this page and upload files.
 * import.review permission is required to view batch details.
 */
final class ImportBatchIndex extends Component
{
    use HasStaffAuth;
    use WithFileUploads;
    use WithPagination;

    #[Url]
    public string $statusFilter = '';

    public ?UploadedFile $uploadFile = null;

    public string $uploadNotes = '';

    public bool $showUploadForm = false;

    public function mount(): void
    {
        $this->requirePermission('import.upload');
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function batches(): LengthAwarePaginator
    {
        $scope = $this->staffScope();

        $query = DB::table('import_batches')
            ->where('institution_id', $scope['institution_id'])
            ->orderByDesc('created_at');

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        return $query->paginate(20, ['id', 'status', 'original_filename', 'file_size_bytes', 'created_at', 'notes']);
    }

    public function upload(UploadImportFile $action): void
    {
        $this->requirePermission('import.upload');

        $this->validate([
            'uploadFile' => ['required', 'file', 'max:51200', 'mimes:csv,xlsx'],
        ]);

        $scope = $this->staffScope();
        abort_if($scope['institution_id'] === null, 422, 'No institution scope.');

        // Derive the current operational period from the active position, if any.
        $account = auth('staff')->user();
        $periodId = null;

        if ($account?->staff_profile_id) {
            $pos = DB::table('staff_positions')
                ->where('staff_profile_id', $account->staff_profile_id)
                ->whereNull('ended_on')
                ->whereNotNull('institution_semester_id')
                ->orderByDesc('started_on')
                ->value('id');

            if ($pos) {
                $periodId = DB::table('staff_position_periods')
                    ->where('staff_position_id', $pos)
                    ->value('operational_period_id');
            }
        }

        try {
            $action(
                source: $this->uploadFile,
                actorAccountId: $this->staffAccountId(),
                institutionId: $scope['institution_id'],
                operationalPeriodId: $periodId ? (int) $periodId : null,
                notes: $this->uploadNotes ?: null,
            );

            session()->flash('success', __('ui.import_uploaded', [], null, 'File uploaded. Proceed to process it.'));
            $this->showUploadForm = false;
            $this->reset(['uploadFile', 'uploadNotes']);
        } catch (\Throwable $e) {
            $this->addError('upload', $e->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.staff.imports.index', [
            'batches' => $this->batches(),
            'canReview' => $this->staffCan('import.review'),
        ])->layout('layouts.staff');
    }
}

@php /** @var \App\Livewire\Staff\Imports\ImportBatchIndex $this */ @endphp

<div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-block-end:var(--space-6);flex-wrap:wrap;gap:var(--space-3)">
        <h1 style="font-size:var(--text-2xl);font-weight:700;margin:0">{{ __('ui.imports', [], null, 'Imports') }}</h1>
        <button wire:click="$set('showUploadForm', true)" class="btn btn--primary btn--sm">
            + {{ __('ui.upload_file', [], null, 'Upload File') }}
        </button>
    </div>

    @error('upload') <div class="alert alert--danger">{{ $message }}</div> @enderror

    {{-- Upload form --}}
    @if($showUploadForm)
    <div class="card" style="margin-block-end:var(--space-6)">
        <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">{{ __('ui.upload_import_file', [], null, 'Upload Import File') }}</h2>
        <div class="form-group">
            <label class="form-label form-label--required">{{ __('ui.file', [], null, 'File') }}</label>
            <input type="file" wire:model="uploadFile" class="form-control @error('uploadFile') form-control--error @enderror"
                accept=".csv,.xlsx">
            <span class="form-hint">{{ __('ui.accepted_formats', [], null, 'Accepted formats: CSV, XLSX (max 50 MB)') }}</span>
            @error('uploadFile') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label class="form-label">{{ __('ui.notes', [], null, 'Notes') }}</label>
            <input type="text" wire:model="uploadNotes" class="form-control"
                placeholder="{{ __('ui.upload_notes_placeholder', [], null, 'Optional notes about this batch…') }}">
        </div>
        <div style="display:flex;gap:var(--space-3)">
            <button wire:click="upload" class="btn btn--primary">{{ __('ui.upload', [], null, 'Upload') }}</button>
            <button wire:click="$set('showUploadForm', false)" class="btn btn--outline">{{ __('ui.cancel', [], null, 'Cancel') }}</button>
        </div>
    </div>
    @endif

    {{-- Filter --}}
    <div style="margin-block-end:var(--space-4)">
        <select wire:model.live="statusFilter" class="form-control form-select" style="max-inline-size:200px">
            <option value="">{{ __('ui.all_statuses', [], null, 'All Statuses') }}</option>
            <option value="uploaded">uploaded</option>
            <option value="parsing">parsing</option>
            <option value="ready_for_mapping">ready_for_mapping</option>
            <option value="validating">validating</option>
            <option value="ready_for_review">ready_for_review</option>
            <option value="applying">applying</option>
            <option value="completed">completed</option>
            <option value="completed_with_errors">completed_with_errors</option>
            <option value="cancelled">cancelled</option>
        </select>
    </div>

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('ui.filename', [], null, 'File') }}</th>
                <th>{{ __('ui.status', [], null, 'Status') }}</th>
                <th>{{ __('ui.file_size', [], null, 'Size') }}</th>
                <th>{{ __('ui.notes', [], null, 'Notes') }}</th>
                <th>{{ __('ui.uploaded_at', [], null, 'Uploaded') }}</th>
                <th></th>
            </tr></thead>
            <tbody>
                @forelse($batches as $batch)
                <tr>
                    <td>{{ $batch->original_filename ?? '#' . $batch->id }}</td>
                    <td>
                        <span class="badge badge--{{ match($batch->status) {
                            'completed' => 'active',
                            'cancelled', 'completed_with_errors' => 'closed',
                            'uploaded', 'parsing', 'ready_for_mapping', 'validating', 'ready_for_review', 'applying' => 'pending',
                            default => 'draft'
                        } }}">{{ $batch->status }}</span>
                    </td>
                    <td>{{ $batch->file_size_bytes ? number_format($batch->file_size_bytes / 1024, 1) . ' KB' : '—' }}</td>
                    <td style="max-inline-size:200px;overflow:hidden;text-overflow:ellipsis">{{ $batch->notes ?? '—' }}</td>
                    <td>{{ $batch->created_at }}</td>
                    <td>
                        @if($canReview)
                        <a href="{{ route('staff.imports.detail', ['batchId' => $batch->id]) }}" class="btn btn--outline btn--sm" wire:navigate>
                            {{ __('ui.process', [], null, 'Process') }}
                        </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;color:var(--text-secondary);padding:var(--space-8);font-style:italic">{{ __('ui.no_imports', [], null, 'No import batches found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $batches->links() }}
</div>

<style>.card{background:var(--surface-card);border:1px solid var(--card-border);border-radius:var(--card-radius);padding:var(--card-padding);box-shadow:var(--card-shadow)}</style>

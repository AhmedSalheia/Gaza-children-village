@php /** @var \App\Livewire\Admin\Imports\ImportBatchIndex $this */ @endphp

<div>
    <div class="page-header">
        <h1 class="page-title">{{ __('ui.imports', [], null, 'Import Batches') }}</h1>
        <button wire:click="$set('showUploadForm', true)" class="btn btn--primary btn--sm">
            + {{ __('ui.upload_file', [], null, 'Upload File') }}
        </button>
    </div>

    @include('livewire.admin._partials.flash-message')

    {{-- Upload form --}}
    @if($showUploadForm)
        <div class="card" style="margin-block-end:var(--space-4);max-inline-size:560px">
            <h2 style="font-size:var(--text-base);font-weight:600;margin-block-end:var(--space-4)">
                {{ __('ui.upload_import_file', [], null, 'Upload Import File') }}
            </h2>

            <div class="form-group">
                <label class="form-label form-label--required">{{ __('ui.institution', [], null, 'Institution') }}</label>
                <select wire:model="uploadInstitutionId" class="form-control form-select @error('uploadInstitutionId') form-control--error @enderror">
                    <option value="0">{{ __('ui.select', [], null, 'Select…') }}</option>
                    @foreach($institutions as $inst)
                        <option value="{{ $inst->id }}">{{ $inst->name_ar }}</option>
                    @endforeach
                </select>
                @error('uploadInstitutionId')<span class="form-error">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label class="form-label form-label--required">{{ __('ui.file', [], null, 'File (CSV or Excel)') }}</label>
                <input wire:model="uploadFile" type="file" accept=".csv,.xlsx" class="form-control @error('uploadFile') form-control--error @enderror">
                @error('uploadFile')<span class="form-error">{{ $message }}</span>@enderror
                <span class="form-hint">{{ __('ui.max_size', [], null, 'Max 10 MB. Accepted: .csv, .xlsx') }}</span>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('ui.notes', [], null, 'Notes') }}</label>
                <textarea wire:model="uploadNotes" class="form-control" rows="2"></textarea>
            </div>

            <div style="display:flex;gap:var(--space-2)">
                <button wire:click="upload" wire:loading.attr="disabled" class="btn btn--primary btn--sm">
                    <span wire:loading wire:target="upload">{{ __('ui.uploading', [], null, 'Uploading…') }}</span>
                    <span wire:loading.remove wire:target="upload">{{ __('ui.upload', [], null, 'Upload') }}</span>
                </button>
                <button wire:click="cancelUpload" class="btn btn--outline btn--sm">{{ __('ui.cancel', [], null, 'Cancel') }}</button>
            </div>
        </div>
    @endif

    <div class="filters-bar">
        <select wire:model.live="statusFilter" class="form-control form-select" style="max-inline-size:220px">
            <option value="">{{ __('ui.all_statuses', [], null, 'All statuses') }}</option>
            @foreach($statusOptions as $opt)
                <option value="{{ $opt }}">{{ $opt }}</option>
            @endforeach
        </select>
    </div>

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('ui.filename', [], null, 'File') }}</th>
                    <th>{{ __('ui.status', [], null, 'Status') }}</th>
                    <th>{{ __('ui.created', [], null, 'Created') }}</th>
                    <th>{{ __('ui.applied', [], null, 'Applied') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches as $batch)
                    <tr>
                        <td style="font-size:var(--text-sm);color:var(--text-secondary)">#{{ $batch->id }}</td>
                        <td>{{ $batch->original_filename ?? __('ui.no_file', [], null, '(no file)') }}</td>
                        <td>
                            <span class="badge badge--{{ match($batch->status) {
                                'completed' => 'active',
                                'cancelled', 'completed_with_errors' => 'closed',
                                'ready_for_review', 'ready_for_mapping' => 'open',
                                'applying', 'validating', 'parsing' => 'pending',
                                default => 'draft'
                            } }}">{{ $batch->status }}</span>
                        </td>
                        <td style="font-size:var(--text-sm)">
                            {{ $batch->created_at ? \Carbon\Carbon::parse($batch->created_at)->format('Y-m-d H:i') : '—' }}
                        </td>
                        <td style="font-size:var(--text-sm)">
                            {{ $batch->applied_at ? \Carbon\Carbon::parse($batch->applied_at)->format('Y-m-d H:i') : '—' }}
                        </td>
                        <td>
                            <a href="{{ route('admin.imports.detail', ['batchId' => $batch->id]) }}" class="btn btn--outline btn--sm" wire:navigate>
                                {{ __('ui.view', [], null, 'View') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-state">{{ __('ui.no_batches', [], null, 'No import batches yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $batches->links() }}
</div>

@include('livewire.admin._partials.page-styles')

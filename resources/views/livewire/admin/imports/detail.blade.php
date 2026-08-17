@php /** @var \App\Livewire\Admin\Imports\ImportBatchDetail $this */ @endphp

<div x-data="{}"
    x-on:download-csv.window="
        const a = document.createElement('a');
        a.href = 'data:text/csv;base64,' + $event.detail.content;
        a.download = $event.detail.filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    "
>
    <div class="page-header">
        <div style="display:flex;align-items:center;gap:var(--space-3)">
            <a href="{{ route('admin.imports.index') }}" class="btn btn--outline btn--sm" wire:navigate>← {{ __('ui.back', [], null, 'Back') }}</a>
            <h1 class="page-title" style="margin:0">
                {{ __('ui.import_batch', [], null, 'Import Batch') }} #{{ $batchId }}
            </h1>
            @if($batch)
                <span class="badge badge--{{ match($batch->status->value) {
                    'completed' => 'active',
                    'cancelled', 'completed_with_errors' => 'closed',
                    'ready_for_review', 'ready_for_mapping' => 'open',
                    default => 'pending'
                } }}">{{ $batch->status->value }}</span>
            @endif
        </div>
    </div>

    @if($flashMessage !== '')
        <div class="alert alert--{{ $flashType === 'success' ? 'success' : 'danger' }}">{{ $flashMessage }}</div>
    @endif

    @if($batch)
        {{-- Actions bar --}}
        <div class="card" style="margin-block-end:var(--space-4)">
            <div style="display:flex;gap:var(--space-2);flex-wrap:wrap;align-items:center">
                @php $status = $batch->status->value; @endphp

                @if($status === 'uploaded')
                    <button wire:click="parseFile" wire:loading.attr="disabled" class="btn btn--primary btn--sm">
                        {{ __('ui.parse_file', [], null, 'Parse File') }}
                    </button>
                @endif

                @if($status === 'ready_for_mapping')
                    <button wire:click="saveColumnMappings" class="btn btn--primary btn--sm">
                        {{ __('ui.save_mappings', [], null, 'Save Column Mappings') }}
                    </button>
                @endif

                @if($status === 'validating')
                    <button wire:click="validateRows" class="btn btn--primary btn--sm">
                        {{ __('ui.validate_rows', [], null, 'Run Validation') }}
                    </button>
                @endif

                @if($status === 'ready_for_review')
                    <button wire:click="loadPreview" class="btn btn--outline btn--sm">
                        {{ __('ui.load_preview', [], null, 'Load Preview') }}
                    </button>
                    <button wire:click="apply" wire:loading.attr="disabled" class="btn btn--primary btn--sm">
                        {{ __('ui.apply', [], null, 'Apply Batch') }}
                    </button>
                @endif

                @if(in_array($status, ['completed', 'completed_with_errors']))
                    <button wire:click="downloadReport" class="btn btn--secondary btn--sm">
                        ↓ {{ __('ui.download_report', [], null, 'Download Result Report') }}
                    </button>
                @endif

                @if(!in_array($status, ['completed', 'completed_with_errors', 'cancelled']))
                    <button wire:click="cancel" class="btn btn--danger btn--sm" onclick="return confirm('{{ __('ui.confirm_cancel', [], null, 'Cancel this import?') }}')">
                        {{ __('ui.cancel_batch', [], null, 'Cancel') }}
                    </button>
                @endif
            </div>
        </div>

        {{-- Batch metadata --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6);margin-block-end:var(--space-6)">
            <div class="card">
                <h2 style="font-size:var(--text-base);font-weight:600;margin-block-end:var(--space-3)">{{ __('ui.batch_info', [], null, 'Batch Info') }}</h2>
                {{-- original_filename and file_size_bytes live on import_batches, not import_files --}}
                <dl style="display:grid;grid-template-columns:auto 1fr;gap:var(--space-2) var(--space-4)">
                    <dt style="color:var(--text-secondary);font-size:var(--text-sm)">{{ __('ui.filename', [], null, 'File') }}</dt>
                    <dd>{{ $batch->original_filename ?? '—' }}</dd>
                    <dt style="color:var(--text-secondary);font-size:var(--text-sm)">{{ __('ui.file_size', [], null, 'Size') }}</dt>
                    <dd>{{ $batch->file_size_bytes ? number_format($batch->file_size_bytes / 1024, 1) . ' KB' : '—' }}</dd>
                    <dt style="color:var(--text-secondary);font-size:var(--text-sm)">{{ __('ui.row_count', [], null, 'Rows') }}</dt>
                    <dd>{{ $batch->total_rows ?? '—' }}</dd>
                    <dt style="color:var(--text-secondary);font-size:var(--text-sm)">{{ __('ui.applied', [], null, 'Applied') }}</dt>
                    <dd>{{ $batch->applied_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                </dl>
            </div>

            @if($status === 'ready_for_review' || in_array($status, ['completed', 'completed_with_errors']))
                <div class="card">
                    <h2 style="font-size:var(--text-base);font-weight:600;margin-block-end:var(--space-3)">{{ __('ui.row_preview', [], null, 'Row Preview') }}</h2>

                    <div style="display:flex;gap:var(--space-2);margin-block-end:var(--space-3)">
                        <select wire:model="previewStatusFilter" class="form-control form-select" style="max-inline-size:180px">
                            <option value="">{{ __('ui.all', [], null, 'All rows') }}</option>
                            <option value="created">{{ __('status.created') }}</option>
                            <option value="updated">{{ __('status.updated') }}</option>
                            <option value="skipped_existing">{{ __('status.skipped') }}</option>
                            <option value="conflict">{{ __('status.conflict') }}</option>
                            <option value="invalid">{{ __('status.invalid') }}</option>
                            <option value="failed">{{ __('status.failed') }}</option>
                        </select>
                        <button wire:click="loadPreview" class="btn btn--outline btn--sm">{{ __('ui.refresh', [], null, 'Refresh') }}</button>
                    </div>

                    @if($previewData !== null)
                        <div style="font-size:var(--text-sm);color:var(--text-secondary);margin-block-end:var(--space-2)">
                            {{ __('ui.total', [], null, 'Total') }}: {{ $previewData['total'] }} |
                            {{ __('ui.valid', [], null, 'Valid') }}: {{ $previewData['valid'] }} |
                            {{ __('ui.errors', [], null, 'Errors') }}: {{ $previewData['errors'] }}
                        </div>

                        <div class="data-table-wrapper" style="max-block-size:300px;overflow-y:auto">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('ui.status', [], null, 'Status') }}</th>
                                        <th>{{ __('ui.summary', [], null, 'Summary') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($previewData['rows'] ?? [] as $row)
                                        <tr>
                                            <td>{{ $row['row_number'] }}</td>
                                            <td>
                                                <span class="badge badge--{{ match($row['status']) { 'created','updated' => 'active', 'skipped_existing' => 'pending', default => 'closed' } }}">
                                                    {{ $row['status'] }}
                                                </span>
                                            </td>
                                            <td style="font-size:var(--text-sm)">{{ $row['summary'] ?? $row['proposed_action'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- Column mapping form (shown when in ready_for_mapping status) --}}
        @if($status === 'ready_for_mapping')
            <div class="card">
                <h2 style="font-size:var(--text-base);font-weight:600;margin-block-end:var(--space-3)">{{ __('ui.column_mapping', [], null, 'Column Mapping') }}</h2>
                <p style="font-size:var(--text-sm);color:var(--text-secondary);margin-block-end:var(--space-4)">
                    {{ __('ui.column_mapping_hint', [], null, 'Map the file columns to GCV DATA fields. Leave unmapped columns empty.') }}
                </p>
                {{-- The actual column detection and mapping UI is simplified here --}}
                <div class="alert alert--info">
                    {{ __('ui.auto_map_notice', [], null, 'Column aliases are resolved automatically using the built-in alias registry. Click "Save Column Mappings" to proceed.') }}
                </div>
                <button wire:click="saveColumnMappings" class="btn btn--primary btn--sm" style="margin-block-start:var(--space-3)">
                    {{ __('ui.save_mappings', [], null, 'Save & Auto-Map') }}
                </button>
            </div>
        @endif
    @endif
</div>

@include('livewire.admin._partials.page-styles')

@php /** @var \App\Livewire\Staff\Imports\ImportBatchDetail $this */ @endphp

<div>
    <div style="margin-block-end:var(--space-6)">
        <a href="{{ route('staff.imports.index') }}" class="link" wire:navigate style="font-size:var(--text-sm)">
            ← {{ __('ui.back_to_imports', [], null, 'Back to Imports') }}
        </a>
        <h1 style="font-size:var(--text-2xl);font-weight:700;margin:var(--space-1) 0 0">
            {{ $batch?->original_filename ?? __('ui.import_batch', [], null, 'Import Batch') . ' #' . $batchId }}
        </h1>
        @if($batch)
        <div style="margin-block-start:var(--space-1)">
            <span class="badge badge--{{ match($batch->status) {
                'completed' => 'active',
                'cancelled', 'completed_with_errors' => 'closed',
                default => 'pending'
            } }}">{{ $batch->status }}</span>
            <span style="font-size:var(--text-sm);color:var(--text-secondary);margin-inline-start:var(--space-2)">
                {{ number_format(($batch->file_size_bytes ?? 0) / 1024, 1) }} KB
            </span>
        </div>
        @endif
    </div>

    @error('parse')   <div class="alert alert--danger">{{ $message }}</div> @enderror
    @error('mapping') <div class="alert alert--danger">{{ $message }}</div> @enderror
    @error('validate') <div class="alert alert--danger">{{ $message }}</div> @enderror
    @error('preview') <div class="alert alert--danger">{{ $message }}</div> @enderror
    @error('apply')   <div class="alert alert--danger">{{ $message }}</div> @enderror
    @error('cancel')  <div class="alert alert--danger">{{ $message }}</div> @enderror
    @error('report')  <div class="alert alert--danger">{{ $message }}</div> @enderror

    {{-- Workflow actions --}}
    @if($batch)
    <div class="card" style="margin-block-end:var(--space-6)">
        <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">{{ __('ui.processing_pipeline', [], null, 'Processing Pipeline') }}</h2>
        <div style="display:flex;gap:var(--space-3);flex-wrap:wrap">

            {{-- Parse --}}
            @if($batch->status === 'uploaded')
            @foreach($files as $file)
            <button wire:click="parseFile({{ $file->id }})" class="btn btn--primary btn--sm">
                {{ __('ui.parse_file', [], null, 'Parse File') }}
            </button>
            @endforeach
            @endif

            {{-- Map columns --}}
            @if(in_array($batch->status, ['ready_for_mapping']))
            <button wire:click="saveColumnMappings" class="btn btn--primary btn--sm">
                {{ __('ui.auto_map_columns', [], null, 'Auto-Map Columns') }}
            </button>
            <button wire:click="$set('showMappingForm', true)" class="btn btn--outline btn--sm">
                {{ __('ui.manual_map', [], null, 'Manual Map') }}
            </button>
            @endif

            {{-- Validate --}}
            @if(in_array($batch->status, ['ready_for_mapping', 'validating']))
            <button wire:click="validateRows" class="btn btn--secondary btn--sm">
                {{ __('ui.validate', [], null, 'Validate Rows') }}
            </button>
            @endif

            {{-- Preview --}}
            @if(in_array($batch->status, ['ready_for_review', 'validating', 'completed', 'completed_with_errors']))
            <button wire:click="loadPreview" class="btn btn--outline btn--sm">
                {{ __('ui.preview', [], null, 'Preview Rows') }}
            </button>
            @endif

            {{-- Apply --}}
            @if($canApply && $batch->status === 'ready_for_review')
            <button wire:click="apply" class="btn btn--primary btn--sm">
                {{ __('ui.apply_batch', [], null, 'Apply Batch') }}
            </button>
            @endif

            {{-- Cancel --}}
            @if(! in_array($batch->status, ['completed', 'completed_with_errors', 'cancelled']))
            <button wire:click="cancel" class="btn btn--danger btn--sm">
                {{ __('ui.cancel_import', [], null, 'Cancel') }}
            </button>
            @endif

            {{-- Download report --}}
            @if(in_array($batch->status, ['completed', 'completed_with_errors']))
            <button wire:click="downloadReport" class="btn btn--outline btn--sm">
                ↓ {{ __('ui.download_report', [], null, 'Download Report') }}
            </button>
            @endif

        </div>
    </div>
    @endif

    {{-- Manual column mapping --}}
    @if($showMappingForm)
    <div class="card" style="margin-block-end:var(--space-6)">
        <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">{{ __('ui.column_mappings', [], null, 'Column Mappings') }}</h2>
        <p style="color:var(--text-secondary);font-size:var(--text-sm);margin-block-end:var(--space-4)">
            {{ __('ui.column_mappings_hint', [], null, 'Enter JSON mappings or leave empty for auto-resolve. Format: {"source_column": "target_field"}') }}
        </p>
        <div class="form-group">
            <label class="form-label">{{ __('ui.mappings_json', [], null, 'Mappings JSON') }}</label>
            <textarea class="form-control" rows="5" wire:model="columnMappingsJson"
                placeholder='{"الاسم الكامل": "full_name_ar", "تاريخ الميلاد": "birth_date"}'></textarea>
        </div>
        <div style="display:flex;gap:var(--space-3)">
            <button wire:click="saveColumnMappings" class="btn btn--primary">{{ __('ui.save_mappings', [], null, 'Save Mappings') }}</button>
            <button wire:click="$set('showMappingForm', false)" class="btn btn--outline">{{ __('ui.cancel', [], null, 'Cancel') }}</button>
        </div>
    </div>
    @endif

    {{-- Preview --}}
    @if(! empty($previewData))
    <div class="card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-block-end:var(--space-4)">
            <h2 style="font-size:var(--text-lg);font-weight:600;margin:0">{{ __('ui.row_preview', [], null, 'Row Preview') }}</h2>
            <div style="display:flex;gap:var(--space-2);align-items:center">
                <select wire:model.live="previewStatus" class="form-control form-select form-select--sm" style="font-size:var(--text-sm)">
                    <option value="">{{ __('ui.all', [], null, 'All') }}</option>
                    <option value="valid">valid</option>
                    <option value="invalid">invalid</option>
                    <option value="skipped">skipped</option>
                </select>
                <button wire:click="$set('previewPage', max(1, previewPage - 1))" class="btn btn--outline btn--sm">‹</button>
                <span style="font-size:var(--text-sm)">{{ $previewPage }}</span>
                <button wire:click="$set('previewPage', previewPage + 1)" class="btn btn--outline btn--sm">›</button>
            </div>
        </div>

        @if(!empty($previewData['rows']))
        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('ui.row', [], null, 'Row') }}</th>
                        <th>{{ __('ui.status', [], null, 'Status') }}</th>
                        <th>{{ __('ui.data', [], null, 'Data') }}</th>
                        <th>{{ __('ui.errors', [], null, 'Errors') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($previewData['rows'] as $row)
                    <tr>
                        <td>{{ $row['row_number'] ?? '—' }}</td>
                        <td>
                            <span class="badge badge--{{ match($row['status'] ?? '') {
                                'valid' => 'active',
                                'invalid' => 'closed',
                                'skipped' => 'draft',
                                default => 'pending'
                            } }}">{{ $row['status'] ?? '—' }}</span>
                        </td>
                        <td style="font-size:var(--text-xs);max-inline-size:300px">
                            @if(isset($row['data']) && is_array($row['data']))
                                @foreach($row['data'] as $k => $v)
                                <div><strong>{{ $k }}</strong>: {{ $v }}</div>
                                @endforeach
                            @endif
                        </td>
                        <td style="font-size:var(--text-xs);color:var(--text-danger)">
                            @if(!empty($row['errors']))
                                @foreach($row['errors'] as $err)
                                <div>{{ $err }}</div>
                                @endforeach
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @endif

    {{-- Files list --}}
    @if($files->isNotEmpty())
    <div class="card" style="margin-block-start:var(--space-6)">
        <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">{{ __('ui.files', [], null, 'Files') }}</h2>
        <table class="data-table">
            <thead><tr>
                <th>ID</th>
                <th>{{ __('ui.storage_path', [], null, 'Storage Path') }}</th>
                <th>SHA-256</th>
            </tr></thead>
            <tbody>
                @foreach($files as $file)
                <tr>
                    <td>{{ $file->id }}</td>
                    <td style="font-size:var(--text-xs);font-family:monospace">{{ $file->storage_path }}</td>
                    <td style="font-size:var(--text-xs);font-family:monospace">{{ substr($file->content_sha256 ?? '', 0, 16) }}…</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

<style>.card{background:var(--surface-card);border:1px solid var(--card-border);border-radius:var(--card-radius);padding:var(--card-padding);box-shadow:var(--card-shadow)}</style>

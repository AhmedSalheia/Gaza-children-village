<div>
    {{-- ── Page header ─────────────────────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0">{{ __('reports.report_centre') }}</h4>
    </div>

    <div class="row g-4">
        {{-- ── Report family browser ───────────────────────────────────── --}}
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">{{ __('reports.report_families') }}</div>
                <div class="list-group list-group-flush">
                    @forelse($this->definitions as $family => $defs)
                        <div class="list-group-item bg-light small fw-bold text-uppercase">
                            {{ match($family) {
                                'registry' => __('reports.family_registry'),
                                'attendance' => __('reports.family_attendance'),
                                'marks' => __('reports.family_marks'),
                                'compliance' => __('reports.family_compliance'),
                                'staff' => __('reports.family_staff'),
                                'requests' => __('reports.family_requests'),
                                'audit' => __('reports.family_audit'),
                                default => $family,
                            } }}
                        </div>
                        @foreach($defs as $def)
                            <button type="button"
                                wire:click="selectDefinition('{{ $def->code }}')"
                                class="list-group-item list-group-item-action {{ $definitionCode === $def->code ? 'active' : '' }}">
                                {{ $def->name_ar }}
                            </button>
                        @endforeach
                    @empty
                        <div class="list-group-item text-muted text-center py-4">
                            {{ __('reports.no_reports_permission') }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── Filters + results ────────────────────────────────────────── --}}
        <div class="col-md-9">
            @if($this->selectedDefinition)
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>{{ $this->selectedDefinition->name_ar }}</span>
                        <span class="small text-muted">{{ $this->selectedDefinition->description_ar }}</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @if(in_array('institution_semester_id', $this->filterSchema, true))
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('reports.semester') }}</label>
                                    <select class="form-select form-select-sm" wire:model.live="semesterId">
                                        <option value="0">{{ __('reports.select_option') }}</option>
                                        @foreach($this->semesters as $sem)
                                            <option value="{{ $sem->id }}">
                                                {{ $sem->institution_name }} · {{ $sem->semester_name }}
                                                @if($sem->status === 'open') ({{ __('reports.open_status') }}) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            @if(in_array('class_group_id', $this->filterSchema, true))
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('reports.class_group') }}</label>
                                    <select class="form-select form-select-sm" wire:model.live="classGroupId">
                                        <option value="0">{{ __('reports.all') }}</option>
                                        @foreach($this->classGroups as $cg)
                                            <option value="{{ $cg->id }}">{{ $cg->name_ar }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            @if(in_array('operational_period_id', $this->filterSchema, true))
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('reports.operational_period') }}</label>
                                    <select class="form-select form-select-sm" wire:model.live="periodId">
                                        <option value="0">{{ __('reports.all') }}</option>
                                        @foreach($this->operationalPeriods as $p)
                                            <option value="{{ $p->id }}">{{ $p->name_ar }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            @if(in_array('date_from', $this->filterSchema, true))
                                <div class="col-md-2">
                                    <label class="form-label">{{ __('reports.date_from') }}</label>
                                    <input type="date" class="form-control form-control-sm" wire:model.live="dateFrom">
                                </div>
                            @endif

                            @if(in_array('date_to', $this->filterSchema, true))
                                <div class="col-md-2">
                                    <label class="form-label">{{ __('reports.date_to') }}</label>
                                    <input type="date" class="form-control form-control-sm" wire:model.live="dateTo">
                                </div>
                            @endif
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <button wire:click="runReport" wire:loading.attr="disabled" class="btn btn-primary btn-sm">
                                <span wire:loading wire:target="runReport" class="spinner-border spinner-border-sm me-1"></span>
                                {{ __('reports.run_report') }}
                            </button>
                            @if($canExport)
                                <button wire:click="exportReport" wire:loading.attr="disabled" class="btn btn-outline-success btn-sm">
                                    <span wire:loading wire:target="exportReport" class="spinner-border spinner-border-sm me-1"></span>
                                    {{ __('reports.export_excel') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ── Background export status panel ───────────────────── --}}
                @if($this->pendingOperation)
                    <div wire:poll.3s class="alert {{ match($this->pendingOperation->status) {
                        'completed' => 'alert-success',
                        'failed' => 'alert-danger',
                        default => 'alert-info',
                    } }} d-flex align-items-center justify-content-between">
                        <div>
                            @if($this->pendingOperation->status === 'completed')
                                {{ __('reports.export_completed') }}
                            @elseif($this->pendingOperation->status === 'failed')
                                {{ __('reports.export_failed') }} {{ $this->pendingOperation->failure_summary ?? __('reports.unknown_error') }}
                            @else
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                {{ __('reports.export_generating') }} ({{ $this->pendingOperation->status === 'queued' ? __('reports.queued') : __('reports.processing') }})
                            @endif
                        </div>
                        @if($this->pendingOperation->status === 'completed')
                            <button wire:click="downloadCompletedExport" class="btn btn-success btn-sm">{{ __('reports.download') }}</button>
                        @endif
                    </div>
                @endif

                {{-- ── Results table ────────────────────────────────────── --}}
                @if($hasRun)
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>{{ __('reports.results_first_rows', ['limit' => \App\Livewire\Admin\Reports\ReportCentre::PREVIEW_LIMIT]) }}</span>
                            <span class="small text-muted">{{ __('reports.rows_shown', ['count' => $this->rows->count()]) }}</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        @foreach($this->headings as $heading)
                                            <th>{{ $heading }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($this->rows as $row)
                                        <tr>
                                            @foreach((array) $row as $value)
                                                <td class="small">{{ $value ?? '—' }}</td>
                                            @endforeach
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ max(count($this->headings), 1) }}" class="text-center text-muted py-4">
                                                {{ __('reports.no_results') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @else
                <div class="card">
                    <div class="card-body text-center text-muted py-5">
                        {{ __('reports.select_report_to_start') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@script
<script>
    $wire.on('start-download', ({ url }) => {
        window.location.href = url;
    });
</script>
@endscript

<div>
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0">{{ __('reports.marks_results_title') }}</h4>
    </div>

    <div class="btn-group mb-4" role="group">
        <button type="button" wire:click="$set('reportType','completion')"
            class="btn btn-{{ $reportType === 'completion' ? 'primary' : 'outline-primary' }} btn-sm">
            {{ __('reports.marks_completion_short') }}
        </button>
        <button type="button" wire:click="$set('reportType','results')"
            class="btn btn-{{ $reportType === 'results' ? 'primary' : 'outline-primary' }} btn-sm">
            {{ __('reports.published_results') }}
        </button>
    </div>

    {{-- Class group filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('reports.class_group') }}</label>
                    <select class="form-select form-select-sm" wire:model.live="classGroupId">
                        <option value="0">{{ __('reports.all') }}</option>
                        @foreach($this->classGroups as $cg)
                            <option value="{{ $cg->id }}">{{ $cg->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    @if($reportType === 'completion')
        @php $s = $this->completionSummary; @endphp
        <div class="row g-3 mb-4">
            @foreach([['label' => __('reports.stat_total'), 'value' => $s->total, 'color' => 'primary'], ['label' => __('reports.sheet_draft'), 'value' => $s->draft, 'color' => 'secondary'], ['label' => __('reports.sheet_submitted'), 'value' => $s->submitted, 'color' => 'info'], ['label' => __('reports.sheet_verified'), 'value' => $s->verified, 'color' => 'warning'], ['label' => __('reports.sheet_returned'), 'value' => $s->returned, 'color' => 'danger'], ['label' => __('reports.sheet_approved'), 'value' => $s->approved, 'color' => 'success']] as $stat)
                <div class="col-6 col-md-2">
                    <div class="card text-center border-0 bg-{{ $stat['color'] }} bg-opacity-10">
                        <div class="card-body py-2">
                            <div class="fs-5 fw-bold text-{{ $stat['color'] }}">{{ $stat['value'] }}</div>
                            <div class="small text-muted">{{ $stat['label'] }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($canExport)
            <div class="mb-3">
                <button wire:click="exportCompletion" wire:loading.attr="disabled" class="btn btn-outline-success btn-sm">
                    <span wire:loading wire:target="exportCompletion" class="spinner-border spinner-border-sm me-1"></span>
                    {{ __('reports.export_excel') }}
                </button>
            </div>
        @endif

        <div class="card">
            <div class="card-header">{{ __('reports.mark_sheets') }}</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr><th>{{ __('reports.group') }}</th><th>{{ __('reports.subject') }}</th><th>{{ __('reports.teacher') }}</th><th>{{ __('reports.status') }}</th><th>{{ __('reports.version') }}</th><th>{{ __('reports.submission') }}</th><th>{{ __('reports.approval') }}</th></tr>
                    </thead>
                    <tbody>
                        @forelse($this->completionRows as $row)
                            <tr>
                                <td>{{ $row->class_group_name }}</td>
                                <td>{{ $row->subject_name }}</td>
                                <td>{{ $row->teacher_name }}</td>
                                <td>
                                    <span class="badge bg-{{ match($row->status) { 'approved' => 'success', 'verified' => 'warning', 'submitted' => 'info', 'returned' => 'danger', default => 'secondary' } }}">
                                        {{ match($row->status) { 'draft' => __('reports.sheet_draft'), 'submitted' => __('reports.sheet_submitted'), 'returned' => __('reports.sheet_returned'), 'verified' => __('reports.sheet_verified'), 'approved' => __('reports.sheet_approved'), default => $row->status } }}
                                    </span>
                                </td>
                                <td>v{{ $row->version }}</td>
                                <td class="small text-muted">{{ $row->submitted_at ? \Carbon\Carbon::parse($row->submitted_at)->format('Y-m-d') : '—' }}</td>
                                <td class="small text-muted">{{ $row->approved_at ? \Carbon\Carbon::parse($row->approved_at)->format('Y-m-d') : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">{{ __('reports.no_sheets') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @else
        @if($canExport)
            <div class="mb-3">
                <button wire:click="exportResults" wire:loading.attr="disabled" class="btn btn-outline-success btn-sm">
                    <span wire:loading wire:target="exportResults" class="spinner-border spinner-border-sm me-1"></span>
                    {{ __('reports.export_results_excel') }}
                </button>
            </div>
        @endif

        <div class="card">
            <div class="card-header">{{ __('reports.published_results_first_500') }}</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr><th>{{ __('reports.group') }}</th><th>{{ __('reports.student') }}</th><th>{{ __('reports.subject') }}</th><th>{{ __('reports.score') }}</th><th>{{ __('reports.grade') }}</th><th>{{ __('reports.status') }}</th></tr>
                    </thead>
                    <tbody>
                        @forelse($this->resultRows as $row)
                            <tr>
                                <td>{{ $row->class_group_name }}</td>
                                <td>{{ $row->student_name }}</td>
                                <td>{{ $row->subject_name }}</td>
                                <td class="fw-semibold">{{ $row->normalized_score !== null ? number_format((float)$row->normalized_score, 1) : '—' }}</td>
                                <td>@if($row->grade_code)<span class="badge bg-primary bg-opacity-75">{{ $row->grade_code }}</span>@else<span class="text-muted">—</span>@endif</td>
                                <td>
                                    <span class="badge bg-{{ match($row->completeness_status) { 'complete' => 'success', 'incomplete' => 'warning', default => 'secondary' } }} bg-opacity-75 small">
                                        {{ match($row->completeness_status) { 'complete' => __('reports.complete'), 'incomplete' => __('reports.incomplete'), 'all_absent' => __('reports.all_absent'), 'no_assessments' => __('reports.no_assessments'), default => $row->completeness_status } }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">{{ __('reports.no_published_results') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

@script
<script>
    $wire.on('start-download', ({ url }) => {
        window.location.href = url;
    });
</script>
@endscript

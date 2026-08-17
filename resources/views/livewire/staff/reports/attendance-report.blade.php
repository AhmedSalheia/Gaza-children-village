<div>
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0">{{ __('reports.attendance_report') }}</h4>
    </div>

    <div class="btn-group mb-4" role="group">
        <button type="button" wire:click="$set('reportType','student')"
            class="btn btn-{{ $reportType === 'student' ? 'primary' : 'outline-primary' }} btn-sm">
            {{ __('reports.student_attendance') }}
        </button>
        <button type="button" wire:click="$set('reportType','staff')"
            class="btn btn-{{ $reportType === 'staff' ? 'primary' : 'outline-primary' }} btn-sm">
            {{ __('reports.staff_attendance') }}
        </button>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                @if($reportType === 'student')
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
                <div class="col-md-3">
                    <label class="form-label">{{ __('reports.date_from') }}</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="dateFrom">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('reports.date_to') }}</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="dateTo">
                </div>
            </div>
        </div>
    </div>

    @if($reportType === 'student')
        @php $stats = $this->summaryStats; @endphp
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card text-center border-0 bg-light"><div class="card-body py-2">
                    <div class="fs-4 fw-bold text-primary">{{ number_format($stats->total) }}</div>
                    <div class="small text-muted">{{ __('reports.stat_total') }}</div>
                </div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center border-0 bg-success bg-opacity-10"><div class="card-body py-2">
                    <div class="fs-4 fw-bold text-success">{{ number_format($stats->present) }}</div>
                    <div class="small text-muted">{{ __('reports.present') }}</div>
                </div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center border-0 bg-danger bg-opacity-10"><div class="card-body py-2">
                    <div class="fs-4 fw-bold text-danger">{{ number_format($stats->absent) }}</div>
                    <div class="small text-muted">{{ __('reports.absent') }}</div>
                </div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center border-0 bg-warning bg-opacity-10"><div class="card-body py-2">
                    <div class="fs-4 fw-bold text-warning">{{ number_format($stats->late) }}</div>
                    <div class="small text-muted">{{ __('reports.late') }}</div>
                </div></div>
            </div>
        </div>

        @if($canExport)
            <div class="mb-3">
                <button wire:click="exportStudentAttendance" wire:loading.attr="disabled" class="btn btn-outline-success btn-sm">
                    <span wire:loading wire:target="exportStudentAttendance" class="spinner-border spinner-border-sm me-1"></span>
                    {{ __('reports.export_excel') }}
                </button>
            </div>
        @endif

        <div class="card">
            <div class="card-header">{{ __('reports.attendance_records_first_500') }}</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr><th>{{ __('reports.date') }}</th><th>{{ __('reports.group') }}</th><th>{{ __('reports.student') }}</th><th>{{ __('reports.status') }}</th><th>{{ __('reports.reason') }}</th><th>{{ __('reports.record_status') }}</th></tr>
                    </thead>
                    <tbody>
                        @forelse($this->studentRows as $row)
                            <tr>
                                <td>{{ $row->attendance_date }}</td>
                                <td>{{ $row->class_group_name }}</td>
                                <td>{{ $row->student_name }}</td>
                                <td>
                                    <span class="badge bg-{{ match($row->status_code) { 'present' => 'success', 'absent' => 'danger', 'late' => 'warning', default => 'secondary' } }}">
                                        {{ match($row->status_code) { 'present' => __('reports.present'), 'absent' => __('reports.absent'), 'late' => __('reports.late'), default => $row->status_code } }}
                                    </span>
                                </td>
                                <td class="small text-muted">{{ $row->reason ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ match($row->sheet_status) { 'verified' => 'success', 'submitted' => 'info', 'returned' => 'warning', default => 'secondary' } }} bg-opacity-75 small">
                                        {{ match($row->sheet_status) { 'draft' => __('reports.sheet_draft'), 'submitted' => __('reports.sheet_submitted'), 'returned' => __('reports.sheet_returned'), 'verified' => __('reports.sheet_verified'), default => $row->sheet_status } }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">{{ __('reports.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @else
        @if(!$this->isFullScopePosition())
            <div class="alert alert-info">{{ __('reports.staff_attendance_restricted') }}</div>
        @else
            <div class="card">
                <div class="card-header">{{ __('reports.staff_attendance_first_500') }}</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr><th>{{ __('reports.date') }}</th><th>{{ __('reports.employee') }}</th><th>{{ __('reports.code') }}</th><th>{{ __('reports.status') }}</th><th>{{ __('reports.arrival_time') }}</th><th>{{ __('reports.verified_col') }}</th></tr>
                        </thead>
                        <tbody>
                            @forelse($this->staffRows as $row)
                                <tr>
                                    <td>{{ $row->record_date }}</td>
                                    <td>{{ $row->staff_name }}</td>
                                    <td class="small text-muted">{{ $row->staff_code }}</td>
                                    <td>
                                        <span class="badge bg-{{ match($row->status_code) { 'present' => 'success', 'absent' => 'danger', 'late' => 'warning', default => 'secondary' } }}">
                                            {{ match($row->status_code) { 'present' => __('reports.present'), 'absent' => __('reports.absent'), 'late' => __('reports.late'), default => $row->status_code } }}
                                        </span>
                                    </td>
                                    <td class="small">{{ $row->confirmed_arrived_at ?? '—' }}</td>
                                    <td>@if($row->is_verified)<span class="text-success">✓</span>@else<span class="text-muted">—</span>@endif</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">{{ __('reports.no_records') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif
</div>

@script
<script>
    $wire.on('start-download', ({ url }) => {
        window.location.href = url;
    });
</script>
@endscript

<div>
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0">تقرير الحضور</h4>
    </div>

    <div class="btn-group mb-4" role="group">
        <button type="button" wire:click="$set('reportType','student')"
            class="btn btn-{{ $reportType === 'student' ? 'primary' : 'outline-primary' }} btn-sm">
            حضور الطلاب
        </button>
        <button type="button" wire:click="$set('reportType','staff')"
            class="btn btn-{{ $reportType === 'staff' ? 'primary' : 'outline-primary' }} btn-sm">
            حضور الكادر
        </button>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                @if($reportType === 'student')
                    <div class="col-md-3">
                        <label class="form-label">المجموعة الدراسية</label>
                        <select class="form-select form-select-sm" wire:model.live="classGroupId">
                            <option value="0">الكل</option>
                            @foreach($this->classGroups as $cg)
                                <option value="{{ $cg->id }}">{{ $cg->name_ar }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-3">
                    <label class="form-label">من تاريخ</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="dateFrom">
                </div>
                <div class="col-md-3">
                    <label class="form-label">إلى تاريخ</label>
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
                    <div class="small text-muted">الإجمالي</div>
                </div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center border-0 bg-success bg-opacity-10"><div class="card-body py-2">
                    <div class="fs-4 fw-bold text-success">{{ number_format($stats->present) }}</div>
                    <div class="small text-muted">حاضر</div>
                </div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center border-0 bg-danger bg-opacity-10"><div class="card-body py-2">
                    <div class="fs-4 fw-bold text-danger">{{ number_format($stats->absent) }}</div>
                    <div class="small text-muted">غائب</div>
                </div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center border-0 bg-warning bg-opacity-10"><div class="card-body py-2">
                    <div class="fs-4 fw-bold text-warning">{{ number_format($stats->late) }}</div>
                    <div class="small text-muted">متأخر</div>
                </div></div>
            </div>
        </div>

        @if($canExport)
            <div class="mb-3">
                <button wire:click="exportStudentAttendance" wire:loading.attr="disabled" class="btn btn-outline-success btn-sm">
                    <span wire:loading wire:target="exportStudentAttendance" class="spinner-border spinner-border-sm me-1"></span>
                    تصدير إلى Excel
                </button>
            </div>
        @endif

        <div class="card">
            <div class="card-header">سجلات الحضور (أول 500)</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr><th>التاريخ</th><th>المجموعة</th><th>الطالب</th><th>الحالة</th><th>السبب</th><th>حالة السجل</th></tr>
                    </thead>
                    <tbody>
                        @forelse($this->studentRows as $row)
                            <tr>
                                <td>{{ $row->attendance_date }}</td>
                                <td>{{ $row->class_group_name }}</td>
                                <td>{{ $row->student_name }}</td>
                                <td>
                                    <span class="badge bg-{{ match($row->status_code) { 'present' => 'success', 'absent' => 'danger', 'late' => 'warning', default => 'secondary' } }}">
                                        {{ match($row->status_code) { 'present' => 'حاضر', 'absent' => 'غائب', 'late' => 'متأخر', default => $row->status_code } }}
                                    </span>
                                </td>
                                <td class="small text-muted">{{ $row->reason ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ match($row->sheet_status) { 'verified' => 'success', 'submitted' => 'info', 'returned' => 'warning', default => 'secondary' } }} bg-opacity-75 small">
                                        {{ match($row->sheet_status) { 'draft' => 'مسودة', 'submitted' => 'مُقدَّم', 'returned' => 'مُعاد', 'verified' => 'مُتحقَّق', default => $row->sheet_status } }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">لا توجد سجلات.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @else
        @if(!$this->isFullScopePosition())
            <div class="alert alert-info">تقرير حضور الكادر متاح للمدير والمدير المساعد والمرشد فقط.</div>
        @else
            <div class="card">
                <div class="card-header">حضور الكادر (أول 500)</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr><th>التاريخ</th><th>الموظف</th><th>الرمز</th><th>الحالة</th><th>وقت الوصول</th><th>موثَّق</th></tr>
                        </thead>
                        <tbody>
                            @forelse($this->staffRows as $row)
                                <tr>
                                    <td>{{ $row->record_date }}</td>
                                    <td>{{ $row->staff_name }}</td>
                                    <td class="small text-muted">{{ $row->staff_code }}</td>
                                    <td>
                                        <span class="badge bg-{{ match($row->status_code) { 'present' => 'success', 'absent' => 'danger', 'late' => 'warning', default => 'secondary' } }}">
                                            {{ match($row->status_code) { 'present' => 'حاضر', 'absent' => 'غائب', 'late' => 'متأخر', default => $row->status_code } }}
                                        </span>
                                    </td>
                                    <td class="small">{{ $row->confirmed_arrived_at ?? '—' }}</td>
                                    <td>@if($row->is_verified)<span class="text-success">✓</span>@else<span class="text-muted">—</span>@endif</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">لا توجد سجلات.</td></tr>
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

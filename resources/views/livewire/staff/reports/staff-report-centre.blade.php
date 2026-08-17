<div>
    {{-- ── Page header ─────────────────────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0">مركز التقارير</h4>
    </div>

    <div class="row g-4">
        {{-- ── Report family browser ───────────────────────────────────── --}}
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">عائلات التقارير</div>
                <div class="list-group list-group-flush">
                    @forelse($this->definitions as $family => $defs)
                        <div class="list-group-item bg-light small fw-bold text-uppercase">
                            {{ match($family) {
                                'registry' => 'السجلات',
                                'attendance' => 'الحضور',
                                'marks' => 'الدرجات',
                                'compliance' => 'الالتزام',
                                'staff' => 'الكادر',
                                'requests' => 'الطلبات',
                                'audit' => 'المراجعة',
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
                            لا تتوفر تقارير ضمن صلاحياتك.
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
                            @if(in_array('class_group_id', $this->filterSchema, true))
                                <div class="col-md-4">
                                    <label class="form-label">المجموعة الدراسية</label>
                                    <select class="form-select form-select-sm" wire:model.live="classGroupId">
                                        <option value="0">الكل</option>
                                        @foreach($this->classGroups as $cg)
                                            <option value="{{ $cg->id }}">{{ $cg->name_ar }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            @if(in_array('date_from', $this->filterSchema, true))
                                <div class="col-md-3">
                                    <label class="form-label">من تاريخ</label>
                                    <input type="date" class="form-control form-control-sm" wire:model.live="dateFrom">
                                </div>
                            @endif

                            @if(in_array('date_to', $this->filterSchema, true))
                                <div class="col-md-3">
                                    <label class="form-label">إلى تاريخ</label>
                                    <input type="date" class="form-control form-control-sm" wire:model.live="dateTo">
                                </div>
                            @endif
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <button wire:click="runReport" wire:loading.attr="disabled" class="btn btn-primary btn-sm">
                                <span wire:loading wire:target="runReport" class="spinner-border spinner-border-sm me-1"></span>
                                عرض التقرير
                            </button>
                            @if($canExport)
                                <button wire:click="exportReport" wire:loading.attr="disabled" class="btn btn-outline-success btn-sm">
                                    <span wire:loading wire:target="exportReport" class="spinner-border spinner-border-sm me-1"></span>
                                    تصدير إلى Excel
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ── Pending async export ─────────────────────────────── --}}
                @if($this->pendingOperation)
                    <div wire:poll.3s class="alert {{ match($this->pendingOperation->status) {
                        'completed' => 'alert-success',
                        'failed' => 'alert-danger',
                        default => 'alert-info',
                    } }} d-flex align-items-center justify-content-between">
                        <div>
                            @if($this->pendingOperation->status === 'completed')
                                اكتمل إنشاء ملف التصدير.
                            @elseif($this->pendingOperation->status === 'failed')
                                فشل التصدير: {{ $this->pendingOperation->failure_summary ?? 'خطأ غير معروف' }}
                            @else
                                جارٍ إنشاء ملف التصدير في الخلفية… ({{ $this->pendingOperation->status === 'queued' ? 'في قائمة الانتظار' : 'قيد المعالجة' }})
                            @endif
                        </div>
                        @if($this->pendingOperation->status === 'completed')
                            <button wire:click="downloadCompletedExport" class="btn btn-success btn-sm">تنزيل</button>
                        @endif
                    </div>
                @endif

                {{-- ── Results table ────────────────────────────────────── --}}
                @if($hasRun)
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>النتائج (أول {{ \App\Livewire\Staff\Reports\StaffReportCentre::PREVIEW_LIMIT }} صف)</span>
                            <span class="small text-muted">{{ $this->rows->count() }} صف معروض</span>
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
                                                لا توجد نتائج بالمعايير المحددة.
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
                        اختر تقريراً من القائمة الجانبية للبدء.
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

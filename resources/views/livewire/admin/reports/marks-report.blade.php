<div>
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0">تقارير الدرجات والنتائج</h4>
    </div>

    {{-- Report type --}}
    <div class="btn-group mb-4" role="group">
        <button type="button" wire:click="$set('reportType','completion')"
            class="btn btn-{{ $reportType === 'completion' ? 'primary' : 'outline-primary' }} btn-sm">
            اكتمال إدخال الدرجات
        </button>
        <button type="button" wire:click="$set('reportType','results')"
            class="btn btn-{{ $reportType === 'results' ? 'primary' : 'outline-primary' }} btn-sm">
            النتائج المنشورة
        </button>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">الفصل الدراسي</label>
                    <select class="form-select form-select-sm" wire:model.live="semesterId">
                        <option value="0">— اختر —</option>
                        @foreach($this->semesters as $sem)
                            <option value="{{ $sem->id }}">{{ $sem->institution_name }} · {{ $sem->semester_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">المجموعة الدراسية</label>
                    <select class="form-select form-select-sm" wire:model.live="classGroupId">
                        <option value="0">الكل</option>
                        @foreach($this->classGroups as $cg)
                            <option value="{{ $cg->id }}">{{ $cg->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    @if($reportType === 'completion')
        {{-- Summary stats --}}
        @php $s = $this->completionSummary; @endphp
        <div class="row g-3 mb-4">
            @foreach([['label' => 'الإجمالي', 'value' => $s->total, 'color' => 'primary'], ['label' => 'مسودة', 'value' => $s->draft, 'color' => 'secondary'], ['label' => 'مُقدَّم', 'value' => $s->submitted, 'color' => 'info'], ['label' => 'مُتحقَّق', 'value' => $s->verified, 'color' => 'warning'], ['label' => 'مُعاد', 'value' => $s->returned, 'color' => 'danger'], ['label' => 'مُعتمَد', 'value' => $s->approved, 'color' => 'success']] as $stat)
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

        @if($canExport && $semesterId > 0)
            <div class="mb-3">
                <button wire:click="exportCompletion" wire:loading.attr="disabled" class="btn btn-outline-success btn-sm">
                    <span wire:loading wire:target="exportCompletion" class="spinner-border spinner-border-sm me-1"></span>
                    تصدير إلى Excel
                </button>
            </div>
        @endif

        <div class="card">
            <div class="card-header">جداول الدرجات</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>المجموعة</th>
                            <th>المادة</th>
                            <th>المعلم</th>
                            <th>الحالة</th>
                            <th>الإصدار</th>
                            <th>تاريخ التقديم</th>
                            <th>تاريخ الاعتماد</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->completionRows as $row)
                            <tr>
                                <td>{{ $row->class_group_name }}</td>
                                <td>{{ $row->subject_name }}</td>
                                <td>{{ $row->teacher_name }}</td>
                                <td>
                                    <span class="badge bg-{{ match($row->status) { 'approved' => 'success', 'verified' => 'warning', 'submitted' => 'info', 'returned' => 'danger', default => 'secondary' } }}">
                                        {{ match($row->status) { 'draft' => 'مسودة', 'submitted' => 'مُقدَّم', 'returned' => 'مُعاد', 'verified' => 'مُتحقَّق', 'approved' => 'مُعتمَد', default => $row->status } }}
                                    </span>
                                </td>
                                <td>v{{ $row->version }}</td>
                                <td class="small text-muted">{{ $row->submitted_at ? \Carbon\Carbon::parse($row->submitted_at)->format('Y-m-d') : '—' }}</td>
                                <td class="small text-muted">{{ $row->approved_at ? \Carbon\Carbon::parse($row->approved_at)->format('Y-m-d') : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">لا توجد جداول.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @else
        {{-- Published results --}}
        @if($canExport && $semesterId > 0)
            <div class="mb-3">
                <button wire:click="exportResults" wire:loading.attr="disabled" class="btn btn-outline-success btn-sm">
                    <span wire:loading wire:target="exportResults" class="spinner-border spinner-border-sm me-1"></span>
                    تصدير النتائج إلى Excel
                </button>
            </div>
        @endif

        <div class="card">
            <div class="card-header">النتائج المنشورة (أول 500)</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>المجموعة</th>
                            <th>اسم الطالب</th>
                            <th>المادة</th>
                            <th>الدرجة (100)</th>
                            <th>التقدير</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->resultRows as $row)
                            <tr>
                                <td>{{ $row->class_group_name }}</td>
                                <td>{{ $row->student_name }}</td>
                                <td>{{ $row->subject_name }}</td>
                                <td class="fw-semibold">{{ $row->normalized_score !== null ? number_format((float)$row->normalized_score, 1) : '—' }}</td>
                                <td>
                                    @if($row->grade_code)
                                        <span class="badge bg-primary bg-opacity-75">{{ $row->grade_code }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ match($row->completeness_status) { 'complete' => 'success', 'incomplete' => 'warning', default => 'secondary' } }} bg-opacity-75 small">
                                        {{ match($row->completeness_status) { 'complete' => 'مكتمل', 'incomplete' => 'غير مكتمل', 'all_absent' => 'غياب كامل', 'no_assessments' => 'لا تقييمات', default => $row->completeness_status } }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">لا توجد نتائج منشورة.</td>
                            </tr>
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

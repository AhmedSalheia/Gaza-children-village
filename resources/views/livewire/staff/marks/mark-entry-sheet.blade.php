@php /** @var \App\Livewire\Staff\Marks\MarkEntrySheet $this */ @endphp

<div>
    <div class="page-header">
        <h1 class="page-title">Mark Entry Sheet</h1>
        <a href="{{ route('staff.marks.index') }}" class="btn btn--outline btn--sm">← Back</a>
    </div>

    @if($flashMessage !== '')
        <div class="alert alert--{{ $flashType === 'success' ? 'success' : 'danger' }}"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
            {{ $flashMessage }}
        </div>
    @endif

    @if($sheet)
        {{-- Sheet metadata --}}
        <div class="card" style="margin-block-end:var(--space-4);display:flex;flex-wrap:wrap;align-items:center;gap:var(--space-4)">
            <span class="badge badge--{{ match($sheet->status->value) {
                'draft','returned' => 'archived',
                'submitted' => 'info',
                'verified' => 'warning',
                'approved','published' => 'active',
                default => 'archived'
            } }}" style="font-size:var(--text-base)">
                {{ $sheet->status->labelAr() }}
            </span>

            {{-- Grading scale selector --}}
            @if($sheet->isEditable() && $canEnter)
                <div style="display:flex;align-items:center;gap:var(--space-2)">
                    <label style="font-size:var(--text-sm);color:var(--color-muted)" for="scale-select">
                        Grading Scale:
                    </label>
                    <select id="scale-select"
                            wire:model.live="selectedScaleId"
                            wire:change="attachGradingScale"
                            class="form-control"
                            style="width:auto;min-width:160px">
                        <option value="">— None —</option>
                        @foreach($gradingScales as $scale)
                            <option value="{{ $scale->id }}"
                                {{ (int)$selectedScaleId === (int)$scale->id ? 'selected' : '' }}>
                                {{ $scale->name_ar }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @elseif($sheet->grading_scale_id)
                <span style="font-size:var(--text-sm);color:var(--color-muted)">
                    Scale: {{ $gradingScales->firstWhere('id', $sheet->grading_scale_id)?->name_ar ?? '—' }}
                </span>
            @endif

            @if($sheet->return_reason)
                <div style="color:var(--color-danger);font-size:var(--text-sm)">
                    <strong>Return reason:</strong> {{ $sheet->return_reason }}
                </div>
            @endif
        </div>

        @if($sheet->isEditable() && $canEnter)
            {{-- Action bar --}}
            <div style="display:flex;gap:var(--space-2);margin-block-end:var(--space-3);align-items:center">
                <button wire:click="submit" class="btn btn--primary">Submit for Review</button>
                @if($canReturn)
                    <button wire:click="startReturn" class="btn btn--outline">Return</button>
                @endif
            </div>
        @endif

        @if($canVerify && $sheet->status->value === 'submitted')
            <div style="display:flex;gap:var(--space-2);margin-block-end:var(--space-3)">
                <button wire:click="verify" class="btn btn--primary">Verify</button>
                <button wire:click="startReturn" class="btn btn--outline">Return to Teacher</button>
            </div>
        @endif

        {{-- Link to correction interface for approved/published sheets --}}
        @if(in_array($sheet->status->value, ['approved', 'published']) && $canCorrect)
            <div style="margin-block-end:var(--space-3)">
                <a href="{{ route('staff.marks.correct', ['sheetId' => $sheet->id]) }}"
                   class="btn btn--outline btn--sm">
                    Submit Mark Correction
                </a>
            </div>
        @endif

        {{-- Return dialog --}}
        @if($showReturn)
            <div class="card" style="margin-block-end:var(--space-4);border-color:var(--color-warning)">
                <h3 style="margin-block-end:var(--space-2)">Return Reason</h3>
                <textarea wire:model="returnReason" class="form-control" rows="3" placeholder="Explain what needs to be corrected…"></textarea>
                <div style="display:flex;gap:var(--space-2);margin-block-start:var(--space-2)">
                    <button wire:click="confirmReturn" class="btn btn--danger btn--sm">Confirm Return</button>
                    <button wire:click="$set('showReturn', false)" class="btn btn--outline btn--sm">Cancel</button>
                </div>
            </div>
        @endif

        {{-- Spreadsheet mark entry --}}
        @if($assessments->isNotEmpty() && $marks->isNotEmpty())
            @php
                // Group marks by enrollment_id for quick lookup
                $markMap    = $marks->groupBy('enrollment_id');
                $enrollments = $marks->unique('enrollment_id')->values();

                $exceptOptions = [
                    ''        => '—',
                    'absent'  => 'Absent',
                    'exempt'  => 'Exempt',
                    'medical' => 'Medical',
                ];
            @endphp

            <div class="data-table-wrapper" style="overflow-x:auto" x-data="markSheet()">
                <table class="data-table" id="mark-sheet-table">
                    <thead>
                        <tr>
                            <th style="min-width:200px">Student</th>
                            @foreach($assessments as $assessment)
                                <th style="min-width:160px">
                                    <div dir="rtl">{{ $assessment->name_ar }}</div>
                                    <div style="font-size:var(--text-xs);color:var(--color-muted)">/ {{ $assessment->max_score }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($enrollments as $enrollment)
                            @php $studentMarks = $markMap->get($enrollment->enrollment_id, collect()); @endphp
                            <tr>
                                <td dir="rtl">{{ $enrollment->student_name }}</td>
                                @foreach($assessments as $assessment)
                                    @php
                                        $mark = $studentMarks->firstWhere('assessment_definition_id', $assessment->id);
                                        $hasException = $mark && $mark->exception_status;
                                    @endphp
                                    <td>
                                        @if($sheet->isEditable() && $canEnter)
                                            {{-- Per-cell entry: score OR exception status, mutually exclusive --}}
                                            <div style="display:flex;flex-direction:column;gap:4px"
                                                 x-data="markCell({{ $enrollment->enrollment_id }}, {{ $assessment->id }}, '{{ $mark?->exception_status ?? '' }}')"
                                            >
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    max="{{ $assessment->max_score }}"
                                                    :value="exceptionStatus ? '' : '{{ $mark?->score ?? '' }}'"
                                                    :disabled="exceptionStatus !== ''"
                                                    class="form-control"
                                                    style="width:80px"
                                                    @keydown.tab.prevent="focusNext($event)"
                                                    @keydown.arrow-right.prevent="focusNext($event)"
                                                    @keydown.arrow-left.prevent="focusPrev($event)"
                                                    @change="saveScore($event)"
                                                    data-enroll="{{ $enrollment->enrollment_id }}"
                                                    data-assess="{{ $assessment->id }}"
                                                    :placeholder="exceptionStatus ? exceptionLabel : ''"
                                                >
                                                <select
                                                    x-model="exceptionStatus"
                                                    @change="saveException()"
                                                    class="form-control"
                                                    style="width:100px;font-size:var(--text-xs)"
                                                    aria-label="Exception status"
                                                >
                                                    <option value="">Score</option>
                                                    <option value="absent">Absent</option>
                                                    <option value="exempt">Exempt</option>
                                                    <option value="medical">Medical</option>
                                                </select>
                                            </div>
                                        @else
                                            <span>{{ $mark?->score ?? ($mark?->exception_status ? ucfirst($mark->exception_status) : '—') }}</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <script>
            function markSheet() {
                return {
                    focusNext(e) {
                        const inputs = [...document.querySelectorAll('#mark-sheet-table input[type=number]')];
                        const idx = inputs.indexOf(e.target);
                        if (idx < inputs.length - 1) inputs[idx + 1].focus();
                    },
                    focusPrev(e) {
                        const inputs = [...document.querySelectorAll('#mark-sheet-table input[type=number]')];
                        const idx = inputs.indexOf(e.target);
                        if (idx > 0) inputs[idx - 1].focus();
                    }
                }
            }

            function markCell(enrollmentId, assessmentId, initialException) {
                const exceptionLabels = { absent: 'Absent', exempt: 'Exempt', medical: 'Medical' };
                return {
                    enrollmentId,
                    assessmentId,
                    exceptionStatus: initialException || '',
                    get exceptionLabel() {
                        return exceptionLabels[this.exceptionStatus] || '';
                    },
                    saveScore(e) {
                        const score = e.target.value === '' ? null : e.target.value;
                        @this.saveMark(this.enrollmentId, this.assessmentId, score, null, null);
                    },
                    saveException() {
                        const ex = this.exceptionStatus === '' ? null : this.exceptionStatus;
                        @this.saveMark(this.enrollmentId, this.assessmentId, null, ex, null);
                    }
                }
            }
            </script>
        @else
            <p style="color:var(--color-muted);padding:var(--space-4)">
                No students or assessments found for this sheet.
            </p>
        @endif
    @else
        <div class="card">
            <p>Mark sheet could not be loaded. The mark-entry window may be closed or you may not have a teaching assignment for this class.</p>
            <a href="{{ route('staff.marks.index') }}" class="btn btn--primary btn--sm" style="margin-block-start:var(--space-2)">Back to My Subjects</a>
        </div>
    @endif
</div>

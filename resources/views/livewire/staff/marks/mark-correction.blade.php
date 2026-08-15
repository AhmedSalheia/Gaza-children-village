@php /** @var \App\Livewire\Staff\Marks\MarkCorrection $this */ @endphp

<div>
    <div class="page-header">
        <h1 class="page-title">Submit Mark Correction</h1>
        @if($sheet)
            <a href="{{ route('staff.marks.sheet', ['assignmentId' => $sheet->teaching_assignment_id]) }}"
               class="btn btn--outline btn--sm">← Back to Sheet</a>
        @else
            <a href="{{ route('staff.marks.index') }}" class="btn btn--outline btn--sm">← Back</a>
        @endif
    </div>

    @if($flashMessage !== '')
        <div class="alert alert--{{ $flashType === 'success' ? 'success' : 'danger' }}"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)">
            {{ $flashMessage }}
        </div>
    @endif

    @if($sheet)
        <div class="card" style="margin-block-end:var(--space-4)">
            <p style="font-size:var(--text-sm);color:var(--color-muted)">
                Corrections are append-only. The original mark is preserved and a new correction row is recorded
                with your reason. Select a mark below to correct it.
            </p>
        </div>

        {{-- Existing corrections --}}
        @if($corrections->isNotEmpty())
            <h3 style="margin-block-end:var(--space-2)">Previous Corrections</h3>
            <div class="data-table-wrapper" style="margin-block-end:var(--space-4)">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Assessment</th>
                            <th>Corrected Value</th>
                            <th>Reason</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($corrections as $corr)
                            <tr>
                                <td dir="rtl">{{ $corr->student_name }}</td>
                                <td dir="rtl">{{ $corr->assessment_name }}</td>
                                <td>
                                    @if($corr->corrected_score !== null)
                                        {{ $corr->corrected_score }}
                                    @elseif($corr->corrected_exception)
                                        <span class="badge badge--archived">{{ ucfirst($corr->corrected_exception) }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td style="font-size:var(--text-sm)">{{ $corr->correction_reason }}</td>
                                <td style="font-size:var(--text-sm);color:var(--color-muted)">
                                    {{ $corr->corrected_at ? \Carbon\Carbon::parse($corr->corrected_at)->format('d/m/Y H:i') : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Mark selection and correction form --}}
        @if($selectedMarkId === 0)
            <h3 style="margin-block-end:var(--space-2)">Select a Mark to Correct</h3>
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Assessment</th>
                            <th>Current Value</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($marks as $mark)
                            <tr>
                                <td dir="rtl">{{ $mark->student_name }}</td>
                                <td dir="rtl">{{ $mark->assessment_name }}</td>
                                <td>
                                    @if($mark->score !== null)
                                        {{ $mark->score }} / {{ $mark->max_score }}
                                    @elseif($mark->exception_status)
                                        <span class="badge badge--archived">{{ ucfirst($mark->exception_status) }}</span>
                                    @else
                                        <span style="color:var(--color-muted)">Not entered</span>
                                    @endif
                                </td>
                                <td>
                                    <button wire:click="selectMark({{ $mark->mark_id }})"
                                            class="btn btn--outline btn--sm">
                                        Correct
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="color:var(--color-muted)">No marks found on this sheet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            {{-- Correction entry form --}}
            @php
                $selectedMark = $marks->firstWhere('mark_id', $selectedMarkId);
            @endphp
            <div class="card" style="max-width:520px">
                <h3 style="margin-block-end:var(--space-3)">
                    Correction for:
                    @if($selectedMark)
                        <span dir="rtl">{{ $selectedMark->student_name }}</span>
                        — <span dir="rtl">{{ $selectedMark->assessment_name }}</span>
                        (current: {{ $selectedMark->score ?? ucfirst($selectedMark->exception_status ?? '—') }})
                    @endif
                </h3>

                <div class="form-group">
                    <label class="form-label">Corrected Value</label>
                    <div style="display:flex;gap:var(--space-3);align-items:flex-start;flex-wrap:wrap">
                        <div>
                            <label style="font-size:var(--text-sm);color:var(--color-muted)">Score</label>
                            <input type="number" wire:model="correctedScore"
                                   step="0.01" min="0"
                                   max="{{ $selectedMark?->max_score ?? 100 }}"
                                   class="form-control" style="width:100px"
                                   placeholder="0.00"
                                   :disabled="$wire.correctedExcept !== ''">
                        </div>
                        <div>
                            <label style="font-size:var(--text-sm);color:var(--color-muted)">Exception</label>
                            <select wire:model="correctedExcept" class="form-control" style="width:130px">
                                <option value="">— Score —</option>
                                <option value="absent">Absent</option>
                                <option value="exempt">Exempt</option>
                                <option value="medical">Medical</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="correction-reason">Reason for Correction <span style="color:var(--color-danger)">*</span></label>
                    <textarea id="correction-reason"
                              wire:model="correctionReason"
                              class="form-control"
                              rows="3"
                              placeholder="Describe why this correction is needed (min 5 characters)…"></textarea>
                </div>

                <div style="display:flex;gap:var(--space-2);margin-block-start:var(--space-3)">
                    <button wire:click="submitCorrection" class="btn btn--primary">Submit Correction</button>
                    <button wire:click="cancelCorrection" class="btn btn--outline">Cancel</button>
                </div>
            </div>
        @endif
    @else
        <div class="card">
            <p>Mark sheet not found or not accessible.</p>
        </div>
    @endif
</div>

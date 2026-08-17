@php /** @var \App\Livewire\Staff\Marks\MarkCorrection $this */ @endphp

<div>
    <div class="page-header">
        <h1 class="page-title">{{ __('marks.submit_correction_title') }}</h1>
        @if($sheet)
            <a href="{{ route('staff.marks.sheet', ['assignmentId' => $sheet->teaching_assignment_id]) }}"
               class="btn btn--outline btn--sm">← {{ __('marks.back_to_sheet') }}</a>
        @else
            <a href="{{ route('staff.marks.index') }}" class="btn btn--outline btn--sm">← {{ __('ui.back') }}</a>
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
                {{ __('marks.correction_intro') }}
            </p>
        </div>

        {{-- Existing corrections --}}
        @if($corrections->isNotEmpty())
            <h3 style="margin-block-end:var(--space-2)">{{ __('marks.previous_corrections') }}</h3>
            <div class="data-table-wrapper" style="margin-block-end:var(--space-4)">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('marks.student') }}</th>
                            <th>{{ __('marks.assessment') }}</th>
                            <th>{{ __('marks.corrected_value') }}</th>
                            <th>{{ __('ui.reason') }}</th>
                            <th>{{ __('ui.date') }}</th>
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
            <h3 style="margin-block-end:var(--space-2)">{{ __('marks.select_mark_to_correct') }}</h3>
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('marks.student') }}</th>
                            <th>{{ __('marks.assessment') }}</th>
                            <th>{{ __('marks.current_value') }}</th>
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
                                        <span style="color:var(--color-muted)">{{ __('marks.not_entered') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <button wire:click="selectMark({{ $mark->mark_id }})"
                                            class="btn btn--outline btn--sm">
                                        {{ __('marks.correct') }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="color:var(--color-muted)">{{ __('marks.no_marks_on_sheet') }}</td></tr>
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
                    {{ __('marks.correction_for') }}
                    @if($selectedMark)
                        <span dir="rtl">{{ $selectedMark->student_name }}</span>
                        — <span dir="rtl">{{ $selectedMark->assessment_name }}</span>
                        ({{ __('marks.current_label') }} {{ $selectedMark->score ?? ucfirst($selectedMark->exception_status ?? '—') }})
                    @endif
                </h3>

                <div class="form-group">
                    <label class="form-label">{{ __('marks.corrected_value') }}</label>
                    <div style="display:flex;gap:var(--space-3);align-items:flex-start;flex-wrap:wrap">
                        <div>
                            <label style="font-size:var(--text-sm);color:var(--color-muted)">{{ __('marks.score') }}</label>
                            <input type="number" wire:model="correctedScore"
                                   step="0.01" min="0"
                                   max="{{ $selectedMark?->max_score ?? 100 }}"
                                   class="form-control" style="width:100px"
                                   placeholder="0.00"
                                   :disabled="$wire.correctedExcept !== ''">
                        </div>
                        <div>
                            <label style="font-size:var(--text-sm);color:var(--color-muted)">{{ __('marks.exception') }}</label>
                            <select wire:model="correctedExcept" class="form-control" style="width:130px">
                                <option value="">{{ __('marks.score_option') }}</option>
                                <option value="absent">{{ __('marks.absent') }}</option>
                                <option value="exempt">{{ __('marks.exempt') }}</option>
                                <option value="medical">{{ __('marks.medical') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="correction-reason">{{ __('marks.reason_for_correction') }} <span style="color:var(--color-danger)">*</span></label>
                    <textarea id="correction-reason"
                              wire:model="correctionReason"
                              class="form-control"
                              rows="3"
                              placeholder="{{ __('marks.correction_reason_placeholder') }}"></textarea>
                </div>

                <div style="display:flex;gap:var(--space-2);margin-block-start:var(--space-3)">
                    <button wire:click="submitCorrection" class="btn btn--primary">{{ __('marks.submit_correction') }}</button>
                    <button wire:click="cancelCorrection" class="btn btn--outline">{{ __('ui.cancel') }}</button>
                </div>
            </div>
        @endif
    @else
        <div class="card">
            <p>{{ __('marks.sheet_not_found') }}</p>
        </div>
    @endif
</div>

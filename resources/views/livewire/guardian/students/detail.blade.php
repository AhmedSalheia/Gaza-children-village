@php /** @var \App\Livewire\Guardian\Students\StudentDetail $this */ @endphp

<div>
    <div style="margin-block-end:var(--space-4)">
        <a href="{{ route('guardian.dashboard') }}" wire:navigate class="link" style="font-size:var(--text-sm)">
            ← {{ __('ui.back_to_my_children', [], null, 'Back to My Children') }}
        </a>
    </div>

    @if(! $student)
        <div class="error-state" role="alert">
            {{ __('ui.student_not_found', [], null, 'Student record not available.') }}
        </div>
    @else

    {{-- ── Student identity ──────────────────────────────────────────── --}}
    <div style="display:flex;align-items:center;gap:var(--space-4);margin-block-end:var(--space-6);flex-wrap:wrap">
        <div style="width:64px;height:64px;border-radius:50%;background:var(--brand-primary,#1a56db);display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <span style="color:white;font-size:var(--text-2xl);font-weight:700;line-height:1">
                {{ mb_substr($student->full_name_ar, 0, 1) }}
            </span>
        </div>
        <div>
            <h1 style="font-size:var(--text-2xl);font-weight:700;margin:0;color:var(--text-primary)">
                {{ $student->full_name_ar }}
            </h1>
            @if($student->full_name_en)
            <div style="font-size:var(--text-base);color:var(--text-secondary)">{{ $student->full_name_en }}</div>
            @endif
            <div style="margin-block-start:var(--space-1);display:flex;gap:var(--space-2);flex-wrap:wrap">
                <span class="badge badge--active">{{ $student->lifecycle_status }}</span>
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6);margin-block-end:var(--space-6)">

        {{-- Identity card --}}
        <div class="card">
            <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">
                {{ __('ui.student_identity', [], null, 'Student Identity') }}
            </h2>
            <dl style="display:grid;grid-template-columns:auto 1fr;gap:var(--space-2) var(--space-4);align-items:baseline">
                <dt style="color:var(--text-secondary);font-size:var(--text-sm);font-weight:500;white-space:nowrap">{{ __('ui.student_code', [], null, 'Student Code') }}</dt>
                <dd style="font-family:monospace;font-size:var(--text-sm)">{{ $student->student_code }}</dd>

                @if($ageRange)
                <dt style="color:var(--text-secondary);font-size:var(--text-sm);font-weight:500">{{ __('ui.age_range', [], null, 'Age Range') }}</dt>
                <dd style="font-size:var(--text-sm)">{{ $ageRange }} {{ __('ui.years', [], null, 'years') }}</dd>
                @endif
            </dl>
        </div>

        {{-- Relationship card --}}
        @if($relationship)
        <div class="card">
            <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">
                {{ __('ui.my_relationship', [], null, 'My Relationship') }}
            </h2>
            <dl style="display:grid;grid-template-columns:auto 1fr;gap:var(--space-2) var(--space-4);align-items:baseline">
                <dt style="color:var(--text-secondary);font-size:var(--text-sm);font-weight:500">{{ __('ui.relationship_type', [], null, 'Type') }}</dt>
                <dd style="font-size:var(--text-sm)">{{ $relationship->relationship_type }}</dd>

                <dt style="color:var(--text-secondary);font-size:var(--text-sm);font-weight:500">{{ __('ui.legal_authority', [], null, 'Legal Authority') }}</dt>
                <dd style="font-size:var(--text-sm)">{{ $relationship->legal_authority }}</dd>

                @if($relationship->contact_priority !== null)
                <dt style="color:var(--text-secondary);font-size:var(--text-sm);font-weight:500">{{ __('ui.contact_priority', [], null, 'Contact Priority') }}</dt>
                <dd style="font-size:var(--text-sm)">{{ $relationship->contact_priority }}</dd>
                @endif

                <dt style="color:var(--text-secondary);font-size:var(--text-sm);font-weight:500">{{ __('ui.emergency_contact', [], null, 'Emergency Contact') }}</dt>
                <dd style="font-size:var(--text-sm)">{{ $relationship->is_emergency_contact ? __('ui.yes', [], null, 'Yes') : __('ui.no', [], null, 'No') }}</dd>
            </dl>

            {{-- ── Correction-request area ─────────────────────────────── --}}
            <div style="margin-block-start:var(--space-4);padding-block-start:var(--space-4);border-block-start:1px solid var(--card-border)">

                @if($correctionSuccessMessage)
                    <div class="alert alert--success" role="status" style="font-size:var(--text-sm);margin-block-end:var(--space-3)">
                        ✓ {{ $correctionSuccessMessage }}
                    </div>
                @endif

                @if($pendingCorrection)
                    {{-- Pending indicator --}}
                    <div style="display:flex;align-items:flex-start;gap:var(--space-2);background:var(--color-warning-50,#fffbeb);border:1px solid var(--color-warning-300,#fcd34d);border-radius:var(--radius-md,6px);padding:var(--space-3)">
                        <span aria-hidden="true" style="font-size:var(--text-base);flex-shrink:0">⏳</span>
                        <div style="font-size:var(--text-sm)">
                            <div style="font-weight:600;color:var(--color-warning-800,#92400e)">
                                {{ __('ui.correction_pending', [], null, 'Correction request pending review') }}
                            </div>
                            <div style="color:var(--color-warning-700,#b45309);margin-block-start:var(--space-1)">
                                {{ __('ui.correction_pending_detail', [], null, 'Your request has been sent to staff and will be reviewed shortly.') }}
                            </div>
                            @if($pendingCorrection->requested_contact_priority !== null)
                            <div style="color:var(--color-warning-700,#b45309);margin-block-start:var(--space-1)">
                                {{ __('ui.contact_priority', [], null, 'Contact Priority') }}: {{ $pendingCorrection->requested_contact_priority }}
                            </div>
                            @endif
                            @if($pendingCorrection->requested_is_emergency_contact !== null)
                            <div style="color:var(--color-warning-700,#b45309)">
                                {{ __('ui.emergency_contact', [], null, 'Emergency Contact') }}: {{ $pendingCorrection->requested_is_emergency_contact ? __('ui.yes', [], null, 'Yes') : __('ui.no', [], null, 'No') }}
                            </div>
                            @endif
                        </div>
                    </div>

                @elseif($correctionFormOpen)
                    {{-- Correction form --}}
                    <form wire:submit="submitCorrectionRequest" novalidate>
                        <div style="font-size:var(--text-sm);font-weight:600;margin-block-end:var(--space-3);color:var(--text-primary)">
                            {{ __('ui.request_correction', [], null, 'Request a Correction') }}
                        </div>

                        @error('correctionPriority')
                        <div class="field-error" role="alert" style="margin-block-end:var(--space-2)">{{ $message }}</div>
                        @enderror

                        <div style="display:flex;flex-direction:column;gap:var(--space-3)">

                            {{-- Contact priority --}}
                            <label style="display:flex;flex-direction:column;gap:var(--space-1)">
                                <span style="font-size:var(--text-sm);color:var(--text-secondary)">
                                    {{ __('ui.contact_priority', [], null, 'Contact Priority') }}
                                    <span style="font-weight:400;color:var(--text-tertiary)">({{ __('ui.optional', [], null, 'optional') }})</span>
                                </span>
                                <input
                                    type="number"
                                    min="1"
                                    class="input"
                                    wire:model="correctionPriority"
                                    placeholder="{{ __('ui.correction_priority_placeholder', [], null, 'e.g. 1') }}"
                                    style="max-width:120px"
                                >
                            </label>

                            {{-- Emergency contact --}}
                            <fieldset style="border:none;padding:0;margin:0">
                                <legend style="font-size:var(--text-sm);color:var(--text-secondary);margin-block-end:var(--space-2)">
                                    {{ __('ui.emergency_contact', [], null, 'Emergency Contact') }}
                                    <span style="font-weight:400;color:var(--text-tertiary)">({{ __('ui.optional', [], null, 'optional') }})</span>
                                </legend>
                                <div style="display:flex;gap:var(--space-4)">
                                    <label style="display:flex;align-items:center;gap:var(--space-2);font-size:var(--text-sm);cursor:pointer">
                                        <input type="radio" wire:model="correctionIsEmergency" value="1">
                                        {{ __('ui.yes', [], null, 'Yes') }}
                                    </label>
                                    <label style="display:flex;align-items:center;gap:var(--space-2);font-size:var(--text-sm);cursor:pointer">
                                        <input type="radio" wire:model="correctionIsEmergency" value="0">
                                        {{ __('ui.no', [], null, 'No') }}
                                    </label>
                                    <label style="display:flex;align-items:center;gap:var(--space-2);font-size:var(--text-sm);cursor:pointer">
                                        <input type="radio" wire:model="correctionIsEmergency" value="">
                                        {{ __('ui.no_change', [], null, 'No change') }}
                                    </label>
                                </div>
                            </fieldset>

                            {{-- Note --}}
                            <label style="display:flex;flex-direction:column;gap:var(--space-1)">
                                <span style="font-size:var(--text-sm);color:var(--text-secondary)">
                                    {{ __('ui.correction_note_label', [], null, 'Note for staff') }}
                                    <span style="font-weight:400;color:var(--text-tertiary)">({{ __('ui.optional', [], null, 'optional') }})</span>
                                </span>
                                <textarea
                                    class="input"
                                    rows="2"
                                    wire:model="correctionNote"
                                    placeholder="{{ __('ui.correction_note_placeholder', [], null, 'Briefly explain the correction needed…') }}"
                                    style="resize:vertical"
                                ></textarea>
                            </label>

                        </div>

                        <div style="display:flex;gap:var(--space-2);margin-block-start:var(--space-4)">
                            <button type="submit" class="btn btn--primary btn--sm" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="submitCorrectionRequest">{{ __('ui.submit_correction', [], null, 'Submit Request') }}</span>
                                <span wire:loading wire:target="submitCorrectionRequest">{{ __('ui.submitting', [], null, 'Submitting…') }}</span>
                            </button>
                            <button type="button" class="btn btn--outline btn--sm" wire:click="closeCorrectionForm">
                                {{ __('ui.cancel', [], null, 'Cancel') }}
                            </button>
                        </div>
                    </form>

                @else
                    {{-- Trigger button --}}
                    <button type="button" class="btn btn--outline btn--sm" wire:click="openCorrectionForm">
                        {{ __('ui.flag_correction', [], null, 'Flag a correction') }}
                    </button>
                    <span style="font-size:var(--text-xs);color:var(--text-secondary);margin-inline-start:var(--space-2)">
                        {{ __('ui.correction_hint', [], null, 'Contact priority or emergency-contact status wrong?') }}
                    </span>
                @endif

            </div>
        </div>
        @endif

    </div>

    {{-- ── Current placement ─────────────────────────────────────────── --}}
    <div class="card">
        <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">
            {{ __('ui.current_placement', [], null, 'Current Academic Placement') }}
        </h2>

        @php $placement = $this->placement(app(\Modules\AcademicManagement\Actions\ResolveCurrentPlacement::class)) @endphp

        @if(! $placement)
            <div class="empty-state" style="padding:var(--space-6) 0">
                <div class="empty-state__icon" aria-hidden="true">📋</div>
                <p class="empty-state__body">
                    {{ __('ui.no_active_placement', [], null, 'No active placement this semester.') }}
                </p>
            </div>
        @else
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:var(--space-4)">

                <div style="border-inline-start:3px solid var(--interactive-primary,#1a56db);padding-inline-start:var(--space-3)">
                    <div style="font-size:var(--text-xs);font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--text-secondary)">
                        {{ __('ui.institution', [], null, 'Institution') }}
                    </div>
                    <div style="font-weight:600;margin-block-start:var(--space-1)">
                        {{ app()->getLocale() === 'ar' ? $placement->institution_name_ar : ($placement->institution_name_en ?? $placement->institution_name_ar) }}
                    </div>
                </div>

                <div style="border-inline-start:3px solid var(--interactive-primary,#1a56db);padding-inline-start:var(--space-3)">
                    <div style="font-size:var(--text-xs);font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--text-secondary)">
                        {{ __('ui.academic_level', [], null, 'Academic Level') }}
                    </div>
                    <div style="font-weight:600;margin-block-start:var(--space-1)">
                        {{ app()->getLocale() === 'ar' ? $placement->level_name_ar : ($placement->level_name_en ?? $placement->level_name_ar) }}
                    </div>
                </div>

                <div style="border-inline-start:3px solid var(--interactive-primary,#1a56db);padding-inline-start:var(--space-3)">
                    <div style="font-size:var(--text-xs);font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--text-secondary)">
                        {{ __('ui.class_group', [], null, 'Class Group') }}
                    </div>
                    <div style="font-weight:600;margin-block-start:var(--space-1)">
                        {{ app()->getLocale() === 'ar' ? $placement->class_group_name_ar : ($placement->class_group_name_en ?? $placement->class_group_name_ar) }}
                    </div>
                </div>

                @if($placement->period_name_ar)
                <div style="border-inline-start:3px solid var(--interactive-primary,#1a56db);padding-inline-start:var(--space-3)">
                    <div style="font-size:var(--text-xs);font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--text-secondary)">
                        {{ __('ui.period', [], null, 'Period') }}
                    </div>
                    <div style="font-weight:600;margin-block-start:var(--space-1)">
                        {{ app()->getLocale() === 'ar' ? $placement->period_name_ar : ($placement->period_name_en ?? $placement->period_name_ar) }}
                    </div>
                </div>
                @endif

                <div style="border-inline-start:3px solid var(--interactive-primary,#1a56db);padding-inline-start:var(--space-3)">
                    <div style="font-size:var(--text-xs);font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--text-secondary)">
                        {{ __('ui.semester', [], null, 'Semester') }}
                    </div>
                    <div style="font-weight:600;margin-block-start:var(--space-1)">
                        {{ app()->getLocale() === 'ar' ? $placement->semester_name_ar : ($placement->semester_name_en ?? $placement->semester_name_ar) }}
                    </div>
                </div>

            </div>

            <div style="margin-block-start:var(--space-4);padding-block-start:var(--space-4);border-block-start:1px solid var(--card-border);display:flex;align-items:center;gap:var(--space-2)">
                <span class="read-only-indicator" aria-hidden="true">🔒</span>
                <span style="font-size:var(--text-xs);color:var(--text-secondary)">
                    {{ __('ui.placement_read_only_note', [], null, 'This information is managed by school administration. Contact the school to request corrections.') }}
                </span>
            </div>
        @endif
    </div>

    {{-- ── Published Results ─────────────────────────────────────────── --}}
    <div class="card" style="margin-block-start:var(--space-6)">
        <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">
            {{ __('ui.published_results', [], null, 'Academic Results') }}
        </h2>

        @if($publishedResults->isEmpty())
            <div class="empty-state" style="padding:var(--space-4) 0">
                <div class="empty-state__icon" aria-hidden="true">📊</div>
                <p class="empty-state__body" style="color:var(--text-secondary);font-size:var(--text-sm)">
                    {{ __('ui.results_not_published', [], null, 'Results have not been published yet for this semester.') }}
                </p>
            </div>
        @else
            @php $firstRow = $publishedResults->first(); @endphp
            <div style="font-size:var(--text-xs);color:var(--text-secondary);margin-block-end:var(--space-3)">
                {{ __('ui.published_on', [], null, 'Published') }}:
                {{ \Carbon\Carbon::parse($firstRow->published_at)->format('Y-m-d') }}
                &middot; {{ __('ui.version', [], null, 'v') }}{{ $firstRow->version }}
            </div>
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:var(--text-sm)">
                    <thead>
                        <tr style="border-block-end:2px solid var(--card-border)">
                            <th style="text-align:start;padding:var(--space-2) var(--space-3);color:var(--text-secondary);font-weight:600">{{ __('ui.subject', [], null, 'Subject') }}</th>
                            <th style="text-align:center;padding:var(--space-2) var(--space-3);color:var(--text-secondary);font-weight:600">{{ __('ui.score', [], null, 'Score') }}</th>
                            <th style="text-align:center;padding:var(--space-2) var(--space-3);color:var(--text-secondary);font-weight:600">{{ __('ui.grade', [], null, 'Grade') }}</th>
                            <th style="text-align:center;padding:var(--space-2) var(--space-3);color:var(--text-secondary);font-weight:600">{{ __('ui.result', [], null, 'Result') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($publishedResults as $row)
                        <tr style="border-block-end:1px solid var(--card-border)">
                            <td style="padding:var(--space-2) var(--space-3);font-weight:500">{{ $row->subject_name_ar ?? $row->subject_name_en ?? '—' }}</td>
                            <td style="padding:var(--space-2) var(--space-3);text-align:center">
                                @if($row->normalized_score !== null)
                                    {{ number_format((float) $row->normalized_score, 1) }}%
                                @else
                                    —
                                @endif
                            </td>
                            <td style="padding:var(--space-2) var(--space-3);text-align:center">
                                {{ $row->grade_name_ar ?? $row->grade_code ?? '—' }}
                            </td>
                            <td style="padding:var(--space-2) var(--space-3);text-align:center">
                                @if($row->is_passing === null)
                                    <span style="color:var(--text-secondary)">—</span>
                                @elseif($row->is_passing)
                                    <span class="badge badge--active">{{ __('ui.passing', [], null, 'Pass') }}</span>
                                @else
                                    <span class="badge badge--inactive">{{ __('ui.failing', [], null, 'Fail') }}</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ── Published Attendance ───────────────────────────────────────── --}}
    <div class="card" style="margin-block-start:var(--space-6)">
        <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">
            {{ __('ui.published_attendance', [], null, 'Attendance Summary') }}
        </h2>

        @if(! $publishedAttendance->snapshot)
            <div class="empty-state" style="padding:var(--space-4) 0">
                <div class="empty-state__icon" aria-hidden="true">📅</div>
                <p class="empty-state__body" style="color:var(--text-secondary);font-size:var(--text-sm)">
                    {{ __('ui.attendance_not_published', [], null, 'Attendance data has not been published yet for this semester.') }}
                </p>
            </div>
        @else
            @php
                $attSnap    = $publishedAttendance->snapshot;
                $attSummary = $publishedAttendance->summary;
                $attRows    = $publishedAttendance->rows;
            @endphp

            <div style="font-size:var(--text-xs);color:var(--text-secondary);margin-block-end:var(--space-4)">
                {{ __('ui.published_on', [], null, 'Published') }}:
                {{ \Carbon\Carbon::parse($attSnap->published_at)->format('Y-m-d') }}
                &middot; {{ __('ui.version', [], null, 'v') }}{{ $attSnap->version }}
                @if($attSnap->period_from)
                    &middot; {{ $attSnap->period_from }} → {{ $attSnap->period_to }}
                @endif
            </div>

            {{-- Summary counts --}}
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:var(--space-3);margin-block-end:var(--space-4)">
                <div style="text-align:center;background:var(--surface-page,#f9fafb);border-radius:var(--radius-md,6px);padding:var(--space-3)">
                    <div style="font-size:var(--text-2xl);font-weight:700;color:var(--text-primary)">{{ $attSummary->total }}</div>
                    <div style="font-size:var(--text-xs);color:var(--text-secondary);margin-block-start:var(--space-1)">{{ __('ui.total_days', [], null, 'Days') }}</div>
                </div>
                <div style="text-align:center;background:var(--color-success-50,#f0fdf4);border-radius:var(--radius-md,6px);padding:var(--space-3)">
                    <div style="font-size:var(--text-2xl);font-weight:700;color:var(--color-success-700,#15803d)">{{ $attSummary->present }}</div>
                    <div style="font-size:var(--text-xs);color:var(--color-success-600,#16a34a);margin-block-start:var(--space-1)">{{ __('ui.present', [], null, 'Present') }}</div>
                </div>
                <div style="text-align:center;background:var(--color-danger-50,#fef2f2);border-radius:var(--radius-md,6px);padding:var(--space-3)">
                    <div style="font-size:var(--text-2xl);font-weight:700;color:var(--color-danger-700,#b91c1c)">{{ $attSummary->absent }}</div>
                    <div style="font-size:var(--text-xs);color:var(--color-danger-600,#dc2626);margin-block-start:var(--space-1)">{{ __('ui.absent', [], null, 'Absent') }}</div>
                </div>
                <div style="text-align:center;background:var(--color-warning-50,#fffbeb);border-radius:var(--radius-md,6px);padding:var(--space-3)">
                    <div style="font-size:var(--text-2xl);font-weight:700;color:var(--color-warning-700,#b45309)">{{ $attSummary->late }}</div>
                    <div style="font-size:var(--text-xs);color:var(--color-warning-600,#d97706);margin-block-start:var(--space-1)">{{ __('ui.late', [], null, 'Late') }}</div>
                </div>
            </div>

            {{-- Daily detail (only when detail_level = daily_status) --}}
            @if($attRows->isNotEmpty())
                <div style="margin-block-start:var(--space-4);border-block-start:1px solid var(--card-border);padding-block-start:var(--space-4)">
                    <div style="font-size:var(--text-sm);font-weight:600;margin-block-end:var(--space-3)">
                        {{ __('ui.daily_detail', [], null, 'Daily Detail') }}
                    </div>
                    <div style="overflow-x:auto;max-height:320px;overflow-y:auto">
                        <table style="width:100%;border-collapse:collapse;font-size:var(--text-sm)">
                            <thead style="position:sticky;top:0;background:var(--surface-card)">
                                <tr style="border-block-end:1px solid var(--card-border)">
                                    <th style="text-align:start;padding:var(--space-2) var(--space-3);color:var(--text-secondary)">{{ __('ui.date', [], null, 'Date') }}</th>
                                    <th style="text-align:center;padding:var(--space-2) var(--space-3);color:var(--text-secondary)">{{ __('ui.status', [], null, 'Status') }}</th>
                                    @if($attSnap->show_reason)
                                    <th style="text-align:start;padding:var(--space-2) var(--space-3);color:var(--text-secondary)">{{ __('ui.reason', [], null, 'Reason') }}</th>
                                    @endif
                                    @if($attSnap->show_arrival_departure)
                                    <th style="text-align:center;padding:var(--space-2) var(--space-3);color:var(--text-secondary)">{{ __('ui.arrival', [], null, 'Arrival') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($attRows as $dayRow)
                                <tr style="border-block-end:1px solid var(--card-border)">
                                    <td style="padding:var(--space-2) var(--space-3)">{{ \Carbon\Carbon::parse($dayRow->attendance_date)->format('Y-m-d') }}</td>
                                    <td style="padding:var(--space-2) var(--space-3);text-align:center">
                                        <span class="badge {{ $dayRow->status_code === 'present' ? 'badge--active' : ($dayRow->status_code === 'absent' ? 'badge--inactive' : 'badge--pending') }}">
                                            {{ $dayRow->status_code ?? '—' }}
                                        </span>
                                    </td>
                                    @if($attSnap->show_reason)
                                    <td style="padding:var(--space-2) var(--space-3);color:var(--text-secondary)">{{ $dayRow->reason ?? '—' }}</td>
                                    @endif
                                    @if($attSnap->show_arrival_departure)
                                    <td style="padding:var(--space-2) var(--space-3);text-align:center">{{ $dayRow->arrived_at ?? '—' }}</td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif
    </div>

    @endif
</div>

<style>
.card{background:var(--surface-card);border:1px solid var(--card-border);border-radius:var(--card-radius);padding:var(--card-padding);box-shadow:var(--card-shadow)}
.alert--success{background:var(--color-success-50,#f0fdf4);border:1px solid var(--color-success-300,#86efac);border-radius:var(--radius-md,6px);padding:var(--space-3);color:var(--color-success-800,#166534)}
@media(max-width:640px){
    div[style*="grid-template-columns:1fr 1fr"]{grid-template-columns:1fr!important}
    div[style*="grid-template-columns:repeat(4,1fr)"]{grid-template-columns:repeat(2,1fr)!important}
}
</style>

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

    @endif
</div>

<style>
.card{background:var(--surface-card);border:1px solid var(--card-border);border-radius:var(--card-radius);padding:var(--card-padding);box-shadow:var(--card-shadow)}
@media(max-width:640px){
    div[style*="grid-template-columns:1fr 1fr"]{grid-template-columns:1fr!important}
}
</style>

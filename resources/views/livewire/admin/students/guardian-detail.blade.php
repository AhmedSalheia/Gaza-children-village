@php /** @var \App\Livewire\Admin\Students\GuardianDetail $this */ @endphp

<div>
    <div class="page-header">
        <div style="display:flex;align-items:center;gap:var(--space-3)">
            <a href="{{ route('admin.guardians.index') }}" class="btn btn--outline btn--sm" wire:navigate>← {{ __('ui.back', [], null, 'Back') }}</a>
            <h1 class="page-title" style="margin:0">
                {{ $person?->full_name_ar ?? '—' }}
            </h1>
        </div>
    </div>

    @if($guardian)
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6);margin-block-end:var(--space-6)">
            <div class="card">
                <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">{{ __('ui.profile', [], null, 'Profile') }}</h2>
                <dl style="display:grid;grid-template-columns:auto 1fr;gap:var(--space-2) var(--space-4)">
                    <dt style="color:var(--text-secondary);font-size:var(--text-sm)">{{ __('ui.guardian_code', [], null, 'Guardian Code') }}</dt>
                    <dd><code>{{ $guardian->guardian_code }}</code></dd>

                    <dt style="color:var(--text-secondary);font-size:var(--text-sm)">{{ __('ui.name_ar', [], null, 'Arabic Name') }}</dt>
                    <dd dir="rtl">{{ $person?->full_name_ar ?? '—' }}</dd>

                    <dt style="color:var(--text-secondary);font-size:var(--text-sm)">{{ __('ui.name_en', [], null, 'English Name') }}</dt>
                    <dd>{{ $person?->full_name_en ?? '—' }}</dd>

                    <dt style="color:var(--text-secondary);font-size:var(--text-sm)">{{ __('ui.status', [], null, 'Status') }}</dt>
                    <dd>
                        <span class="badge badge--{{ $guardian->lifecycle_status === 'active' ? 'active' : 'archived' }}">
                            {{ $guardian->lifecycle_status }}
                        </span>
                    </dd>
                </dl>
            </div>

            <div class="card">
                <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">{{ __('ui.students', [], null, 'Students') }}</h2>
                @forelse($relationships as $rel)
                    <div style="padding:var(--space-2) 0;border-block-end:1px solid var(--table-border)">
                        <a href="{{ route('admin.students.detail', ['studentId' => $rel->student_id]) }}" class="link" wire:navigate>
                            <span dir="rtl">{{ $rel->student_name_ar }}</span>
                        </a>
                        <code style="font-size:var(--text-xs);margin-inline-start:var(--space-2)">{{ $rel->student_code }}</code>
                        @php
                            $relActive   = $rel->ends_on === null || $rel->ends_on >= now()->toDateString();
                            $relVerified = $rel->verification_status === 'verified';
                        @endphp
                        <div style="display:flex;gap:var(--space-1);margin-block-start:var(--space-1)">
                            <span class="badge badge--draft">{{ $rel->relationship_type }}</span>
                            <span class="badge badge--{{ $relActive ? 'active' : 'closed' }}">
                                {{ $relActive ? __('ui.active', [], null, 'Active') : __('ui.ended', [], null, 'Ended') }}
                            </span>
                            @if($relVerified)
                                <span class="badge badge--open">{{ __('ui.verified', [], null, 'Verified') }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p style="color:var(--text-secondary)">{{ __('ui.no_students', [], null, 'No students linked.') }}</p>
                @endforelse
            </div>
        </div>

        {{-- ── Pending correction requests ──────────────────────────────────── --}}
        @if($pendingCorrectionRequests->isNotEmpty())
        <div class="card">
            <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4);display:flex;align-items:center;gap:var(--space-2)">
                {{ __('ui.pending_corrections', [], null, 'Pending Correction Requests') }}
                <span class="badge badge--draft" style="font-size:var(--text-xs)">{{ $pendingCorrectionRequests->count() }}</span>
            </h2>

            <div style="display:flex;flex-direction:column;gap:var(--space-4)">
                @foreach($pendingCorrectionRequests as $cr)
                <div style="border:1px solid var(--color-warning-300,#fcd34d);border-radius:var(--radius-md,6px);padding:var(--space-4);background:var(--color-warning-50,#fffbeb)">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:var(--space-4);flex-wrap:wrap">
                        <div>
                            <div style="font-weight:600;font-size:var(--text-sm);color:var(--text-primary);margin-block-end:var(--space-2)">
                                <span dir="rtl">{{ $cr->student_name_ar }}</span>
                                <code style="font-size:var(--text-xs);margin-inline-start:var(--space-2);font-weight:400">{{ $cr->student_code }}</code>
                            </div>

                            <dl style="display:grid;grid-template-columns:auto 1fr;gap:var(--space-1) var(--space-3);font-size:var(--text-sm);align-items:baseline">

                                @if($cr->requested_contact_priority !== null)
                                <dt style="color:var(--text-secondary);white-space:nowrap">{{ __('ui.contact_priority', [], null, 'Contact Priority') }}</dt>
                                <dd>
                                    <span style="text-decoration:line-through;color:var(--text-tertiary)">{{ $cr->current_contact_priority ?? '—' }}</span>
                                    <span style="margin-inline:var(--space-1)">→</span>
                                    <strong>{{ $cr->requested_contact_priority }}</strong>
                                </dd>
                                @endif

                                @if($cr->requested_is_emergency_contact !== null)
                                <dt style="color:var(--text-secondary);white-space:nowrap">{{ __('ui.emergency_contact', [], null, 'Emergency Contact') }}</dt>
                                <dd>
                                    <span style="text-decoration:line-through;color:var(--text-tertiary)">
                                        {{ $cr->current_is_emergency_contact ? __('ui.yes', [], null, 'Yes') : __('ui.no', [], null, 'No') }}
                                    </span>
                                    <span style="margin-inline:var(--space-1)">→</span>
                                    <strong>{{ $cr->requested_is_emergency_contact ? __('ui.yes', [], null, 'Yes') : __('ui.no', [], null, 'No') }}</strong>
                                </dd>
                                @endif

                                @if($cr->note)
                                <dt style="color:var(--text-secondary)">{{ __('ui.note', [], null, 'Note') }}</dt>
                                <dd style="color:var(--text-primary)">{{ $cr->note }}</dd>
                                @endif

                                <dt style="color:var(--text-secondary)">{{ __('ui.submitted', [], null, 'Submitted') }}</dt>
                                <dd style="color:var(--text-secondary)">{{ \Carbon\Carbon::parse($cr->created_at)->diffForHumans() }}</dd>

                            </dl>
                        </div>

                        @if($canManage)
                        <div style="display:flex;gap:var(--space-2);flex-shrink:0">
                            <button
                                type="button"
                                class="btn btn--primary btn--sm"
                                wire:click="approveCorrectionRequest({{ $cr->id }})"
                                wire:confirm="{{ __('ui.confirm_approve_correction', [], null, 'Apply this correction to the relationship record?') }}"
                            >
                                {{ __('ui.approve', [], null, 'Approve') }}
                            </button>
                            <button
                                type="button"
                                class="btn btn--outline btn--sm"
                                wire:click="rejectCorrectionRequest({{ $cr->id }})"
                                wire:confirm="{{ __('ui.confirm_reject_correction', [], null, 'Reject this correction request?') }}"
                            >
                                {{ __('ui.reject', [], null, 'Reject') }}
                            </button>
                        </div>
                        @else
                        <div>
                            <span class="badge badge--draft" style="font-size:var(--text-xs)">
                                {{ __('ui.pending', [], null, 'Pending review') }}
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    @endif
</div>

@include('livewire.admin._partials.page-styles')

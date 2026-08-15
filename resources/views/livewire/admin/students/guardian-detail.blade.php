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
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6)">
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
    @endif
</div>

@include('livewire.admin._partials.page-styles')

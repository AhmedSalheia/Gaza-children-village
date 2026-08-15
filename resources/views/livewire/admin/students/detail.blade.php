@php /** @var \App\Livewire\Admin\Students\StudentDetail $this */ @endphp

<div>
    <div class="page-header">
        <div style="display:flex;align-items:center;gap:var(--space-3)">
            <a href="{{ route('admin.students.index') }}" class="btn btn--outline btn--sm" wire:navigate>← {{ __('ui.back', [], null, 'Back') }}</a>
            <h1 class="page-title" style="margin:0">
                {{ $person?->full_name_ar ?? '—' }}
            </h1>
            @if($student)
                <span class="badge badge--{{ match($student->lifecycle_status) { 'active' => 'active', 'draft' => 'draft', 'withdrawn','inactive' => 'closed', 'graduated' => 'archived', default => 'pending' } }}">
                    {{ $student->lifecycle_status }}
                </span>
            @endif
        </div>
    </div>

    @if($student)
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6);margin-block-end:var(--space-6)">
            {{-- Profile card --}}
            <div class="card">
                <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">{{ __('ui.profile', [], null, 'Profile') }}</h2>
                <dl style="display:grid;grid-template-columns:auto 1fr;gap:var(--space-2) var(--space-4)">
                    <dt style="color:var(--text-secondary);font-size:var(--text-sm)">{{ __('ui.student_code', [], null, 'Student Code') }}</dt>
                    <dd><code>{{ $student->student_code }}</code></dd>

                    <dt style="color:var(--text-secondary);font-size:var(--text-sm)">{{ __('ui.name_ar', [], null, 'Arabic Name') }}</dt>
                    <dd dir="rtl">{{ $person?->full_name_ar ?? '—' }}</dd>

                    <dt style="color:var(--text-secondary);font-size:var(--text-sm)">{{ __('ui.name_en', [], null, 'English Name') }}</dt>
                    <dd>{{ $person?->full_name_en ?? '—' }}</dd>

                    <dt style="color:var(--text-secondary);font-size:var(--text-sm)">{{ __('ui.birth_date', [], null, 'Birth Date') }}</dt>
                    <dd>{{ $person?->birth_date ?? '—' }} ({{ $person?->birth_date_precision ?? '' }})</dd>

                    <dt style="color:var(--text-secondary);font-size:var(--text-sm)">{{ __('ui.registered_on', [], null, 'Registered On') }}</dt>
                    <dd>{{ $student->registered_on ?? '—' }}</dd>
                </dl>
            </div>

            {{-- Guardians card --}}
            <div class="card">
                <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">{{ __('ui.guardians', [], null, 'Guardians') }}</h2>
                @forelse($guardianRelationships as $rel)
                    <div style="padding:var(--space-2) 0;border-block-end:1px solid var(--table-border)">
                        <div>
                            <a href="{{ route('admin.guardians.detail', ['guardianId' => $rel->guardian_id]) }}" class="link" wire:navigate>
                                <span dir="rtl">{{ $rel->full_name_ar }}</span>
                            </a>
                            <span style="font-size:var(--text-sm);color:var(--text-secondary);margin-inline-start:var(--space-2)">
                                {{ $rel->relationship_type }}
                            </span>
                        </div>
                        @php
                            $relActive   = $rel->ends_on === null || $rel->ends_on >= now()->toDateString();
                            $relVerified = $rel->verification_status === 'verified';
                        @endphp
                        <div style="display:flex;gap:var(--space-2);margin-block-start:var(--space-1)">
                            <span class="badge badge--{{ $relActive ? 'active' : 'closed' }}">
                                {{ $relActive ? __('ui.active', [], null, 'Active') : __('ui.ended', [], null, 'Ended') }}
                            </span>
                            @if($relVerified)
                                <span class="badge badge--open">{{ __('ui.verified', [], null, 'Verified') }}</span>
                            @endif
                            @if($rel->portal_eligible)
                                <span class="badge badge--pending">{{ __('ui.portal', [], null, 'Portal') }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p style="color:var(--text-secondary)">{{ __('ui.no_guardians', [], null, 'No guardians linked.') }}</p>
                @endforelse
            </div>
        </div>

        {{-- Enrollment history --}}
        <div class="card">
            <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">{{ __('ui.enrollment_history', [], null, 'Enrolment History') }}</h2>
            @if($enrollmentHistory->isEmpty())
                <p style="color:var(--text-secondary)">{{ __('ui.no_enrollments', [], null, 'No enrolment records.') }}</p>
            @else
                <div class="data-table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('ui.class_group', [], null, 'Class Group') }}</th>
                                <th>{{ __('ui.level', [], null, 'Level') }}</th>
                                <th>{{ __('ui.status', [], null, 'Status') }}</th>
                                <th>{{ __('ui.enrolled_on', [], null, 'Enrolled') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($enrollmentHistory as $enrollment)
                                <tr>
                                    <td>{{ optional($enrollment->classGroup)->name_ar ?? '—' }}</td>
                                    <td>{{ optional($enrollment->classGroup?->academicLevel)->name_ar ?? '—' }}</td>
                                    <td>
                                        <span class="badge badge--{{ match($enrollment->enrollment_status->value) { 'active' => 'active', 'draft' => 'draft', 'withdrawn','transferred' => 'closed', 'completed','promoted' => 'archived', default => 'pending' } }}">
                                            {{ $enrollment->enrollment_status->value }}
                                        </span>
                                    </td>
                                    <td>{{ $enrollment->enrolled_on?->format('Y-m-d') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</div>

@include('livewire.admin._partials.page-styles')

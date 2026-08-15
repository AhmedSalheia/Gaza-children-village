@php /** @var \App\Livewire\Staff\Students\StudentDetail $this */ @endphp

<div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-block-end:var(--space-6);flex-wrap:wrap;gap:var(--space-3)">
        <div>
            <a href="{{ route('staff.students.index') }}" class="link" wire:navigate style="font-size:var(--text-sm)">
                ← {{ __('ui.back_to_students', [], null, 'Back to Students') }}
            </a>
            <h1 style="font-size:var(--text-2xl);font-weight:700;color:var(--text-primary);margin:var(--space-1) 0 0">
                {{ $student?->full_name_ar ?? '—' }}
            </h1>
            @if($student?->full_name_en)
            <div style="color:var(--text-secondary)">{{ $student->full_name_en }}</div>
            @endif
        </div>
        <div style="display:flex;gap:var(--space-2)">
            @if($canManageRelationships)
            <a href="{{ route('staff.students.relationships', ['studentProfileId' => $studentProfileId]) }}" class="btn btn--outline btn--sm" wire:navigate>
                {{ __('ui.relationships', [], null, 'Relationships') }}
            </a>
            @endif
            @if($canTransfer)
            <a href="{{ route('staff.enrollments.transfer', ['studentProfileId' => $studentProfileId]) }}" class="btn btn--secondary btn--sm" wire:navigate>
                {{ __('ui.transfer', [], null, 'Transfer') }}
            </a>
            @endif
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6)">
        {{-- Basic profile --}}
        <div class="card">
            <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">{{ __('ui.profile', [], null, 'Profile') }}</h2>
            <dl style="display:grid;grid-template-columns:auto 1fr;gap:var(--space-2) var(--space-4)">
                <dt style="color:var(--text-secondary);font-size:var(--text-sm)">{{ __('ui.student_code', [], null, 'Student Code') }}</dt>
                <dd style="font-weight:500">{{ $student?->student_code }}</dd>
                <dt style="color:var(--text-secondary);font-size:var(--text-sm)">{{ __('ui.status', [], null, 'Status') }}</dt>
                <dd><span class="badge badge--{{ $student?->lifecycle_status === 'active' ? 'active' : 'draft' }}">{{ $student?->lifecycle_status }}</span></dd>
                <dt style="color:var(--text-secondary);font-size:var(--text-sm)">{{ __('ui.gender', [], null, 'Gender') }}</dt>
                <dd>{{ $student?->gender ?? '—' }}</dd>
                <dt style="color:var(--text-secondary);font-size:var(--text-sm)">{{ __('ui.birth_date', [], null, 'Birth Date') }}</dt>
                <dd>{{ $student?->birth_date ? \Carbon\Carbon::parse($student->birth_date)->format('Y-m-d') : '—' }}</dd>
                <dt style="color:var(--text-secondary);font-size:var(--text-sm)">{{ __('ui.registered_on', [], null, 'Registered On') }}</dt>
                <dd>{{ $student?->registered_on ?? '—' }}</dd>
            </dl>
        </div>

        {{-- Sensitive data (counselor only) --}}
        <div class="card">
            <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">
                {{ __('ui.welfare_fields', [], null, 'Welfare & Status') }}
                @if(!$canViewSensitive)
                <span style="font-size:var(--text-xs);font-weight:400;color:var(--text-secondary)">({{ __('ui.restricted', [], null, 'Restricted') }})</span>
                @endif
            </h2>
            @if($canViewSensitive && $sensitiveData)
            <dl style="display:grid;grid-template-columns:auto 1fr;gap:var(--space-2) var(--space-4)">
                <dt style="color:var(--text-secondary);font-size:var(--text-sm)">{{ __('ui.orphan_status', [], null, 'Orphan Status') }}</dt>
                <dd>{{ $sensitiveData->orphan_status ?? '—' }}</dd>
                <dt style="color:var(--text-secondary);font-size:var(--text-sm)">{{ __('ui.displacement_status', [], null, 'Displacement Status') }}</dt>
                <dd>{{ $sensitiveData->displacement_status ?? '—' }}</dd>
                <dt style="color:var(--text-secondary);font-size:var(--text-sm)">{{ __('ui.evidence_status', [], null, 'Evidence Status') }}</dt>
                <dd>{{ $sensitiveData->evidence_status ?? '—' }}</dd>
            </dl>
            @else
            <p style="color:var(--text-secondary);font-style:italic">{{ __('ui.sensitive_fields_restricted', [], null, 'Sensitive welfare fields are only visible to counselors.') }}</p>
            @endif
        </div>
    </div>

    {{-- Guardian relationships --}}
    @if($guardianRelationships->isNotEmpty())
    <div class="card" style="margin-block-start:var(--space-6)">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-block-end:var(--space-4)">
            <h2 style="font-size:var(--text-lg);font-weight:600;margin:0">{{ __('ui.guardians', [], null, 'Guardians') }}</h2>
            @if($canManageRelationships)
            <a href="{{ route('staff.students.relationships', ['studentProfileId' => $studentProfileId]) }}" class="btn btn--outline btn--sm" wire:navigate>
                {{ __('ui.manage', [], null, 'Manage') }}
            </a>
            @endif
        </div>
        <div class="data-table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('ui.guardian', [], null, 'Guardian') }}</th>
                    <th>{{ __('ui.relationship_type', [], null, 'Type') }}</th>
                    <th>{{ __('ui.verification', [], null, 'Verified') }}</th>
                    <th>{{ __('ui.portal_eligible', [], null, 'Portal') }}</th>
                    <th>{{ __('ui.ends_on', [], null, 'Ends On') }}</th>
                </tr></thead>
                <tbody>
                    @foreach($guardianRelationships as $rel)
                    <tr>
                        <td>{{ $rel->guardian_name }}</td>
                        <td>{{ $rel->relationship_type }}</td>
                        <td>
                            <span class="badge badge--{{ $rel->verification_status === 'verified' ? 'active' : 'pending' }}">
                                {{ $rel->verification_status }}
                            </span>
                        </td>
                        <td>{{ $rel->portal_eligible ? '✓' : '—' }}</td>
                        <td>{{ $rel->ends_on ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Enrollment history --}}
    <div class="card" style="margin-block-start:var(--space-6)">
        <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">{{ __('ui.enrollment_history', [], null, 'Enrollment History') }}</h2>
        @if($enrollmentHistory->isEmpty())
        <p style="color:var(--text-secondary);font-style:italic">{{ __('ui.no_enrollments', [], null, 'No enrollment history.') }}</p>
        @else
        <div class="data-table-wrapper">
            <table class="data-table">
                <thead><tr>
                    <th>{{ __('ui.semester', [], null, 'Semester') }}</th>
                    <th>{{ __('ui.class_group', [], null, 'Class Group') }}</th>
                    <th>{{ __('ui.level', [], null, 'Level') }}</th>
                    <th>{{ __('ui.status', [], null, 'Status') }}</th>
                    <th>{{ __('ui.enrolled_on', [], null, 'Enrolled On') }}</th>
                </tr></thead>
                <tbody>
                    @foreach($enrollmentHistory as $e)
                    <tr>
                        <td>{{ $e->semester_name }}</td>
                        <td>{{ $e->class_group_name }}</td>
                        <td>{{ $e->level_name }}</td>
                        <td><span class="badge badge--{{ match($e->enrollment_status) {'active'=>'active','draft'=>'draft','completed'=>'archived',default=>'closed'} }}">{{ $e->enrollment_status }}</span></td>
                        <td>{{ $e->enrolled_on }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

<style>
.card{background:var(--surface-card);border:1px solid var(--card-border);border-radius:var(--card-radius);padding:var(--card-padding);box-shadow:var(--card-shadow)}
dl dt{font-weight:500}
</style>

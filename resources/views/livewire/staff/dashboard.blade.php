@php /** @var \App\Livewire\Staff\Dashboard $this */ @endphp

<div>
    {{-- Scope header --}}
    @if($hasScope)
        <div style="background:var(--surface-card);border:1px solid var(--card-border);border-radius:var(--card-radius);padding:var(--space-4) var(--space-6);margin-block-end:var(--space-6);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--space-2)">
            <div>
                <div style="font-size:var(--text-xs);color:var(--text-secondary);text-transform:uppercase;letter-spacing:var(--tracking-wide)">{{ __('ui.institution', [], null, 'Institution') }}</div>
                <div style="font-size:var(--text-lg);font-weight:700;color:var(--text-primary)">{{ $institutionInfo?->name_ar ?? '—' }}</div>
            </div>
            @if($semesterInfo)
            <div style="text-align:end">
                <div style="font-size:var(--text-xs);color:var(--text-secondary);text-transform:uppercase;letter-spacing:var(--tracking-wide)">{{ __('ui.semester', [], null, 'Semester') }}</div>
                <div style="font-size:var(--text-base);font-weight:600;color:var(--text-primary)">
                    {{ $semesterInfo->semester_name }}
                    <span class="badge badge--{{ $semesterInfo->status === 'open' ? 'open' : 'closed' }}" style="margin-inline-start:var(--space-2)">{{ $semesterInfo->status }}</span>
                </div>
            </div>
            @endif
        </div>
    @else
        <div class="alert alert--warning" role="alert">
            {{ __('ui.no_position_scope', [], null, 'Your account has no active institutional position. Contact your administrator.') }}
        </div>
    @endif

    @if($hasScope)
    {{-- Stat cards --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:var(--space-4);margin-block-end:var(--space-8)">
        @if($canViewStudents)
        <div class="stat-card stat-card--green">
            <div class="stat-card__value">{{ number_format($studentCount) }}</div>
            <div class="stat-card__label">{{ __('ui.enrolled_students', [], null, 'Enrolled Students') }}</div>
        </div>
        @endif

        @if($canManageEnrollments)
        <div class="stat-card stat-card--gold">
            <div class="stat-card__value">{{ number_format($pendingEnrollmentCount) }}</div>
            <div class="stat-card__label">{{ __('ui.draft_enrollments', [], null, 'Draft Enrollments') }}</div>
        </div>
        @endif

        @if($canPromote)
        <div class="stat-card stat-card--teal">
            <div class="stat-card__value">{{ number_format($pendingPromotionCount) }}</div>
            <div class="stat-card__label">{{ __('ui.pending_promotions', [], null, 'Pending Proposals') }}</div>
        </div>
        @endif

        @if($canViewImports)
        <div class="stat-card">
            <div class="stat-card__value">{{ number_format($activeImportCount) }}</div>
            <div class="stat-card__label">{{ __('ui.active_imports', [], null, 'Active Imports') }}</div>
        </div>
        @endif
    </div>

    {{-- Quick actions --}}
    @php
        $actions = [];
        if($canCreateStudent) $actions[] = ['route' => 'staff.students.add', 'label' => __('ui.add_student', [], null, 'Add Student'), 'style' => 'primary'];
        if($canUploadImports) $actions[] = ['route' => 'staff.imports.index', 'label' => __('ui.upload_import', [], null, 'Upload Import'), 'style' => 'secondary'];
        if($canPromote) $actions[] = ['route' => 'staff.promotions.index', 'label' => __('ui.review_promotions', [], null, 'Review Promotions'), 'style' => 'outline'];
    @endphp
    @if(count($actions) > 0)
    <div class="card" style="margin-block-end:var(--space-6)">
        <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">{{ __('ui.quick_actions', [], null, 'Quick Actions') }}</h2>
        <div style="display:flex;gap:var(--space-3);flex-wrap:wrap">
            @foreach($actions as $action)
            <a href="{{ route($action['route']) }}" class="btn btn--{{ $action['style'] }}" wire:navigate>
                {{ $action['label'] }}
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Recent imports --}}
    @if($canViewImports && $recentImports->isNotEmpty())
    <div class="card">
        <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">{{ __('ui.recent_imports', [], null, 'Recent Imports') }}</h2>
        <table class="data-table">
            <thead><tr>
                <th>{{ __('ui.filename', [], null, 'File') }}</th>
                <th>{{ __('ui.status', [], null, 'Status') }}</th>
                <th>{{ __('ui.created_at', [], null, 'Uploaded') }}</th>
            </tr></thead>
            <tbody>
                @foreach($recentImports as $batch)
                <tr>
                    <td>
                        @if($canViewImports)
                        <a href="{{ route('staff.imports.detail', ['batchId' => $batch->id]) }}" class="link" wire:navigate>
                            {{ $batch->original_filename ?? '#' . $batch->id }}
                        </a>
                        @else
                        {{ $batch->original_filename ?? '#' . $batch->id }}
                        @endif
                    </td>
                    <td><span class="badge badge--{{ match($batch->status) {'completed'=>'active','cancelled','completed_with_errors'=>'closed',default=>'pending'} }}">{{ $batch->status }}</span></td>
                    <td>{{ $batch->created_at }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    @endif
</div>

<style>
.stat-card{background:var(--surface-card);border:1px solid var(--card-border);border-radius:var(--card-radius);padding:var(--space-6);box-shadow:var(--card-shadow);border-inline-start:4px solid var(--neutral-200)}
.stat-card--green{border-inline-start-color:var(--brand-green)}
.stat-card--gold{border-inline-start-color:var(--brand-gold)}
.stat-card--teal{border-inline-start-color:var(--brand-teal)}
.stat-card__value{font-size:var(--text-3xl);font-weight:700;color:var(--text-primary);line-height:var(--leading-tight)}
.stat-card__label{font-size:var(--text-sm);color:var(--text-secondary);margin-block-start:var(--space-1)}
.card{background:var(--surface-card);border:1px solid var(--card-border);border-radius:var(--card-radius);padding:var(--card-padding);box-shadow:var(--card-shadow)}
</style>

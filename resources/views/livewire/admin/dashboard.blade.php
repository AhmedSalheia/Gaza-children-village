@php /** @var \App\Livewire\Admin\Dashboard $this */ @endphp

{{--
 Each section is conditionally rendered based on the permission flags
 passed from Dashboard::render(). Role-restricted admins (calendar_manager,
 account_manager) only see sections their role permits.
--}}

<div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-block-end:var(--space-6)">
        <h1 style="font-size:var(--text-2xl);font-weight:700;color:var(--text-primary);margin:0">
            {{ __('ui.dashboard', [], null, 'Dashboard') }}
        </h1>
        <span style="font-size:var(--text-sm);color:var(--text-secondary)">
            {{ now()->locale(app()->getLocale())->isoFormat('LL') }}
        </span>
    </div>

    @if(! $canViewStudents && ! $canViewEnrollments && ! $canPromote && ! $canReviewImports && ! $canViewInstitutions && ! $canViewSemesters && ! $canCreateStudents && ! $canUploadImports)
        <div class="card" style="text-align:center;padding:var(--space-12)">
            <p style="color:var(--text-secondary)">
                {{ __('ui.no_dashboard_permissions', [], null, 'No overview data is available for your role. Use the navigation above to access your assigned area.') }}
            </p>
        </div>
    @else

    {{-- Summary stat cards --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:var(--space-4);margin-block-end:var(--space-8)">

        @if($canViewStudents)
            @php
                $activeStudents = $studentCounts['active'] ?? 0;
                $draftStudents  = $studentCounts['draft'] ?? 0;
                $totalStudents  = array_sum($studentCounts);
            @endphp
            <div class="stat-card">
                <div class="stat-card__value">{{ number_format($totalStudents) }}</div>
                <div class="stat-card__label">{{ __('ui.total_students', [], null, 'Total Students') }}</div>
            </div>
            <div class="stat-card stat-card--green">
                <div class="stat-card__value">{{ number_format($activeStudents) }}</div>
                <div class="stat-card__label">{{ __('ui.active_students', [], null, 'Active Students') }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__value">{{ number_format($draftStudents) }}</div>
                <div class="stat-card__label">{{ __('ui.draft_students', [], null, 'Draft Profiles') }}</div>
            </div>
        @endif

        @if($canViewEnrollments)
            <div class="stat-card">
                <div class="stat-card__value">{{ number_format($activeEnrollmentCount) }}</div>
                <div class="stat-card__label">{{ __('ui.active_enrollments', [], null, 'Active Enrolments') }}</div>
            </div>
        @endif

        @if($canPromote)
            <div class="stat-card stat-card--gold">
                <div class="stat-card__value">{{ number_format($pendingPromotionCount) }}</div>
                <div class="stat-card__label">{{ __('ui.pending_promotions', [], null, 'Pending Promotions') }}</div>
            </div>
        @endif

        @if($canReviewImports)
            <div class="stat-card">
                <div class="stat-card__value">{{ number_format($activeImportCount) }}</div>
                <div class="stat-card__label">{{ __('ui.active_imports', [], null, 'Active Imports') }}</div>
            </div>
        @endif

        @if($canViewInstitutions)
            <div class="stat-card">
                <div class="stat-card__value">{{ number_format($institutionCount) }}</div>
                <div class="stat-card__label">{{ __('ui.active_institutions', [], null, 'Active Institutions') }}</div>
            </div>
        @endif

        @if($canViewSemesters)
            <div class="stat-card stat-card--teal">
                <div class="stat-card__value">{{ number_format($openSemesterCount) }}</div>
                <div class="stat-card__label">{{ __('ui.open_semesters', [], null, 'Open Semesters') }}</div>
            </div>
        @endif

    </div>

    {{-- Detail panels --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6)">

        @if($canViewStudents)
        <div class="card">
            <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">
                {{ __('ui.students_by_status', [], null, 'Students by Status') }}
            </h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('ui.status', [], null, 'Status') }}</th>
                        <th>{{ __('ui.count', [], null, 'Count') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($studentCounts as $status => $count)
                        <tr>
                            <td>
                                <span class="badge badge--{{ match($status) {
                                    'active' => 'active',
                                    'draft' => 'draft',
                                    'withdrawn', 'inactive' => 'closed',
                                    'graduated' => 'archived',
                                    default => 'pending'
                                } }}">{{ $status }}</span>
                            </td>
                            <td>{{ number_format($count) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="empty-state">{{ __('ui.no_data', [], null, 'No data yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

        @if($canReviewImports)
        <div class="card">
            <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">
                {{ __('ui.recent_imports', [], null, 'Recent Imports') }}
            </h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('ui.filename', [], null, 'File') }}</th>
                        <th>{{ __('ui.status', [], null, 'Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentBatches as $batch)
                        <tr>
                            <td>
                                <a href="{{ route('admin.imports.detail', ['batchId' => $batch->id]) }}" class="link" wire:navigate>
                                    {{ $batch->original_filename ?? '#' . $batch->id }}
                                </a>
                            </td>
                            <td>
                                <span class="badge badge--{{ match($batch->status) {
                                    'completed' => 'active',
                                    'cancelled', 'completed_with_errors' => 'closed',
                                    'uploaded', 'parsing', 'ready_for_mapping', 'validating', 'ready_for_review', 'applying' => 'pending',
                                    default => 'draft'
                                } }}">{{ $batch->status }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="empty-state">{{ __('ui.no_imports', [], null, 'No imports yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($activeImportCount > 0)
                <div style="margin-block-start:var(--space-4)">
                    <a href="{{ route('admin.imports.index') }}" class="btn btn--outline btn--sm" wire:navigate>
                        {{ __('ui.view_all', [], null, 'View all imports') }}
                    </a>
                </div>
            @endif
        </div>
        @endif

    </div>

    {{-- Quick actions (only shown for actions the admin can actually perform) --}}
    @php
        $quickActions = [];
        if($canCreateStudents) $quickActions[] = ['route' => 'admin.students.add', 'label' => __('ui.add_student', [], null, 'Add Student'), 'style' => 'primary', 'prefix' => '+ '];
        if($canUploadImports) $quickActions[] = ['route' => 'admin.imports.index', 'label' => __('ui.upload_import', [], null, 'Upload Import'), 'style' => 'secondary', 'prefix' => ''];
        if($canPromote) $quickActions[] = ['route' => 'admin.promotions.index', 'label' => __('ui.review_promotions', [], null, 'Review Promotions'), 'style' => 'outline', 'prefix' => ''];
        if($canViewInstitutions) $quickActions[] = ['route' => 'admin.institutions.index', 'label' => __('ui.institutions', [], null, 'Institutions'), 'style' => 'outline', 'prefix' => ''];
    @endphp
    @if(count($quickActions) > 0)
    <div class="card" style="margin-block-start:var(--space-6)">
        <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">
            {{ __('ui.quick_actions', [], null, 'Quick Actions') }}
        </h2>
        <div style="display:flex;gap:var(--space-3);flex-wrap:wrap">
            @foreach($quickActions as $action)
                <a href="{{ route($action['route']) }}" class="btn btn--{{ $action['style'] }}" wire:navigate>
                    {{ $action['prefix'] }}{{ $action['label'] }}
                </a>
            @endforeach
        </div>
    </div>
    @endif

    @endif {{-- end permission check --}}
</div>

<style>
.stat-card {
    background: var(--surface-card);
    border: 1px solid var(--card-border);
    border-radius: var(--card-radius);
    padding: var(--space-6);
    box-shadow: var(--card-shadow);
    border-inline-start: 4px solid var(--neutral-200);
}
.stat-card--green { border-inline-start-color: var(--brand-green); }
.stat-card--gold  { border-inline-start-color: var(--brand-gold); }
.stat-card--teal  { border-inline-start-color: var(--brand-teal); }
.stat-card__value {
    font-size: var(--text-3xl);
    font-weight: 700;
    color: var(--text-primary);
    line-height: var(--leading-tight);
}
.stat-card__label {
    font-size: var(--text-sm);
    color: var(--text-secondary);
    margin-block-start: var(--space-1);
}
.card {
    background: var(--surface-card);
    border: 1px solid var(--card-border);
    border-radius: var(--card-radius);
    padding: var(--card-padding);
    box-shadow: var(--card-shadow);
}
.empty-state {
    text-align: center;
    color: var(--text-secondary);
    padding: var(--space-6);
    font-style: italic;
}
</style>

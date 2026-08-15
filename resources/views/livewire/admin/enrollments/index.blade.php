@php /** @var \App\Livewire\Admin\Enrollments\EnrollmentIndex $this */ @endphp

<div>
    <div class="page-header">
        <h1 class="page-title">{{ __('ui.enrollments', [], null, 'Enrolments') }}</h1>
        <div style="display:flex;gap:var(--space-2)">
            @if($canTransfer)
            <a href="{{ route('admin.transfers.index') }}" class="btn btn--outline btn--sm" wire:navigate>{{ __('ui.transfers', [], null, 'Transfers') }}</a>
            @endif
            @if($canPromote)
            <a href="{{ route('admin.promotions.index') }}" class="btn btn--outline btn--sm" wire:navigate>{{ __('ui.promotions', [], null, 'Promotions') }}</a>
            @endif
        </div>
    </div>

    <div class="filters-bar">
        <input type="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="{{ __('ui.search', [], null, 'Search…') }}" style="max-inline-size:240px">

        <select wire:model.live="institutionFilter" class="form-control form-select" style="max-inline-size:220px">
            <option value="0">{{ __('ui.all_institutions', [], null, 'All institutions') }}</option>
            @foreach($institutions as $inst)
                <option value="{{ $inst->id }}">{{ $inst->name_ar }}</option>
            @endforeach
        </select>

        <select wire:model.live="semesterFilter" class="form-control form-select" style="max-inline-size:240px">
            <option value="0">{{ __('ui.all_semesters', [], null, 'All semesters') }}</option>
            @foreach($semesters as $sem)
                <option value="{{ $sem->id }}">{{ $sem->institution_name }} — {{ $sem->semester_name }}</option>
            @endforeach
        </select>

        <select wire:model.live="statusFilter" class="form-control form-select" style="max-inline-size:160px">
            <option value="">{{ __('ui.all_statuses', [], null, 'All statuses') }}</option>
            @foreach($statusOptions as $opt)
                <option value="{{ $opt }}">{{ $opt }}</option>
            @endforeach
        </select>
    </div>

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('ui.student', [], null, 'Student') }}</th>
                    <th>{{ __('ui.class_group', [], null, 'Class Group') }}</th>
                    <th>{{ __('ui.level', [], null, 'Level') }}</th>
                    <th>{{ __('ui.institution', [], null, 'Institution') }}</th>
                    <th>{{ __('ui.status', [], null, 'Status') }}</th>
                    <th>{{ __('ui.enrolled_on', [], null, 'Enrolled') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($enrollments as $enrollment)
                    <tr>
                        <td>
                            <div style="font-weight:600" dir="rtl">{{ $enrollment->student_name }}</div>
                            <code style="font-size:var(--text-xs)">{{ $enrollment->student_code }}</code>
                        </td>
                        <td dir="rtl">{{ $enrollment->class_group_name }}</td>
                        <td>{{ $enrollment->level_name }}</td>
                        <td>{{ $enrollment->institution_name }}</td>
                        <td>
                            <span class="badge badge--{{ match($enrollment->enrollment_status) {
                                'active' => 'active',
                                'draft' => 'draft',
                                'withdrawn','transferred' => 'closed',
                                'completed','promoted' => 'archived',
                                default => 'pending'
                            } }}">{{ $enrollment->enrollment_status }}</span>
                        </td>
                        <td style="font-size:var(--text-sm)">{{ $enrollment->enrolled_on ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-state">{{ __('ui.no_enrollments', [], null, 'No enrolments found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">
        <div class="pagination__info">{{ $enrollments->total() }} {{ __('ui.total', [], null, 'total') }}</div>
        {{ $enrollments->links() }}
    </div>
</div>

@include('livewire.admin._partials.page-styles')

@php /** @var \App\Livewire\Admin\Students\StudentIndex $this */ @endphp

<div>
    <div class="page-header">
        <h1 class="page-title">{{ __('ui.students', [], null, 'Students') }}</h1>
        @if($canCreateStudent)
        <a href="{{ route('admin.students.add') }}" class="btn btn--primary btn--sm" wire:navigate>
            + {{ __('ui.add_student', [], null, 'Add Student') }}
        </a>
        @endif
    </div>

    <div class="filters-bar">
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            class="form-control"
            placeholder="{{ __('ui.search_students', [], null, 'Search by name or code…') }}"
            style="max-inline-size:280px"
        >

        <select wire:model.live="statusFilter" class="form-control form-select" style="max-inline-size:160px">
            <option value="">{{ __('ui.all_statuses', [], null, 'All statuses') }}</option>
            @foreach($statusOptions as $opt)
                <option value="{{ $opt }}">{{ $opt }}</option>
            @endforeach
        </select>

        <select wire:model.live="institutionFilter" class="form-control form-select" style="max-inline-size:240px">
            <option value="0">{{ __('ui.all_institutions', [], null, 'All institutions') }}</option>
            @foreach($institutions as $inst)
                <option value="{{ $inst->id }}">{{ $inst->name_ar }}</option>
            @endforeach
        </select>

        @if($search || $statusFilter || $institutionFilter)
            <button wire:click="$set('search', ''); $set('statusFilter', ''); $set('institutionFilter', 0)" class="btn btn--outline btn--sm">
                {{ __('ui.clear', [], null, 'Clear') }}
            </button>
        @endif
    </div>

    <div wire:loading class="alert alert--info">{{ __('ui.loading', [], null, 'Loading…') }}</div>

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('ui.student_code', [], null, 'Code') }}</th>
                    <th>{{ __('ui.name', [], null, 'Name') }}</th>
                    <th>{{ __('ui.birth_date', [], null, 'Birth') }}</th>
                    <th>{{ __('ui.status', [], null, 'Status') }}</th>
                    <th>{{ __('ui.registered', [], null, 'Registered') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                    <tr>
                        <td><code>{{ $student->student_code }}</code></td>
                        <td>
                            <div style="font-weight:600" dir="rtl">{{ $student->full_name_ar }}</div>
                            @if($student->full_name_en)
                                <div style="font-size:var(--text-sm);color:var(--text-secondary)">{{ $student->full_name_en }}</div>
                            @endif
                        </td>
                        <td style="font-size:var(--text-sm)">{{ $student->birth_date ?? '—' }}</td>
                        <td>
                            <span class="badge badge--{{ match($student->lifecycle_status) {
                                'active' => 'active',
                                'draft' => 'draft',
                                'withdrawn', 'inactive' => 'closed',
                                'graduated' => 'archived',
                                default => 'pending'
                            } }}">{{ $student->lifecycle_status }}</span>
                        </td>
                        <td style="font-size:var(--text-sm);color:var(--text-secondary)">{{ $student->registered_on ?? '—' }}</td>
                        <td>
                            <a href="{{ route('admin.students.detail', ['studentId' => $student->id]) }}" class="btn btn--outline btn--sm" wire:navigate>
                                {{ __('ui.view', [], null, 'View') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-state">{{ __('ui.no_students', [], null, 'No students found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <div class="pagination__info">
            {{ $students->total() }} {{ __('ui.students', [], null, 'students') }}
        </div>
        {{ $students->links() }}
    </div>
</div>

@include('livewire.admin._partials.page-styles')

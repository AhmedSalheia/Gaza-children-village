@php /** @var \App\Livewire\Admin\Enrollments\TransferIndex $this */ @endphp

<div>
    <div class="page-header">
        <h1 class="page-title">{{ __('ui.transfers', [], null, 'Student Transfers') }}</h1>
        <a href="{{ route('admin.enrollments.index') }}" class="btn btn--outline btn--sm" wire:navigate>
            {{ __('ui.all_enrollments', [], null, 'All Enrolments') }}
        </a>
    </div>

    <div class="filters-bar">
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            class="form-control"
            placeholder="{{ __('ui.search', [], null, 'Search by name or code…') }}"
            style="max-inline-size:280px"
        >
    </div>

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('ui.student', [], null, 'Student') }}</th>
                    <th>{{ __('ui.class_group', [], null, 'Class Group') }}</th>
                    <th>{{ __('ui.institution', [], null, 'Institution') }}</th>
                    <th>{{ __('ui.enrolled_on', [], null, 'Enrolled') }}</th>
                    <th>{{ __('ui.transferred_on', [], null, 'Transferred') }}</th>
                    <th>{{ __('ui.notes', [], null, 'Notes') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transfers as $transfer)
                    <tr>
                        <td>
                            <div style="font-weight:600" dir="rtl">{{ $transfer->student_name }}</div>
                            <code style="font-size:var(--text-xs)">{{ $transfer->student_code }}</code>
                        </td>
                        <td dir="rtl">{{ $transfer->class_group_name }}</td>
                        <td>{{ $transfer->institution_name }}</td>
                        <td style="font-size:var(--text-sm)">{{ $transfer->enrolled_on ?? '—' }}</td>
                        <td style="font-size:var(--text-sm)">{{ $transfer->completed_on ?? '—' }}</td>
                        <td style="font-size:var(--text-sm);color:var(--text-secondary)">{{ $transfer->notes ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-state">{{ __('ui.no_transfers', [], null, 'No transfer records found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $transfers->links() }}
</div>

@include('livewire.admin._partials.page-styles')

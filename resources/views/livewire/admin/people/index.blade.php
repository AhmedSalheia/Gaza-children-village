@php /** @var \App\Livewire\Admin\People\PeopleIndex $this */ @endphp

<div>
    <div class="page-header">
        <h1 class="page-title">{{ __('ui.people', [], null, 'People') }}</h1>
    </div>

    <div class="filters-bar">
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            class="form-control"
            placeholder="{{ __('ui.search_people', [], null, 'Search by name…') }}"
            style="max-inline-size:320px"
        >
    </div>

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('ui.name', [], null, 'Name') }}</th>
                    <th>{{ __('ui.birth_date', [], null, 'Birth Date') }}</th>
                    <th>{{ __('ui.registered', [], null, 'Registered') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($people as $person)
                    <tr>
                        <td>
                            <div style="font-weight:600" dir="rtl">{{ $person->full_name_ar }}</div>
                            @if($person->full_name_en)
                                <div style="font-size:var(--text-sm);color:var(--text-secondary)">{{ $person->full_name_en }}</div>
                            @endif
                        </td>
                        <td>
                            @if($person->birth_date)
                                {{ $person->birth_date }}
                                <span style="font-size:var(--text-xs);color:var(--text-secondary)">({{ $person->birth_date_precision }})</span>
                            @else
                                <span style="color:var(--text-secondary)">—</span>
                            @endif
                        </td>
                        <td style="color:var(--text-secondary);font-size:var(--text-sm)">
                            {{ \Carbon\Carbon::parse($person->created_at)->format('Y-m-d') }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="empty-state">{{ __('ui.no_people', [], null, 'No people found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <div class="pagination__info">
            {{ __('ui.total', [], null, 'Total') }}: {{ $people->total() }}
        </div>
        {{ $people->links() }}
    </div>
</div>

@include('livewire.admin._partials.page-styles')

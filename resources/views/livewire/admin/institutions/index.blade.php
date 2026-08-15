@php /** @var \App\Livewire\Admin\Institutions\InstitutionIndex $this */ @endphp

<div>
    <div class="page-header">
        <h1 class="page-title">{{ __('ui.institutions', [], null, 'Institutions') }}</h1>
    </div>

    {{-- Filters --}}
    <div class="filters-bar">
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            class="form-control"
            placeholder="{{ __('ui.search_institutions', [], null, 'Search by name or code…') }}"
            style="max-inline-size:320px"
        >

        <select wire:model.live="typeFilter" class="form-control form-select" style="max-inline-size:200px">
            <option value="">{{ __('ui.all_types', [], null, 'All types') }}</option>
            @foreach($institutionTypes as $type)
                <option value="{{ $type->id }}">{{ $type->name_ar ?: $type->name_en }}</option>
            @endforeach
        </select>

        <select wire:model.live="statusFilter" class="form-control form-select" style="max-inline-size:150px">
            <option value="all">{{ __('ui.all', [], null, 'All') }}</option>
            <option value="active">{{ __('ui.active', [], null, 'Active') }}</option>
            <option value="inactive">{{ __('ui.inactive', [], null, 'Inactive') }}</option>
        </select>
    </div>

    @if($institutions->isEmpty())
        <div class="empty-state-block">
            <p>{{ __('ui.no_institutions', [], null, 'No institutions found.') }}</p>
        </div>
    @else
        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('ui.name', [], null, 'Name') }}</th>
                        <th>{{ __('ui.code', [], null, 'Code') }}</th>
                        <th>{{ __('ui.type', [], null, 'Type') }}</th>
                        <th>{{ __('ui.status', [], null, 'Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($institutions as $institution)
                        <tr>
                            <td>
                                <div style="font-weight:600">{{ $institution->name_ar }}</div>
                                @if($institution->name_en)
                                    <div style="font-size:var(--text-sm);color:var(--text-secondary)">{{ $institution->name_en }}</div>
                                @endif
                            </td>
                            <td><code style="font-size:var(--text-sm)">{{ $institution->code }}</code></td>
                            <td>{{ optional($institution->institutionType)->name_ar }}</td>
                            <td>
                                <span class="badge badge--{{ $institution->is_active ? 'active' : 'archived' }}">
                                    {{ $institution->is_active ? __('ui.active', [], null, 'Active') : __('ui.inactive', [], null, 'Inactive') }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <div class="pagination__info">
                {{ __('ui.showing_results', ['from' => $institutions->firstItem(), 'to' => $institutions->lastItem(), 'total' => $institutions->total()], null, "Showing {$institutions->firstItem()}–{$institutions->lastItem()} of {$institutions->total()}") }}
            </div>
            {{ $institutions->links() }}
        </div>
    @endif
</div>

@include('livewire.admin._partials.page-styles')

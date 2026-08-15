@php /** @var \App\Livewire\Admin\Students\GuardianIndex $this */ @endphp

<div>
    <div class="page-header">
        <h1 class="page-title">{{ __('ui.guardians', [], null, 'Guardians') }}</h1>
    </div>

    <div class="filters-bar">
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            class="form-control"
            placeholder="{{ __('ui.search_guardians', [], null, 'Search by name or code…') }}"
            style="max-inline-size:320px"
        >
    </div>

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('ui.code', [], null, 'Code') }}</th>
                    <th>{{ __('ui.name', [], null, 'Name') }}</th>
                    <th>{{ __('ui.status', [], null, 'Status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($guardians as $guardian)
                    <tr>
                        <td><code>{{ $guardian->guardian_code }}</code></td>
                        <td>
                            <span dir="rtl">{{ $guardian->full_name_ar }}</span>
                            @if($guardian->full_name_en)
                                <div style="font-size:var(--text-sm);color:var(--text-secondary)">{{ $guardian->full_name_en }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge--{{ $guardian->lifecycle_status === 'active' ? 'active' : 'archived' }}">
                                {{ $guardian->lifecycle_status }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.guardians.detail', ['guardianId' => $guardian->id]) }}" class="btn btn--outline btn--sm" wire:navigate>
                                {{ __('ui.view', [], null, 'View') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty-state">{{ __('ui.no_guardians', [], null, 'No guardians found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">
        <div class="pagination__info">{{ $guardians->total() }} {{ __('ui.guardians', [], null, 'guardians') }}</div>
        {{ $guardians->links() }}
    </div>
</div>

@include('livewire.admin._partials.page-styles')

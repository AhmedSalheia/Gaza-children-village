@php /** @var \App\Livewire\Admin\Audit\CivilRegistryAudit $this */ @endphp

{{--
 Source: audit_events table (source_module='CivilRegistry', action='civil_registry.lookup')
 Columns used: actor_type, actor_account_id, institution_id, recorded_at, metadata (JSON)
 metadata.found (bool) indicates whether the registry returned a match.
 No national IDs are stored or displayed.
--}}

<div>
    <div class="page-header">
        <h1 class="page-title">{{ __('ui.civil_registry_audit', [], null, 'Civil Registry Lookup Audit') }}</h1>
    </div>

    <div class="alert alert--info" style="margin-block-end:var(--space-4)">
        {{ __('ui.audit_notice', [], null, 'This log records civil registry lookups. No national IDs are stored or displayed — only actor, institution, and lookup result metadata.') }}
    </div>

    <div class="filters-bar">
        <select wire:model.live="institutionFilter" class="form-control form-select" style="max-inline-size:240px">
            <option value="0">{{ __('ui.all_institutions', [], null, 'All institutions') }}</option>
            @foreach($institutions as $inst)
                <option value="{{ $inst->id }}">{{ $inst->name_ar }}</option>
            @endforeach
        </select>

        <select wire:model.live="actorTypeFilter" class="form-control form-select" style="max-inline-size:180px">
            <option value="">{{ __('ui.all_actor_types', [], null, 'All actor types') }}</option>
            <option value="administrative">administrative</option>
            <option value="staff">staff</option>
        </select>

        <input type="date" wire:model.live="dateFrom" class="form-control" style="max-inline-size:160px">
        <span style="color:var(--text-secondary)">→</span>
        <input type="date" wire:model.live="dateTo" class="form-control" style="max-inline-size:160px">
    </div>

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('ui.timestamp', [], null, 'Timestamp') }}</th>
                    <th>{{ __('ui.actor_type', [], null, 'Actor Type') }}</th>
                    <th>{{ __('ui.actor_id', [], null, 'Actor ID') }}</th>
                    <th>{{ __('ui.institution', [], null, 'Institution') }}</th>
                    <th>{{ __('ui.result', [], null, 'Result') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($auditEntries as $entry)
                    @php
                        $meta   = is_string($entry->metadata) ? json_decode($entry->metadata, true) : (array) $entry->metadata;
                        $found  = $meta['found'] ?? null;
                    @endphp
                    <tr>
                        <td style="font-size:var(--text-sm)">
                            {{ \Carbon\Carbon::parse($entry->recorded_at)->format('Y-m-d H:i') }}
                        </td>
                        <td>
                            <span class="badge badge--draft">{{ $entry->actor_type }}</span>
                        </td>
                        <td style="color:var(--text-secondary);font-size:var(--text-sm)">
                            @if($entry->actor_account_id)#{{ $entry->actor_account_id }}@else—@endif
                        </td>
                        <td>{{ $entry->institution_name ?? '—' }}</td>
                        <td>
                            @if($found === true)
                                <span class="badge badge--active">{{ __('ui.matched', [], null, 'Matched') }}</span>
                            @elseif($found === false)
                                <span class="badge badge--pending">{{ __('ui.not_found', [], null, 'Not found') }}</span>
                            @else
                                <span class="badge badge--draft">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-state">
                            {{ __('ui.no_audit_entries', [], null, 'No audit entries found for the selected filters.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $auditEntries->links() }}
</div>

@include('livewire.admin._partials.page-styles')

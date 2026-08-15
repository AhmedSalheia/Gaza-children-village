@php /** @var \App\Livewire\Admin\Students\RelationshipIndex $this */ @endphp

{{--
 Schema note: guardian_student_relationships
   verification_status  string(32) default 'unverified'
   portal_eligible      boolean    default false
   ends_on              date nullable  (null = not ended / active)
 There is no relationship_status column; status is derived from ends_on.
--}}

<div>
    <div class="page-header">
        <h1 class="page-title">{{ __('ui.relationships', [], null, 'Guardian Relationships') }}</h1>
    </div>

    @include('livewire.admin._partials.flash-message')

    <div class="filters-bar">
        <select wire:model.live="statusFilter" class="form-control form-select" style="max-inline-size:180px">
            <option value="">{{ __('ui.all', [], null, 'All') }}</option>
            <option value="active">{{ __('ui.active', [], null, 'Active') }}</option>
            <option value="ended">{{ __('ui.ended', [], null, 'Ended') }}</option>
        </select>
    </div>

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('ui.guardian', [], null, 'Guardian') }}</th>
                    <th>{{ __('ui.student', [], null, 'Student') }}</th>
                    <th>{{ __('ui.type', [], null, 'Type') }}</th>
                    <th>{{ __('ui.status', [], null, 'Status') }}</th>
                    <th>{{ __('ui.verified', [], null, 'Verified') }}</th>
                    <th>{{ __('ui.portal', [], null, 'Portal') }}</th>
                    <th>{{ __('ui.actions', [], null, 'Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($relationships as $rel)
                    @php
                        $today      = now()->toDateString();
                        $isActive   = $rel->ends_on === null || $rel->ends_on >= $today;
                        $isVerified = $rel->verification_status === 'verified';
                        $isPortal   = (bool) $rel->portal_eligible;
                    @endphp
                    <tr>
                        <td>
                            <span dir="rtl">{{ $rel->guardian_name }}</span>
                            <code style="font-size:var(--text-xs)">{{ $rel->guardian_code }}</code>
                        </td>
                        <td>
                            <span dir="rtl">{{ $rel->student_name }}</span>
                            <code style="font-size:var(--text-xs)">{{ $rel->student_code }}</code>
                        </td>
                        <td>{{ $rel->relationship_type }}</td>
                        <td>
                            <span class="badge badge--{{ $isActive ? 'active' : 'closed' }}">
                                {{ $isActive ? __('ui.active', [], null, 'Active') : __('ui.ended', [], null, 'Ended') }}
                            </span>
                        </td>
                        <td>
                            @if($isVerified)
                                <span class="badge badge--open">✓ {{ $rel->verification_status }}</span>
                            @else
                                <span style="color:var(--text-secondary)">—</span>
                            @endif
                        </td>
                        <td>
                            @if($isPortal)
                                <span class="badge badge--pending">✓</span>
                            @else
                                <span style="color:var(--text-secondary)">—</span>
                            @endif
                        </td>
                        <td style="display:flex;gap:var(--space-1);flex-wrap:wrap">
                            @if(! $isVerified && $isActive)
                                <button wire:click="verify({{ $rel->id }})" class="btn btn--outline btn--sm">
                                    {{ __('ui.verify', [], null, 'Verify') }}
                                </button>
                            @endif
                            @if($isVerified && ! $isPortal && $isActive)
                                <button wire:click="activate({{ $rel->id }})" class="btn btn--primary btn--sm">
                                    {{ __('ui.activate_portal', [], null, 'Activate Portal') }}
                                </button>
                            @endif
                            @if($isActive)
                                <button
                                    wire:click="end({{ $rel->id }})"
                                    class="btn btn--danger btn--sm"
                                    onclick="return confirm('{{ __('ui.confirm_end', [], null, 'End this relationship?') }}')"
                                >
                                    {{ __('ui.end', [], null, 'End') }}
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty-state">{{ __('ui.no_relationships', [], null, 'No relationships found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $relationships->links() }}
</div>

@include('livewire.admin._partials.page-styles')

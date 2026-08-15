@php /** @var \App\Livewire\Staff\Students\GuardianRelationships $this */ @endphp

<div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-block-end:var(--space-6);flex-wrap:wrap;gap:var(--space-3)">
        <div>
            <a href="{{ route('staff.students.detail', ['studentProfileId' => $studentProfileId]) }}" class="link" wire:navigate style="font-size:var(--text-sm)">
                ← {{ __('ui.back_to_profile', [], null, 'Back to Profile') }}
            </a>
            <h1 style="font-size:var(--text-2xl);font-weight:700;margin:var(--space-1) 0 0">
                {{ __('ui.guardian_relationships', [], null, 'Guardian Relationships') }}
                <span style="font-size:var(--text-base);font-weight:400;color:var(--text-secondary)">— {{ $student?->full_name_ar }}</span>
            </h1>
        </div>
        @if($canManage)
        <button wire:click="$set('showAddForm', true)" class="btn btn--primary btn--sm">
            + {{ __('ui.add_relationship', [], null, 'Add Relationship') }}
        </button>
        @endif
    </div>

    @error('addRelationship') <div class="alert alert--danger">{{ $message }}</div> @enderror
    @error('verify') <div class="alert alert--danger">{{ $message }}</div> @enderror
    @error('endRelationship') <div class="alert alert--danger">{{ $message }}</div> @enderror

    {{-- Add form --}}
    @if($showAddForm)
    <div class="card" style="margin-block-end:var(--space-6)">
        <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">{{ __('ui.add_relationship', [], null, 'Add Relationship') }}</h2>
        <div class="form-group">
            <label class="form-label form-label--required">{{ __('ui.guardian_profile_id', [], null, 'Guardian Profile ID') }}</label>
            <input type="number" wire:model="guardianProfileId" class="form-control @error('guardianProfileId') form-control--error @enderror" min="1">
            <span class="form-hint">{{ __('ui.guardian_profile_id_hint', [], null, 'Enter the guardian profile ID number.') }}</span>
            @error('guardianProfileId') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label class="form-label form-label--required">{{ __('ui.relationship_type', [], null, 'Relationship Type') }}</label>
            <select wire:model="relationshipType" class="form-control form-select @error('relationshipType') form-control--error @enderror">
                <option value="">— {{ __('ui.select', [], null, 'Select') }} —</option>
                @foreach($relationshipTypes as $type)
                <option value="{{ $type->value }}">{{ $type->value }}</option>
                @endforeach
            </select>
            @error('relationshipType') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label class="form-label">{{ __('ui.legal_authority', [], null, 'Legal Authority') }}</label>
            <select wire:model="legalAuthority" class="form-control form-select">
                @foreach($legalAuthorityOptions as $opt)
                <option value="{{ $opt->value }}">{{ $opt->value }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label style="display:flex;align-items:center;gap:var(--space-2)">
                <input type="checkbox" wire:model="isEmergencyContact"> {{ __('ui.emergency_contact', [], null, 'Emergency Contact') }}
            </label>
        </div>
        <div style="display:flex;gap:var(--space-3)">
            <button wire:click="addRelationship" class="btn btn--primary">{{ __('ui.save', [], null, 'Save') }}</button>
            <button wire:click="$set('showAddForm', false)" class="btn btn--outline">{{ __('ui.cancel', [], null, 'Cancel') }}</button>
        </div>
    </div>
    @endif

    {{-- End confirmation --}}
    @if($endingRelationshipId)
    <div class="alert alert--warning" style="margin-block-end:var(--space-4)">
        <strong>{{ __('ui.confirm_end_relationship', [], null, 'End this relationship?') }}</strong>
        <div class="form-group" style="margin-block-start:var(--space-3)">
            <label class="form-label">{{ __('ui.reason', [], null, 'Reason (optional)') }}</label>
            <input type="text" wire:model="endReason" class="form-control">
        </div>
        <div style="display:flex;gap:var(--space-3);margin-block-start:var(--space-3)">
            <button wire:click="endRelationship" class="btn btn--danger btn--sm">{{ __('ui.confirm_end', [], null, 'Yes, End') }}</button>
            <button wire:click="cancelEnd" class="btn btn--outline btn--sm">{{ __('ui.cancel', [], null, 'Cancel') }}</button>
        </div>
    </div>
    @endif

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('ui.guardian', [], null, 'Guardian') }}</th>
                <th>{{ __('ui.type', [], null, 'Type') }}</th>
                <th>{{ __('ui.verification', [], null, 'Verified') }}</th>
                <th>{{ __('ui.portal', [], null, 'Portal') }}</th>
                <th>{{ __('ui.legal_authority', [], null, 'Legal Auth.') }}</th>
                <th>{{ __('ui.ends_on', [], null, 'Ends On') }}</th>
                <th></th>
            </tr></thead>
            <tbody>
                @forelse($relationships as $rel)
                <tr>
                    <td>{{ $rel->guardian_name }}</td>
                    <td>{{ $rel->relationship_type }}</td>
                    <td><span class="badge badge--{{ $rel->verification_status === 'verified' ? 'active' : 'pending' }}">{{ $rel->verification_status }}</span></td>
                    <td>{{ $rel->portal_eligible ? '✓' : '—' }}</td>
                    <td>{{ $rel->legal_authority }}</td>
                    <td>{{ $rel->ends_on ?? __('ui.active', [], null, 'Active') }}</td>
                    <td style="white-space:nowrap">
                        @if($canVerify && $rel->verification_status !== 'verified' && !$rel->ends_on)
                        <button wire:click="verify({{ $rel->id }})" class="btn btn--outline btn--sm">{{ __('ui.verify', [], null, 'Verify') }}</button>
                        @endif
                        @if($canManage && !$rel->ends_on)
                        <button wire:click="confirmEnd({{ $rel->id }})" class="btn btn--ghost btn--sm">{{ __('ui.end', [], null, 'End') }}</button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;color:var(--text-secondary);padding:var(--space-8);font-style:italic">{{ __('ui.no_relationships', [], null, 'No relationships found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<style>.card{background:var(--surface-card);border:1px solid var(--card-border);border-radius:var(--card-radius);padding:var(--card-padding);box-shadow:var(--card-shadow)}</style>

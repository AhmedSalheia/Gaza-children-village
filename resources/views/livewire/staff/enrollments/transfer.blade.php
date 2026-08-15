@php /** @var \App\Livewire\Staff\Enrollments\TransferStudent $this */ @endphp

<div>
    <div style="margin-block-end:var(--space-6)">
        <a href="{{ route('staff.students.detail', ['studentProfileId' => $studentProfileId]) }}" class="link" wire:navigate style="font-size:var(--text-sm)">
            ← {{ __('ui.back_to_profile', [], null, 'Back to Profile') }}
        </a>
        <h1 style="font-size:var(--text-2xl);font-weight:700;margin:var(--space-1) 0 0">{{ __('ui.transfer_student', [], null, 'Transfer Student') }}</h1>
    </div>

    @error('transfer') <div class="alert alert--danger">{{ $message }}</div> @enderror

    {{-- Current enrollment context --}}
    @if($currentEnrollment)
    <div class="card" style="margin-block-end:var(--space-6);background:var(--neutral-50)">
        <div style="font-size:var(--text-sm);color:var(--text-secondary)">{{ __('ui.current_enrollment', [], null, 'Current Enrollment') }}</div>
        <div style="font-weight:600">{{ $student?->full_name_ar }} — {{ $currentEnrollment->class_group_name }} ({{ $currentEnrollment->level_name }})</div>
        <div style="font-size:var(--text-sm);color:var(--text-secondary)">{{ $currentEnrollment->semester_name }} · {{ $currentEnrollment->enrollment_status }}</div>
    </div>
    @endif

    {{-- Step 1: Select target --}}
    @if($step === 1)
    <div class="card">
        <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">{{ __('ui.select_target_class', [], null, 'Select Target Class Group') }}</h2>
        <div class="form-group">
            <label class="form-label form-label--required">{{ __('ui.target_class_group', [], null, 'Target Class Group') }}</label>
            <select wire:model="targetClassGroupId" class="form-control form-select @error('targetClassGroupId') form-control--error @enderror">
                <option value="0">— {{ __('ui.select', [], null, 'Select') }} —</option>
                @foreach($availableGroups as $g)
                <option value="{{ $g->id }}">{{ $g->institution_name }} / {{ $g->semester_name }} / {{ $g->level_name }} — {{ $g->name_ar }}</option>
                @endforeach
            </select>
            @error('targetClassGroupId') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        <button wire:click="selectTarget" class="btn btn--primary">{{ __('ui.next', [], null, 'Next') }}</button>
    </div>
    @endif

    {{-- Step 2: Reason and confirm --}}
    @if($step === 2)
    <div class="card">
        <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">{{ __('ui.transfer_details', [], null, 'Transfer Details') }}</h2>
        <div class="form-group">
            <label class="form-label form-label--required">{{ __('ui.enrollment_date', [], null, 'Enrollment Date') }}</label>
            <input type="date" wire:model="enrolledOn" class="form-control @error('enrolledOn') form-control--error @enderror">
            @error('enrolledOn') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label class="form-label">{{ __('ui.transfer_reason', [], null, 'Transfer Reason') }}</label>
            <textarea wire:model="transferNotes" class="form-control" rows="3"
                placeholder="{{ __('ui.transfer_notes_placeholder', [], null, 'Reason for transfer (optional)…') }}"></textarea>
        </div>
        <div class="form-group">
            <label style="display:flex;align-items:center;gap:var(--space-2)">
                <input type="checkbox" wire:model="capacityOverride">
                {{ __('ui.override_capacity', [], null, 'Override capacity limit') }}
            </label>
        </div>
        @if($capacityOverride)
        <div class="form-group">
            <label class="form-label form-label--required">{{ __('ui.override_reason', [], null, 'Capacity Override Reason') }}</label>
            <input type="text" wire:model="capacityOverrideReason" class="form-control">
        </div>
        @endif
        <div style="display:flex;gap:var(--space-3)">
            <button wire:click="confirmTransfer" class="btn btn--primary">{{ __('ui.confirm_transfer', [], null, 'Confirm Transfer') }}</button>
            <button wire:click="back" class="btn btn--outline">{{ __('ui.back', [], null, 'Back') }}</button>
        </div>
    </div>
    @endif
</div>

<style>.card{background:var(--surface-card);border:1px solid var(--card-border);border-radius:var(--card-radius);padding:var(--card-padding);box-shadow:var(--card-shadow)}</style>

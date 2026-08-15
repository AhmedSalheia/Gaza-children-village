@php /** @var \App\Livewire\Staff\Students\AddStudent $this */ @endphp

<div>
    <div style="margin-block-end:var(--space-6)">
        <a href="{{ route('staff.students.index') }}" class="link" wire:navigate style="font-size:var(--text-sm)">
            ← {{ __('ui.back_to_students', [], null, 'Back to Students') }}
        </a>
        <h1 style="font-size:var(--text-2xl);font-weight:700;color:var(--text-primary);margin:var(--space-1) 0 0">
            {{ __('ui.add_student', [], null, 'Add Student') }}
        </h1>
    </div>

    {{-- Step indicators --}}
    <div style="display:flex;gap:var(--space-4);margin-block-end:var(--space-8)">
        @foreach([1 => __('ui.national_id', [], null, 'National ID'), 2 => __('ui.review_data', [], null, 'Review Data'), 3 => __('ui.confirm', [], null, 'Confirm')] as $n => $label)
        <div style="display:flex;align-items:center;gap:var(--space-2)">
            <div style="width:2rem;height:2rem;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:var(--text-sm);background:{{ $step >= $n ? 'var(--interactive-primary)' : 'var(--neutral-200)' }};color:{{ $step >= $n ? 'white' : 'var(--text-secondary)' }}">{{ $n }}</div>
            <span style="font-size:var(--text-sm);color:{{ $step === $n ? 'var(--text-primary)' : 'var(--text-secondary)' }};font-weight:{{ $step === $n ? '600' : '400' }}">{{ $label }}</span>
        </div>
        @endforeach
    </div>

    <div style="max-inline-size:600px">

        {{-- Step 1: National ID --}}
        @if($step === 1)
        <div class="card">
            <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">{{ __('ui.enter_national_id', [], null, 'Enter National ID') }}</h2>

            @if($canLookup)
            <div class="form-group">
                <label class="form-label form-label--required">{{ __('ui.national_id', [], null, 'National ID') }}</label>
                <input type="text" wire:model="nationalId" class="form-control @error('nationalId') form-control--error @enderror"
                    placeholder="{{ __('ui.national_id_placeholder', [], null, 'Enter national ID number') }}">
                @error('nationalId') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div style="display:flex;gap:var(--space-3)">
                <button wire:click="lookup" class="btn btn--primary">{{ __('ui.lookup', [], null, 'Lookup & Autofill') }}</button>
                <button wire:click="skipLookup" class="btn btn--outline">{{ __('ui.enter_manually', [], null, 'Enter Manually') }}</button>
            </div>
            @else
            <p style="color:var(--text-secondary);margin-block-end:var(--space-4)">{{ __('ui.no_lookup_permission', [], null, 'Civil registry lookup not available for your role.') }}</p>
            <button wire:click="skipLookup" class="btn btn--primary">{{ __('ui.continue', [], null, 'Continue') }}</button>
            @endif
        </div>
        @endif

        {{-- Step 2: Review data --}}
        @if($step === 2)
        <div class="card">
            <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">{{ __('ui.review_student_data', [], null, 'Review Student Data') }}</h2>

            @if($lookupFound)
            <div class="alert alert--success">{{ __('ui.autofill_applied', [], null, 'Civil registry data applied. Please review and correct if needed.') }}</div>
            @elseif($lookupError)
            <div class="alert alert--warning">{{ $lookupError }}</div>
            @endif

            @error('save') <div class="alert alert--danger">{{ $message }}</div> @enderror

            <div class="form-group">
                <label class="form-label form-label--required">{{ __('ui.full_name_ar', [], null, 'Full Name (Arabic)') }}</label>
                <input type="text" wire:model="fullNameAr" class="form-control @error('fullNameAr') form-control--error @enderror" dir="rtl">
                @error('fullNameAr') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('ui.full_name_en', [], null, 'Full Name (English)') }}</label>
                <input type="text" wire:model="fullNameEn" class="form-control" dir="ltr">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('ui.birth_date', [], null, 'Birth Date') }}</label>
                <input type="date" wire:model="birthDate" class="form-control @error('birthDate') form-control--error @enderror">
                @error('birthDate') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('ui.birth_date_precision', [], null, 'Date Precision') }}</label>
                <select wire:model="birthDatePrecision" class="form-control form-select">
                    <option value="full">{{ __('ui.full_date', [], null, 'Full Date') }}</option>
                    <option value="month">{{ __('ui.month_year', [], null, 'Month + Year') }}</option>
                    <option value="year">{{ __('ui.year_only', [], null, 'Year Only') }}</option>
                </select>
            </div>
            <div style="display:flex;gap:var(--space-3)">
                <button wire:click="goToConfirm" class="btn btn--primary">{{ __('ui.continue', [], null, 'Continue') }}</button>
                <button wire:click="back" class="btn btn--outline">{{ __('ui.back', [], null, 'Back') }}</button>
            </div>
        </div>
        @endif

        {{-- Step 3: Confirm --}}
        @if($step === 3)
        <div class="card">
            <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">{{ __('ui.confirm_and_save', [], null, 'Confirm & Save') }}</h2>

            @error('save') <div class="alert alert--danger">{{ $message }}</div> @enderror

            <dl style="display:grid;grid-template-columns:auto 1fr;gap:var(--space-2) var(--space-4);margin-block-end:var(--space-6)">
                <dt style="color:var(--text-secondary);font-size:var(--text-sm)">{{ __('ui.full_name_ar', [], null, 'Name (Arabic)') }}</dt>
                <dd style="font-weight:500">{{ $fullNameAr }}</dd>
                @if($fullNameEn)
                <dt style="color:var(--text-secondary);font-size:var(--text-sm)">{{ __('ui.full_name_en', [], null, 'Name (English)') }}</dt>
                <dd>{{ $fullNameEn }}</dd>
                @endif
                @if($birthDate)
                <dt style="color:var(--text-secondary);font-size:var(--text-sm)">{{ __('ui.birth_date', [], null, 'Birth Date') }}</dt>
                <dd>{{ $birthDate }}</dd>
                @endif
            </dl>

            <div style="display:flex;gap:var(--space-3)">
                <button wire:click="save" class="btn btn--primary">{{ __('ui.save_student', [], null, 'Save Student') }}</button>
                <button wire:click="back" class="btn btn--outline">{{ __('ui.back', [], null, 'Back') }}</button>
            </div>
        </div>
        @endif
    </div>
</div>

<style>.card{background:var(--surface-card);border:1px solid var(--card-border);border-radius:var(--card-radius);padding:var(--card-padding);box-shadow:var(--card-shadow)}</style>

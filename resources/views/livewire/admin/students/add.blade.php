@php /** @var \App\Livewire\Admin\Students\AddStudent $this */ @endphp

<div>
    <div class="page-header">
        <div style="display:flex;align-items:center;gap:var(--space-3)">
            <a href="{{ route('admin.students.index') }}" class="btn btn--outline btn--sm" wire:navigate>← {{ __('ui.back', [], null, 'Back') }}</a>
            <h1 class="page-title" style="margin:0">{{ __('ui.add_student', [], null, 'Add Student') }}</h1>
        </div>
    </div>

    {{-- Step indicator --}}
    <div style="display:flex;gap:var(--space-2);margin-block-end:var(--space-6)">
        @foreach([1 => 'Identity', 2 => 'Civil Registry', 3 => 'Confirm', 4 => 'Done'] as $n => $label)
            <div style="display:flex;align-items:center;gap:var(--space-2)">
                <div style="
                    width:2rem;height:2rem;border-radius:50%;display:flex;align-items:center;justify-content:center;
                    font-weight:700;font-size:var(--text-sm);
                    background:{{ $step >= $n ? 'var(--interactive-primary)' : 'var(--neutral-200)' }};
                    color:{{ $step >= $n ? 'var(--neutral-0)' : 'var(--text-secondary)' }};
                ">{{ $n }}</div>
                <span style="font-size:var(--text-sm);{{ $step === $n ? 'font-weight:600' : 'color:var(--text-secondary)' }}">{{ $label }}</span>
                @if($n < 4)
                    <span style="color:var(--border-default)">→</span>
                @endif
            </div>
        @endforeach
    </div>

    @if($flashMessage !== '')
        <div class="alert alert--{{ $flashType === 'success' ? 'success' : 'danger' }}">{{ $flashMessage }}</div>
    @endif

    {{-- Step 1: Identity --}}
    @if($step === 1)
        <div class="card" style="max-inline-size:600px">
            <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">{{ __('ui.student_identity', [], null, 'Student Identity') }}</h2>

            <div class="form-group">
                <label class="form-label form-label--required">{{ __('ui.name_ar', [], null, 'Arabic Full Name') }}</label>
                <input wire:model="fullNameAr" type="text" class="form-control @error('fullNameAr') form-control--error @enderror" dir="rtl" placeholder="الاسم الكامل">
                @error('fullNameAr')<span class="form-error">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('ui.name_en', [], null, 'English Full Name') }}</label>
                <input wire:model="fullNameEn" type="text" class="form-control" placeholder="Full name in English">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3)">
                <div class="form-group">
                    <label class="form-label">{{ __('ui.birth_date', [], null, 'Birth Date') }}</label>
                    <input wire:model="birthDate" type="date" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">{{ __('ui.precision', [], null, 'Precision') }}</label>
                    <select wire:model="birthDatePrecision" class="form-control form-select">
                        <option value="exact">{{ __('ui.exact', [], null, 'Exact') }}</option>
                        <option value="month">{{ __('ui.month', [], null, 'Month only') }}</option>
                        <option value="year">{{ __('ui.year', [], null, 'Year only') }}</option>
                        <option value="unknown">{{ __('ui.unknown', [], null, 'Unknown') }}</option>
                    </select>
                </div>
            </div>

            <button wire:click="nextStep" class="btn btn--primary">{{ __('ui.next', [], null, 'Next') }} →</button>
        </div>
    @endif

    {{-- Step 2: Civil Registry --}}
    @if($step === 2)
        <div class="card" style="max-inline-size:600px">
            <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-2)">{{ __('ui.civil_registry_lookup', [], null, 'Civil Registry Lookup') }}</h2>
            <p style="color:var(--text-secondary);font-size:var(--text-sm);margin-block-end:var(--space-4)">
                {{ __('ui.cr_lookup_description', [], null, 'Optionally look up the civil registry to autofill identity fields. The national ID is used only for the lookup and is not stored.') }}
            </p>

            @if($civilLookupError !== '')
                <div class="alert alert--danger">{{ $civilLookupError }}</div>
            @endif

            @if(!$civilLookupDone)
                <div class="form-group">
                    <label class="form-label">{{ __('ui.national_id', [], null, 'National ID') }}</label>
                    <div style="display:flex;gap:var(--space-2)">
                        <input wire:model="nationalIdRaw" type="text" class="form-control" placeholder="●●●●●●●●●" autocomplete="off" style="font-family:monospace">
                        <button wire:click="lookupCivilRegistry" wire:loading.attr="disabled" class="btn btn--secondary">
                            <span wire:loading wire:target="lookupCivilRegistry">{{ __('ui.searching', [], null, 'Searching…') }}</span>
                            <span wire:loading.remove wire:target="lookupCivilRegistry">{{ __('ui.lookup', [], null, 'Look up') }}</span>
                        </button>
                    </div>
                    <span class="form-hint">{{ __('ui.national_id_hint', [], null, 'The ID is sent to the registry and immediately discarded from this form.') }}</span>
                </div>
            @endif

            @if($civilLookupDone && $civilMatch !== null)
                @if($civilMatch['found'])
                    <div class="alert alert--success" style="margin-block-end:var(--space-4)">
                        ✓ {{ __('ui.cr_match_found', [], null, 'Match found in civil registry.') }}
                        @if($civilMatch['is_deceased'] ?? false)
                            <br><strong>⚠ {{ __('ui.cr_deceased', [], null, 'Warning: This person is recorded as deceased in the registry.') }}</strong>
                        @endif
                    </div>

                    @if(!$applyAutofill)
                        <div style="background:var(--teal-50);border:1px solid var(--teal-200);border-radius:var(--radius-sm);padding:var(--space-4);margin-block-end:var(--space-4)">
                            <p style="font-size:var(--text-sm);margin:0 0 var(--space-2)">{{ __('ui.autofill_proposal', [], null, 'Autofill proposal (review before accepting):') }}</p>
                            @if($civilMatch['proposed_name_ar'] ?? null)
                                <div><strong>{{ __('ui.name', [], null, 'Name') }}:</strong> <span dir="rtl">{{ $civilMatch['proposed_name_ar'] }}</span></div>
                            @endif
                            @if($civilMatch['proposed_birth_date'] ?? null)
                                <div><strong>{{ __('ui.birth_date', [], null, 'Birth') }}:</strong> {{ $civilMatch['proposed_birth_date'] }}</div>
                            @endif
                            <button wire:click="applyAutofillProposal" class="btn btn--primary btn--sm" style="margin-block-start:var(--space-2)">
                                {{ __('ui.apply_autofill', [], null, 'Apply to form') }}
                            </button>
                        </div>
                    @else
                        <div class="alert alert--info">✓ {{ __('ui.autofill_applied', [], null, 'Autofill applied. You can adjust values before creating the profile.') }}</div>
                    @endif
                @else
                    <div class="alert alert--warning">{{ __('ui.cr_no_match', [], null, 'No match found in civil registry. You can continue without autofill.') }}</div>
                @endif
            @endif

            <div style="display:flex;gap:var(--space-2);margin-block-start:var(--space-4)">
                <button wire:click="nextStep" class="btn btn--primary">{{ __('ui.continue', [], null, 'Continue') }} →</button>
                <button wire:click="$set('step', 1)" class="btn btn--outline">← {{ __('ui.back', [], null, 'Back') }}</button>
            </div>
        </div>
    @endif

    {{-- Step 3: Confirm --}}
    @if($step === 3)
        <div class="card" style="max-inline-size:600px">
            <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">{{ __('ui.confirm_and_create', [], null, 'Confirm & Create') }}</h2>

            <dl style="display:grid;grid-template-columns:auto 1fr;gap:var(--space-2) var(--space-4);margin-block-end:var(--space-6)">
                <dt style="color:var(--text-secondary)">{{ __('ui.name_ar', [], null, 'Arabic Name') }}</dt>
                <dd dir="rtl">{{ $fullNameAr }}</dd>
                <dt style="color:var(--text-secondary)">{{ __('ui.name_en', [], null, 'English Name') }}</dt>
                <dd>{{ $fullNameEn ?: '—' }}</dd>
                <dt style="color:var(--text-secondary)">{{ __('ui.birth_date', [], null, 'Birth Date') }}</dt>
                <dd>{{ $birthDate ?: '—' }} ({{ $birthDatePrecision }})</dd>
            </dl>

            <p style="font-size:var(--text-sm);color:var(--text-secondary);margin-block-end:var(--space-4)">
                {{ __('ui.create_student_note', [], null, 'This will create a new Person record and a draft StudentProfile. You can activate the profile and add enrollments afterward.') }}
            </p>

            <div style="display:flex;gap:var(--space-2)">
                <button wire:click="create" wire:loading.attr="disabled" class="btn btn--primary">
                    <span wire:loading wire:target="create">{{ __('ui.creating', [], null, 'Creating…') }}</span>
                    <span wire:loading.remove wire:target="create">{{ __('ui.create_profile', [], null, 'Create Profile') }}</span>
                </button>
                <button wire:click="$set('step', 2)" class="btn btn--outline">← {{ __('ui.back', [], null, 'Back') }}</button>
            </div>
        </div>
    @endif

    {{-- Step 4: Success --}}
    @if($step === 4)
        <div class="card" style="max-inline-size:500px;text-align:center">
            <div style="font-size:3rem;margin-block-end:var(--space-4)">✓</div>
            <h2 style="font-size:var(--text-xl);font-weight:600;margin-block-end:var(--space-2)">
                {{ __('ui.student_created', [], null, 'Student profile created!') }}
            </h2>
            <p style="color:var(--text-secondary);margin-block-end:var(--space-6)">
                {{ __('ui.student_created_note', [], null, 'The profile is in draft status. Open it to add enrollment and guardian relationships.') }}
            </p>
            <div style="display:flex;gap:var(--space-2);justify-content:center">
                <button wire:click="goToStudent" class="btn btn--primary">
                    {{ __('ui.view_student', [], null, 'View Student') }}
                </button>
                <a href="{{ route('admin.students.add') }}" class="btn btn--outline" wire:navigate>
                    {{ __('ui.add_another', [], null, 'Add Another') }}
                </a>
            </div>
        </div>
    @endif
</div>

@include('livewire.admin._partials.page-styles')

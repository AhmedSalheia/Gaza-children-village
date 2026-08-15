@php /** @var \App\Livewire\Admin\AcademicStructure\SubjectIndex $this */ @endphp

<div>
    <div class="page-header">
        <h1 class="page-title">{{ __('ui.subjects', [], null, 'Subjects') }}</h1>
        <button wire:click="$set('showForm', true)" class="btn btn--primary btn--sm">
            + {{ __('ui.add_subject', [], null, 'Add Subject') }}
        </button>
    </div>

    @include('livewire.admin._partials.flash-message')

    @if($showForm)
        <div class="card" style="margin-block-end:var(--space-4)">
            <h2 style="font-size:var(--text-base);font-weight:600;margin-block-end:var(--space-4)">
                {{ __('ui.new_subject', [], null, 'New Subject') }}
            </h2>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:var(--space-3)">
                <div class="form-group">
                    <label class="form-label form-label--required">{{ __('ui.code', [], null, 'Code') }}</label>
                    <input wire:model="code" type="text" class="form-control @error('code') form-control--error @enderror" placeholder="ARABIC">
                    @error('code')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label form-label--required">{{ __('ui.name_ar', [], null, 'Arabic Name') }}</label>
                    <input wire:model="nameAr" type="text" class="form-control @error('nameAr') form-control--error @enderror" dir="rtl">
                    @error('nameAr')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label form-label--required">{{ __('ui.name_en', [], null, 'English Name') }}</label>
                    <input wire:model="nameEn" type="text" class="form-control @error('nameEn') form-control--error @enderror">
                    @error('nameEn')<span class="form-error">{{ $message }}</span>@enderror
                </div>
            </div>
            <div style="display:flex;gap:var(--space-2)">
                <button wire:click="save" class="btn btn--primary btn--sm">{{ __('ui.save', [], null, 'Save') }}</button>
                <button wire:click="cancelForm" class="btn btn--outline btn--sm">{{ __('ui.cancel', [], null, 'Cancel') }}</button>
            </div>
        </div>
    @endif

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-6)">
        {{-- Subject catalogue --}}
        <div>
            <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-3)">{{ __('ui.catalogue', [], null, 'Catalogue') }}</h2>
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
                        @forelse($subjects as $subject)
                            <tr>
                                <td><code>{{ $subject->code }}</code></td>
                                <td dir="rtl">{{ $subject->name_ar }}</td>
                                <td>
                                    <span class="badge badge--{{ $subject->is_active ? 'active' : 'archived' }}">
                                        {{ $subject->is_active ? __('ui.active', [], null, 'Active') : __('ui.inactive', [], null, 'Inactive') }}
                                    </span>
                                </td>
                                <td>
                                    @if($subject->is_active)
                                        <button wire:click="toggle({{ $subject->id }}, false)" class="btn btn--outline btn--sm">
                                            {{ __('ui.deactivate', [], null, 'Deactivate') }}
                                        </button>
                                        @if($offeringInstSemId > 0)
                                            <button wire:click="addOffering({{ $subject->id }})" class="btn btn--secondary btn--sm">
                                                + {{ __('ui.offer', [], null, 'Offer') }}
                                            </button>
                                        @endif
                                    @else
                                        <button wire:click="toggle({{ $subject->id }}, true)" class="btn btn--primary btn--sm">
                                            {{ __('ui.activate', [], null, 'Activate') }}
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="empty-state">{{ __('ui.no_subjects', [], null, 'No subjects found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Subject offerings --}}
        <div>
            <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-3)">{{ __('ui.offerings', [], null, 'Offerings') }}</h2>
            <div class="filters-bar">
                <select wire:model.live="offeringInstSemId" class="form-control form-select">
                    <option value="0">{{ __('ui.select_semester', [], null, 'Select semester…') }}</option>
                    @foreach($openSemesters as $sem)
                        <option value="{{ $sem->id }}">{{ $sem->institution_name }} — {{ $sem->semester_name }}</option>
                    @endforeach
                </select>
            </div>
            @if($offeringInstSemId > 0)
                <div class="data-table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('ui.subject', [], null, 'Subject') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($offerings as $offering)
                                <tr>
                                    <td dir="rtl">{{ $offering->name_ar }}</td>
                                    <td>
                                        <button wire:click="removeOffering({{ $offering->id }})" class="btn btn--danger btn--sm">
                                            {{ __('ui.remove', [], null, 'Remove') }}
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="empty-state">{{ __('ui.no_offerings', [], null, 'No subjects offered in this semester.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

@include('livewire.admin._partials.page-styles')

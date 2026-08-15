@php /** @var \App\Livewire\Admin\AcademicStructure\AcademicLevelIndex $this */ @endphp

<div>
    <div class="page-header">
        <h1 class="page-title">{{ __('ui.academic_levels', [], null, 'Academic Levels') }}</h1>
        <button wire:click="$set('showForm', true)" class="btn btn--primary btn--sm">
            + {{ __('ui.add_level', [], null, 'Add Level') }}
        </button>
    </div>

    @include('livewire.admin._partials.flash-message')

    {{-- Create form --}}
    @if($showForm)
        <div class="card" style="margin-block-end:var(--space-4)">
            <h2 style="font-size:var(--text-base);font-weight:600;margin-block-end:var(--space-4)">
                {{ __('ui.new_level', [], null, 'New Academic Level') }}
            </h2>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:var(--space-3);align-items:end">
                <div class="form-group" style="margin:0">
                    <label class="form-label form-label--required">{{ __('ui.code', [], null, 'Code') }}</label>
                    <input wire:model="code" type="text" class="form-control @error('code') form-control--error @enderror" placeholder="KG1">
                    @error('code')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label form-label--required">{{ __('ui.name_ar', [], null, 'Arabic Name') }}</label>
                    <input wire:model="nameAr" type="text" class="form-control @error('nameAr') form-control--error @enderror" dir="rtl">
                    @error('nameAr')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label form-label--required">{{ __('ui.name_en', [], null, 'English Name') }}</label>
                    <input wire:model="nameEn" type="text" class="form-control @error('nameEn') form-control--error @enderror">
                    @error('nameEn')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">{{ __('ui.sequence', [], null, 'Order') }}</label>
                    <input wire:model="sequence" type="number" class="form-control" style="max-inline-size:80px">
                </div>
            </div>
            <div style="display:flex;gap:var(--space-2);margin-block-start:var(--space-3)">
                <button wire:click="save" class="btn btn--primary btn--sm">{{ __('ui.save', [], null, 'Save') }}</button>
                <button wire:click="cancelForm" class="btn btn--outline btn--sm">{{ __('ui.cancel', [], null, 'Cancel') }}</button>
            </div>
        </div>
    @endif

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('ui.code', [], null, 'Code') }}</th>
                    <th>{{ __('ui.name_ar', [], null, 'Arabic') }}</th>
                    <th>{{ __('ui.name_en', [], null, 'English') }}</th>
                    <th>{{ __('ui.status', [], null, 'Status') }}</th>
                    <th>{{ __('ui.actions', [], null, 'Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($levels as $level)
                    <tr>
                        <td style="color:var(--text-secondary)">{{ $level->sequence }}</td>
                        <td><code>{{ $level->code }}</code></td>
                        <td dir="rtl">{{ $level->name_ar }}</td>
                        <td>{{ $level->name_en }}</td>
                        <td>
                            <span class="badge badge--{{ $level->is_active ? 'active' : 'archived' }}">
                                {{ $level->is_active ? __('ui.active', [], null, 'Active') : __('ui.inactive', [], null, 'Inactive') }}
                            </span>
                        </td>
                        <td>
                            @if($level->is_active)
                                <button wire:click="toggle({{ $level->id }}, false)" class="btn btn--outline btn--sm">
                                    {{ __('ui.deactivate', [], null, 'Deactivate') }}
                                </button>
                            @else
                                <button wire:click="toggle({{ $level->id }}, true)" class="btn btn--primary btn--sm">
                                    {{ __('ui.activate', [], null, 'Activate') }}
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-state">{{ __('ui.no_levels', [], null, 'No academic levels found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@include('livewire.admin._partials.page-styles')

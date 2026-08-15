@php /** @var \App\Livewire\Admin\AcademicStructure\ClassroomIndex $this */ @endphp

<div>
    <div class="page-header">
        <h1 class="page-title">{{ __('ui.classrooms', [], null, 'Classrooms') }}</h1>
        <button wire:click="$set('showForm', true)" class="btn btn--primary btn--sm">
            + {{ __('ui.add_classroom', [], null, 'Add Classroom') }}
        </button>
    </div>

    @include('livewire.admin._partials.flash-message')

    <div class="filters-bar">
        <select wire:model.live="institutionId" class="form-control form-select" style="max-inline-size:280px">
            <option value="0">{{ __('ui.all_institutions', [], null, 'All institutions') }}</option>
            @foreach($institutions as $inst)
                <option value="{{ $inst->id }}">{{ $inst->name_ar }}</option>
            @endforeach
        </select>
    </div>

    @if($showForm)
        <div class="card" style="margin-block-end:var(--space-4)">
            <h2 style="font-size:var(--text-base);font-weight:600;margin-block-end:var(--space-4)">
                {{ __('ui.new_classroom', [], null, 'New Classroom') }}
            </h2>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:var(--space-3)">
                <div class="form-group">
                    <label class="form-label form-label--required">{{ __('ui.institution', [], null, 'Institution') }}</label>
                    <select wire:model="institutionId" class="form-control form-select @error('institutionId') form-control--error @enderror">
                        <option value="0">{{ __('ui.select', [], null, 'Select…') }}</option>
                        @foreach($institutions as $inst)
                            <option value="{{ $inst->id }}">{{ $inst->name_ar }}</option>
                        @endforeach
                    </select>
                    @error('institutionId')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label form-label--required">{{ __('ui.code', [], null, 'Code') }}</label>
                    <input wire:model="roomCode" type="text" class="form-control @error('roomCode') form-control--error @enderror">
                    @error('roomCode')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label form-label--required">{{ __('ui.name_ar', [], null, 'Arabic Name') }}</label>
                    <input wire:model="nameAr" type="text" class="form-control @error('nameAr') form-control--error @enderror" dir="rtl">
                    @error('nameAr')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('ui.name_en', [], null, 'English Name') }}</label>
                    <input wire:model="nameEn" type="text" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('ui.capacity', [], null, 'Capacity') }}</label>
                    <input wire:model="capacity" type="number" class="form-control" min="1">
                </div>
            </div>
            <div style="display:flex;gap:var(--space-2)">
                <button wire:click="save" class="btn btn--primary btn--sm">{{ __('ui.save', [], null, 'Save') }}</button>
                <button wire:click="cancelForm" class="btn btn--outline btn--sm">{{ __('ui.cancel', [], null, 'Cancel') }}</button>
            </div>
        </div>
    @endif

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('ui.code', [], null, 'Code') }}</th>
                    <th>{{ __('ui.name', [], null, 'Name') }}</th>
                    <th>{{ __('ui.institution', [], null, 'Institution') }}</th>
                    <th>{{ __('ui.capacity', [], null, 'Capacity') }}</th>
                    <th>{{ __('ui.status', [], null, 'Status') }}</th>
                    <th>{{ __('ui.actions', [], null, 'Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($classrooms as $classroom)
                    <tr>
                        <td><code>{{ $classroom->code }}</code></td>
                        <td dir="rtl">{{ $classroom->name_ar }}</td>
                        <td>{{ $classroom->institution_name_ar ?? '—' }}</td>
                        <td>{{ $classroom->capacity ?? '—' }}</td>
                        <td>
                            <span class="badge badge--{{ $classroom->is_active ? 'active' : 'archived' }}">
                                {{ $classroom->is_active ? __('ui.active', [], null, 'Active') : __('ui.inactive', [], null, 'Inactive') }}
                            </span>
                        </td>
                        <td>
                            @if($classroom->is_active)
                                <button wire:click="toggle({{ $classroom->id }}, false)" class="btn btn--outline btn--sm">
                                    {{ __('ui.deactivate', [], null, 'Deactivate') }}
                                </button>
                            @else
                                <button wire:click="toggle({{ $classroom->id }}, true)" class="btn btn--primary btn--sm">
                                    {{ __('ui.activate', [], null, 'Activate') }}
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-state">{{ __('ui.no_classrooms', [], null, 'No classrooms found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $classrooms->links() }}
</div>

@include('livewire.admin._partials.page-styles')

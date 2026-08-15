@php /** @var \App\Livewire\Admin\AcademicStructure\ClassGroupIndex $this */ @endphp

<div>
    <div class="page-header">
        <h1 class="page-title">{{ __('ui.class_groups', [], null, 'Class Groups') }}</h1>
        <button wire:click="$set('showForm', true)" class="btn btn--primary btn--sm">
            + {{ __('ui.add_class_group', [], null, 'Add Class Group') }}
        </button>
    </div>

    @include('livewire.admin._partials.flash-message')

    <div class="filters-bar">
        <select wire:model.live="instSemId" class="form-control form-select" style="max-inline-size:320px">
            <option value="0">{{ __('ui.all_semesters', [], null, 'All open semesters') }}</option>
            @foreach($openSemesters as $sem)
                <option value="{{ $sem->id }}">{{ $sem->institution_name }} — {{ $sem->semester_name }}</option>
            @endforeach
        </select>

        <select wire:model.live="lifecycleFilter" class="form-control form-select" style="max-inline-size:160px">
            <option value="">{{ __('ui.all_statuses', [], null, 'All statuses') }}</option>
            <option value="draft">Draft</option>
            <option value="active">Active</option>
            <option value="archived">Archived</option>
        </select>
    </div>

    @if($showForm && $instSemId > 0)
        <div class="card" style="margin-block-end:var(--space-4)">
            <h2 style="font-size:var(--text-base);font-weight:600;margin-block-end:var(--space-4)">
                {{ __('ui.new_class_group', [], null, 'New Class Group') }}
            </h2>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:var(--space-3)">
                <div class="form-group">
                    <label class="form-label form-label--required">{{ __('ui.code', [], null, 'Code') }}</label>
                    <input wire:model="formCode" type="text" class="form-control @error('formCode') form-control--error @enderror">
                    @error('formCode')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label form-label--required">{{ __('ui.name_ar', [], null, 'Arabic Name') }}</label>
                    <input wire:model="formNameAr" type="text" class="form-control @error('formNameAr') form-control--error @enderror" dir="rtl">
                    @error('formNameAr')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('ui.name_en', [], null, 'English Name') }}</label>
                    <input wire:model="formNameEn" type="text" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label form-label--required">{{ __('ui.academic_level', [], null, 'Academic Level') }}</label>
                    <select wire:model="formAcademicLevelId" class="form-control form-select @error('formAcademicLevelId') form-control--error @enderror">
                        <option value="0">{{ __('ui.select', [], null, 'Select…') }}</option>
                        @foreach($academicLevels as $level)
                            <option value="{{ $level->id }}">{{ $level->name_ar }}</option>
                        @endforeach
                    </select>
                    @error('formAcademicLevelId')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label form-label--required">{{ __('ui.operational_period', [], null, 'Shift') }}</label>
                    <select wire:model="formOperationalPeriodId" class="form-control form-select @error('formOperationalPeriodId') form-control--error @enderror">
                        <option value="0">{{ __('ui.select', [], null, 'Select…') }}</option>
                        @foreach($operationalPeriods as $op)
                            <option value="{{ $op->id }}">{{ $op->name_ar }}</option>
                        @endforeach
                    </select>
                    @error('formOperationalPeriodId')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('ui.classroom', [], null, 'Classroom') }}</label>
                    <select wire:model="formClassroomId" class="form-control form-select">
                        <option value="0">{{ __('ui.none', [], null, 'None') }}</option>
                        @foreach($classrooms as $cr)
                            <option value="{{ $cr->id }}">{{ $cr->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('ui.capacity', [], null, 'Capacity') }}</label>
                    <input wire:model="formCapacity" type="number" class="form-control" min="1">
                </div>
            </div>
            <div style="display:flex;gap:var(--space-2)">
                <button wire:click="save" class="btn btn--primary btn--sm">{{ __('ui.save', [], null, 'Save') }}</button>
                <button wire:click="cancelForm" class="btn btn--outline btn--sm">{{ __('ui.cancel', [], null, 'Cancel') }}</button>
            </div>
        </div>
    @elseif($showForm && $instSemId === 0)
        <div class="alert alert--warning">{{ __('ui.select_semester_first', [], null, 'Please select an institution semester before adding a class group.') }}</div>
    @endif

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('ui.code', [], null, 'Code') }}</th>
                    <th>{{ __('ui.name', [], null, 'Name') }}</th>
                    <th>{{ __('ui.academic_level', [], null, 'Level') }}</th>
                    <th>{{ __('ui.classroom', [], null, 'Room') }}</th>
                    <th>{{ __('ui.capacity', [], null, 'Cap.') }}</th>
                    <th>{{ __('ui.status', [], null, 'Status') }}</th>
                    <th>{{ __('ui.actions', [], null, 'Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($classGroups as $group)
                    <tr>
                        <td><code>{{ $group->code }}</code></td>
                        <td dir="rtl">{{ $group->name_ar }}</td>
                        <td>{{ optional($group->academicLevel)->name_ar }}</td>
                        <td>{{ optional($group->classroom)->name_ar ?? '—' }}</td>
                        <td>{{ $group->capacity ?? '—' }}</td>
                        <td>
                            <span class="badge badge--{{ match($group->lifecycle_status->value ?? $group->lifecycle_status) { 'active' => 'active', 'archived' => 'archived', default => 'draft' } }}">
                                {{ $group->lifecycle_status->value ?? $group->lifecycle_status }}
                            </span>
                        </td>
                        <td style="display:flex;gap:var(--space-1)">
                            @if(($group->lifecycle_status->value ?? $group->lifecycle_status) === 'draft')
                                <button wire:click="activate({{ $group->id }})" class="btn btn--primary btn--sm">{{ __('ui.activate', [], null, 'Activate') }}</button>
                            @elseif(($group->lifecycle_status->value ?? $group->lifecycle_status) === 'active')
                                <button wire:click="archive({{ $group->id }})" class="btn btn--outline btn--sm">{{ __('ui.archive', [], null, 'Archive') }}</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty-state">{{ __('ui.no_class_groups', [], null, 'No class groups found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $classGroups->links() }}
</div>

@include('livewire.admin._partials.page-styles')

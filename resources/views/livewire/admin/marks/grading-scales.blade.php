@php /** @var \App\Livewire\Admin\Marks\GradingScaleIndex $this */ @endphp

<div>
    <div class="page-header">
        <h1 class="page-title">{{ __('ui.grading_scales', [], null, 'Grading Scales') }}</h1>
        <button wire:click="$set('showForm', true)" class="btn btn--primary btn--sm">
            + {{ __('ui.add', [], null, 'Add') }}
        </button>
    </div>

    @include('livewire.admin._partials.flash-message')

    @if($showForm)
        <div class="card" style="margin-block-end:var(--space-4)">
            <h2 style="font-size:var(--text-base);font-weight:600;margin-block-end:var(--space-4)">{{ __('marks.new_grading_scale') }}</h2>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:var(--space-3);margin-block-end:var(--space-3)">
                <div class="form-group">
                    <label class="form-label form-label--required">{{ __('ui.institution') }}</label>
                    <select wire:model="institutionId" class="form-control form-select">
                        <option value="0">{{ __('marks.select_institution') }}</option>
                        @foreach($institutions as $inst)
                            <option value="{{ $inst->id }}">{{ $inst->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label form-label--required">{{ __('marks.code') }}</label>
                    <input wire:model="code" type="text" class="form-control @error('code') form-control--error @enderror" placeholder="{{ __('marks.code_placeholder') }}">
                    @error('code')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label form-label--required">{{ __('marks.arabic_name') }}</label>
                    <input wire:model="nameAr" type="text" class="form-control @error('nameAr') form-control--error @enderror" dir="rtl">
                    @error('nameAr')<span class="form-error">{{ $message }}</span>@enderror
                </div>
            </div>

            {{-- Grade rows --}}
            <h3 style="font-size:var(--text-sm);font-weight:600;margin-block-end:var(--space-2)">{{ __('marks.grade_tiers') }}</h3>
            <div class="data-table-wrapper" style="margin-block-end:var(--space-3)">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('marks.code') }}</th>
                            <th>{{ __('marks.arabic_name') }}</th>
                            <th>{{ __('marks.min_score') }}</th>
                            <th>{{ __('marks.max_score') }}</th>
                            <th>{{ __('marks.passing') }}</th>
                            <th>{{ __('marks.seq') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($gradeRows as $i => $row)
                            <tr>
                                <td><input wire:model="gradeRows.{{ $i }}.code" type="text" class="form-control" style="width:60px" placeholder="A+"></td>
                                <td><input wire:model="gradeRows.{{ $i }}.name_ar" type="text" class="form-control" dir="rtl"></td>
                                <td><input wire:model="gradeRows.{{ $i }}.min_score" type="number" step="0.01" class="form-control" style="width:80px"></td>
                                <td><input wire:model="gradeRows.{{ $i }}.max_score" type="number" step="0.01" class="form-control" style="width:80px"></td>
                                <td><input wire:model="gradeRows.{{ $i }}.is_passing" type="checkbox"></td>
                                <td><input wire:model="gradeRows.{{ $i }}.sequence" type="number" class="form-control" style="width:60px"></td>
                                <td><button wire:click="removeGradeRow({{ $i }})" class="btn btn--danger btn--sm">✕</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button wire:click="addGradeRow" class="btn btn--outline btn--sm" style="margin-block-end:var(--space-3)">+ {{ __('marks.add_grade') }}</button>

            <div style="display:flex;gap:var(--space-2)">
                <button wire:click="save" class="btn btn--primary btn--sm">{{ __('marks.save_scale') }}</button>
                <button wire:click="cancelForm" class="btn btn--outline btn--sm">{{ __('ui.cancel') }}</button>
            </div>
        </div>
    @endif

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('marks.code') }}</th>
                    <th>{{ __('ui.name') }}</th>
                    <th>{{ __('ui.institution') }}</th>
                    <th>{{ __('ui.status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($scales as $scale)
                    <tr>
                        <td><code>{{ $scale->code }}</code></td>
                        <td dir="rtl">{{ $scale->name_ar }}</td>
                        <td>{{ $scale->institution_name }}</td>
                        <td>
                            <span class="badge badge--{{ $scale->is_active ? 'active' : 'archived' }}">
                                {{ $scale->is_active ? __('ui.active') : __('ui.inactive') }}
                            </span>
                        </td>
                        <td>
                            <button wire:click="toggle({{ $scale->id }}, {{ $scale->is_active ? 'true' : 'false' }})" class="btn btn--outline btn--sm">
                                {{ $scale->is_active ? __('ui.deactivate') : __('ui.activate') }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-state">{{ __('marks.no_grading_scales') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@include('livewire.admin._partials.page-styles')

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
            <h2 style="font-size:var(--text-base);font-weight:600;margin-block-end:var(--space-4)">New Grading Scale</h2>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:var(--space-3);margin-block-end:var(--space-3)">
                <div class="form-group">
                    <label class="form-label form-label--required">Institution</label>
                    <select wire:model="institutionId" class="form-control form-select">
                        <option value="0">Select institution…</option>
                        @foreach($institutions as $inst)
                            <option value="{{ $inst->id }}">{{ $inst->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label form-label--required">Code</label>
                    <input wire:model="code" type="text" class="form-control @error('code') form-control--error @enderror" placeholder="e.g. STANDARD_5">
                    @error('code')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label form-label--required">Arabic Name</label>
                    <input wire:model="nameAr" type="text" class="form-control @error('nameAr') form-control--error @enderror" dir="rtl">
                    @error('nameAr')<span class="form-error">{{ $message }}</span>@enderror
                </div>
            </div>

            {{-- Grade rows --}}
            <h3 style="font-size:var(--text-sm);font-weight:600;margin-block-end:var(--space-2)">Grade Tiers</h3>
            <div class="data-table-wrapper" style="margin-block-end:var(--space-3)">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Arabic Name</th>
                            <th>Min Score</th>
                            <th>Max Score</th>
                            <th>Passing</th>
                            <th>Seq</th>
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
            <button wire:click="addGradeRow" class="btn btn--outline btn--sm" style="margin-block-end:var(--space-3)">+ Add Grade</button>

            <div style="display:flex;gap:var(--space-2)">
                <button wire:click="save" class="btn btn--primary btn--sm">Save Scale</button>
                <button wire:click="cancelForm" class="btn btn--outline btn--sm">Cancel</button>
            </div>
        </div>
    @endif

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Institution</th>
                    <th>Status</th>
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
                                {{ $scale->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <button wire:click="toggle({{ $scale->id }}, {{ $scale->is_active ? 'true' : 'false' }})" class="btn btn--outline btn--sm">
                                {{ $scale->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-state">No grading scales yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@include('livewire.admin._partials.page-styles')

@php /** @var \App\Livewire\Admin\Marks\AssessmentDefinitionIndex $this */ @endphp

<div>
    <div class="page-header">
        <h1 class="page-title">Assessment Definitions</h1>
        @if($semesterId > 0)
            <button wire:click="$set('showForm', true)" class="btn btn--primary btn--sm">+ Add Definition</button>
        @endif
    </div>

    @include('livewire.admin._partials.flash-message')

    <div class="filters-bar">
        <select wire:model.live="semesterId" class="form-control form-select">
            <option value="0">Select semester…</option>
            @foreach($openSemesters as $sem)
                <option value="{{ $sem->id }}">{{ $sem->institution_name }} — {{ $sem->semester_name }}</option>
            @endforeach
        </select>
    </div>

    @if($showForm && $semesterId > 0)
        <div class="card" style="margin-block-end:var(--space-4)">
            <h2 style="font-size:var(--text-base);font-weight:600;margin-block-end:var(--space-4)">New Assessment Definition</h2>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3)">
                <div class="form-group">
                    <label class="form-label form-label--required">Arabic Name</label>
                    <input wire:model="nameAr" type="text" class="form-control @error('nameAr') form-control--error @enderror" dir="rtl">
                    @error('nameAr')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">English Name</label>
                    <input wire:model="nameEn" type="text" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label form-label--required">Type</label>
                    <select wire:model="assessmentType" class="form-control form-select @error('assessmentType') form-control--error @enderror">
                        <option value="">Select type…</option>
                        @foreach($assessmentTypes as $type)
                            <option value="{{ $type['value'] }}">{{ $type['labelAr'] }}</option>
                        @endforeach
                    </select>
                    @error('assessmentType')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label form-label--required">Max Score</label>
                    <input wire:model="maxScore" type="number" step="0.01" min="0.01" class="form-control @error('maxScore') form-control--error @enderror">
                    @error('maxScore')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Weight (%)</label>
                    <input wire:model="weight" type="number" step="0.01" min="0" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Assessment Date</label>
                    <input wire:model="assessmentDate" type="date" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Class Group (optional)</label>
                    <select wire:model="classGroupId" class="form-control form-select">
                        <option value="0">All groups</option>
                        @foreach($classGroups as $cg)
                            <option value="{{ $cg->id }}">{{ $cg->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Subject (optional)</label>
                    <select wire:model="subjectOfferingId" class="form-control form-select">
                        <option value="0">All subjects</option>
                        @foreach($subjectOfferings as $so)
                            <option value="{{ $so->id }}">{{ $so->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:var(--space-2);margin-block-start:var(--space-3)">
                <button wire:click="save" class="btn btn--primary btn--sm">Save</button>
                <button wire:click="cancelForm" class="btn btn--outline btn--sm">Cancel</button>
            </div>
        </div>
    @endif

    @if($semesterId > 0)
        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Class / Subject</th>
                        <th>Max</th>
                        <th>Weight</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($definitions as $def)
                        <tr>
                            <td dir="rtl">{{ $def->name_ar }}</td>
                            <td>{{ $def->assessment_type }}</td>
                            <td>{{ $def->class_group_name ?? '—' }} / {{ $def->subject_name ?? '—' }}</td>
                            <td>{{ $def->max_score }}</td>
                            <td>{{ $def->weight }}%</td>
                            <td>
                                <span class="badge badge--{{ $def->status === 'active' ? 'active' : 'archived' }}">
                                    {{ $def->status }}
                                </span>
                            </td>
                            <td>
                                @if($def->status === 'active')
                                    <button wire:click="archive({{ $def->id }})" class="btn btn--outline btn--sm">Archive</button>
                                @else
                                    <button wire:click="restore({{ $def->id }})" class="btn btn--primary btn--sm">Restore</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-state">No definitions for this semester.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <p style="color:var(--color-muted);padding:var(--space-4)">Select a semester to view assessment definitions.</p>
    @endif
</div>

@include('livewire.admin._partials.page-styles')

@php /** @var \App\Livewire\Admin\Marks\MarkEntryWindowIndex $this */ @endphp

<div>
    <div class="page-header">
        <h1 class="page-title">Mark-Entry Windows</h1>
        @if($semesterId > 0)
            <button wire:click="$set('showForm', true)" class="btn btn--primary btn--sm">+ New Window</button>
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
            <h2 style="font-size:var(--text-base);font-weight:600;margin-block-end:var(--space-4)">New Mark-Entry Window</h2>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3)">
                <div class="form-group">
                    <label class="form-label">Arabic Name (optional)</label>
                    <input wire:model="nameAr" type="text" class="form-control" dir="rtl" placeholder="e.g. نافذة الفصل الأول">
                </div>
                <div class="form-group">
                    <label class="form-label">Opens At</label>
                    <input wire:model="opensAt" type="datetime-local" class="form-control @error('opensAt') form-control--error @enderror">
                    @error('opensAt')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Closes At</label>
                    <input wire:model="closesAt" type="datetime-local" class="form-control @error('closesAt') form-control--error @enderror">
                    @error('closesAt')<span class="form-error">{{ $message }}</span>@enderror
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
                <button wire:click="save" class="btn btn--primary btn--sm">Create Window</button>
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
                        <th>Scope</th>
                        <th>Opens</th>
                        <th>Closes</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($windows as $win)
                        <tr>
                            <td dir="rtl">{{ $win->name_ar ?? '—' }}</td>
                            <td>{{ $win->class_group_name ?? 'All' }} / {{ $win->subject_name ?? 'All' }}</td>
                            <td>{{ \Carbon\Carbon::parse($win->opens_at)->format('d/m/Y H:i') }}</td>
                            <td>{{ \Carbon\Carbon::parse($win->closes_at)->format('d/m/Y H:i') }}</td>
                            <td>
                                <span class="badge badge--{{ in_array($win->status, ['open','extended']) ? 'active' : ($win->status === 'cancelled' ? 'danger' : 'archived') }}">
                                    {{ $win->status }}
                                </span>
                            </td>
                            <td style="display:flex;gap:var(--space-1);flex-wrap:wrap">
                                @if($win->status === 'scheduled')
                                    <button wire:click="openWindow({{ $win->id }})" class="btn btn--primary btn--sm">Open</button>
                                @endif
                                @if(in_array($win->status, ['open','extended','scheduled']))
                                    <button wire:click="closeWindow({{ $win->id }})" class="btn btn--outline btn--sm">Close</button>
                                    <button wire:click="cancelWindow({{ $win->id }})" class="btn btn--danger btn--sm">Cancel</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state">No windows for this semester.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <p style="color:var(--color-muted);padding:var(--space-4)">Select a semester to manage windows.</p>
    @endif
</div>

@include('livewire.admin._partials.page-styles')

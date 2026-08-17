@php /** @var \App\Livewire\Admin\Marks\MarkEntryWindowIndex $this */ @endphp

<div>
    <div class="page-header">
        <h1 class="page-title">{{ __('marks.mark_entry_windows') }}</h1>
        @if($semesterId > 0)
            <button wire:click="$set('showForm', true)" class="btn btn--primary btn--sm">+ {{ __('marks.new_window') }}</button>
        @endif
    </div>

    @include('livewire.admin._partials.flash-message')

    <div class="filters-bar">
        <select wire:model.live="semesterId" class="form-control form-select">
            <option value="0">{{ __('marks.select_semester') }}</option>
            @foreach($openSemesters as $sem)
                <option value="{{ $sem->id }}">{{ $sem->institution_name }} — {{ $sem->semester_name }}</option>
            @endforeach
        </select>
    </div>

    @if($showForm && $semesterId > 0)
        <div class="card" style="margin-block-end:var(--space-4)">
            <h2 style="font-size:var(--text-base);font-weight:600;margin-block-end:var(--space-4)">{{ __('marks.new_mark_entry_window') }}</h2>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3)">
                <div class="form-group">
                    <label class="form-label">{{ __('marks.arabic_name_optional') }}</label>
                    <input wire:model="nameAr" type="text" class="form-control" dir="rtl" placeholder="{{ __('marks.window_name_placeholder') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('marks.opens_at') }}</label>
                    <input wire:model="opensAt" type="datetime-local" class="form-control @error('opensAt') form-control--error @enderror">
                    @error('opensAt')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('marks.closes_at') }}</label>
                    <input wire:model="closesAt" type="datetime-local" class="form-control @error('closesAt') form-control--error @enderror">
                    @error('closesAt')<span class="form-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('marks.class_group_optional') }}</label>
                    <select wire:model="classGroupId" class="form-control form-select">
                        <option value="0">{{ __('marks.all_groups') }}</option>
                        @foreach($classGroups as $cg)
                            <option value="{{ $cg->id }}">{{ $cg->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('marks.subject_optional') }}</label>
                    <select wire:model="subjectOfferingId" class="form-control form-select">
                        <option value="0">{{ __('marks.all_subjects') }}</option>
                        @foreach($subjectOfferings as $so)
                            <option value="{{ $so->id }}">{{ $so->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:var(--space-2);margin-block-start:var(--space-3)">
                <button wire:click="save" class="btn btn--primary btn--sm">{{ __('marks.create_window') }}</button>
                <button wire:click="cancelForm" class="btn btn--outline btn--sm">{{ __('ui.cancel') }}</button>
            </div>
        </div>
    @endif

    @if($semesterId > 0)
        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('ui.name') }}</th>
                        <th>{{ __('marks.scope') }}</th>
                        <th>{{ __('marks.opens') }}</th>
                        <th>{{ __('marks.closes') }}</th>
                        <th>{{ __('ui.status') }}</th>
                        <th>{{ __('ui.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($windows as $win)
                        <tr>
                            <td dir="rtl">{{ $win->name_ar ?? '—' }}</td>
                            <td>{{ $win->class_group_name ?? __('marks.all') }} / {{ $win->subject_name ?? __('marks.all') }}</td>
                            <td>{{ \Carbon\Carbon::parse($win->opens_at)->format('d/m/Y H:i') }}</td>
                            <td>{{ \Carbon\Carbon::parse($win->closes_at)->format('d/m/Y H:i') }}</td>
                            <td>
                                <span class="badge badge--{{ in_array($win->status, ['open','extended']) ? 'active' : ($win->status === 'cancelled' ? 'danger' : 'archived') }}">
                                    {{ $win->status }}
                                </span>
                            </td>
                            <td style="display:flex;gap:var(--space-1);flex-wrap:wrap">
                                @if($win->status === 'scheduled')
                                    <button wire:click="openWindow({{ $win->id }})" class="btn btn--primary btn--sm">{{ __('marks.open') }}</button>
                                @endif
                                @if(in_array($win->status, ['open','extended','scheduled']))
                                    <button wire:click="closeWindow({{ $win->id }})" class="btn btn--outline btn--sm">{{ __('ui.close') }}</button>
                                    <button wire:click="cancelWindow({{ $win->id }})" class="btn btn--danger btn--sm">{{ __('ui.cancel') }}</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state">{{ __('marks.no_windows') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <p style="color:var(--color-muted);padding:var(--space-4)">{{ __('marks.select_semester_manage_windows') }}</p>
    @endif
</div>

@include('livewire.admin._partials.page-styles')

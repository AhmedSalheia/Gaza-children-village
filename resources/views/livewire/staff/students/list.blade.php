@php /** @var \App\Livewire\Staff\Students\StudentList $this */ @endphp

<div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-block-end:var(--space-6);flex-wrap:wrap;gap:var(--space-3)">
        <h1 style="font-size:var(--text-2xl);font-weight:700;color:var(--text-primary);margin:0">
            {{ __('ui.students', [], null, 'Students') }}
        </h1>
        @if($canCreateStudent)
        <a href="{{ route('staff.students.add') }}" class="btn btn--primary btn--sm" wire:navigate>
            + {{ __('ui.add_student', [], null, 'Add Student') }}
        </a>
        @endif
    </div>

    {{-- Filters --}}
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:var(--space-3);margin-block-end:var(--space-4);align-items:end">
        <div class="form-group" style="margin:0">
            <label class="form-label">{{ __('ui.search', [], null, 'Search') }}</label>
            <input type="search" wire:model.live.debounce.300ms="search" class="form-control"
                placeholder="{{ __('ui.search_students_placeholder', [], null, 'Name or student code…') }}">
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label">{{ __('ui.academic_level', [], null, 'Academic Level') }}</label>
            <select wire:model.live="levelFilter" class="form-control form-select">
                <option value="0">{{ __('ui.all', [], null, 'All') }}</option>
                @foreach($academicLevels as $level)
                    <option value="{{ $level->id }}">{{ $level->name_ar }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label">{{ __('ui.class_group', [], null, 'Class Group') }}</label>
            <select wire:model.live="classGroupFilter" class="form-control form-select">
                <option value="0">{{ __('ui.all', [], null, 'All') }}</option>
                @foreach($classGroups as $cg)
                    <option value="{{ $cg->id }}">{{ $cg->level_name }} — {{ $cg->name_ar }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin:0">
            <label class="form-label">{{ __('ui.status', [], null, 'Status') }}</label>
            <select wire:model.live="statusFilter" class="form-control form-select">
                <option value="">{{ __('ui.all', [], null, 'All') }}</option>
                <option value="draft">{{ __('status.draft') }}</option>
                <option value="active">{{ __('status.active') }}</option>
                <option value="suspended">{{ __('status.suspended') }}</option>
            </select>
        </div>
    </div>

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('ui.student_code', [], null, 'Code') }}</th>
                <th>{{ __('ui.name', [], null, 'Name') }}</th>
                <th>{{ __('ui.class_group', [], null, 'Class Group') }}</th>
                <th>{{ __('ui.level', [], null, 'Level') }}</th>
                <th>{{ __('ui.enrollment_status', [], null, 'Enrollment') }}</th>
                <th></th>
            </tr></thead>
            <tbody>
                @forelse($students as $student)
                <tr>
                    <td>{{ $student->student_code }}</td>
                    <td>
                        <a href="{{ route('staff.students.detail', ['studentProfileId' => $student->student_id]) }}" class="link" wire:navigate>
                            {{ $student->name_ar }}
                        </a>
                        @if($student->name_en)
                            <div style="font-size:var(--text-xs);color:var(--text-secondary)">{{ $student->name_en }}</div>
                        @endif
                    </td>
                    <td>{{ $student->class_group_name }}</td>
                    <td>{{ $student->level_name }}</td>
                    <td><span class="badge badge--{{ match($student->enrollment_status) {'active'=>'active','draft'=>'draft','suspended'=>'pending',default=>'closed'} }}">{{ $student->enrollment_status }}</span></td>
                    <td style="white-space:nowrap">
                        <a href="{{ route('staff.students.detail', ['studentProfileId' => $student->student_id]) }}" class="btn btn--outline btn--sm" wire:navigate>{{ __('ui.view', [], null, 'View') }}</a>
                        @if($canManageEnrollments)
                        <a href="{{ route('staff.enrollments.transfer', ['studentProfileId' => $student->student_id]) }}" class="btn btn--ghost btn--sm" wire:navigate>{{ __('ui.transfer', [], null, 'Transfer') }}</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;color:var(--text-secondary);padding:var(--space-8);font-style:italic">{{ __('ui.no_students', [], null, 'No students found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $students->links() }}
</div>

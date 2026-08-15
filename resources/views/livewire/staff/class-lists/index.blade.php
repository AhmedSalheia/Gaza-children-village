@php /** @var \App\Livewire\Staff\ClassLists\ClassList $this */ @endphp

<div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-block-end:var(--space-6)">
        <h1 style="font-size:var(--text-2xl);font-weight:700;color:var(--text-primary);margin:0">
            {{ __('ui.class_lists', [], null, 'Class Lists') }}
        </h1>
    </div>

    <div style="display:grid;grid-template-columns:280px 1fr;gap:var(--space-6)">

        {{-- Class group selector --}}
        <div>
            <h2 style="font-size:var(--text-base);font-weight:600;margin-block-end:var(--space-3)">{{ __('ui.class_groups', [], null, 'Class Groups') }}</h2>
            @if($classGroups->isEmpty())
            <p style="color:var(--text-secondary);font-style:italic">{{ __('ui.no_class_groups', [], null, 'No class groups assigned.') }}</p>
            @else
            <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:var(--space-1)">
                @foreach($classGroups as $cg)
                <li>
                    <button
                        wire:click="$set('classGroupId', {{ $cg->id }})"
                        style="width:100%;text-align:start;padding:var(--space-2) var(--space-3);border-radius:var(--radius-sm);border:none;cursor:pointer;background:{{ $classGroupId === $cg->id ? 'var(--interactive-primary)' : 'transparent' }};color:{{ $classGroupId === $cg->id ? 'white' : 'var(--text-primary)' }};transition:background var(--transition-fast)">
                        <div style="font-weight:500;font-size:var(--text-sm)">{{ $cg->name_ar }}</div>
                        <div style="font-size:var(--text-xs);opacity:0.8">{{ $cg->level_name }}</div>
                        @if($cg->classroom_name)
                        <div style="font-size:var(--text-xs);opacity:0.7">{{ $cg->classroom_name }}</div>
                        @endif
                        <span style="font-size:var(--text-xs);padding:1px 6px;border-radius:999px;background:rgba(0,0,0,0.1)">{{ $cg->lifecycle_status }}</span>
                    </button>
                </li>
                @endforeach
            </ul>
            @endif
        </div>

        {{-- Student list --}}
        <div>
            @if($classGroupId === 0)
            <div style="display:flex;align-items:center;justify-content:center;height:200px;color:var(--text-secondary)">
                {{ __('ui.select_class_group', [], null, 'Select a class group to view its students.') }}
            </div>
            @else
            <div style="display:flex;align-items:center;justify-content:space-between;margin-block-end:var(--space-4)">
                <h2 style="font-size:var(--text-lg);font-weight:600;margin:0">
                    {{ __('ui.students', [], null, 'Students') }}
                    <span style="font-weight:400;color:var(--text-secondary)">({{ $classStudents->count() }})</span>
                </h2>
                <div style="display:flex;gap:var(--space-2)">
                    <button wire:click="downloadCsv" class="btn btn--outline btn--sm">
                        ↓ {{ __('ui.download_csv', [], null, 'Download CSV') }}
                    </button>
                    @if($canManageEnrollments)
                    <a href="{{ route('staff.enrollments.index') }}" class="btn btn--secondary btn--sm" wire:navigate>
                        {{ __('ui.manage_enrollments', [], null, 'Manage Enrollments') }}
                    </a>
                    @endif
                </div>
            </div>

            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead><tr>
                        <th>#</th>
                        <th>{{ __('ui.student_code', [], null, 'Code') }}</th>
                        <th>{{ __('ui.name', [], null, 'Name') }}</th>
                        <th>{{ __('ui.status', [], null, 'Status') }}</th>
                        <th>{{ __('ui.enrolled_on', [], null, 'Enrolled On') }}</th>
                    </tr></thead>
                    <tbody>
                        @forelse($classStudents as $i => $s)
                        <tr>
                            <td style="color:var(--text-secondary)">{{ $i + 1 }}</td>
                            <td>{{ $s->student_code }}</td>
                            <td>
                                <a href="{{ route('staff.students.detail', ['studentProfileId' => $s->student_id]) }}" class="link" wire:navigate>
                                    {{ $s->name_ar }}
                                </a>
                                @if($s->name_en)
                                <div style="font-size:var(--text-xs);color:var(--text-secondary)">{{ $s->name_en }}</div>
                                @endif
                            </td>
                            <td><span class="badge badge--{{ match($s->enrollment_status) {'active'=>'active','draft'=>'draft',default=>'closed'} }}">{{ $s->enrollment_status }}</span></td>
                            <td>{{ $s->enrolled_on }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" style="text-align:center;color:var(--text-secondary);padding:var(--space-8);font-style:italic">{{ __('ui.no_students_in_class', [], null, 'No students in this class group.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>

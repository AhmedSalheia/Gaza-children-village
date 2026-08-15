@php /** @var \App\Livewire\Staff\Marks\MySubjects $this */ @endphp

<div>
    <div class="page-header">
        <h1 class="page-title">{{ __('ui.marks', [], null, 'Marks') }}</h1>
    </div>

    @if($flashMessage !== '')
        <div class="alert alert--{{ $flashType === 'success' ? 'success' : 'danger' }}"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)">
            {{ $flashMessage }}
        </div>
    @endif

    @if($openWindows->isNotEmpty())
        <div class="card" style="margin-block-end:var(--space-4);background:var(--color-info-bg,#eff6ff)">
            <p style="font-weight:600;margin-block-end:var(--space-1)">Open mark-entry windows:</p>
            @foreach($openWindows as $window)
                <p style="font-size:var(--text-sm)">
                    {{ $window->name_ar ?? 'Window' }}
                    — closes {{ \Carbon\Carbon::parse($window->closes_at)->diffForHumans() }}
                </p>
            @endforeach
        </div>
    @endif

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Subject</th>
                    <th>Mark Sheet Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assignments as $row)
                    <tr>
                        <td dir="rtl">{{ $row->class_name }}</td>
                        <td dir="rtl">{{ $row->subject_name }}</td>
                        <td>
                            @if($row->sheet_status)
                                <span class="badge badge--{{ match($row->sheet_status) {
                                    'draft','returned' => 'archived',
                                    'submitted' => 'info',
                                    'verified' => 'warning',
                                    'approved','published' => 'active',
                                    default => 'archived'
                                } }}">{{ $row->sheet_status }}</span>
                            @else
                                <span class="badge badge--archived">No sheet</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('staff.marks.sheet', ['assignmentId' => $row->assignment_id]) }}"
                               class="btn btn--primary btn--sm">
                                @if($row->sheet_status === null)
                                    Open Sheet
                                @elseif(in_array($row->sheet_status, ['draft','returned']))
                                    Continue Entry
                                @else
                                    View Sheet
                                @endif
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-state">
                            {{ $canVerify ? 'No assignments in this semester.' : 'You have no teaching assignments this semester.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

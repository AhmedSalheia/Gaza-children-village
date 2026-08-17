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
            <p style="font-weight:600;margin-block-end:var(--space-1)">{{ __('marks.open_windows_label') }}</p>
            @foreach($openWindows as $window)
                <p style="font-size:var(--text-sm)">
                    {{ $window->name_ar ?? __('marks.window') }}
                    — {{ __('marks.closes_label') }} {{ \Carbon\Carbon::parse($window->closes_at)->diffForHumans() }}
                </p>
            @endforeach
        </div>
    @endif

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('marks.class') }}</th>
                    <th>{{ __('ui.subject') }}</th>
                    <th>{{ __('marks.mark_sheet_status') }}</th>
                    <th>{{ __('ui.actions') }}</th>
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
                                <span class="badge badge--archived">{{ __('marks.no_sheet') }}</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('staff.marks.sheet', ['assignmentId' => $row->assignment_id]) }}"
                               class="btn btn--primary btn--sm">
                                @if($row->sheet_status === null)
                                    {{ __('marks.open_sheet') }}
                                @elseif(in_array($row->sheet_status, ['draft','returned']))
                                    {{ __('marks.continue_entry') }}
                                @else
                                    {{ __('marks.view_sheet') }}
                                @endif
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-state">
                            {{ $canVerify ? __('marks.no_assignments_semester') : __('marks.no_teaching_assignments') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

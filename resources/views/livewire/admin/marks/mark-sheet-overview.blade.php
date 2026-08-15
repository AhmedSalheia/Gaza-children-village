@php /** @var \App\Livewire\Admin\Marks\MarkSheetOverview $this */ @endphp

<div>
    <div class="page-header">
        <h1 class="page-title">Mark Sheet Status</h1>
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

    @if($semesterId > 0)
        {{-- Summary stats --}}
        <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:var(--space-3);margin-block-end:var(--space-4)">
            @foreach([
                ['label' => 'Total', 'value' => $stats->total, 'color' => 'var(--color-muted)'],
                ['label' => 'Submitted', 'value' => $stats->submitted, 'color' => 'var(--color-info)'],
                ['label' => 'Verified', 'value' => $stats->verified, 'color' => 'var(--color-warning)'],
                ['label' => 'Approved', 'value' => $stats->approved, 'color' => 'var(--color-success)'],
                ['label' => 'Published', 'value' => $stats->published, 'color' => 'var(--color-primary)'],
            ] as $stat)
                <div class="card" style="text-align:center;padding:var(--space-3)">
                    <div style="font-size:var(--text-2xl);font-weight:700;color:{{ $stat['color'] }}">{{ $stat['value'] ?? 0 }}</div>
                    <div style="font-size:var(--text-sm);color:var(--color-muted)">{{ $stat['label'] }}</div>
                </div>
            @endforeach
        </div>

        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Subject</th>
                        <th>Teacher</th>
                        <th>Status</th>
                        <th>Ver</th>
                        <th>Submitted</th>
                        <th>Verified</th>
                        <th>Approved</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($markSheets as $sheet)
                        <tr>
                            <td dir="rtl">{{ $sheet->class_group_name }}</td>
                            <td dir="rtl">{{ $sheet->subject_name }}</td>
                            <td dir="rtl">{{ $sheet->teacher_name }}</td>
                            <td>
                                <span class="badge badge--{{ match($sheet->status) {
                                    'draft','returned' => 'archived',
                                    'submitted' => 'info',
                                    'verified' => 'warning',
                                    'approved','published' => 'active',
                                    default => 'archived'
                                } }}">{{ $sheet->status }}</span>
                            </td>
                            <td>{{ $sheet->version }}</td>
                            <td>{{ $sheet->submitted_at ? \Carbon\Carbon::parse($sheet->submitted_at)->format('d/m') : '—' }}</td>
                            <td>{{ $sheet->verified_at  ? \Carbon\Carbon::parse($sheet->verified_at)->format('d/m')  : '—' }}</td>
                            <td>{{ $sheet->approved_at  ? \Carbon\Carbon::parse($sheet->approved_at)->format('d/m')  : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty-state">No mark sheets for this semester.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <p style="color:var(--color-muted);padding:var(--space-4)">Select a semester to view mark sheet status.</p>
    @endif
</div>

@include('livewire.admin._partials.page-styles')

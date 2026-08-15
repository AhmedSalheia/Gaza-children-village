@php /** @var \App\Livewire\Admin\Calendar\CalendarIndex $this */ @endphp

<div>
    <div class="page-header">
        <h1 class="page-title">{{ __('ui.academic_calendar', [], null, 'Academic Calendar') }}</h1>
    </div>

    <div style="display:grid;grid-template-columns:280px 1fr;gap:var(--space-6);align-items:start">
        {{-- Academic years sidebar --}}
        <div class="card">
            <h2 style="font-size:var(--text-base);font-weight:600;margin-block-end:var(--space-3)">
                {{ __('ui.academic_years', [], null, 'Academic Years') }}
            </h2>
            <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:var(--space-1)">
                @forelse($academicYears as $year)
                    <li>
                        <button
                            wire:click="selectYear({{ $year->id }})"
                            class="{{ $selectedYearId === $year->id ? 'btn btn--primary btn--sm' : 'btn btn--outline btn--sm' }}"
                            style="inline-size:100%;text-align:start"
                        >
                            {{ $year->label ?? $year->code ?? $year->id }}
                            @if($year->status ?? null)
                                <span class="badge badge--{{ match($year->status) { 'open' => 'open', 'archived' => 'archived', default => 'draft' } }}" style="margin-inline-start:auto">
                                    {{ $year->status }}
                                </span>
                            @endif
                        </button>
                    </li>
                @empty
                    <li style="color:var(--text-secondary);font-size:var(--text-sm)">
                        {{ __('ui.no_academic_years', [], null, 'No academic years found.') }}
                    </li>
                @endforelse
            </ul>
        </div>

        {{-- Semesters and institution semesters --}}
        <div>
            @if($selectedYearId === null)
                <div class="empty-state-block">
                    <p>{{ __('ui.select_year_prompt', [], null, 'Select an academic year to view semesters.') }}</p>
                </div>
            @else
                <div class="card" style="margin-block-end:var(--space-4)">
                    <h2 style="font-size:var(--text-base);font-weight:600;margin-block-end:var(--space-3)">
                        {{ __('ui.semesters', [], null, 'Semesters') }}
                    </h2>
                    @forelse($semesters as $semester)
                        <div style="padding:var(--space-2) 0;border-block-end:1px solid var(--table-border)">
                            <strong>{{ $semester->name_ar ?? $semester->name_en ?? $semester->id }}</strong>
                            <span style="margin-inline-start:var(--space-2);color:var(--text-secondary);font-size:var(--text-sm)">
                                {{ $semester->starts_on }} → {{ $semester->ends_on }}
                            </span>
                        </div>
                    @empty
                        <p style="color:var(--text-secondary);font-size:var(--text-sm)">
                            {{ __('ui.no_semesters', [], null, 'No semesters for this year.') }}
                        </p>
                    @endforelse
                </div>

                <div class="card">
                    <h2 style="font-size:var(--text-base);font-weight:600;margin-block-end:var(--space-3)">
                        {{ __('ui.institution_semesters', [], null, 'Institution Semesters') }}
                    </h2>
                    @if($institutionSemesters->isEmpty())
                        <p style="color:var(--text-secondary);font-size:var(--text-sm)">
                            {{ __('ui.no_institution_semesters', [], null, 'No institution semesters yet.') }}
                        </p>
                    @else
                        <div class="data-table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('ui.institution', [], null, 'Institution') }}</th>
                                        <th>{{ __('ui.semester', [], null, 'Semester') }}</th>
                                        <th>{{ __('ui.status', [], null, 'Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($institutionSemesters as $is)
                                        <tr>
                                            <td>{{ $is->institution_name_ar }}</td>
                                            <td>{{ $is->semester_name_ar }}</td>
                                            <td>
                                                <span class="badge badge--{{ match($is->status) { 'open' => 'open', 'archived' => 'archived', 'closed' => 'closed', default => 'draft' } }}">
                                                    {{ $is->status }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

@include('livewire.admin._partials.page-styles')

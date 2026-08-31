@php
    /** @var \App\Livewire\Admin\Reports\ReportCentre $this */
@endphp

<div>

    {{-- ================================================================
         Page Header
    ================================================================= --}}
    <div class="page-header">

        <div>
            <h1 class="page-title">
                {{ __('reports.report_centre') }}
            </h1>
        </div>

    </div>


    {{-- ================================================================
         Main Content
    ================================================================= --}}
    <div class="report-layout">

        {{-- ============================================================
             Report Families
        ============================================================= --}}
        <div class="report-sidebar">

            <div class="card">

                <div class="card-header">
                    {{ __('reports.report_families') }}
                </div>

                <div class="report-family-list">

                    @forelse($this->definitions as $family => $defs)

                        {{-- Family --}}
                        <div class="report-family-title">

                            {{ match($family) {

                                'registry' =>
                                    __('reports.family_registry'),

                                'attendance' =>
                                    __('reports.family_attendance'),

                                'marks' =>
                                    __('reports.family_marks'),

                                'compliance' =>
                                    __('reports.family_compliance'),

                                'staff' =>
                                    __('reports.family_staff'),

                                'requests' =>
                                    __('reports.family_requests'),

                                'audit' =>
                                    __('reports.family_audit'),

                                default =>
                                    $family,

                            } }}

                        </div>


                        {{-- Reports --}}
                        @foreach($defs as $def)

                            <button
                                type="button"
                                wire:click="selectDefinition('{{ $def->code }}')"
                                class="report-family-item
                                    {{ $definitionCode === $def->code ? 'report-family-item--active' : '' }}"
                            >

                                {{ $def->name_ar }}

                            </button>

                        @endforeach

                    @empty

                        <div class="empty-state">
                            {{ __('reports.no_reports_permission') }}
                        </div>

                    @endforelse

                </div>

            </div>

        </div>


        {{-- ============================================================
             Right Content
        ============================================================= --}}
        <div class="report-content">

            @if($this->selectedDefinition)

                {{-- ====================================================
                     Report Filters
                ===================================================== --}}
                <div class="form-section">

                    <div class="section-header">

                        <div>

                            <h2 class="section-title">
                                {{ $this->selectedDefinition->name_ar }}
                            </h2>

                            @if($this->selectedDefinition->description_ar)

                                <p class="page-subtitle">
                                    {{ $this->selectedDefinition->description_ar }}
                                </p>

                            @endif

                        </div>

                    </div>


                    {{-- Filters --}}
                    <div class="form-grid">

                        {{-- Semester --}}
                        @if(in_array('institution_semester_id', $this->filterSchema, true))

                            <div>

                                <label class="form-label">
                                    {{ __('reports.semester') }}
                                </label>

                                <select
                                    class="form-control form-select"
                                    wire:model.live="semesterId"
                                >

                                    <option value="0">
                                        {{ __('reports.select_option') }}
                                    </option>

                                    @foreach($this->semesters as $sem)

                                        <option value="{{ $sem->id }}">

                                            {{ $sem->institution_name }}
                                            ·
                                            {{ $sem->semester_name }}

                                            @if($sem->status === 'open')
                                                ({{ __('reports.open_status') }})
                                            @endif

                                        </option>

                                    @endforeach

                                </select>

                            </div>

                        @endif


                        {{-- Class Group --}}
                        @if(in_array('class_group_id', $this->filterSchema, true))

                            <div>

                                <label class="form-label">
                                    {{ __('reports.class_group') }}
                                </label>

                                <select
                                    class="form-control form-select"
                                    wire:model.live="classGroupId"
                                >

                                    <option value="0">
                                        {{ __('reports.all') }}
                                    </option>

                                    @foreach($this->classGroups as $cg)

                                        <option value="{{ $cg->id }}">
                                            {{ $cg->name_ar }}
                                        </option>

                                    @endforeach

                                </select>

                            </div>

                        @endif


                        {{-- Operational Period --}}
                        @if(in_array('operational_period_id', $this->filterSchema, true))

                            <div>

                                <label class="form-label">
                                    {{ __('reports.operational_period') }}
                                </label>

                                <select
                                    class="form-control form-select"
                                    wire:model.live="periodId"
                                >

                                    <option value="0">
                                        {{ __('reports.all') }}
                                    </option>

                                    @foreach($this->operationalPeriods as $p)

                                        <option value="{{ $p->id }}">
                                            {{ $p->name_ar }}
                                        </option>

                                    @endforeach

                                </select>

                            </div>

                        @endif


                        {{-- Date From --}}
                        @if(in_array('date_from', $this->filterSchema, true))

                            <div>

                                <label class="form-label">
                                    {{ __('reports.date_from') }}
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    wire:model.live="dateFrom"
                                >

                            </div>

                        @endif


                        {{-- Date To --}}
                        @if(in_array('date_to', $this->filterSchema, true))

                            <div>

                                <label class="form-label">
                                    {{ __('reports.date_to') }}
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    wire:model.live="dateTo"
                                >

                            </div>

                        @endif

                    </div>


                    {{-- Actions --}}
                    <div class="form-actions">

                        <button
                            wire:click="runReport"
                            wire:loading.attr="disabled"
                            class="btn btn--primary btn--sm"
                        >

                            <span
                                wire:loading
                                wire:target="runReport"
                                class="loading-spinner"
                            ></span>

                            {{ __('reports.run_report') }}

                        </button>


                        @if($canExport)

                            <button
                                wire:click="exportReport"
                                wire:loading.attr="disabled"
                                class="btn btn--outline btn--sm"
                            >

                                <span
                                    wire:loading
                                    wire:target="exportReport"
                                    class="loading-spinner"
                                ></span>

                                {{ __('reports.export_excel') }}

                            </button>

                        @endif

                    </div>

                </div>


                {{-- ====================================================
                     Background Export Status
                ===================================================== --}}
                @if($this->pendingOperation)

                    <div
                        wire:poll.3s
                        class="alert alert--{{ match($this->pendingOperation->status) {

                            'completed' => 'success',

                            'failed' => 'danger',

                            default => 'info',

                        } }}"
                    >

                        <div>

                            @if($this->pendingOperation->status === 'completed')

                                {{ __('reports.export_completed') }}

                            @elseif($this->pendingOperation->status === 'failed')

                                {{ __('reports.export_failed') }}

                                {{ $this->pendingOperation->failure_summary
                                    ?? __('reports.unknown_error') }}

                            @else

                                <span class="loading-spinner"></span>

                                {{ __('reports.export_generating') }}

                                @if($this->pendingOperation->status === 'queued')

                                    ({{ __('reports.queued') }})

                                @else

                                    ({{ __('reports.processing') }})

                                @endif

                            @endif

                        </div>


                        @if($this->pendingOperation->status === 'completed')

                            <button
                                wire:click="downloadCompletedExport"
                                class="btn btn--primary btn--sm"
                            >
                                {{ __('reports.download') }}
                            </button>

                        @endif

                    </div>

                @endif


                {{-- ====================================================
                     Results
                ===================================================== --}}
                @if($hasRun)

                    <div class="data-table-wrapper">

                        <div class="table-header">

                            <div>

                                <div class="section-title">
                                    {{ __('reports.results_first_rows', [
                                        'limit' => \App\Livewire\Admin\Reports\ReportCentre::PREVIEW_LIMIT
                                    ]) }}
                                </div>

                            </div>

                            <div class="table-header__info">

                                {{ __('reports.rows_shown', [
                                    'count' => $this->rows->count()
                                ]) }}

                            </div>

                        </div>


                        <div class="table-scroll">

                            <table class="data-table">

                                <thead>

                                    <tr>

                                        @foreach($this->headings as $heading)

                                            <th>
                                                {{ $heading }}
                                            </th>

                                        @endforeach

                                    </tr>

                                </thead>


                                <tbody>

                                    @forelse($this->rows as $row)

                                        <tr>

                                            @foreach((array) $row as $value)

                                                <td>
                                                    {{ $value ?? '—' }}
                                                </td>

                                            @endforeach

                                        </tr>

                                    @empty

                                        <tr>

                                            <td
                                                colspan="{{ max(count($this->headings), 1) }}"
                                                class="empty-state"
                                            >

                                                {{ __('reports.no_results') }}

                                            </td>

                                        </tr>

                                    @endforelse

                                </tbody>

                            </table>

                        </div>

                    </div>

                @endif


            @else

                {{-- ====================================================
                     No Report Selected
                ===================================================== --}}
                <div class="empty-state">

                    {{ __('reports.select_report_to_start') }}

                </div>

            @endif

        </div>

    </div>

</div>


{{-- ================================================================
     Page Specific Styles
================================================================= --}}
@include('livewire.admin._partials.page-styles')


@script
<script>

    $wire.on('start-download', ({ url }) => {

        window.location.href = url;

    });

</script>
@endscript


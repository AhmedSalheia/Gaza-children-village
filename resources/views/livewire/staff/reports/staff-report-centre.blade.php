<div class="report-centre-page">

    {{-- =========================================================
        PAGE HEADER
    ========================================================== --}}
    <div class="report-page-header mb-4">

        <div class="report-header-content">

            <div class="report-header-icon">
                <i class="bi bi-bar-chart-line"></i>
            </div>

            <div>
                <h4 class="report-page-title mb-1">
                    {{ __('reports.report_centre') }}
                </h4>

                <p class="report-page-subtitle mb-0">
                    {{ __('reports.select_report_to_start') }}
                </p>
            </div>

        </div>

    </div>


    {{-- =========================================================
        REPORT CARDS
    ========================================================== --}}
    <div class="reports-browser-card mb-4">

        <div class="reports-browser-header">

            <div class="reports-browser-title">

                <div class="reports-browser-icon">
                    <i class="bi bi-grid-3x3-gap"></i>
                </div>

                <div>

                    <h6 class="mb-1">
                        {{ __('reports.report_families') }}
                    </h6>

                    <small>
                        {{ __('reports.select_report_to_start') }}
                    </small>

                </div>

            </div>

        </div>


        <div class="reports-browser-body">

            @forelse($this->definitions as $family => $defs)

                <div class="report-family-section">

                    {{-- FAMILY TITLE --}}
                    <div class="report-family-title">

                        <span class="family-title-icon">

                            @switch($family)

                                @case('registry')
                                    <i class="bi bi-journal-text"></i>
                                    @break

                                @case('attendance')
                                    <i class="bi bi-calendar-check"></i>
                                    @break

                                @case('marks')
                                    <i class="bi bi-bar-chart"></i>
                                    @break

                                @case('compliance')
                                    <i class="bi bi-shield-check"></i>
                                    @break

                                @case('staff')
                                    <i class="bi bi-people"></i>
                                    @break

                                @case('requests')
                                    <i class="bi bi-inbox"></i>
                                    @break

                                @case('audit')
                                    <i class="bi bi-clock-history"></i>
                                    @break

                                @default
                                    <i class="bi bi-folder"></i>

                            @endswitch

                        </span>


                        <span class="family-title-text">

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

                        </span>

                    </div>


                    {{-- REPORT CARDS --}}
                    <div class="report-cards-grid">

                        @foreach($defs as $def)

                            <button
                                type="button"
                                wire:click="selectDefinition('{{ $def->code }}')"
                                class="report-card {{ $definitionCode === $def->code ? 'selected' : '' }}"
                                data-report-card
                            >

                                <span class="report-card-decoration decoration-one"></span>
                                <span class="report-card-decoration decoration-two"></span>


                                <div class="report-card-top">

                                    <div class="report-card-icon">

                                        @switch($family)

                                            @case('registry')
                                                <i class="bi bi-journal-text"></i>
                                                @break

                                            @case('attendance')
                                                <i class="bi bi-calendar-check"></i>
                                                @break

                                            @case('marks')
                                                <i class="bi bi-bar-chart"></i>
                                                @break

                                            @case('compliance')
                                                <i class="bi bi-shield-check"></i>
                                                @break

                                            @case('staff')
                                                <i class="bi bi-people"></i>
                                                @break

                                            @case('requests')
                                                <i class="bi bi-inbox"></i>
                                                @break

                                            @case('audit')
                                                <i class="bi bi-clock-history"></i>
                                                @break

                                            @default
                                                <i class="bi bi-file-earmark-text"></i>

                                        @endswitch

                                    </div>


                                    <div class="report-card-arrow">
                                        <i class="bi bi-arrow-down"></i>
                                    </div>

                                </div>


                                <div class="report-card-content">

                                    <h6 class="report-card-title">
                                        {{ $def->name_ar }}
                                    </h6>


                                    @if(!empty($def->description_ar))

                                        <p class="report-card-description">
                                            {{ $def->description_ar }}
                                        </p>

                                    @endif

                                </div>


                                <div class="report-card-footer">

                                    <span>
                                        {{ __('reports.run_report') }}
                                    </span>

                                    <i class="bi bi-chevron-down"></i>

                                </div>

                            </button>

                        @endforeach

                    </div>

                </div>

            @empty

                <div class="empty-reports">

                    <div class="empty-reports-icon">
                        <i class="bi bi-file-earmark-x"></i>
                    </div>

                    <strong>
                        {{ __('reports.no_reports_permission') }}
                    </strong>

                </div>

            @endforelse

        </div>

    </div>


    {{-- =========================================================
        SELECTED REPORT
    ========================================================== --}}
    @if($this->selectedDefinition)

        <div
            id="selected-report"
            class="selected-report-wrapper"
        >

            {{-- REPORT CONFIGURATION --}}
            <div class="selected-report-card mb-4">

                <div class="selected-report-header">

                    <div class="selected-report-info">

                        <div class="selected-report-icon">
                            <i class="bi bi-file-earmark-bar-graph"></i>
                        </div>


                        <div>

                            <div class="selected-report-label">

                                <i class="bi bi-check-circle-fill"></i>

                                {{ __('reports.report_centre') }}

                            </div>


                            <h5 class="mb-1">
                                {{ $this->selectedDefinition->name_ar }}
                            </h5>


                            @if(!empty($this->selectedDefinition->description_ar))

                                <p class="mb-0">
                                    {{ $this->selectedDefinition->description_ar }}
                                </p>

                            @endif

                        </div>

                    </div>


                    <div class="selected-report-status">

                        <span>

                            <i class="bi bi-circle-fill"></i>

                            {{ __('reports.run_report') }}

                        </span>

                    </div>

                </div>


                {{-- FILTERS --}}
                <div class="report-filters">

                    <div class="filter-section-title">

                        <span class="filter-section-icon">
                            <i class="bi bi-funnel"></i>
                        </span>

                        <div>

                            <strong>
                                {{ __('reports.class_group') }}
                            </strong>

                            <small>
                                {{ __('reports.select_report_to_start') }}
                            </small>

                        </div>

                    </div>


                    <div class="row g-3">

                        @if(in_array('class_group_id', $this->filterSchema, true))

                            <div class="col-md-4">

                                <div
                                    class="form-group"
                                    style="margin:0"
                                >

                                    <label
                                        class="form-label"
                                        for="classGroupId"
                                    >
                                        {{ __('reports.class_group') }}
                                    </label>


                                    <select
                                        id="classGroupId"
                                        wire:model.live="classGroupId"
                                        class="form-control"
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

                            </div>

                        @endif


                        @if(in_array('date_from', $this->filterSchema, true))

                            <div class="col-md-3">

                                <div
                                    class="form-group"
                                    style="margin:0"
                                >

                                    <label
                                        class="form-label"
                                        for="dateFrom"
                                    >
                                        {{ __('reports.date_from') }}
                                    </label>


                                    <input
                                        id="dateFrom"
                                        type="date"
                                        class="form-control"
                                        wire:model.live="dateFrom"
                                    >

                                </div>

                            </div>

                        @endif


                        @if(in_array('date_to', $this->filterSchema, true))

                            <div class="col-md-3">

                                <div
                                    class="form-group"
                                    style="margin:0"
                                >

                                    <label
                                        class="form-label"
                                        for="dateTo"
                                    >
                                        {{ __('reports.date_to') }}
                                    </label>


                                    <input
                                        id="dateTo"
                                        type="date"
                                        class="form-control"
                                        wire:model.live="dateTo"
                                    >

                                </div>

                            </div>

                        @endif

                    </div>


                    {{-- ACTIONS --}}
                    <div class="report-actions mt-4">

                        <button
                            type="button"
                            wire:click="runReport"
                            wire:loading.attr="disabled"
                            class="action-button action-primary"
                        >

                            <span
                                wire:loading
                                wire:target="runReport"
                                class="spinner-border spinner-border-sm"
                            ></span>


                            <i
                                wire:loading.remove
                                wire:target="runReport"
                                class="bi bi-play-circle"
                            ></i>


                            <span>
                                {{ __('reports.run_report') }}
                            </span>

                        </button>


                        @if($canExport)

                            <button
                                type="button"
                                wire:click="exportReport"
                                wire:loading.attr="disabled"
                                class="action-button action-success"
                            >

                                <span
                                    wire:loading
                                    wire:target="exportReport"
                                    class="spinner-border spinner-border-sm"
                                ></span>


                                <i
                                    wire:loading.remove
                                    wire:target="exportReport"
                                    class="bi bi-file-earmark-excel"
                                ></i>


                                <span>
                                    {{ __('reports.export_excel') }}
                                </span>

                            </button>

                        @endif

                    </div>

                </div>

            </div>


            {{-- =====================================================
                EXPORT STATUS
            ====================================================== --}}
            @if($this->pendingOperation)

                <div
                    wire:poll.3s
                    class="export-status-card {{
                        match($this->pendingOperation->status) {

                            'completed' => 'completed',

                            'failed' => 'failed',

                            default => 'processing',

                        }
                    }}"
                >

                    <div class="export-status-info">

                        <div class="export-status-icon">

                            @if($this->pendingOperation->status === 'completed')

                                <i class="bi bi-check-circle"></i>

                            @elseif($this->pendingOperation->status === 'failed')

                                <i class="bi bi-exclamation-circle"></i>

                            @else

                                <span class="spinner-border spinner-border-sm"></span>

                            @endif

                        </div>


                        <div>

                            <strong>

                                @if($this->pendingOperation->status === 'completed')

                                    {{ __('reports.export_file_completed') }}

                                @elseif($this->pendingOperation->status === 'failed')

                                    {{ __('reports.export_failed') }}

                                @else

                                    {{ __('reports.export_generating') }}

                                @endif

                            </strong>


                            @if($this->pendingOperation->status === 'failed')

                                <small>
                                    {{
                                        $this->pendingOperation->failure_summary
                                        ?? __('reports.unknown_error')
                                    }}
                                </small>

                            @elseif($this->pendingOperation->status !== 'completed')

                                <small>

                                    {{
                                        $this->pendingOperation->status === 'queued'
                                            ? __('reports.queued')
                                            : __('reports.processing')
                                    }}

                                </small>

                            @endif

                        </div>

                    </div>


                    @if($this->pendingOperation->status === 'completed')

                        <button
                            type="button"
                            wire:click="downloadCompletedExport"
                            class="download-button"
                        >

                            <i class="bi bi-download"></i>

                            <span>
                                {{ __('reports.download') }}
                            </span>

                        </button>

                    @endif

                </div>

            @endif


            {{-- =====================================================
                RESULTS
            ====================================================== --}}
            @if($hasRun)

                <div class="results-card">

                    <div class="results-header">

                        <div class="results-title">

                            <div class="results-title-icon">
                                <i class="bi bi-table"></i>
                            </div>


                            <div>

                                <h6>

                                    {{
                                        __('reports.results_first_rows', [
                                            'limit' =>
                                            \App\Livewire\Staff\Reports\StaffReportCentre::PREVIEW_LIMIT
                                        ])
                                    }}

                                </h6>


                                <small>

                                    {{
                                        __('reports.rows_shown', [
                                            'count' => $this->rows->count()
                                        ])
                                    }}

                                </small>

                            </div>

                        </div>


                        <div class="results-count">
                            {{ $this->rows->count() }}
                        </div>

                    </div>


                    <div class="table-responsive">

                        <table class="professional-report-table">

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
                                            class="empty-table-cell"
                                        >

                                            <div class="empty-table">

                                                <div class="empty-table-icon">
                                                    <i class="bi bi-inbox"></i>
                                                </div>

                                                <strong>
                                                    {{ __('reports.no_results') }}
                                                </strong>

                                                <span>
                                                    {{ __('reports.no_results') }}
                                                </span>

                                            </div>

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            @endif

        </div>

    @else

        {{-- =====================================================
            NO REPORT SELECTED
        ====================================================== --}}
        <div class="no-report-card">

            <div class="no-report-icon">
                <i class="bi bi-hand-index-thumb"></i>
            </div>

            <h5>
                {{ __('reports.select_report_to_start') }}
            </h5>

            <p>
                {{ __('reports.select_report_to_start') }}
            </p>

        </div>

    @endif

</div>


{{-- =============================================================
    DOWNLOAD + AUTO SCROLL
============================================================= --}}
@script

<script>

    $wire.on('start-download', ({ url }) => {
        window.location.href = url;
    });


    let reportScrollPending = false;


    document.addEventListener('click', function (event) {

        const card = event.target.closest('[data-report-card]');

        if (!card) {
            return;
        }

        reportScrollPending = true;

    });


    if (window.Livewire) {

        Livewire.hook('morph.updated', () => {

            if (!reportScrollPending) {
                return;
            }


            const report =
                document.getElementById('selected-report');


            if (!report) {
                return;
            }


            reportScrollPending = false;


            setTimeout(() => {

                const headerOffset = 20;


                const elementPosition =
                    report.getBoundingClientRect().top +
                    window.pageYOffset;


                const offsetPosition =
                    elementPosition - headerOffset;


                window.scrollTo({

                    top: offsetPosition,

                    behavior: 'smooth'

                });

            }, 100);

        });

    }

</script>

@endscript


{{-- =============================================================
    PAGE CSS
============================================================= --}}
<style>

/* =============================================================
   PAGE BASE
============================================================= */

.report-centre-page {
    width: 100%;
    max-width: 100%;
    margin: 0;
    padding: 0;

    /* مهم جداً */
    border: 0 !important;
    outline: 0 !important;
    box-shadow: none !important;

    background: transparent;
}


/* =============================================================
   REMOVE YELLOW LINE FROM PAGE / LAYOUT / SIDEBAR
============================================================= */

.report-centre-page,
.report-centre-page::before,
.report-centre-page::after,

.report-centre-page:focus,
.report-centre-page:focus-visible,
.report-centre-page:active,

main,
main:focus,
main:focus-visible,

[role="main"],
[role="main"]:focus,
[role="main"]:focus-visible,

aside,
aside:focus,
aside:focus-visible,

.sidebar,
.sidebar:focus,
.sidebar:focus-visible,

.sidebar-wrapper,
.sidebar-wrapper:focus,
.sidebar-wrapper:focus-visible,

.sidebar-content,
.sidebar-content:focus,
.sidebar-content:focus-visible,

[data-sidebar],
[data-sidebar]:focus,
[data-sidebar]:focus-visible {

    outline: none !important;
}


/* إزالة أي Border من حاويات الصفحة والسايدبار */
main,
[role="main"],
aside,
.sidebar,
.sidebar-wrapper,
.sidebar-content,
[data-sidebar] {

    border-top: none !important;
    border-bottom: none !important;
    border-left: none !important;
    border-right: none !important;
}


/* إزالة أي Shadow يمكن أن يظهر كخط */
main,
[role="main"],
aside,
.sidebar,
.sidebar-wrapper,
.sidebar-content,
[data-sidebar] {

    box-shadow: none !important;
}


/* =============================================================
   GLOBAL FOCUS
============================================================= */

.report-centre-page button:focus,
.report-centre-page button:focus-visible,
.report-centre-page a:focus,
.report-centre-page a:focus-visible {

    outline: none !important;
}


/* =============================================================
   PAGE HEADER
============================================================= */

.report-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}


.report-header-content {
    display: flex;
    align-items: center;
    gap: 14px;
}


.report-header-icon {
    width: 50px;
    height: 50px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 14px;

    background: linear-gradient(
        135deg,
        #78aeda,
        #4f91c5
    );

    color: #fff;

    font-size: 21px;

    box-shadow:
        0 8px 20px rgba(65, 125, 170, .18);
}


.report-page-title {
    color: #1f3f5c;
    font-size: 21px;
    font-weight: 700;
}


.report-page-subtitle {
    color: #8b9aaa;
    font-size: 12px;
}


/* =============================================================
   REPORT BROWSER
============================================================= */

.reports-browser-card {
    overflow: hidden;

    background: #fff;

    border: 1px solid #dfe8f0;

    border-radius: 16px;

    box-shadow:
        0 6px 25px rgba(30, 70, 100, .05);
}


.reports-browser-header {
    padding: 18px 20px;

    border-bottom: 1px solid #e5edf4;

    background:
        linear-gradient(
            135deg,
            #ffffff,
            #f5faff
        );
}


.reports-browser-title {
    display: flex;
    align-items: center;
    gap: 12px;
}


.reports-browser-icon {
    width: 42px;
    height: 42px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 11px;

    background: #e6f1fa;

    color: #3778a8;

    font-size: 18px;
}


.reports-browser-title h6 {
    color: #274863;
    font-size: 14px;
    font-weight: 700;
}


.reports-browser-title small {
    color: #9aa7b5;
    font-size: 10px;
}


.reports-browser-body {
    padding: 20px;
}


/* =============================================================
   FAMILY
============================================================= */

.report-family-section {
    margin-bottom: 25px;
}


.report-family-section:last-child {
    margin-bottom: 0;
}


.report-family-title {
    display: flex;
    align-items: center;
    gap: 9px;

    margin-bottom: 13px;
}


.family-title-icon {
    width: 32px;
    height: 32px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 9px;

    background:
        linear-gradient(
            135deg,
            #e4f0fa,
            #d5e8f6
        );

    color: #397cae;

    font-size: 13px;
}


.family-title-text {
    color: #3d5b73;

    font-size: 12px;
    font-weight: 700;
}


/* =============================================================
   REPORT CARDS GRID
============================================================= */

.report-cards-grid {
    display: grid;

    grid-template-columns:
        repeat(3, minmax(0, 1fr));

    gap: 14px;
}


/* =============================================================
   REPORT CARD
============================================================= */

.report-card {

    position: relative;

    width: 100%;
    min-height: 175px;

    display: flex;
    flex-direction: column;

    padding: 18px;

    border: 1px solid #b8d2e6;

    border-radius: 15px;

    background:
        linear-gradient(
            135deg,
            #dcecf9 0%,
            #c9e1f4 50%,
            #b8d6ef 100%
        );

    color: #244b68;

    text-align: start;

    cursor: pointer;

    overflow: hidden;

    box-shadow:
        0 6px 18px rgba(45, 95, 130, .10);

    transition:
        transform .25s ease,
        box-shadow .25s ease,
        border-color .25s ease,
        background .25s ease;
}


/* منع الخط الأصفر على البطاقة */
.report-card,
.report-card:hover,
.report-card:focus,
.report-card:focus-visible,
.report-card:active,
.report-card.selected {

    outline: none !important;

    -webkit-tap-highlight-color: transparent;
}


/* البطاقة المحددة */
.report-card.selected {

    border-color: #9fc2dc !important;

    background:
        linear-gradient(
            135deg,
            #dcecf9 0%,
            #c9e1f4 50%,
            #b8d6ef 100%
        );

    box-shadow:
        0 8px 22px rgba(45, 95, 130, .14);
}


/* مهم جداً: إزالة الخط الأصفر */
.report-card.selected::before {

    display: none !important;

    content: none !important;

}


/* Hover */
.report-card:hover {

    transform: translateY(-4px);

    border-color: #8fb9da;

    background:
        linear-gradient(
            135deg,
            #e5f2fc 0%,
            #d2e7f7 50%,
            #c0dcf0 100%
        );

    box-shadow:
        0 12px 28px rgba(45, 95, 130, .17);
}


/* =============================================================
   CARD DECORATION
============================================================= */

.report-card-decoration {

    position: absolute;

    display: block;

    border-radius: 50%;

    pointer-events: none;
}


.decoration-one {

    width: 150px;
    height: 150px;

    top: -80px;

    inset-inline-end: -45px;

    background:
        rgba(255, 255, 255, .25);
}


.decoration-two {

    width: 100px;
    height: 100px;

    bottom: -55px;

    inset-inline-start: -30px;

    background:
        rgba(255, 255, 255, .18);
}


/* =============================================================
   CARD TOP
============================================================= */

.report-card-top {

    position: relative;
    z-index: 3;

    display: flex;
    align-items: center;
    justify-content: space-between;
}


.report-card-icon {

    width: 43px;
    height: 43px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 11px;

    background:
        rgba(255, 255, 255, .58);

    border:
        1px solid rgba(255, 255, 255, .80);

    color: #285e86;

    font-size: 18px;

    box-shadow:
        0 4px 10px rgba(40, 90, 125, .08);
}


.report-card-arrow {

    width: 31px;
    height: 31px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background:
        rgba(255, 255, 255, .50);

    border:
        1px solid rgba(255, 255, 255, .70);

    color: #285e86;

    font-size: 11px;

    transition:
        transform .25s ease,
        background .25s ease;
}


.report-card:hover .report-card-arrow {

    transform: translateY(4px);

    background:
        rgba(255, 255, 255, .75);
}


/* =============================================================
   CARD CONTENT
============================================================= */

.report-card-content {

    position: relative;
    z-index: 3;

    flex: 1;

    padding-top: 16px;
}


.report-card-title {

    margin:
        0 0 7px;

    color: #214866;

    font-size: 14px;

    font-weight: 700;

    line-height: 1.6;
}


.report-card-description {

    display: -webkit-box;

    margin: 0;

    overflow: hidden;

    color: #5d7c95;

    font-size: 10px;

    line-height: 1.7;

    -webkit-line-clamp: 2;

    -webkit-box-orient: vertical;
}


/* =============================================================
   CARD FOOTER
============================================================= */

.report-card-footer {

    position: relative;
    z-index: 3;

    display: flex;
    align-items: center;
    justify-content: space-between;

    padding-top: 12px;

    border-top:
        1px solid rgba(40, 90, 125, .13);

    color: #376b8d;

    font-size: 10px;

    font-weight: 600;
}


.report-card-footer i {

    font-size: 9px;

    transition:
        transform .2s ease;
}


.report-card:hover .report-card-footer i {

    transform: translateY(3px);
}


/* =============================================================
   EMPTY REPORTS
============================================================= */

.empty-reports {

    min-height: 220px;

    display: flex;
    flex-direction: column;

    align-items: center;
    justify-content: center;

    text-align: center;

    color: #8c9baa;
}


.empty-reports-icon {

    width: 60px;
    height: 60px;

    display: flex;
    align-items: center;
    justify-content: center;

    margin-bottom: 12px;

    border-radius: 16px;

    background: #eef5fa;

    color: #6d879c;

    font-size: 24px;
}


.empty-reports strong {
    font-size: 12px;
}


/* =============================================================
   SELECTED REPORT
============================================================= */

.selected-report-wrapper {

    scroll-margin-top: 20px;

    animation:
        reportAppear .35s ease;
}


@keyframes reportAppear {

    from {

        opacity: 0;

        transform:
            translateY(12px);

    }

    to {

        opacity: 1;

        transform:
            translateY(0);

    }

}


/* =============================================================
   SELECTED REPORT CARD
============================================================= */

.selected-report-card {

    overflow: hidden;

    background: #fff;

    border:
        1px solid #dfe8f0;

    border-radius: 15px;

    box-shadow:
        0 7px 25px rgba(30, 70, 100, .06);
}


.selected-report-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    padding: 20px;

    background:
        linear-gradient(
            135deg,
            #f7fbfe,
            #eaf4fb
        );

    border-bottom:
        1px solid #e1ebf3;
}


.selected-report-info {

    display: flex;

    align-items: center;

    gap: 13px;
}


.selected-report-icon {

    width: 48px;
    height: 48px;

    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 12px;

    background:
        linear-gradient(
            135deg,
            #78aeda,
            #4f91c5
        );

    color: #fff;

    font-size: 20px;

    box-shadow:
        0 7px 18px rgba(65, 125, 170, .18);
}


.selected-report-label {

    margin-bottom: 3px;

    color: #397aa7;

    font-size: 9px;

    font-weight: 700;

    text-transform: uppercase;
}


.selected-report-label i {
    font-size: 8px;
}


.selected-report-info h5 {

    color: #294861;

    font-size: 15px;

    font-weight: 700;
}


.selected-report-info p {

    color: #8797a6;

    font-size: 10px;
}


.selected-report-status {

    flex-shrink: 0;
}


.selected-report-status span {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding: 7px 11px;

    border-radius: 20px;

    background: #e5f1fa;

    color: #397aa7;

    font-size: 9px;

    font-weight: 600;
}


.selected-report-status i {
    font-size: 6px;
}


/* =============================================================
   FILTERS
============================================================= */

.report-filters {
    padding: 20px;
}


.filter-section-title {

    display: flex;

    align-items: center;

    gap: 9px;

    margin-bottom: 17px;
}


.filter-section-icon {

    width: 35px;
    height: 35px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 9px;

    background: #e8f3fa;

    color: #397aa7;

    font-size: 14px;
}


.filter-section-title div {

    display: flex;

    flex-direction: column;
}


.filter-section-title strong {

    color: #4f6374;

    font-size: 12px;

    font-weight: 700;
}


.filter-section-title small {

    color: #9aa7b4;

    font-size: 9px;
}


/* =============================================================
   FORM
============================================================= */

.report-filters .form-label {

    margin-bottom: 7px;

    color: #596b7a;

    font-size: 11px;

    font-weight: 600;
}


.report-filters .form-control {

    min-height: 42px;

    border:
        1px solid #dce5ec;

    border-radius: 8px;

    background: #fff;

    color: #495057;

    font-size: 12px;

    box-shadow: none;

    transition:
        all .2s ease;
}


.report-filters .form-control:hover {

    border-color:
        #bfcfdc;
}


.report-filters .form-control:focus {

    border-color:
        #75a9ce;

    outline: none !important;

    box-shadow:
        0 0 0 3px rgba(75, 145, 195, .10);
}


/* =============================================================
   ACTIONS
============================================================= */

.report-actions {

    display: flex;

    align-items: center;

    gap: 10px;
}


.action-button {

    min-height: 42px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    padding: 9px 18px;

    border-radius: 8px;

    font-size: 11px;

    font-weight: 600;

    cursor: pointer;

    transition:
        all .22s ease;
}


.action-primary {

    border:
        1px solid #5594bd;

    background:
        linear-gradient(
            135deg,
            #69a6cf,
            #4387b8
        );

    color: #fff;

    box-shadow:
        0 6px 15px rgba(65, 125, 170, .16);
}


.action-primary:hover {

    color: #fff;

    transform:
        translateY(-2px);

    box-shadow:
        0 9px 20px rgba(65, 125, 170, .22);
}


.action-success {

    border:
        1px solid #9fc3dc;

    background: #fff;

    color: #397aa7;
}


.action-success:hover {

    background: #e8f3fa;

    color: #285f83;

    transform:
        translateY(-2px);

    box-shadow:
        0 8px 17px rgba(65, 125, 170, .12);
}


.action-button:disabled {

    opacity: .65;

    cursor: not-allowed;

    transform: none !important;

    box-shadow: none !important;
}


/* =============================================================
   EXPORT STATUS
============================================================= */

.export-status-card {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    margin-bottom: 20px;

    padding: 14px 16px;

    border: 1px solid;

    border-radius: 11px;
}


.export-status-card.processing {

    background: #eef7fc;

    border-color: #c7dfef;
}


.export-status-card.completed {

    background: #f0fdf4;

    border-color: #bbf7d0;
}


.export-status-card.failed {

    background: #fff1f2;

    border-color: #fecdd3;
}


.export-status-info {

    display: flex;

    align-items: center;

    gap: 10px;
}


.export-status-icon {

    width: 37px;
    height: 37px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 9px;

    background: #fff;

    font-size: 16px;
}


.processing .export-status-icon {
    color: #397aa7;
}


.completed .export-status-icon {
    color: #16a34a;
}


.failed .export-status-icon {
    color: #dc3545;
}


.export-status-info div:last-child {

    display: flex;

    flex-direction: column;
}


.export-status-info strong {

    color: #495057;

    font-size: 11px;

    font-weight: 600;
}


.export-status-info small {

    margin-top: 2px;

    color: #8a94a6;

    font-size: 9px;
}


/* =============================================================
   DOWNLOAD
============================================================= */

.download-button {

    min-height: 36px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    padding: 7px 14px;

    border:
        1px solid #5594bd;

    border-radius: 7px;

    background: #5594bd;

    color: #fff;

    font-size: 10px;

    font-weight: 600;

    cursor: pointer;

    transition:
        all .2s ease;
}


.download-button:hover {

    background: #4380ad;

    border-color: #4380ad;

    transform:
        translateY(-1px);

    color: #fff;
}


/* =============================================================
   RESULTS
============================================================= */

.results-card {

    overflow: hidden;

    background: #fff;

    border:
        1px solid #dfe8f0;

    border-radius: 15px;

    box-shadow:
        0 7px 25px rgba(30, 70, 100, .05);
}


.results-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 16px 18px;

    border-bottom:
        1px solid #edf1f4;
}


.results-title {

    display: flex;

    align-items: center;

    gap: 10px;
}


.results-title-icon {

    width: 38px;
    height: 38px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 9px;

    background: #e8f3fa;

    color: #397aa7;
}


.results-title div:last-child {

    display: flex;

    flex-direction: column;
}


.results-title h6 {

    margin:
        0 0 3px;

    color: #344d61;

    font-size: 12px;

    font-weight: 700;
}


.results-title small {

    color: #9aa2ad;

    font-size: 9px;
}


.results-count {

    min-width: 31px;
    height: 29px;

    display: flex;

    align-items: center;
    justify-content: center;

    padding: 0 9px;

    border-radius: 8px;

    background: #e8f3fa;

    color: #397aa7;

    font-size: 10px;

    font-weight: 700;
}


/* =============================================================
   TABLE
============================================================= */

.professional-report-table {

    width: 100%;

    margin: 0;

    border-collapse: separate;

    border-spacing: 0;

    font-size: 11px;
}


.professional-report-table thead th {

    padding: 13px 14px;

    background: #f6f9fb;

    border-bottom:
        1px solid #e3e9ee;

    color: #687887;

    font-size: 9px;

    font-weight: 700;

    white-space: nowrap;
}


.professional-report-table tbody td {

    padding: 13px 14px;

    border-bottom:
        1px solid #f0f2f5;

    color: #555f6d;

    vertical-align: middle;
}


.professional-report-table tbody tr {

    transition:
        background .15s ease;
}


.professional-report-table tbody tr:hover {

    background: #f7fbfe;
}


.professional-report-table tbody tr:last-child td {

    border-bottom: 0;
}


/* =============================================================
   EMPTY TABLE
============================================================= */

.empty-table-cell {
    padding: 0 !important;
}


.empty-table {

    min-height: 230px;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;

    text-align: center;
}


.empty-table-icon {

    width: 55px;
    height: 55px;

    display: flex;

    align-items: center;
    justify-content: center;

    margin-bottom: 11px;

    border-radius: 50%;

    background: #eef4f8;

    color: #71879a;

    font-size: 22px;
}


.empty-table strong {

    color: #596273;

    font-size: 12px;
}


.empty-table span {

    margin-top: 4px;

    color: #9aa2ad;

    font-size: 10px;
}


/* =============================================================
   NO REPORT
============================================================= */

.no-report-card {

    min-height: 260px;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;

    padding: 35px;

    background: #fff;

    border:
        1px solid #dfe8f0;

    border-radius: 15px;

    text-align: center;

    box-shadow:
        0 6px 24px rgba(30, 70, 100, .04);
}


.no-report-icon {

    width: 65px;
    height: 65px;

    display: flex;

    align-items: center;
    justify-content: center;

    margin-bottom: 15px;

    border-radius: 17px;

    background:
        linear-gradient(
            135deg,
            #e7f3fa,
            #d7eaf5
        );

    color: #4b86ae;

    font-size: 27px;
}


.no-report-card h5 {

    color: #4b6172;

    font-size: 14px;

    font-weight: 700;
}


.no-report-card p {

    max-width: 400px;

    margin: 0;

    color: #9aa2ad;

    font-size: 10px;
}


/* =============================================================
   RESPONSIVE
============================================================= */

@media (max-width: 1199px) {

    .report-cards-grid {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

    }

}


@media (max-width: 767px) {

    .reports-browser-body {
        padding: 14px;
    }


    .report-cards-grid {
        grid-template-columns: 1fr;
    }


    .report-card {
        min-height: 155px;
    }


    .selected-report-header {

        align-items: flex-start;

        flex-direction: column;
    }


    .selected-report-status {
        width: 100%;
    }


    .selected-report-status span {

        width: 100%;

        justify-content: center;
    }


    .report-actions {

        flex-direction: column;

        align-items: stretch;
    }


    .action-button {
        width: 100%;
    }


    .export-status-card {

        align-items: stretch;

        flex-direction: column;
    }


    .download-button {
        width: 100%;
    }


    .professional-report-table {
        min-width: 700px;
    }

}


@media (max-width: 480px) {

    .report-header-content {
        gap: 10px;
    }


    .report-header-icon {

        width: 42px;
        height: 42px;

        font-size: 18px;
    }


    .report-page-title {
        font-size: 18px;
    }


    .report-page-subtitle {
        font-size: 9px;
    }


    .selected-report-info {
        align-items: flex-start;
    }

}

</style>

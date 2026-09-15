@php
    /**
     * Staff portal — formal requests list.
     * Wire model: App\Livewire\Staff\FormalRequests\FormalRequestList
     */
@endphp

<div class="formal-requests-page">

    {{-- =========================================================
         FLASH MESSAGE
    ========================================================== --}}
    @if($flashMessage)
        <div class="formal-flash-message">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ $flashMessage }}</span>
        </div>
    @endif


    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div class="formal-page-header">

        <div class="formal-header-content">

            <div class="formal-header-icon">
                <i class="bi bi-file-earmark-text"></i>
            </div>

            <div>
                <h1 class="formal-page-title">
                    {{ __('ui.formal_requests') }}
                </h1>

                <p class="formal-page-subtitle">
                    {{ __('ui.formal_requests_description') }}
                </p>
            </div>

        </div>


        @if($canPrepare)

            <a
                href="{{ route('staff.formal-requests.new') }}"
                class="formal-primary-button"
            >
                <i class="bi bi-plus-lg"></i>
                <span>{{ __('ui.new_request') }}</span>
            </a>

        @endif

    </div>


    {{-- =========================================================
         FILTER CARD
    ========================================================== --}}
    <div class="formal-filter-card">

        <div class="formal-filter-header">

            <div class="formal-filter-title">

                <div class="formal-filter-icon">
                    <i class="bi bi-funnel"></i>
                </div>

                <div>

                    <h2>
                        {{ __('ui.filters') }}
                    </h2>

                    <p>
                        {{ __('ui.filter_formal_requests') }}
                    </p>

                </div>

            </div>

        </div>


        <div class="formal-filter-body">

            {{-- =================================================
                 STATUS FILTER
            ================================================== --}}
            <div class="formal-filter-field">

                <label>
                    {{ __('ui.status') }}
                </label>

                <div class="formal-select-wrapper">

                    <select
                        wire:model.live="statusFilter"
                        class="formal-select"
                    >

                        <option value="">
                            {{ __('ui.all_statuses') }}
                        </option>

                        @foreach($statusOptions as $s)

                            <option value="{{ $s }}">
                                {{ __('ui.' . $s) }}
                            </option>

                        @endforeach

                    </select>

                    <i class="bi bi-chevron-down formal-select-arrow"></i>

                </div>

            </div>


            {{-- =================================================
                 TYPE FILTER
            ================================================== --}}
            <div class="formal-filter-field">

                <label>
                    {{ __('ui.type') }}
                </label>

                <div class="formal-select-wrapper">

                    <select
                        wire:model.live="typeFilter"
                        class="formal-select"
                    >

                        <option value="">
                            {{ __('ui.all_types') }}
                        </option>

                        @foreach($typeOptions as $t)

                            <option value="{{ $t }}">
                                {{ __('ui.' . $t) }}
                            </option>

                        @endforeach

                    </select>

                    <i class="bi bi-chevron-down formal-select-arrow"></i>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         REQUESTS TABLE
    ========================================================== --}}
    <div class="formal-results-card">

        {{-- =====================================================
             RESULTS HEADER
        ====================================================== --}}
        <div class="formal-results-header">

            <div class="formal-results-title">

                <div class="formal-results-icon">
                    <i class="bi bi-table"></i>
                </div>

                <div>

                    <h6>
                        {{ __('ui.formal_requests') }}
                    </h6>

                    <small>
                        {{ __('ui.total_records') }}
                    </small>

                </div>

            </div>


            <div class="formal-results-count">
                {{ $requests->total() }}
            </div>

        </div>


        {{-- =====================================================
             TABLE
        ====================================================== --}}
        <div class="formal-table-responsive">

            <table class="formal-requests-table">

                <thead>

                    <tr>

                        <th>
                            {{ __('ui.document_number') }}
                        </th>

                        <th>
                            {{ __('ui.request_title') }}
                        </th>

                        <th>
                            {{ __('ui.type') }}
                        </th>

                        <th>
                            {{ __('ui.status') }}
                        </th>

                        <th>
                            {{ __('ui.priority') }}
                        </th>

                        <th>
                            {{ __('ui.date') }}
                        </th>

                        <th>
                            {{ __('ui.actions') }}
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($requests as $req)

                        <tr>

                            {{-- =================================================
                                 DOCUMENT NUMBER
                            ================================================== --}}
                            <td>

                                <span class="formal-document-number">
                                    {{ $req->request_number }}
                                </span>

                            </td>


                            {{-- =================================================
                                 TITLE
                            ================================================== --}}
                            <td>

                                <div class="formal-request-title">

                                    {{ app()->getLocale() === 'ar'
                                        ? ($req->title_ar ?? $req->title_en)
                                        : ($req->title_en ?? $req->title_ar)
                                    }}

                                </div>

                            </td>


                            {{-- =================================================
                                 TYPE
                            ================================================== --}}
                            <td>

                                <span class="formal-type">

                                    <i class="bi bi-file-earmark"></i>

                                    {{ __('ui.' . $req->request_type) }}

                                </span>

                            </td>


                            {{-- =================================================
                                 STATUS
                            ================================================== --}}
                            <td>

                                @if(
                                    in_array(
                                        $req->current_status,
                                        ['closed', 'cancelled', 'superseded']
                                    )
                                )

                                    <span class="formal-status formal-status-gray">

                                        <span></span>

                                        {{ __('ui.' . $req->current_status) }}

                                    </span>


                                @elseif($req->current_status === 'signed')

                                    <span class="formal-status formal-status-green">

                                        <span></span>

                                        {{ __('ui.' . $req->current_status) }}

                                    </span>


                                @elseif(str_contains($req->current_status, 'review'))

                                    <span class="formal-status formal-status-yellow">

                                        <span></span>

                                        {{ __('ui.' . $req->current_status) }}

                                    </span>


                                @elseif(str_contains($req->current_status, 'returned'))

                                    <span class="formal-status formal-status-red">

                                        <span></span>

                                        {{ __('ui.' . $req->current_status) }}

                                    </span>


                                @else

                                    <span class="formal-status formal-status-blue">

                                        <span></span>

                                        {{ __('ui.' . $req->current_status) }}

                                    </span>

                                @endif

                            </td>


                            {{-- =================================================
                                 PRIORITY
                            ================================================== --}}
                            <td>

                                @php

                                    $priorityLabels = [

                                        1 => __('ui.priority_low'),

                                        2 => __('ui.priority_medium'),

                                        3 => __('ui.priority_high'),

                                        4 => __('ui.priority_urgent'),

                                    ];

                                @endphp


                                <span
                                    class="
                                        formal-priority

                                        @if($req->priority == 4)
                                            priority-urgent
                                        @elseif($req->priority == 3)
                                            priority-high
                                        @elseif($req->priority == 2)
                                            priority-medium
                                        @else
                                            priority-low
                                        @endif
                                    "
                                >

                                    @if($req->priority == 4)

                                        <i class="bi bi-exclamation-circle-fill"></i>

                                    @elseif($req->priority == 3)

                                        <i class="bi bi-arrow-up-circle-fill"></i>

                                    @elseif($req->priority == 2)

                                        <i class="bi bi-dash-circle-fill"></i>

                                    @else

                                        <i class="bi bi-arrow-down-circle-fill"></i>

                                    @endif


                                    {{ $priorityLabels[$req->priority] ?? $req->priority }}

                                </span>

                            </td>


                            {{-- =================================================
                                 DATE
                            ================================================== --}}
                            <td>

                                <span class="formal-date">

                                    <i class="bi bi-calendar3"></i>

                                    {{ $req->created_at
                                        ->locale(app()->getLocale())
                                        ->translatedFormat('d M Y')
                                    }}

                                </span>

                            </td>


                            {{-- =================================================
                                 ACTIONS
                            ================================================== --}}
                            <td>

                                <div class="formal-actions">

                                    <a
                                        href="{{ route(
                                            'staff.formal-requests.detail',
                                            $req->id
                                        ) }}"
                                        class="formal-view-button"
                                    >

                                        <i class="bi bi-eye"></i>

                                        {{ __('ui.view') }}

                                    </a>


                                    @if($canPrepare && $req->isCancellable())

                                        <button
                                            type="button"
                                            wire:click="cancel({{ $req->id }})"
                                            wire:confirm="{{ __('ui.cancel_confirm') }}"
                                            class="formal-cancel-button"
                                        >

                                            <i class="bi bi-x-circle"></i>

                                            {{ __('ui.cancel') }}

                                        </button>

                                    @endif

                                </div>

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td colspan="7">

                                <div class="formal-empty-table">

                                    <div class="formal-empty-icon">
                                        <i class="bi bi-inbox"></i>
                                    </div>

                                    <strong>
                                        {{ __('ui.no_formal_requests') }}
                                    </strong>

                                    <span>
                                        {{ __('ui.no_data') }}
                                    </span>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- =====================================================
             PAGINATION
        ====================================================== --}}
        @if($requests->hasPages())

            <div class="formal-pagination">
                {{ $requests->links() }}
            </div>

        @endif

    </div>

</div>


{{-- =============================================================
     PAGE CSS
============================================================== --}}
<style>

    .formal-requests-page {
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 0;

        border: 0 !important;
        outline: 0 !important;
        box-shadow: none !important;

        background: transparent;
    }


    /* =========================================================
       FLASH MESSAGE
    ========================================================== */

    .formal-flash-message {
        display: flex;
        align-items: center;
        gap: 9px;

        margin-block-end: var(--space-4);

        padding: 11px 14px;

        border: 1px solid #d7eadf;
        border-radius: var(--radius-md);

        background: #f0faf4;
        color: #39845d;

        font-size: var(--text-sm);
        font-weight: 600;
    }

    .formal-flash-message i {
        font-size: 15px;
    }


    /* =========================================================
       PAGE HEADER
    ========================================================== */

    .formal-page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;

        gap: var(--space-4);

        margin-block-end: var(--space-6);
    }

    .formal-header-content {
        display: flex;
        align-items: center;

        gap: 14px;
    }

    .formal-header-icon {
        width: 50px;
        height: 50px;

        display: flex;
        align-items: center;
        justify-content: center;

        flex-shrink: 0;

        border-radius: 14px;

        background: linear-gradient(
            135deg,
            #e7f3fa,
            #d7eaf5
        );

        color: #4b86ae;

        font-size: 22px;
    }

    .formal-page-title {
        margin: 0;

        color: var(--text-primary);

        font-size: var(--text-2xl);
        font-weight: 700;

        line-height: 1.3;
    }

    .formal-page-subtitle {
        margin: 4px 0 0;

        color: var(--text-secondary);

        font-size: var(--text-sm);
    }


    /* =========================================================
       NEW REQUEST BUTTON
    ========================================================== */

    .formal-primary-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        gap: 8px;

        padding: 10px 16px;

        border: 0;
        border-radius: var(--radius-md);

        background: var(--interactive-primary);
        color: white;

        font-size: var(--text-sm);
        font-weight: 600;

        text-decoration: none;

        transition:
            transform .15s ease,
            box-shadow .15s ease,
            opacity .15s ease;
    }

    .formal-primary-button:hover {
        opacity: .92;

        transform: translateY(-1px);

        box-shadow:
            0 5px 14px rgba(0, 0, 0, .10);
    }


    /* =========================================================
       FILTER CARD
    ========================================================== */

    .formal-filter-card {
        margin-block-end: var(--space-6);

        background: var(--surface-primary);

        border: 1px solid var(--border-color);

        border-radius: var(--radius-lg);

        overflow: hidden;

        box-shadow:
            0 2px 8px rgba(0, 0, 0, .04);
    }

    .formal-filter-header {
        display: flex;
        align-items: center;

        padding:
            var(--space-4)
            var(--space-5);

        border-block-end:
            1px solid var(--border-color);

        background:
            var(
                --surface-secondary,
                var(--surface-primary)
            );
    }

    .formal-filter-title {
        display: flex;
        align-items: center;

        gap: 12px;
    }

    .formal-filter-icon {
        width: 38px;
        height: 38px;

        display: flex;
        align-items: center;
        justify-content: center;

        flex-shrink: 0;

        border-radius: var(--radius-sm);

        background: var(--interactive-primary);

        color: white;

        font-size: 17px;
    }

    .formal-filter-title h2 {
        margin: 0;

        color: var(--text-primary);

        font-size: var(--text-base);
        font-weight: 700;
    }

    .formal-filter-title p {
        margin: 2px 0 0;

        color: var(--text-secondary);

        font-size: var(--text-xs);
    }

    .formal-filter-body {
        display: grid;

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

        gap: var(--space-5);

        padding: var(--space-5);
    }

    .formal-filter-field label {
        display: block;

        margin-block-end: 7px;

        color: var(--text-primary);

        font-size: var(--text-xs);
        font-weight: 700;
    }

    .formal-select-wrapper {
        position: relative;
    }

    .formal-select {
        width: 100%;

        min-height: 42px;

        padding:
            9px 38px 9px 12px;

        appearance: none;

        border:
            1px solid var(--border-color);

        border-radius: var(--radius-md);

        background: var(--surface-primary);

        color: var(--text-primary);

        font-size: var(--text-sm);

        outline: none;

        transition:
            border-color .15s ease,
            box-shadow .15s ease;
    }

    .formal-select:focus {
        border-color:
            var(--interactive-primary);

        box-shadow:
            0 0 0 3px rgba(75, 134, 174, .12);
    }

    .formal-select-arrow {
        position: absolute;

        inset-inline-end: 13px;

        top: 50%;

        transform:
            translateY(-50%);

        pointer-events: none;

        color: var(--text-secondary);

        font-size: 11px;
    }


    /* =========================================================
       RESULTS CARD
    ========================================================== */

    .formal-results-card {
        width: 100%;

        background: #fff;

        border:
            1px solid #dfe8f0;

        border-radius: 15px;

        overflow: hidden;

        box-shadow:
            0 6px 24px rgba(30, 70, 100, .04);
    }

    .formal-results-header {
        display: flex;
        align-items: center;
        justify-content: space-between;

        gap: 15px;

        padding: 16px 20px;

        border-block-end:
            1px solid #e7edf2;

        background: #fff;
    }

    .formal-results-title {
        display: flex;
        align-items: center;

        gap: 12px;
    }

    .formal-results-icon {
        width: 38px;
        height: 38px;

        display: flex;
        align-items: center;
        justify-content: center;

        border-radius: 11px;

        background: #e7f3fa;

        color: #4b86ae;

        font-size: 17px;
    }

    .formal-results-title h6 {
        margin: 0;

        color: #4b6172;

        font-size: 14px;
        font-weight: 700;
    }

    .formal-results-title small {
        display: block;

        margin-top: 3px;

        color: #9aa2ad;

        font-size: 10px;
    }

    .formal-results-count {
        min-width: 34px;
        height: 30px;

        display: flex;
        align-items: center;
        justify-content: center;

        padding: 0 9px;

        border-radius: 9px;

        background: #eef6fa;

        color: #4b86ae;

        font-size: 12px;
        font-weight: 700;
    }


    /* =========================================================
       TABLE
    ========================================================== */

    .formal-table-responsive {
        width: 100%;

        overflow-x: auto;
    }

    .formal-requests-table {
        width: 100%;

        min-width: 900px;

        border-collapse: collapse;
    }

    .formal-requests-table thead {
        background: #f7fafc;
    }

    .formal-requests-table th {
        padding: 13px 16px;

        border-block-end:
            1px solid #e2eaf0;

        color: #71879a;

        font-size: 10px;
        font-weight: 700;

        text-align: start;

        white-space: nowrap;
    }

    .formal-requests-table td {
        padding: 14px 16px;

        border-block-end:
            1px solid #edf2f5;

        color: #596b7a;

        font-size: 12px;

        vertical-align: middle;
    }

    .formal-requests-table tbody tr {
        transition:
            background .15s ease;
    }

    .formal-requests-table tbody tr:hover {
        background: #f9fcfe;
    }

    .formal-requests-table tbody tr:last-child td {
        border-block-end: 0;
    }


    /* =========================================================
       DOCUMENT NUMBER
    ========================================================== */

    .formal-document-number {
        display: inline-flex;
        align-items: center;

        padding: 5px 8px;

        border-radius: 7px;

        background: #f1f5f8;

        color: #596f7e;

        font-family: monospace;

        font-size: 10px;
    }


    /* =========================================================
       REQUEST TITLE
    ========================================================== */

    .formal-request-title {
        min-width: 180px;

        color: #4b6172;

        font-size: 12px;
        font-weight: 700;

        line-height: 1.5;
    }


    /* =========================================================
       TYPE
    ========================================================== */

    .formal-type {
        display: inline-flex;
        align-items: center;

        gap: 6px;

        color: #71879a;

        white-space: nowrap;
    }

    .formal-type i {
        color: #8fa2af;
    }


    /* =========================================================
       STATUS
    ========================================================== */

    .formal-status {
        display: inline-flex;
        align-items: center;

        gap: 6px;

        padding: 5px 9px;

        border-radius: 20px;

        font-size: 10px;
        font-weight: 700;

        white-space: nowrap;
    }

    .formal-status > span {
        width: 6px;
        height: 6px;

        border-radius: 50%;
    }

    .formal-status-blue {
        background: #eaf4fa;

        color: #4b86ae;
    }

    .formal-status-blue > span {
        background: #4b86ae;
    }

    .formal-status-green {
        background: #eaf7f0;

        color: #39845d;
    }

    .formal-status-green > span {
        background: #39845d;
    }

    .formal-status-yellow {
        background: #fff8e8;

        color: #a98232;
    }

    .formal-status-yellow > span {
        background: #c99b3c;
    }

    .formal-status-red {
        background: #fff0f0;

        color: #b65b5b;
    }

    .formal-status-red > span {
        background: #c96a6a;
    }

    .formal-status-gray {
        background: #f1f3f5;

        color: #77818a;
    }

    .formal-status-gray > span {
        background: #8c969e;
    }


    /* =========================================================
       PRIORITY
    ========================================================== */

    .formal-priority {
        display: inline-flex;
        align-items: center;

        gap: 6px;

        white-space: nowrap;

        font-size: 11px;
        font-weight: 600;
    }

    .priority-low {
        color: #7d8b95;
    }

    .priority-medium {
        color: #a98232;
    }

    .priority-high {
        color: #b96c35;
    }

    .priority-urgent {
        color: #b95757;
    }


    /* =========================================================
       DATE
    ========================================================== */

    .formal-date {
        display: inline-flex;
        align-items: center;

        gap: 6px;

        color: #8a969f;

        font-size: 10px;

        white-space: nowrap;
    }

    .formal-date i {
        color: #a2adb5;
    }


    /* =========================================================
       ACTIONS
    ========================================================== */

    .formal-actions {
        display: flex;
        align-items: center;

        gap: 6px;

        white-space: nowrap;
    }

    .formal-view-button,
    .formal-cancel-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        gap: 5px;

        min-height: 30px;

        padding: 5px 9px;

        border-radius: 7px;

        font-size: 10px;
        font-weight: 700;

        text-decoration: none;

        cursor: pointer;

        transition:
            background .15s ease,
            border-color .15s ease;
    }

    .formal-view-button {
        border:
            1px solid #d3e5ef;

        background: #eef7fb;

        color: #4b86ae;
    }

    .formal-view-button:hover {
        background: #e1f0f7;
    }

    .formal-cancel-button {
        border:
            1px solid #efd6d6;

        background: #fff4f4;

        color: #b65b5b;
    }

    .formal-cancel-button:hover {
        background: #ffeaea;
    }


    /* =========================================================
       EMPTY TABLE
    ========================================================== */

    .formal-empty-table {
        min-height: 220px;

        display: flex;
        flex-direction: column;

        align-items: center;
        justify-content: center;

        padding: 30px;

        text-align: center;
    }

    .formal-empty-icon {
        width: 58px;
        height: 58px;

        display: flex;
        align-items: center;
        justify-content: center;

        margin-block-end: 13px;

        border-radius: 15px;

        background:
            linear-gradient(
                135deg,
                #e7f3fa,
                #d7eaf5
            );

        color: #4b86ae;

        font-size: 24px;
    }

    .formal-empty-table strong {
        color: #596273;

        font-size: 12px;
    }

    .formal-empty-table span {
        margin-top: 4px;

        color: #9aa2ad;

        font-size: 10px;
    }


    /* =========================================================
       PAGINATION
    ========================================================== */

    .formal-pagination {
        padding: 14px 18px;

        border-block-start:
            1px solid #e7edf2;

        background: #fff;
    }


    /* =========================================================
       REMOVE PAGE / SIDEBAR OUTLINES
    ========================================================== */

    .formal-requests-page,
    .formal-requests-page::before,
    .formal-requests-page::after,
    .formal-requests-page:focus,
    .formal-requests-page:focus-visible,

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


    /* =========================================================
       RESPONSIVE
    ========================================================== */

    @media (max-width: 900px) {

        .formal-filter-body {
            grid-template-columns: 1fr;
        }

        .formal-page-header {
            align-items: flex-start;
        }

    }


    @media (max-width: 700px) {

        .formal-page-header {
            flex-direction: column;
        }

        .formal-primary-button {
            width: 100%;
        }

        .formal-header-icon {
            width: 44px;
            height: 44px;

            font-size: 19px;
        }

        .formal-page-title {
            font-size: 20px;
        }

        .formal-page-subtitle {
            font-size: 10px;
        }

        .formal-filter-body {
            padding: 14px;
        }

        .formal-results-header {
            padding: 14px;
        }

        .formal-requests-table {
            min-width: 900px;
        }

    }

</style>


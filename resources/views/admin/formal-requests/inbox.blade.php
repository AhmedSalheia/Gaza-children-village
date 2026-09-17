@php
    /**
     * Admin portal — management inbox for formal institution requests.
     *
     * Wire model:
     * App\Livewire\Admin\FormalRequests\ManagementInbox
     */

    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';

    $statusLabels = [
        'submitted_to_management' => [
            'label' => __('ui.status_submitted_to_management', [], null, 'Submitted to Management'),
            'class' => 'status-submitted',
            'icon'  => 'bi-send',
        ],

        'under_management_review' => [
            'label' => __('ui.status_under_management_review', [], null, 'Under Management Review'),
            'class' => 'status-review',
            'icon'  => 'bi-search',
        ],

        'clarification_requested' => [
            'label' => __('ui.status_clarification_requested', [], null, 'Clarification Requested'),
            'class' => 'status-clarification',
            'icon'  => 'bi-question-circle',
        ],

        'accepted' => [
            'label' => __('ui.status_accepted', [], null, 'Accepted'),
            'class' => 'status-accepted',
            'icon'  => 'bi-check-circle',
        ],

        'rejected' => [
            'label' => __('ui.status_rejected', [], null, 'Rejected'),
            'class' => 'status-rejected',
            'icon'  => 'bi-x-circle',
        ],

        'responded' => [
            'label' => __('ui.status_responded', [], null, 'Responded'),
            'class' => 'status-responded',
            'icon'  => 'bi-reply',
        ],

        'closed' => [
            'label' => __('ui.status_closed', [], null, 'Closed'),
            'class' => 'status-closed',
            'icon'  => 'bi-lock',
        ],
    ];

    $priorityLabels = [
        1 => [
            'label' => __('ui.priority_low', [], null, 'Low'),
            'class' => 'priority-low',
            'icon'  => 'bi-arrow-down-circle',
        ],

        2 => [
            'label' => __('ui.priority_medium', [], null, 'Medium'),
            'class' => 'priority-medium',
            'icon'  => 'bi-dash-circle',
        ],

        3 => [
            'label' => __('ui.priority_high', [], null, 'High'),
            'class' => 'priority-high',
            'icon'  => 'bi-arrow-up-circle',
        ],

        4 => [
            'label' => __('ui.priority_urgent', [], null, 'Urgent'),
            'class' => 'priority-urgent',
            'icon'  => 'bi-exclamation-circle',
        ],
    ];
@endphp

<div class="formal-management-page">

    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div class="formal-page-header">

        <div class="formal-header-content">

            <div class="formal-header-icon">
                <i class="bi bi-inbox"></i>
            </div>

            <div>
                <h1 class="formal-page-title">
                    {{ __('ui.formal_management_inbox', [], null, 'Formal Requests Management') }}
                </h1>

                <p class="formal-page-subtitle">
                    {{ __('ui.formal_management_inbox_description', [], null, 'Review and manage formal institution requests submitted to management.') }}
                </p>
            </div>

        </div>

    </div>


    {{-- =========================================================
         FLASH MESSAGE
    ========================================================== --}}
    @if($flashMessage)

        <div class="formal-flash-message">

            <div class="formal-flash-icon">
                <i class="bi bi-info-circle"></i>
            </div>

            <div class="formal-flash-text">
                {{ $flashMessage }}
            </div>

        </div>

    @endif


    {{-- =========================================================
         FILTERS CARD
    ========================================================== --}}
    <div class="formal-filter-card">

        <div class="formal-filter-header">

            <div class="formal-filter-title">

                <span class="formal-filter-title-icon">
                    <i class="bi bi-funnel"></i>
                </span>

                <div>
                    <h2>
                        {{ __('ui.filters', [], null, 'Filters') }}
                    </h2>

                    <p>
                        {{ __('ui.management_filter_description', [], null, 'Filter formal requests by status or institution.') }}
                    </p>
                </div>

            </div>

        </div>


        <div class="formal-filter-body">

            {{-- Status --}}
            <div class="formal-filter-field">

                <label for="formal-status-filter">
                    <i class="bi bi-flag"></i>
                    {{ __('ui.status', [], null, 'Status') }}
                </label>

                <select
                    id="formal-status-filter"
                    wire:model.live="statusFilter"
                    class="formal-select"
                >
                    <option value="">
                        {{ __('ui.all_statuses', [], null, 'All Statuses') }}
                    </option>

                    @foreach($statusOptions as $status)

                        @php
                            $statusData = $statusLabels[$status] ?? [
                                'label' => ucwords(str_replace('_', ' ', $status)),
                                'class' => 'status-default',
                                'icon'  => 'bi-circle',
                            ];
                        @endphp

                        <option value="{{ $status }}">
                            {{ $statusData['label'] }}
                        </option>

                    @endforeach

                </select>

            </div>


            {{-- Institution --}}
            <div class="formal-filter-field">

                <label for="formal-institution-filter">
                    <i class="bi bi-building"></i>
                    {{ __('ui.institution', [], null, 'Institution') }}
                </label>

                <select
                    id="formal-institution-filter"
                    wire:model.live="institutionFilter"
                    class="formal-select"
                >
                    <option value="">
                        {{ __('ui.all_institutions', [], null, 'All Institutions') }}
                    </option>

                    @foreach($institutions as $institution)

                        <option value="{{ $institution->id }}">
                            {{ $isArabic
                                ? ($institution->name_ar ?: $institution->name_en)
                                : ($institution->name_en ?: $institution->name_ar)
                            }}
                        </option>

                    @endforeach

                </select>

            </div>

        </div>

    </div>


    {{-- =========================================================
         RESULTS CARD
    ========================================================== --}}
    <div class="formal-results-card">

        {{-- Results Header --}}
        <div class="formal-results-header">

            <div class="formal-results-title">

                <span class="formal-results-icon">
                    <i class="bi bi-file-earmark-text"></i>
                </span>

                <div>

                    <h2>
                        {{ __('ui.management_requests', [], null, 'Management Requests') }}
                    </h2>

                    <p>
                        {{ __('ui.management_requests_description', [], null, 'Formal requests requiring management review or action.') }}
                    </p>

                </div>

            </div>


            <div class="formal-results-count">

                <span>
                    {{ $requests->total() }}
                </span>

                {{ __('ui.results', [], null, 'Results') }}

            </div>

        </div>


        {{-- Table --}}
        <div class="formal-table-wrapper">

            <table class="formal-table">

                <thead>

                    <tr>

                        <th>
                            {{ __('ui.document_number', [], null, 'Document Number') }}
                        </th>

                        <th>
                            {{ __('ui.institution', [], null, 'Institution') }}
                        </th>

                        <th>
                            {{ __('ui.title', [], null, 'Title') }}
                        </th>

                        <th>
                            {{ __('ui.type', [], null, 'Type') }}
                        </th>

                        <th>
                            {{ __('ui.status', [], null, 'Status') }}
                        </th>

                        <th>
                            {{ __('ui.priority', [], null, 'Priority') }}
                        </th>

                        <th class="formal-actions-column">
                            {{ __('ui.actions', [], null, 'Actions') }}
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($requests as $req)

                        @php
                            $statusData = $statusLabels[$req->current_status] ?? [
                                'label' => ucwords(str_replace('_', ' ', $req->current_status)),
                                'class' => 'status-default',
                                'icon'  => 'bi-circle',
                            ];

                            $priorityData = $priorityLabels[(int) $req->priority] ?? [
                                'label' => (string) $req->priority,
                                'class' => 'priority-default',
                                'icon'  => 'bi-circle',
                            ];

                            $requestTitle = $isArabic
                                ? ($req->title_ar ?: $req->title_en)
                                : ($req->title_en ?: $req->title_ar);

                            $requestType = __('ui.' . $req->request_type, [], null, ucwords(str_replace('_', ' ', $req->request_type)));

                            /*
                             * The current query does not load an institution relation,
                             * so keep the institution ID as a safe fallback.
                             */
                            $institutionName = $req->institution_id;
                        @endphp

                        <tr>

                            {{-- Document --}}
                            <td>

                                <div class="formal-document-number">
                                    <i class="bi bi-file-earmark"></i>
                                    <span>{{ $req->request_number }}</span>
                                </div>

                            </td>


                            {{-- Institution --}}
                            <td>

                                <div class="formal-institution-cell">

                                    <div class="formal-institution-icon">
                                        <i class="bi bi-building"></i>
                                    </div>

                                    <div>

                                        <span class="formal-institution-name">
                                            {{ $institutionName }}
                                        </span>

                                        <span class="formal-institution-id">
                                            ID: {{ $req->institution_id }}
                                        </span>

                                    </div>

                                </div>

                            </td>


                            {{-- Title --}}
                            <td>

                                <div class="formal-request-title">

                                    {{ $requestTitle }}

                                </div>

                            </td>


                            {{-- Type --}}
                            <td>

                                <span class="formal-type-badge">
                                    {{ $requestType }}
                                </span>

                            </td>


                            {{-- Status --}}
                            <td>

                                <span class="formal-status-badge {{ $statusData['class'] }}">

                                    <i class="bi {{ $statusData['icon'] }}"></i>

                                    {{ $statusData['label'] }}

                                </span>

                            </td>


                            {{-- Priority --}}
                            <td>

                                <span class="formal-priority {{ $priorityData['class'] }}">

                                    <i class="bi {{ $priorityData['icon'] }}"></i>

                                    {{ $priorityData['label'] }}

                                </span>

                            </td>


                            {{-- Actions --}}
                            <td>

                                <a
                                    href="{{ route('admin.formal-requests.review', $req->id) }}"
                                    class="formal-review-button"
                                >
                                    <i class="bi bi-eye"></i>

                                    <span>
                                        {{ __('ui.review', [], null, 'Review') }}
                                    </span>
                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="7">

                                <div class="formal-empty-state">

                                    <div class="formal-empty-icon">
                                        <i class="bi bi-inbox"></i>
                                    </div>

                                    <h3>
                                        {{ __('no_management_requests', [], null, 'No formal requests found') }}
                                    </h3>

                                    <p>
                                        {{ __('no_management_requests_description', [], null, 'There are currently no formal requests matching the selected filters.') }}
                                    </p>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- Pagination --}}
        @if($requests->hasPages())

            <div class="formal-pagination">

                {{ $requests->links() }}

            </div>

        @endif

    </div>


    {{-- =========================================================
         PAGE STYLES
    ========================================================== --}}
    <style>

        .formal-management-page {
            width: 100%;
        }


        /* =====================================================
           PAGE HEADER
        ====================================================== */

        .formal-page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--space-4, 1rem);
            margin-bottom: var(--space-6, 1.5rem);
        }

        .formal-header-content {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .formal-header-icon {
            width: 3.25rem;
            height: 3.25rem;
            flex: 0 0 3.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.875rem;
            background: var(--interactive-primary, #2563eb);
            color: #fff;
            font-size: 1.35rem;
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.18);
        }

        .formal-page-title {
            margin: 0;
            color: var(--text-primary, #111827);
            font-size: 1.5rem;
            line-height: 1.35;
            font-weight: 700;
        }

        .formal-page-subtitle {
            margin: 0.3rem 0 0;
            color: var(--text-secondary, #6b7280);
            font-size: 0.9rem;
            line-height: 1.6;
        }


        /* =====================================================
           FLASH
        ====================================================== */

        .formal-flash-message {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            padding: 0.85rem 1rem;
            border: 1px solid #bfdbfe;
            border-radius: 0.75rem;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .formal-flash-icon {
            width: 2rem;
            height: 2rem;
            flex: 0 0 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            background: #dbeafe;
        }

        .formal-flash-text {
            font-size: 0.875rem;
            font-weight: 500;
        }


        /* =====================================================
           FILTER CARD
        ====================================================== */

        .formal-filter-card,
        .formal-results-card {
            overflow: hidden;
            border: 1px solid var(--border-color, #e5e7eb);
            border-radius: 1rem;
            background: var(--surface-card, #fff);
            box-shadow: 0 4px 18px rgba(15, 23, 42, 0.04);
        }

        .formal-filter-card {
            margin-bottom: 1.25rem;
        }

        .formal-filter-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #eef0f3;
        }

        .formal-filter-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .formal-filter-title-icon {
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.7rem;
            background: #eff6ff;
            color: #2563eb;
            font-size: 1rem;
        }

        .formal-filter-title h2 {
            margin: 0;
            color: var(--text-primary, #111827);
            font-size: 0.95rem;
            font-weight: 700;
        }

        .formal-filter-title p {
            margin: 0.2rem 0 0;
            color: var(--text-secondary, #6b7280);
            font-size: 0.78rem;
        }

        .formal-filter-body {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
            padding: 1.25rem;
        }

        .formal-filter-field {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
        }

        .formal-filter-field label {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            color: var(--text-primary, #374151);
            font-size: 0.8rem;
            font-weight: 600;
        }

        .formal-filter-field label i {
            color: #64748b;
        }

        .formal-select {
            width: 100%;
            min-height: 2.6rem;
            padding: 0.55rem 0.75rem;
            border: 1px solid #d7dce3;
            border-radius: 0.65rem;
            background: #fff;
            color: #374151;
            font-size: 0.85rem;
            outline: none;
            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }

        .formal-select:focus {
            border-color: #93c5fd;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }


        /* =====================================================
           RESULTS HEADER
        ====================================================== */

        .formal-results-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #eef0f3;
        }

        .formal-results-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .formal-results-icon {
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.7rem;
            background: #f0fdf4;
            color: #16a34a;
        }

        .formal-results-title h2 {
            margin: 0;
            color: var(--text-primary, #111827);
            font-size: 0.95rem;
            font-weight: 700;
        }

        .formal-results-title p {
            margin: 0.2rem 0 0;
            color: var(--text-secondary, #6b7280);
            font-size: 0.78rem;
        }

        .formal-results-count {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.4rem 0.7rem;
            border-radius: 999px;
            background: #f8fafc;
            color: #64748b;
            font-size: 0.78rem;
            white-space: nowrap;
        }

        .formal-results-count span {
            color: #111827;
            font-weight: 700;
        }


        /* =====================================================
           TABLE
        ====================================================== */

        .formal-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .formal-table {
            width: 100%;
            min-width: 1050px;
            border-collapse: collapse;
        }

        .formal-table thead {
            background: #f8fafc;
        }

        .formal-table th {
            padding: 0.8rem 1rem;
            border-bottom: 1px solid #e5e7eb;
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 700;
            text-align: start;
            white-space: nowrap;
        }

        .formal-table tbody tr {
            transition: background 0.15s ease;
        }

        .formal-table tbody tr:hover {
            background: #f8fafc;
        }

        .formal-table td {
            padding: 0.95rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }


        /* =====================================================
           DOCUMENT
        ====================================================== */

        .formal-document-number {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            color: #475569;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .formal-document-number i {
            color: #94a3b8;
        }


        /* =====================================================
           INSTITUTION
        ====================================================== */

        .formal-institution-cell {
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }

        .formal-institution-icon {
            width: 2.15rem;
            height: 2.15rem;
            flex: 0 0 2.15rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.55rem;
            background: #f1f5f9;
            color: #475569;
        }

        .formal-institution-cell > div:last-child {
            min-width: 0;
        }

        .formal-institution-name {
            display: block;
            color: #334155;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .formal-institution-id {
            display: block;
            margin-top: 0.15rem;
            color: #94a3b8;
            font-size: 0.68rem;
        }


        /* =====================================================
           REQUEST TITLE
        ====================================================== */

        .formal-request-title {
            max-width: 260px;
            color: #1f2937;
            font-size: 0.82rem;
            font-weight: 600;
            line-height: 1.5;
        }


        /* =====================================================
           TYPE
        ====================================================== */

        .formal-type-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.6rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            background: #f8fafc;
            color: #475569;
            font-size: 0.72rem;
            font-weight: 600;
            white-space: nowrap;
        }


        /* =====================================================
           STATUS
        ====================================================== */

        .formal-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.6rem;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-submitted {
            background: #eff6ff;
            color: #2563eb;
        }

        .status-review {
            background: #f5f3ff;
            color: #7c3aed;
        }

        .status-clarification {
            background: #fffbeb;
            color: #d97706;
        }

        .status-accepted {
            background: #f0fdf4;
            color: #16a34a;
        }

        .status-rejected {
            background: #fef2f2;
            color: #dc2626;
        }

        .status-responded {
            background: #ecfeff;
            color: #0891b2;
        }

        .status-closed {
            background: #f1f5f9;
            color: #64748b;
        }

        .status-default {
            background: #f1f5f9;
            color: #475569;
        }


        /* =====================================================
           PRIORITY
        ====================================================== */

        .formal-priority {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .priority-low {
            color: #16a34a;
        }

        .priority-medium {
            color: #ca8a04;
        }

        .priority-high {
            color: #ea580c;
        }

        .priority-urgent {
            color: #dc2626;
        }

        .priority-default {
            color: #64748b;
        }


        /* =====================================================
           REVIEW BUTTON
        ====================================================== */

        .formal-review-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            min-height: 2.15rem;
            padding: 0.4rem 0.7rem;
            border: 1px solid #dbeafe;
            border-radius: 0.55rem;
            background: #eff6ff;
            color: #2563eb;
            font-size: 0.72rem;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
            transition:
                background 0.2s ease,
                border-color 0.2s ease,
                transform 0.2s ease;
        }

        .formal-review-button:hover {
            border-color: #bfdbfe;
            background: #dbeafe;
            color: #1d4ed8;
            transform: translateY(-1px);
        }


        /* =====================================================
           EMPTY
        ====================================================== */

        .formal-empty-state {
            display: flex;
            align-items: center;
            flex-direction: column;
            justify-content: center;
            padding: 4rem 1.5rem;
            text-align: center;
        }

        .formal-empty-icon {
            width: 4rem;
            height: 4rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            border-radius: 1rem;
            background: #f8fafc;
            color: #94a3b8;
            font-size: 1.5rem;
        }

        .formal-empty-state h3 {
            margin: 0;
            color: #334155;
            font-size: 0.95rem;
            font-weight: 700;
        }

        .formal-empty-state p {
            max-width: 480px;
            margin: 0.4rem 0 0;
            color: #94a3b8;
            font-size: 0.8rem;
            line-height: 1.6;
        }


        /* =====================================================
           PAGINATION
        ====================================================== */

        .formal-pagination {
            padding: 1rem 1.25rem;
            border-top: 1px solid #eef0f3;
        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 900px) {

            .formal-filter-body {
                grid-template-columns: 1fr;
            }

            .formal-results-header {
                align-items: flex-start;
                flex-direction: column;
            }

        }


        @media (max-width: 640px) {

            .formal-header-content {
                align-items: flex-start;
            }

            .formal-header-icon {
                width: 2.75rem;
                height: 2.75rem;
                flex-basis: 2.75rem;
                font-size: 1.1rem;
            }

            .formal-page-title {
                font-size: 1.2rem;
            }

            .formal-page-subtitle {
                font-size: 0.8rem;
            }

            .formal-filter-header,
            .formal-filter-body,
            .formal-results-header,
            .formal-pagination {
                padding-inline: 0.9rem;
            }

        }

    </style>

</div>

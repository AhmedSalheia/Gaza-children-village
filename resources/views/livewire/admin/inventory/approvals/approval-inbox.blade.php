<div class="inventory-approvals-page">

    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div class="approvals-header">

        <div class="approvals-header-main">

            <div class="approvals-header-icon">

                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M9 11l3 3L20 6"/>
                    <path d="M20 12v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h9"/>
                </svg>

            </div>

            <div>

                <div class="approvals-eyebrow">
                    GCV INVENTORY / WORKFLOW
                </div>

                <h1>
                    {{ __('ui.inventory.approvals') }}
                </h1>

                <p>
                    {{ __('ui.inventory.approval_chain') }}
                </p>

            </div>

        </div>

        <div class="approvals-header-count">

            <span class="count-number">
                {{ $steps->count() }}
            </span>

            <span class="count-label">
                {{ __('ui.inventory.count') }}
            </span>

        </div>

    </div>


    {{-- =========================================================
         NAVIGATION
    ========================================================== --}}
    @include('livewire.admin.inventory._nav')


    {{-- =========================================================
         SUMMARY
    ========================================================== --}}
    <div class="approvals-summary-grid">

        <div class="approval-summary-card">

            <div class="approval-summary-icon pending">

                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M12 7v5l3 2"/>
                </svg>

            </div>

            <div>

                <span>
                    {{ __('ui.inventory.approvals') }}
                </span>

                <strong>
                    {{ $steps->count() }}
                </strong>

            </div>

        </div>


        <div class="approval-summary-card">

            <div class="approval-summary-icon requests">

                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M5 4h14v16H5z"/>
                    <path d="M8 8h8"/>
                    <path d="M8 12h8"/>
                    <path d="M8 16h5"/>
                </svg>

            </div>

            <div>

                <span>
                    {{ __('ui.inventory.requests') }}
                </span>

                <strong>
                    {{ $steps->pluck('request_id')->unique()->count() }}
                </strong>

            </div>

        </div>


        <div class="approval-summary-card">

            <div class="approval-summary-icon workflow">

                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M8 6h12"/>
                    <path d="M8 12h12"/>
                    <path d="M8 18h12"/>
                    <circle cx="4" cy="6" r="1"/>
                    <circle cx="4" cy="12" r="1"/>
                    <circle cx="4" cy="18" r="1"/>
                </svg>

            </div>

            <div>

                <span>
                    {{ __('ui.inventory.approval_chain') }}
                </span>

                <strong>
                    {{ $steps->pluck('approver_role')->unique()->count() }}
                </strong>

            </div>

        </div>

    </div>


    {{-- =========================================================
         APPROVAL LIST
    ========================================================== --}}
    <div class="approvals-list-card">

        <div class="approvals-list-header">

            <div class="list-heading">

                <div class="list-heading-icon">

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M4 5h16v14H4z"/>
                        <path d="M8 9h8"/>
                        <path d="M8 13h8"/>
                        <path d="M8 17h5"/>
                    </svg>

                </div>

                <div>

                    <div class="list-heading-kicker">
                        GCV DATA
                    </div>

                    <h2>
                        {{ __('ui.inventory.no_pending_approvals') }}
                    </h2>

                </div>

            </div>

            <span class="list-count">
                {{ $steps->count() }}
            </span>

        </div>


        @forelse($steps as $s)

            <div class="approval-request-card">

                {{-- =================================================
                     STEP INDICATOR
                ================================================== --}}
                <div class="approval-step-indicator">

                    <span>
                        {{ $s->step_order ?? $loop->iteration }}
                    </span>

                </div>


                {{-- =================================================
                     MAIN CONTENT
                ================================================== --}}
                <div class="approval-request-main">

                    <div class="approval-request-top">

                        <div class="approval-request-title">

                            <div class="request-number-label">
                                {{ __('ui.inventory.request_number') }}
                            </div>

                            <strong>
                                {{ $s->request?->request_number ?? '—' }}
                            </strong>

                        </div>


                        <span class="approval-pending-badge">

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <circle cx="12" cy="12" r="9"/>
                                <path d="M12 7v5l3 2"/>
                            </svg>

                            {{ __('ui.inventory.approvals') }}

                        </span>

                    </div>


                    {{-- =================================================
                         REQUEST INFORMATION
                    ================================================== --}}
                    <div class="approval-request-info">

                        <div class="approval-info-item">

                            <div class="approval-info-icon institution">

                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="m3 10 9-6 9 6"/>
                                    <path d="M5 10v10"/>
                                    <path d="M9 10v10"/>
                                    <path d="M15 10v10"/>
                                    <path d="M19 10v10"/>
                                    <path d="M3 20h18"/>
                                </svg>

                            </div>

                            <div>

                                <span>
                                    {{ __('ui.inventory.institution') }}
                                </span>

                                <strong>
                                    {{ $s->request?->institution?->name_ar ?? '—' }}
                                </strong>

                            </div>

                        </div>


                        <div class="approval-info-item">

                            <div class="approval-info-icon role">

                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <circle cx="12" cy="8" r="4"/>
                                    <path d="M4 21c.8-4 3.4-6 8-6s7.2 2 8 6"/>
                                </svg>

                            </div>

                            <div>

                                <span>
                                    {{ __('ui.inventory.approver_role') }}
                                </span>

                                <strong>
                                    {{ $s->approver_role ?? '—' }}
                                </strong>

                            </div>

                        </div>


                        <div class="approval-info-item">

                            <div class="approval-info-icon step">

                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M8 6h12"/>
                                    <path d="M8 12h12"/>
                                    <path d="M8 18h12"/>
                                    <circle cx="4" cy="6" r="1"/>
                                    <circle cx="4" cy="12" r="1"/>
                                    <circle cx="4" cy="18" r="1"/>
                                </svg>

                            </div>

                            <div>

                                <span>
                                    {{ __('ui.inventory.approval_chain') }}
                                </span>

                                <strong>
                                    {{ $s->step_order ?? $loop->iteration }}
                                </strong>

                            </div>

                        </div>

                    </div>


                    {{-- =================================================
                         ACTIONS
                    ================================================== --}}
                    <div class="approval-request-actions">

                        <div class="approval-action-label">

                            <span class="action-dot"></span>

                            {{ __('ui.inventory.approvals') }}

                        </div>


                        <div class="approval-buttons">

                            <button
                                type="button"
                                class="approval-btn approve"
                                wire:click="decide({{ $s->id }}, 'approved')"
                                wire:loading.attr="disabled"
                                wire:target="decide({{ $s->id }}, 'approved')"
                            >

                                <span
                                    wire:loading.remove
                                    wire:target="decide({{ $s->id }}, 'approved')"
                                >

                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="m5 12 4 4L19 6"/>
                                    </svg>

                                    {{ __('ui.inventory.approve') }}

                                </span>

                                <span
                                    wire:loading
                                    wire:target="decide({{ $s->id }}, 'approved')"
                                >
                                    ...
                                </span>

                            </button>


                            <button
                                type="button"
                                class="approval-btn reject"
                                wire:click="decide({{ $s->id }}, 'rejected')"
                                wire:loading.attr="disabled"
                                wire:target="decide({{ $s->id }}, 'rejected')"
                            >

                                <span
                                    wire:loading.remove
                                    wire:target="decide({{ $s->id }}, 'rejected')"
                                >

                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="m7 7 10 10"/>
                                        <path d="m17 7-10 10"/>
                                    </svg>

                                    {{ __('ui.inventory.reject') }}

                                </span>

                                <span
                                    wire:loading
                                    wire:target="decide({{ $s->id }}, 'rejected')"
                                >
                                    ...
                                </span>

                            </button>


                            <a
                                href="{{ route('admin.inventory.requests.show', $s->request) }}"
                                wire:navigate
                                class="approval-btn view"
                            >

                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z"/>
                                    <circle cx="12" cy="12" r="2.5"/>
                                </svg>

                                {{ __('ui.inventory.view') }}

                            </a>

                        </div>

                    </div>

                </div>

            </div>

        @empty

            {{-- =================================================
                 EMPTY STATE
            ================================================== --}}
            <div class="approvals-empty">

                <div class="approvals-empty-icon">

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M9 11l3 3L20 6"/>
                        <path d="M20 12v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h9"/>
                    </svg>

                </div>

                <h3>
                    {{ __('ui.inventory.no_pending_approvals') }}
                </h3>

                <p>
                    {{ __('ui.inventory.no_data') }}
                </p>

            </div>

        @endforelse

    </div>


    {{-- =========================================================
         STYLES
    ========================================================== --}}
    <style>

        .inventory-approvals-page {
            direction: rtl;
            width: 100%;
            max-width: 1600px;
            margin: 0 auto;
            padding: 8px 0 45px;
        }


        /* =====================================================
           HEADER
        ====================================================== */

        .approvals-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 25px 28px;
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 22px;
            background: linear-gradient(
                135deg,
                #ffffff,
                #f8fafc
            );
            box-shadow: 0 10px 35px rgba(15,23,42,.06);
        }

        .approvals-header-main {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .approvals-header-icon {
            width: 58px;
            height: 58px;
            flex: 0 0 58px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 17px;
            background: #111827;
            color: #fff;
            box-shadow: 0 8px 20px rgba(17,24,39,.18);
        }

        .approvals-header-icon svg {
            width: 28px;
            height: 28px;
        }

        .approvals-eyebrow {
            margin-bottom: 4px;
            color: #9ca3af;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .14em;
            direction: ltr;
        }

        .approvals-header h1 {
            margin: 0;
            color: #111827;
            font-size: 23px;
            font-weight: 900;
        }

        .approvals-header p {
            margin: 5px 0 0;
            color: #6b7280;
            font-size: 12px;
        }

        .approvals-header-count {
            min-width: 80px;
            padding: 10px 14px;
            border: 1px solid #e5e7eb;
            border-radius: 13px;
            background: #fff;
            text-align: center;
        }

        .count-number {
            display: block;
            color: #111827;
            font-size: 21px;
            font-weight: 900;
            line-height: 1.1;
        }

        .count-label {
            display: block;
            margin-top: 3px;
            color: #9ca3af;
            font-size: 9px;
            font-weight: 750;
        }


        /* =====================================================
           SUMMARY
        ====================================================== */

        .approvals-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin: 20px 0;
        }

        .approval-summary-card {
            min-height: 88px;
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 16px;
            border: 1px solid #e5e7eb;
            border-radius: 17px;
            background: #fff;
            box-shadow: 0 7px 24px rgba(15,23,42,.045);
        }

        .approval-summary-icon {
            width: 44px;
            height: 44px;
            flex: 0 0 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }

        .approval-summary-icon svg {
            width: 21px;
            height: 21px;
        }

        .approval-summary-icon.pending {
            background: #fffbeb;
            color: #b45309;
        }

        .approval-summary-icon.requests {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .approval-summary-icon.workflow {
            background: #f5f3ff;
            color: #6d28d9;
        }

        .approval-summary-card span {
            display: block;
            color: #6b7280;
            font-size: 10px;
            font-weight: 750;
        }

        .approval-summary-card strong {
            display: block;
            margin-top: 3px;
            color: #111827;
            font-size: 18px;
            font-weight: 900;
        }


        /* =====================================================
           LIST CARD
        ====================================================== */

        .approvals-list-card {
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 8px 28px rgba(15,23,42,.045);
            overflow: hidden;
        }

        .approvals-list-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 19px 22px;
            border-bottom: 1px solid #eef0f2;
        }

        .list-heading {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .list-heading-icon {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #f3f4f6;
            color: #374151;
        }

        .list-heading-icon svg {
            width: 20px;
            height: 20px;
        }

        .list-heading-kicker {
            margin-bottom: 3px;
            color: #9ca3af;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: .14em;
            direction: ltr;
        }

        .list-heading h2 {
            margin: 0;
            color: #111827;
            font-size: 15px;
            font-weight: 900;
        }

        .list-count {
            min-width: 35px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 9px;
            border-radius: 9px;
            background: #f3f4f6;
            color: #374151;
            font-size: 11px;
            font-weight: 900;
        }


        /* =====================================================
           APPROVAL CARD
        ====================================================== */

        .approval-request-card {
            position: relative;
            display: flex;
            gap: 16px;
            padding: 21px 22px;
            border-bottom: 1px solid #eef0f2;
            transition: background .2s ease;
        }

        .approval-request-card:last-child {
            border-bottom: 0;
        }

        .approval-request-card:hover {
            background: #fcfcfd;
        }

        .approval-step-indicator {
            width: 40px;
            flex: 0 0 40px;
            display: flex;
            justify-content: center;
        }

        .approval-step-indicator span {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #fde68a;
            border-radius: 50%;
            background: #fffbeb;
            color: #b45309;
            font-size: 11px;
            font-weight: 900;
        }

        .approval-request-main {
            flex: 1;
            min-width: 0;
        }

        .approval-request-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 15px;
        }

        .request-number-label {
            margin-bottom: 4px;
            color: #9ca3af;
            font-size: 9px;
            font-weight: 800;
        }

        .approval-request-title strong {
            display: block;
            color: #111827;
            font-size: 14px;
            font-weight: 900;
            direction: ltr;
            text-align: right;
        }

        .approval-pending-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            min-height: 28px;
            padding: 4px 9px;
            border-radius: 999px;
            background: #fffbeb;
            color: #b45309;
            font-size: 9px;
            font-weight: 850;
            white-space: nowrap;
        }

        .approval-pending-badge svg {
            width: 14px;
            height: 14px;
        }


        /* =====================================================
           INFO
        ====================================================== */

        .approval-request-info {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .approval-info-item {
            display: flex;
            align-items: center;
            gap: 9px;
            min-width: 0;
            padding: 10px;
            border: 1px solid #eef0f2;
            border-radius: 11px;
            background: #fafafa;
        }

        .approval-info-icon {
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
        }

        .approval-info-icon svg {
            width: 16px;
            height: 16px;
        }

        .approval-info-icon.institution {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .approval-info-icon.role {
            background: #f5f3ff;
            color: #6d28d9;
        }

        .approval-info-icon.step {
            background: #ecfdf5;
            color: #047857;
        }

        .approval-info-item > div:last-child {
            min-width: 0;
        }

        .approval-info-item span {
            display: block;
            margin-bottom: 3px;
            color: #9ca3af;
            font-size: 9px;
            font-weight: 750;
        }

        .approval-info-item strong {
            display: block;
            color: #374151;
            font-size: 10px;
            font-weight: 850;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }


        /* =====================================================
           ACTIONS
        ====================================================== */

        .approval-request-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-top: 14px;
            padding-top: 13px;
            border-top: 1px solid #eef0f2;
        }

        .approval-action-label {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #6b7280;
            font-size: 10px;
            font-weight: 800;
        }

        .action-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #f59e0b;
            box-shadow: 0 0 0 4px #fffbeb;
        }

        .approval-buttons {
            display: flex;
            align-items: center;
            gap: 7px;
            flex-wrap: wrap;
        }

        .approval-btn {
            min-height: 35px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 0 12px;
            border: 0;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 850;
            text-decoration: none !important;
            cursor: pointer;
            transition: .2s ease;
        }

        .approval-btn svg {
            width: 14px;
            height: 14px;
        }

        .approval-btn.approve {
            background: #ecfdf5;
            color: #047857;
        }

        .approval-btn.approve:hover {
            background: #d1fae5;
        }

        .approval-btn.reject {
            background: #fff1f2;
            color: #be123c;
        }

        .approval-btn.reject:hover {
            background: #ffe4e6;
        }

        .approval-btn.view {
            background: #f3f4f6;
            color: #374151;
        }

        .approval-btn.view:hover {
            background: #e5e7eb;
        }

        .approval-btn:disabled {
            opacity: .55;
            cursor: wait;
        }


        /* =====================================================
           EMPTY
        ====================================================== */

        .approvals-empty {
            min-height: 330px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 35px 20px;
            text-align: center;
        }

        .approvals-empty-icon {
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            border-radius: 20px;
            background: #ecfdf5;
            color: #047857;
        }

        .approvals-empty-icon svg {
            width: 34px;
            height: 34px;
        }

        .approvals-empty h3 {
            margin: 0;
            color: #374151;
            font-size: 15px;
            font-weight: 900;
        }

        .approvals-empty p {
            margin: 7px 0 0;
            color: #9ca3af;
            font-size: 11px;
        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 1000px) {

            .approvals-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .approval-request-info {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 700px) {

            .approvals-header {
                flex-direction: column;
                align-items: stretch;
                padding: 20px;
            }

            .approvals-header-count {
                width: 100%;
                box-sizing: border-box;
            }

            .approvals-summary-grid {
                grid-template-columns: 1fr;
            }

            .approval-request-card {
                gap: 10px;
                padding: 17px 15px;
            }

            .approval-step-indicator {
                width: 32px;
                flex-basis: 32px;
            }

            .approval-step-indicator span {
                width: 30px;
                height: 30px;
                font-size: 9px;
            }

            .approval-request-top {
                align-items: flex-start;
                flex-direction: column;
            }

            .approval-request-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .approval-buttons {
                width: 100%;
            }

            .approval-btn {
                flex: 1;
            }

        }


        @media (max-width: 450px) {

            .approvals-header-main {
                align-items: flex-start;
            }

            .approvals-header-icon {
                width: 48px;
                height: 48px;
                flex-basis: 48px;
            }

            .approvals-header h1 {
                font-size: 18px;
            }

            .approval-buttons {
                display: grid;
                grid-template-columns: 1fr;
            }

            .approval-btn {
                width: 100%;
            }

        }

    </style>

</div>

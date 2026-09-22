@php
    /**
     * GCV Inventory Dashboard
     *
     * Livewire:
     * App\Livewire\Admin\Inventory\InventoryDashboard
     */
@endphp

<div class="inventory-dashboard-page">

    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div class="inventory-dashboard-header">

        <div class="inventory-dashboard-header-content">

            <div class="inventory-dashboard-icon">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M3 7.5 12 3l9 4.5v9L12 21l-9-4.5v-9Z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M3 7.5 12 12l9-4.5M12 12v9"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linejoin="round"
                    />
                </svg>
            </div>

            <div>
                <div class="inventory-dashboard-eyebrow">
                    GCV DATA
                </div>

                <h1 class="inventory-dashboard-title">
                    {{ __('ui.inventory.title') }}
                </h1>

                <p class="inventory-dashboard-description">
                    {{ __('ui.inventory.description') }}
                </p>
            </div>

        </div>

        <div class="inventory-dashboard-header-actions">

            <a
                href="{{ route('admin.inventory.items.index') }}"
                wire:navigate
                class="inventory-dashboard-btn inventory-dashboard-btn-secondary"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21V5.5Z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M4 18.5A2.5 2.5 0 0 1 6.5 16H20"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                    />
                </svg>

                <span>
                    {{ __('ui.inventory.items') }}
                </span>
            </a>

            <a
                href="{{ route('admin.inventory.warehouses.index') }}"
                wire:navigate
                class="inventory-dashboard-btn inventory-dashboard-btn-primary"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M3 10.5 12 4l9 6.5V20H3V10.5Z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M8 20v-6h8v6M7 10h.01M12 10h.01M17 10h.01"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                    />
                </svg>

                <span>
                    {{ __('ui.inventory.warehouses') }}
                </span>
            </a>

        </div>

    </div>


    {{-- =========================================================
         INVENTORY NAVIGATION
    ========================================================== --}}
    @include('livewire.admin.inventory._nav')


    {{-- =========================================================
         STATISTICS
    ========================================================== --}}
    <section class="inventory-dashboard-stats">

        {{-- Items --}}
        <a
            href="{{ route('admin.inventory.items.index') }}"
            wire:navigate
            class="inventory-dashboard-stat"
        >

            <div class="inventory-dashboard-stat-icon">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M4 7.5 12 4l8 3.5v9L12 20l-8-3.5v-9Z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M4 7.5 12 11l8-3.5M12 11v9"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                    />
                </svg>
            </div>

            <div class="inventory-dashboard-stat-content">

                <span>
                    {{ __('ui.inventory.items') }}
                </span>

                <strong>
                    {{ number_format($items) }}
                </strong>

                <small>
                    {{ __('ui.inventory.view') }}
                </small>

            </div>

        </a>


        {{-- Warehouses --}}
        <a
            href="{{ route('admin.inventory.warehouses.index') }}"
            wire:navigate
            class="inventory-dashboard-stat"
        >

            <div class="inventory-dashboard-stat-icon">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M3 10.5 12 4l9 6.5V20H3V10.5Z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M8 20v-6h8v6"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                    />
                </svg>
            </div>

            <div class="inventory-dashboard-stat-content">

                <span>
                    {{ __('ui.inventory.warehouses') }}
                </span>

                <strong>
                    {{ number_format($warehouses) }}
                </strong>

                <small>
                    {{ __('ui.inventory.all_warehouses') }}
                </small>

            </div>

        </a>


        {{-- Low Stock --}}
        <a
            href="{{ route('admin.inventory.stock.index') }}"
            wire:navigate
            class="inventory-dashboard-stat inventory-dashboard-stat-warning"
        >

            <div class="inventory-dashboard-stat-icon">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M12 4 21 20H3L12 4Z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M12 9v5M12 17h.01"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                    />
                </svg>
            </div>

            <div class="inventory-dashboard-stat-content">

                <span>
                    {{ __('ui.inventory.low_stock') }}
                </span>

                <strong>
                    {{ number_format($lowStock) }}
                </strong>

                <small>
                    {{ __('ui.inventory.stock') }}
                </small>

            </div>

        </a>


        {{-- Pending Approvals --}}
        <a
            href="{{ route('admin.inventory.approvals.index') }}"
            wire:navigate
            class="inventory-dashboard-stat"
        >

            <div class="inventory-dashboard-stat-icon">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M6 3h12v18H6z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linejoin="round"
                    />
                    <path
                        d="m9 12 2 2 4-4"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </div>

            <div class="inventory-dashboard-stat-content">

                <span>
                    {{ __('ui.inventory.pending_approvals') }}
                </span>

                <strong>
                    {{ number_format($pending) }}
                </strong>

                <small>
                    {{ __('ui.inventory.approvals') }}
                </small>

            </div>

        </a>

    </section>


    {{-- =========================================================
         QUICK ACTIONS
    ========================================================== --}}
    <section class="inventory-dashboard-main-grid">

        {{-- Main quick actions --}}
        <div class="inventory-dashboard-card">

            <div class="inventory-dashboard-card-header">

                <div>
                    <span class="inventory-dashboard-section-label">
                        GCV INVENTORY
                    </span>

                    <h2>
                        {{ __('ui.inventory.movement_summary') }}
                    </h2>

                    <p>
                        {{ __('ui.inventory.description') }}
                    </p>
                </div>

                <div class="inventory-dashboard-card-header-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M4 7h16M4 12h16M4 17h16"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                        />
                    </svg>
                </div>

            </div>


            <div class="inventory-dashboard-actions-grid">

                {{-- New movement --}}
                <a
                    href="{{ route('admin.inventory.movements.index') }}"
                    wire:navigate
                    class="inventory-dashboard-action"
                >

                    <div class="inventory-dashboard-action-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path
                                d="M12 5v14M5 12h14"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                            />
                        </svg>
                    </div>

                    <div>
                        <strong>
                            {{ __('ui.inventory.new_movement') }}
                        </strong>

                        <span>
                            {{ __('ui.inventory.movements') }}
                        </span>
                    </div>

                    <svg
                        class="inventory-dashboard-action-arrow"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            d="m9 18 6-6-6-6"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>

                </a>


                {{-- Requests --}}
                <a
                    href="{{ route('admin.inventory.requests.index') }}"
                    wire:navigate
                    class="inventory-dashboard-action"
                >

                    <div class="inventory-dashboard-action-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path
                                d="M6 3h12v18H6z"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linejoin="round"
                            />
                            <path
                                d="M9 8h6M9 12h6M9 16h3"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                            />
                        </svg>
                    </div>

                    <div>
                        <strong>
                            {{ __('ui.inventory.requests') }}
                        </strong>

                        <span>
                            {{ __('ui.inventory.new_issue_request') }}
                        </span>
                    </div>

                    <svg
                        class="inventory-dashboard-action-arrow"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            d="m9 18 6-6-6-6"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>

                </a>


                {{-- Approvals --}}
                <a
                    href="{{ route('admin.inventory.approvals.index') }}"
                    wire:navigate
                    class="inventory-dashboard-action"
                >

                    <div class="inventory-dashboard-action-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path
                                d="M5 12.5 9.5 17 19 7.5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>

                    <div>
                        <strong>
                            {{ __('ui.inventory.approvals') }}
                        </strong>

                        <span>
                            {{ __('ui.inventory.pending_approvals') }}
                        </span>
                    </div>

                    <svg
                        class="inventory-dashboard-action-arrow"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            d="m9 18 6-6-6-6"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>

                </a>


                {{-- Reallocation --}}
                <a
                    href="{{ route('admin.inventory.reallocation.index') }}"
                    wire:navigate
                    class="inventory-dashboard-action"
                >

                    <div class="inventory-dashboard-action-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path
                                d="M7 7h10l-2.5-2.5M17 17H7l2.5 2.5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                            <path
                                d="M17 7v3M7 17v-3"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                            />
                        </svg>
                    </div>

                    <div>
                        <strong>
                            {{ __('ui.inventory.reallocation') }}
                        </strong>

                        <span>
                            {{ __('ui.inventory.reallocation_description') }}
                        </span>
                    </div>

                    <svg
                        class="inventory-dashboard-action-arrow"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            d="m9 18 6-6-6-6"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>

                </a>

            </div>

        </div>


        {{-- Inventory overview --}}
        <div class="inventory-dashboard-card inventory-dashboard-overview-card">

            <div class="inventory-dashboard-card-header">

                <div>
                    <span class="inventory-dashboard-section-label">
                        {{ __('ui.inventory.stock') }}
                    </span>

                    <h2>
                        {{ __('ui.inventory.stock') }}
                    </h2>
                </div>

                <div class="inventory-dashboard-card-header-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M4 5h16v14H4z"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linejoin="round"
                        />
                        <path
                            d="M8 9h8M8 13h8M8 17h4"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                        />
                    </svg>
                </div>

            </div>


            <div class="inventory-dashboard-overview-list">

                <a
                    href="{{ route('admin.inventory.stock.index') }}"
                    wire:navigate
                    class="inventory-dashboard-overview-item"
                >
                    <span>
                        {{ __('ui.inventory.stock') }}
                    </span>

                    <span class="inventory-dashboard-overview-arrow">
                        ←
                    </span>
                </a>

                <a
                    href="{{ route('admin.inventory.movements.index') }}"
                    wire:navigate
                    class="inventory-dashboard-overview-item"
                >
                    <span>
                        {{ __('ui.inventory.movements') }}
                    </span>

                    <span class="inventory-dashboard-overview-arrow">
                        ←
                    </span>
                </a>

                <a
                    href="{{ route('admin.inventory.counts.index') }}"
                    wire:navigate
                    class="inventory-dashboard-overview-item"
                >
                    <span>
                        {{ __('ui.inventory.counts') }}
                    </span>

                    <span class="inventory-dashboard-overview-arrow">
                        ←
                    </span>
                </a>

                <a
                    href="{{ route('admin.inventory.reports.index') }}"
                    wire:navigate
                    class="inventory-dashboard-overview-item"
                >
                    <span>
                        {{ __('ui.inventory.reports') }}
                    </span>

                    <span class="inventory-dashboard-overview-arrow">
                        ←
                    </span>
                </a>

            </div>

        </div>

    </section>


    {{-- =========================================================
         LOADING
    ========================================================== --}}
    <div
        wire:loading
        class="inventory-dashboard-loading"
    >
        <span class="inventory-dashboard-loading-dot"></span>

        <span>
            {{ __('ui.inventory.save') }}...
        </span>
    </div>


    {{-- =========================================================
         STYLES
    ========================================================== --}}
    <style>
        .inventory-dashboard-page {
            direction: rtl;
            width: 100%;
            color: #172033;
        }

        .inventory-dashboard-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 24px;
            padding: 24px 26px;
            border: 1px solid #e7eaf0;
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 8px 28px rgba(15, 23, 42, 0.045);
        }

        .inventory-dashboard-header-content {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
        }

        .inventory-dashboard-icon {
            width: 58px;
            height: 58px;
            flex: 0 0 58px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            background: #eef4ff;
            color: #315efb;
        }

        .inventory-dashboard-icon svg {
            width: 30px;
            height: 30px;
        }

        .inventory-dashboard-eyebrow {
            margin-bottom: 4px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
            color: #7b8497;
        }

        .inventory-dashboard-title {
            margin: 0;
            font-size: 26px;
            line-height: 1.25;
            font-weight: 800;
            color: #172033;
        }

        .inventory-dashboard-description {
            margin: 7px 0 0;
            max-width: 760px;
            color: #697386;
            font-size: 14px;
            line-height: 1.8;
        }

        .inventory-dashboard-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .inventory-dashboard-btn {
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 15px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            transition:
                transform .18s ease,
                box-shadow .18s ease,
                background .18s ease;
        }

        .inventory-dashboard-btn svg {
            width: 18px;
            height: 18px;
        }

        .inventory-dashboard-btn:hover {
            transform: translateY(-1px);
        }

        .inventory-dashboard-btn-primary {
            background: #315efb;
            color: #ffffff;
            box-shadow: 0 7px 18px rgba(49, 94, 251, .18);
        }

        .inventory-dashboard-btn-primary:hover {
            background: #264edb;
            color: #ffffff;
        }

        .inventory-dashboard-btn-secondary {
            border: 1px solid #e1e5ec;
            background: #ffffff;
            color: #344054;
        }

        .inventory-dashboard-btn-secondary:hover {
            background: #f8f9fb;
            color: #172033;
        }


        /* Navigation */
        .inventory-dashboard-page .inventory-nav {
            margin-bottom: 24px;
        }


        /* Statistics */
        .inventory-dashboard-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .inventory-dashboard-stat {
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 20px;
            border: 1px solid #e7eaf0;
            border-radius: 16px;
            background: #ffffff;
            text-decoration: none;
            box-shadow: 0 6px 22px rgba(15, 23, 42, 0.035);
            transition:
                transform .18s ease,
                box-shadow .18s ease,
                border-color .18s ease;
        }

        .inventory-dashboard-stat:hover {
            transform: translateY(-2px);
            border-color: #d8deea;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.075);
        }

        .inventory-dashboard-stat-icon {
            width: 48px;
            height: 48px;
            flex: 0 0 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 13px;
            background: #f0f4ff;
            color: #315efb;
        }

        .inventory-dashboard-stat-icon svg {
            width: 24px;
            height: 24px;
        }

        .inventory-dashboard-stat-content {
            min-width: 0;
        }

        .inventory-dashboard-stat-content span {
            display: block;
            margin-bottom: 4px;
            color: #727b8e;
            font-size: 13px;
            font-weight: 600;
        }

        .inventory-dashboard-stat-content strong {
            display: block;
            color: #172033;
            font-size: 25px;
            line-height: 1.2;
            font-weight: 800;
        }

        .inventory-dashboard-stat-content small {
            display: block;
            margin-top: 5px;
            color: #98a1b2;
            font-size: 11px;
        }

        .inventory-dashboard-stat-warning .inventory-dashboard-stat-icon {
            background: #fff7e8;
            color: #d58a00;
        }


        /* Main content */
        .inventory-dashboard-main-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.65fr) minmax(280px, .85fr);
            gap: 18px;
        }

        .inventory-dashboard-card {
            min-width: 0;
            padding: 22px;
            border: 1px solid #e7eaf0;
            border-radius: 17px;
            background: #ffffff;
            box-shadow: 0 7px 25px rgba(15, 23, 42, 0.04);
        }

        .inventory-dashboard-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding-bottom: 18px;
            margin-bottom: 18px;
            border-bottom: 1px solid #edf0f4;
        }

        .inventory-dashboard-section-label {
            display: block;
            margin-bottom: 5px;
            color: #315efb;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .07em;
        }

        .inventory-dashboard-card-header h2 {
            margin: 0;
            color: #172033;
            font-size: 18px;
            font-weight: 800;
        }

        .inventory-dashboard-card-header p {
            margin: 6px 0 0;
            color: #7a8394;
            font-size: 12px;
            line-height: 1.7;
        }

        .inventory-dashboard-card-header-icon {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            background: #f5f7fa;
            color: #667085;
        }

        .inventory-dashboard-card-header-icon svg {
            width: 21px;
            height: 21px;
        }


        /* Quick actions */
        .inventory-dashboard-actions-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .inventory-dashboard-action {
            position: relative;
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            border: 1px solid #e9ecf1;
            border-radius: 13px;
            background: #fafbfc;
            text-decoration: none;
            transition:
                background .18s ease,
                border-color .18s ease,
                transform .18s ease;
        }

        .inventory-dashboard-action:hover {
            background: #ffffff;
            border-color: #cfd7e5;
            transform: translateY(-1px);
        }

        .inventory-dashboard-action-icon {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            background: #eef3ff;
            color: #315efb;
        }

        .inventory-dashboard-action-icon svg {
            width: 21px;
            height: 21px;
        }

        .inventory-dashboard-action > div:nth-child(2) {
            min-width: 0;
        }

        .inventory-dashboard-action strong {
            display: block;
            color: #263044;
            font-size: 13px;
            font-weight: 800;
        }

        .inventory-dashboard-action span {
            display: block;
            margin-top: 4px;
            color: #8a93a4;
            font-size: 11px;
            line-height: 1.5;
        }

        .inventory-dashboard-action-arrow {
            width: 17px;
            height: 17px;
            margin-inline-start: auto;
            flex: 0 0 17px;
            color: #9aa3b3;
        }


        /* Overview */
        .inventory-dashboard-overview-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .inventory-dashboard-overview-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            min-height: 48px;
            padding: 0 13px;
            border: 1px solid #edf0f4;
            border-radius: 11px;
            background: #fafbfc;
            color: #344054;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            transition:
                background .18s ease,
                border-color .18s ease,
                color .18s ease;
        }

        .inventory-dashboard-overview-item:hover {
            background: #ffffff;
            border-color: #d5dce7;
            color: #315efb;
        }

        .inventory-dashboard-overview-arrow {
            color: #98a1b2;
            font-size: 16px;
            transition: transform .18s ease;
        }

        .inventory-dashboard-overview-item:hover .inventory-dashboard-overview-arrow {
            transform: translateX(-3px);
            color: #315efb;
        }


        /* Loading */
        .inventory-dashboard-loading {
            position: fixed;
            left: 24px;
            bottom: 24px;
            z-index: 100;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 13px;
            border: 1px solid #e5e8ee;
            border-radius: 10px;
            background: rgba(255, 255, 255, .96);
            color: #667085;
            font-size: 11px;
            box-shadow: 0 8px 25px rgba(15, 23, 42, .09);
        }

        .inventory-dashboard-loading-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #315efb;
            animation: inventoryDashboardPulse 1s infinite ease-in-out;
        }

        @keyframes inventoryDashboardPulse {
            0%, 100% {
                opacity: .35;
                transform: scale(.85);
            }

            50% {
                opacity: 1;
                transform: scale(1);
            }
        }


        /* Responsive */
        @media (max-width: 1100px) {

            .inventory-dashboard-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .inventory-dashboard-main-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 760px) {

            .inventory-dashboard-header {
                flex-direction: column;
                align-items: stretch;
            }

            .inventory-dashboard-header-actions {
                width: 100%;
            }

            .inventory-dashboard-btn {
                flex: 1;
            }

            .inventory-dashboard-stats {
                grid-template-columns: 1fr;
            }

            .inventory-dashboard-actions-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 520px) {

            .inventory-dashboard-header {
                padding: 18px;
                border-radius: 14px;
            }

            .inventory-dashboard-header-content {
                align-items: flex-start;
            }

            .inventory-dashboard-icon {
                width: 48px;
                height: 48px;
                flex-basis: 48px;
            }

            .inventory-dashboard-title {
                font-size: 21px;
            }

            .inventory-dashboard-description {
                font-size: 12px;
            }

            .inventory-dashboard-header-actions {
                flex-direction: column;
            }

            .inventory-dashboard-btn {
                width: 100%;
            }

            .inventory-dashboard-card {
                padding: 17px;
                border-radius: 14px;
            }

        }
    </style>

</div>

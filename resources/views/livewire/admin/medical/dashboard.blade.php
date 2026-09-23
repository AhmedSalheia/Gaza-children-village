<div class="medical-page">

    @include('livewire.admin.medical._nav')

    {{-- ============================================================
         PAGE HEADER
    ============================================================= --}}
    <div class="medical-page-header">
        <div>
            <div class="medical-eyebrow">
                {{ __('medical.title') }}
            </div>

            <h1 class="medical-page-title">
                {{ __('medical.title') }}
            </h1>

            <p class="medical-page-description">
                {{ __('medical.description') }}
            </p>
        </div>

        <div class="medical-header-actions">
            <a
                href="{{ route('admin.medical.visits.create') }}"
                class="medical-btn medical-btn-primary"
                wire:navigate
            >
                <span class="medical-btn-icon">＋</span>
                <span>{{ __('medical.new_visit') }}</span>
            </a>
        </div>
    </div>


    {{-- ============================================================
         MAIN STATISTICS
    ============================================================= --}}
    <div class="medical-section">

        <div class="medical-section-heading">
            <div>
                <h2>{{ __('medical.title') }}</h2>
                <p>{{ __('medical.description') }}</p>
            </div>
        </div>

        <div class="medical-stats-grid">

            {{-- Clinics --}}
            <div class="medical-stat-card">
                <div class="medical-stat-icon medical-icon-blue">
                    <span>+</span>
                </div>

                <div class="medical-stat-content">
                    <span class="medical-stat-label">
                        {{ __('medical.clinics') }}
                    </span>

                    <strong class="medical-stat-value">
                        {{ number_format($clinics) }}
                    </strong>

                    <span class="medical-stat-meta">
                        {{ __('medical.active') }}
                    </span>
                </div>
            </div>


            {{-- Staff --}}
            <div class="medical-stat-card">
                <div class="medical-stat-icon medical-icon-purple">
                    <span>◉</span>
                </div>

                <div class="medical-stat-content">
                    <span class="medical-stat-label">
                        {{ __('medical.staff') }}
                    </span>

                    <strong class="medical-stat-value">
                        {{ number_format($staff) }}
                    </strong>

                    <span class="medical-stat-meta">
                        {{ __('medical.active') }}
                    </span>
                </div>
            </div>


            {{-- Patients --}}
            <div class="medical-stat-card">
                <div class="medical-stat-icon medical-icon-green">
                    <span>♙</span>
                </div>

                <div class="medical-stat-content">
                    <span class="medical-stat-label">
                        {{ __('medical.patients') }}
                    </span>

                    <strong class="medical-stat-value">
                        {{ number_format($patients) }}
                    </strong>

                    <span class="medical-stat-meta">
                        {{ __('medical.active') }}
                    </span>
                </div>
            </div>


            {{-- Medicines --}}
            <div class="medical-stat-card">
                <div class="medical-stat-icon medical-icon-orange">
                    <span>▣</span>
                </div>

                <div class="medical-stat-content">
                    <span class="medical-stat-label">
                        {{ __('medical.medicines') }}
                    </span>

                    <strong class="medical-stat-value">
                        {{ number_format($medicines) }}
                    </strong>

                    <span class="medical-stat-meta">
                        {{ __('medical.active') }}
                    </span>
                </div>
            </div>

        </div>
    </div>


    {{-- ============================================================
         MEDICAL OPERATIONS
    ============================================================= --}}
    <div class="medical-section">

        <div class="medical-section-heading">
            <div>
                <h2>{{ __('medical.operations') }}</h2>
                <p>{{ __('medical.operations_description') }}</p>
            </div>
        </div>

        <div class="medical-stats-grid">

            {{-- Today's Visits --}}
            <div class="medical-stat-card medical-stat-card-large">
                <div class="medical-stat-icon medical-icon-teal">
                    <span>◷</span>
                </div>

                <div class="medical-stat-content">
                    <span class="medical-stat-label">
                        {{ __('medical.today_visits') }}
                    </span>

                    <strong class="medical-stat-value">
                        {{ number_format($visits) }}
                    </strong>

                    <span class="medical-stat-meta">
                        {{ now()->format('Y-m-d') }}
                    </span>
                </div>
            </div>


            {{-- Expiring --}}
            <div class="medical-stat-card medical-stat-warning">
                <div class="medical-stat-icon medical-icon-yellow">
                    <span>!</span>
                </div>

                <div class="medical-stat-content">
                    <span class="medical-stat-label">
                        {{ __('medical.expiring_soon') }}
                    </span>

                    <strong class="medical-stat-value">
                        {{ number_format($expiring) }}
                    </strong>

                    <span class="medical-stat-meta">
                        {{ __('medical.expiring_soon_description') }}
                    </span>
                </div>
            </div>


            {{-- Expired --}}
            <div class="medical-stat-card medical-stat-danger">
                <div class="medical-stat-icon medical-icon-red">
                    <span>!</span>
                </div>

                <div class="medical-stat-content">
                    <span class="medical-stat-label">
                        {{ __('medical.expired') }}
                    </span>

                    <strong class="medical-stat-value">
                        {{ number_format($expired) }}
                    </strong>

                    <span class="medical-stat-meta">
                        {{ __('medical.expired_description') }}
                    </span>
                </div>
            </div>


            {{-- Low Stock --}}
            <div class="medical-stat-card medical-stat-danger">
                <div class="medical-stat-icon medical-icon-orange">
                    <span>↓</span>
                </div>

                <div class="medical-stat-content">
                    <span class="medical-stat-label">
                        {{ __('medical.low_stock') }}
                    </span>

                    <strong class="medical-stat-value">
                        {{ number_format($low) }}
                    </strong>

                    <span class="medical-stat-meta">
                        {{ __('medical.low_stock_description') }}
                    </span>
                </div>
            </div>

        </div>
    </div>


    {{-- ============================================================
         QUICK ACTIONS
    ============================================================= --}}
    <div class="medical-section">

        <div class="medical-section-heading">
            <div>
                <h2>{{ __('medical.quick_actions') }}</h2>

                <p>
                    {{ __('medical.quick_actions_description') }}
                </p>
            </div>
        </div>

        <div class="medical-actions-grid">

            <a
                href="{{ route('admin.medical.visits.create') }}"
                class="medical-action-card"
                wire:navigate
            >
                <div class="medical-action-icon medical-icon-blue">
                    +
                </div>

                <div>
                    <strong>
                        {{ __('medical.new_visit') }}
                    </strong>

                    <span>
                        {{ __('medical.new_visit_description') }}
                    </span>
                </div>

                <span class="medical-action-arrow">
                    ←
                </span>
            </a>


            <a
                href="{{ route('admin.medical.receipts.create') }}"
                class="medical-action-card"
                wire:navigate
            >
                <div class="medical-action-icon medical-icon-green">
                    +
                </div>

                <div>
                    <strong>
                        {{ __('medical.new_receipt') }}
                    </strong>

                    <span>
                        {{ __('medical.new_receipt_description') }}
                    </span>
                </div>

                <span class="medical-action-arrow">
                    ←
                </span>
            </a>


            <a
                href="{{ route('admin.medical.issues.create') }}"
                class="medical-action-card"
                wire:navigate
            >
                <div class="medical-action-icon medical-icon-orange">
                    ↓
                </div>

                <div>
                    <strong>
                        {{ __('medical.new_issue') }}
                    </strong>

                    <span>
                        {{ __('medical.new_issue_description') }}
                    </span>
                </div>

                <span class="medical-action-arrow">
                    ←
                </span>
            </a>


            <a
                href="{{ route('admin.medical.medicines.index') }}"
                class="medical-action-card"
                wire:navigate
            >
                <div class="medical-action-icon medical-icon-purple">
                    ▣
                </div>

                <div>
                    <strong>
                        {{ __('medical.medicines') }}
                    </strong>

                    <span>
                        {{ __('medical.medicines_description') }}
                    </span>
                </div>

                <span class="medical-action-arrow">
                    ←
                </span>
            </a>

        </div>
    </div>


    {{-- ============================================================
         STOCK ALERT SUMMARY
    ============================================================= --}}
    <div class="medical-alert-card">

        <div class="medical-alert-header">

            <div>
                <h2>
                    {{ __('medical.stock_alerts') }}
                </h2>

                <p>
                    {{ __('medical.stock_alerts_description') }}
                </p>
            </div>

            <a
                href="{{ route('admin.medical.batches.index') }}"
                class="medical-alert-link"
                wire:navigate
            >
                {{ __('medical.view_batches') }}
                <span>←</span>
            </a>

        </div>

        <div class="medical-alert-grid">

            <div class="medical-alert-item">
                <span class="medical-alert-dot medical-dot-yellow"></span>

                <div>
                    <strong>
                        {{ number_format($expiring) }}
                    </strong>

                    <span>
                        {{ __('medical.expiring_soon') }}
                    </span>
                </div>
            </div>


            <div class="medical-alert-item">
                <span class="medical-alert-dot medical-dot-red"></span>

                <div>
                    <strong>
                        {{ number_format($expired) }}
                    </strong>

                    <span>
                        {{ __('medical.expired') }}
                    </span>
                </div>
            </div>


            <div class="medical-alert-item">
                <span class="medical-alert-dot medical-dot-orange"></span>

                <div>
                    <strong>
                        {{ number_format($low) }}
                    </strong>

                    <span>
                        {{ __('medical.low_stock') }}
                    </span>
                </div>
            </div>

        </div>
    </div>


    {{-- ============================================================
         PAGE STYLES
    ============================================================= --}}
    <style>
        .medical-page {
            width: 100%;
            direction: rtl;
        }

        .medical-page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 28px;
        }

        .medical-eyebrow {
            display: inline-flex;
            align-items: center;
            padding: 6px 11px;
            border-radius: 8px;
            background: #eef6ff;
            color: #2563eb;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 9px;
        }

        .medical-page-title {
            margin: 0;
            font-size: 29px;
            line-height: 1.25;
            font-weight: 800;
            color: #172033;
        }

        .medical-page-description {
            margin: 8px 0 0;
            color: #697386;
            font-size: 14px;
            line-height: 1.7;
        }

        .medical-header-actions {
            flex-shrink: 0;
        }

        .medical-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 44px;
            padding: 0 18px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            transition: .18s ease;
        }

        .medical-btn-primary {
            background: #2563eb;
            color: #fff;
            box-shadow: 0 5px 14px rgba(37, 99, 235, .18);
        }

        .medical-btn-primary:hover {
            background: #1d4ed8;
            color: #fff;
            transform: translateY(-1px);
        }

        .medical-btn-icon {
            font-size: 20px;
            line-height: 1;
        }

        .medical-section {
            margin-bottom: 26px;
        }

        .medical-section-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .medical-section-heading h2 {
            margin: 0;
            color: #202938;
            font-size: 18px;
            font-weight: 800;
        }

        .medical-section-heading p {
            margin: 5px 0 0;
            color: #7a8495;
            font-size: 13px;
        }

        .medical-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .medical-stat-card {
            min-height: 135px;
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: #fff;
            border: 1px solid #e8ecf2;
            border-radius: 14px;
            box-shadow: 0 3px 14px rgba(16, 24, 40, .045);
            transition: .18s ease;
        }

        .medical-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(16, 24, 40, .07);
        }

        .medical-stat-content {
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .medical-stat-label {
            color: #697386;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .medical-stat-value {
            color: #172033;
            font-size: 28px;
            line-height: 1.1;
            font-weight: 800;
        }

        .medical-stat-meta {
            margin-top: 7px;
            color: #9aa3b2;
            font-size: 11px;
        }

        .medical-stat-icon,
        .medical-action-icon {
            width: 48px;
            height: 48px;
            min-width: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 21px;
            font-weight: 800;
        }

        .medical-icon-blue {
            background: #eaf2ff;
            color: #2563eb;
        }

        .medical-icon-purple {
            background: #f2ecff;
            color: #7c3aed;
        }

        .medical-icon-green {
            background: #eaf9f0;
            color: #16a05d;
        }

        .medical-icon-orange {
            background: #fff3e8;
            color: #ea7b16;
        }

        .medical-icon-yellow {
            background: #fff8dc;
            color: #c38a00;
        }

        .medical-icon-red {
            background: #fff0f0;
            color: #dc3545;
        }

        .medical-icon-teal {
            background: #e7f8f7;
            color: #0f9f9a;
        }

        .medical-stat-warning {
            border-color: #f1e2b4;
        }

        .medical-stat-danger {
            border-color: #f0d0d3;
        }

        .medical-actions-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .medical-action-card {
            position: relative;
            display: flex;
            align-items: center;
            gap: 14px;
            min-height: 100px;
            padding: 18px;
            background: #fff;
            border: 1px solid #e8ecf2;
            border-radius: 14px;
            text-decoration: none;
            box-shadow: 0 3px 14px rgba(16, 24, 40, .045);
            transition: .18s ease;
        }

        .medical-action-card:hover {
            transform: translateY(-2px);
            border-color: #cfd8e6;
            box-shadow: 0 8px 24px rgba(16, 24, 40, .07);
        }

        .medical-action-card > div:nth-child(2) {
            min-width: 0;
            display: flex;
            flex-direction: column;
            padding-left: 20px;
        }

        .medical-action-card strong {
            color: #202938;
            font-size: 14px;
            font-weight: 800;
        }

        .medical-action-card span:not(.medical-action-arrow) {
            color: #7b8494;
            font-size: 11px;
            line-height: 1.6;
        }

        .medical-action-arrow {
            position: absolute;
            left: 14px;
            color: #a3adbc;
            font-size: 17px;
        }

        .medical-alert-card {
            padding: 22px;
            background: #fff;
            border: 1px solid #e8ecf2;
            border-radius: 14px;
            box-shadow: 0 3px 14px rgba(16, 24, 40, .045);
        }

        .medical-alert-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
        }

        .medical-alert-header h2 {
            margin: 0;
            color: #202938;
            font-size: 18px;
            font-weight: 800;
        }

        .medical-alert-header p {
            margin: 5px 0 0;
            color: #7a8495;
            font-size: 13px;
        }

        .medical-alert-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #2563eb;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
        }

        .medical-alert-link:hover {
            color: #1d4ed8;
        }

        .medical-alert-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .medical-alert-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            border-radius: 10px;
            background: #f8fafc;
        }

        .medical-alert-dot {
            width: 10px;
            height: 10px;
            min-width: 10px;
            border-radius: 50%;
        }

        .medical-dot-yellow {
            background: #eab308;
        }

        .medical-dot-red {
            background: #dc3545;
        }

        .medical-dot-orange {
            background: #ea7b16;
        }

        .medical-alert-item div {
            display: flex;
            flex-direction: column;
        }

        .medical-alert-item strong {
            color: #202938;
            font-size: 18px;
            font-weight: 800;
        }

        .medical-alert-item span {
            color: #7a8495;
            font-size: 11px;
            margin-top: 2px;
        }

        @media (max-width: 1200px) {
            .medical-stats-grid,
            .medical-actions-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .medical-page-header {
                flex-direction: column;
                align-items: stretch;
            }

            .medical-header-actions {
                width: 100%;
            }

            .medical-btn {
                width: 100%;
            }

            .medical-stats-grid,
            .medical-actions-grid,
            .medical-alert-grid {
                grid-template-columns: 1fr;
            }

            .medical-page-title {
                font-size: 24px;
            }
        }
    </style>

</div>

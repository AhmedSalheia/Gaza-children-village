@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';
@endphp

<div class="medical-page">

    {{-- ========================================================= --}}
    {{-- Medical Navigation --}}
    {{-- ========================================================= --}}

    @include('livewire.admin.medical._nav')


    {{-- ========================================================= --}}
    {{-- Header --}}
    {{-- ========================================================= --}}

    <div class="medical-page-header">

        <div>

            <div class="medical-eyebrow">
                {{ __('medical.medical_portal', [], null, 'Medical Portal') }}
            </div>

            <h1 class="medical-page-title">
                {{ __('medical.medicine_batches', [], null, 'Medicine Batches') }}
            </h1>

            <p class="medical-page-description">
                {{ __('medical.medicine_batches_description', [], null, 'Monitor medicine batches, quantities, expiry dates and stock status.') }}
            </p>

        </div>

        <a
            href="{{ route('admin.medical.medicines.index') }}"
            wire:navigate
            class="medical-header-action"
        >
            <span>←</span>
            {{ __('medical.medicines', [], null, 'Medicines') }}
        </a>

    </div>


    {{-- ========================================================= --}}
    {{-- Statistics --}}
    {{-- ========================================================= --}}

    <div class="medical-stats-grid">

        {{-- Total Batches --}}
        <div class="medical-stat-card">

            <div class="medical-stat-icon medical-stat-icon-blue">
                #
            </div>

            <div class="medical-stat-content">

                <span class="medical-stat-label">
                    {{ __('medical.total_batches', [], null, 'Total Batches') }}
                </span>

                <strong class="medical-stat-value">
                    {{ $totalBatches }}
                </strong>

                <span class="medical-stat-description">
                    {{ __('medical.total_batches_description', [], null, 'Registered medicine batches') }}
                </span>

            </div>

        </div>


        {{-- Active --}}
        <div class="medical-stat-card">

            <div class="medical-stat-icon medical-stat-icon-green">
                ✓
            </div>

            <div class="medical-stat-content">

                <span class="medical-stat-label">
                    {{ __('medical.active_batches', [], null, 'Active Batches') }}
                </span>

                <strong class="medical-stat-value">
                    {{ $activeBatches }}
                </strong>

                <span class="medical-stat-description">
                    {{ __('medical.active_batches_description', [], null, 'Currently available batches') }}
                </span>

            </div>

        </div>


        {{-- Expiring --}}
        <div class="medical-stat-card">

            <div class="medical-stat-icon medical-stat-icon-orange">
                !
            </div>

            <div class="medical-stat-content">

                <span class="medical-stat-label">
                    {{ __('medical.expiring_soon', [], null, 'Expiring Soon') }}
                </span>

                <strong class="medical-stat-value">
                    {{ $expiringSoon }}
                </strong>

                <span class="medical-stat-description">
                    {{ __('medical.within_90_days', [], null, 'Within the next 90 days') }}
                </span>

            </div>

        </div>


        {{-- Expired --}}
        <div class="medical-stat-card">

            <div class="medical-stat-icon medical-stat-icon-red">
                !
            </div>

            <div class="medical-stat-content">

                <span class="medical-stat-label">
                    {{ __('medical.expired_batches', [], null, 'Expired Batches') }}
                </span>

                <strong class="medical-stat-value">
                    {{ $expiredBatches }}
                </strong>

                <span class="medical-stat-description">
                    {{ __('medical.expired_with_stock', [], null, 'Expired batches with remaining stock') }}
                </span>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- Filter --}}
    {{-- ========================================================= --}}

    <section class="medical-card medical-filter-card">

        <div class="medical-filter-inner">

            <div class="medical-filter-title">

                <div class="medical-filter-icon">
                    ≡
                </div>

                <div>

                    <strong>
                        {{ __('medical.batch_filter', [], null, 'Batch Filter') }}
                    </strong>

                    <span>
                        {{ __('medical.batch_filter_description', [], null, 'Filter batches by their current status.') }}
                    </span>

                </div>

            </div>


            <div class="medical-filter-controls">

                <button
                    type="button"
                    wire:click="$set('status', 'all')"
                    class="medical-filter-button {{ $status === 'all' ? 'active' : '' }}"
                >
                    {{ __('medical.all', [], null, 'All') }}
                </button>

                <button
                    type="button"
                    wire:click="$set('status', 'active')"
                    class="medical-filter-button {{ $status === 'active' ? 'active' : '' }}"
                >
                    {{ __('medical.active', [], null, 'Active') }}
                </button>

                <button
                    type="button"
                    wire:click="$set('status', 'expired')"
                    class="medical-filter-button {{ $status === 'expired' ? 'active' : '' }}"
                >
                    {{ __('medical.expired', [], null, 'Expired') }}
                </button>

                <button
                    type="button"
                    wire:click="$set('status', 'depleted')"
                    class="medical-filter-button {{ $status === 'depleted' ? 'active' : '' }}"
                >
                    {{ __('medical.depleted', [], null, 'Depleted') }}
                </button>

            </div>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- Stock Summary --}}
    {{-- ========================================================= --}}

    <div class="medical-stock-summary">

        <div class="stock-summary-label">
            {{ __('medical.available_quantity', [], null, 'Available Quantity') }}
        </div>

        <div class="stock-summary-value">
            {{ number_format($totalQuantity, 2) }}
        </div>

        <div class="stock-summary-note">
            {{ __('medical.available_quantity_description', [], null, 'Total quantity currently remaining across batches') }}
        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- Batch Table --}}
    {{-- ========================================================= --}}

    <section class="medical-card medical-table-card">

        <div class="medical-card-header">

            <div>

                <h2 class="medical-card-title">
                    {{ __('medical.batch_list', [], null, 'Batch List') }}
                </h2>

                <p class="medical-card-description">
                    {{ __('medical.batch_list_description', [], null, 'Medicine batches sorted by expiry date.') }}
                </p>

            </div>

            <div class="medical-card-badge">
                {{ $batches->total() }}
            </div>

        </div>


        @if($batches->count())

            <div class="medical-table-wrapper">

                <table class="medical-table">

                    <thead>

                        <tr>

                            <th>
                                {{ __('medical.medicine', [], null, 'Medicine') }}
                            </th>

                            <th>
                                {{ __('medical.batch_number', [], null, 'Batch Number') }}
                            </th>

                            <th>
                                {{ __('medical.medical_point', [], null, 'Medical Point') }}
                            </th>

                            <th>
                                {{ __('medical.expiry_date', [], null, 'Expiry Date') }}
                            </th>

                            <th>
                                {{ __('medical.quantity', [], null, 'Quantity') }}
                            </th>

                            <th>
                                {{ __('medical.status', [], null, 'Status') }}
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @foreach($batches as $batch)

                            @php

                                $medicineName = $isArabic
                                    ? $batch->name_ar
                                    : ($batch->name_en ?: $batch->name_ar);

                                $clinicName = $isArabic
                                    ? $batch->clinic_ar
                                    : ($batch->clinic_en ?: $batch->clinic_ar);

                                $expiryDate = $batch->expiry_date
                                    ? \Illuminate\Support\Carbon::parse($batch->expiry_date)
                                    : null;

                                $isExpired = $expiryDate
                                    ? $expiryDate->isPast()
                                    : false;

                                $isExpiringSoon = $expiryDate
                                    ? !$isExpired && $expiryDate->lte(now()->addDays(90))
                                    : false;

                                $quantity = (float) $batch->current_quantity;

                            @endphp

                            <tr>

                                {{-- Medicine --}}
                                <td>

                                    <div class="medicine-cell">

                                        <div class="medicine-icon">
                                            +
                                        </div>

                                        <div>

                                            <div class="medical-table-primary">
                                                {{ $medicineName }}
                                            </div>

                                            <div class="medical-table-secondary">
                                                {{ $batch->code }}
                                            </div>

                                        </div>

                                    </div>

                                </td>


                                {{-- Batch --}}
                                <td>

                                    <span class="medical-batch-number">
                                        {{ $batch->batch_number }}
                                    </span>

                                </td>


                                {{-- Clinic --}}
                                <td>

                                    <div class="clinic-name">
                                        {{ $clinicName }}
                                    </div>

                                </td>


                                {{-- Expiry --}}
                                <td>

                                    <div class="expiry-cell">

                                        <strong
                                            class="
                                                {{ $isExpired
                                                    ? 'expiry-danger'
                                                    : ($isExpiringSoon
                                                        ? 'expiry-warning'
                                                        : 'expiry-normal') }}
                                            "
                                        >
                                            {{ $expiryDate?->format('Y-m-d') ?? '—' }}
                                        </strong>

                                        @if($isExpired)

                                            <span class="expiry-label expiry-danger-label">
                                                {{ __('medical.expired', [], null, 'Expired') }}
                                            </span>

                                        @elseif($isExpiringSoon)

                                            <span class="expiry-label expiry-warning-label">
                                                {{ __('medical.expiring_soon', [], null, 'Expiring Soon') }}
                                            </span>

                                        @endif

                                    </div>

                                </td>


                                {{-- Quantity --}}
                                <td>

                                    <div class="quantity-cell">

                                        <strong
                                            class="{{ $quantity <= 0 ? 'quantity-zero' : '' }}"
                                        >
                                            {{ number_format($quantity, 2) }}
                                        </strong>

                                        <span>
                                            {{ $batch->unit ?? 'unit' }}
                                        </span>

                                    </div>

                                </td>


                                {{-- Status --}}
                                <td>

                                    @if($batch->status === 'active')

                                        <span class="medical-status medical-status-green">

                                            <span class="status-dot"></span>

                                            {{ __('medical.active', [], null, 'Active') }}

                                        </span>

                                    @elseif($batch->status === 'expired')

                                        <span class="medical-status medical-status-red">

                                            <span class="status-dot"></span>

                                            {{ __('medical.expired', [], null, 'Expired') }}

                                        </span>

                                    @elseif($batch->status === 'depleted')

                                        <span class="medical-status medical-status-gray">

                                            <span class="status-dot"></span>

                                            {{ __('medical.depleted', [], null, 'Depleted') }}

                                        </span>

                                    @else

                                        <span class="medical-status medical-status-gray">

                                            <span class="status-dot"></span>

                                            {{ $batch->status }}

                                        </span>

                                    @endif

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>


            {{-- Pagination --}}

            <div class="medical-pagination">
                {{ $batches->links() }}
            </div>

        @else

            <div class="medical-empty">

                <div class="medical-empty-icon">
                    #
                </div>

                <h3>
                    {{ __('medical.no_batches', [], null, 'No medicine batches found') }}
                </h3>

                <p>
                    {{ __('medical.no_batches_description', [], null, 'There are no medicine batches matching the selected filter.') }}
                </p>

            </div>

        @endif

    </section>


    {{-- ========================================================= --}}
    {{-- Styles --}}
    {{-- ========================================================= --}}

    <style>

        .medical-page {
            direction: rtl;
            padding-bottom: 45px;
        }

        .medical-page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            margin: 28px 0;
        }

        .medical-eyebrow {
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .medical-page-title {
            margin: 0;
            color: #0f172a;
            font-size: 30px;
            font-weight: 850;
        }

        .medical-page-description {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 14px;
            line-height: 1.8;
        }

        .medical-header-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 42px;
            padding: 0 15px;
            border-radius: 10px;
            background: #fff;
            border: 1px solid #dbe3ec;
            color: #334155;
            text-decoration: none;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .medical-header-action:hover {
            background: #f8fafc;
            color: #15803d;
        }

        .medical-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .medical-stat-card {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 17px;
            padding: 20px;
            box-shadow: 0 5px 18px rgba(15, 23, 42, .04);
        }

        .medical-stat-icon {
            width: 44px;
            height: 44px;
            min-width: 44px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            font-weight: 900;
        }

        .medical-stat-icon-blue {
            background: #eff6ff;
            color: #2563eb;
        }

        .medical-stat-icon-green {
            background: #f0fdf4;
            color: #15803d;
        }

        .medical-stat-icon-orange {
            background: #fff7ed;
            color: #ea580c;
        }

        .medical-stat-icon-red {
            background: #fef2f2;
            color: #dc2626;
        }

        .medical-stat-content {
            min-width: 0;
        }

        .medical-stat-label {
            display: block;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .medical-stat-value {
            display: block;
            color: #0f172a;
            font-size: 27px;
            line-height: 1.1;
            font-weight: 900;
        }

        .medical-stat-description {
            display: block;
            margin-top: 6px;
            color: #94a3b8;
            font-size: 11px;
            line-height: 1.5;
        }

        .medical-card {
            margin-bottom: 20px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            box-shadow: 0 5px 18px rgba(15, 23, 42, .05);
            overflow: hidden;
        }

        .medical-filter-card {
            padding: 0;
        }

        .medical-filter-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 17px 20px;
        }

        .medical-filter-title {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .medical-filter-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0fdf4;
            color: #15803d;
            font-weight: 900;
        }

        .medical-filter-title strong {
            display: block;
            color: #0f172a;
            font-size: 13px;
        }

        .medical-filter-title span {
            display: block;
            margin-top: 3px;
            color: #94a3b8;
            font-size: 11px;
        }

        .medical-filter-controls {
            display: flex;
            align-items: center;
            gap: 7px;
            flex-wrap: wrap;
        }

        .medical-filter-button {
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #64748b;
            border-radius: 9px;
            padding: 8px 13px;
            font-size: 11px;
            font-weight: 800;
            cursor: pointer;
            transition: .15s ease;
        }

        .medical-filter-button:hover {
            border-color: #bbf7d0;
            color: #15803d;
        }

        .medical-filter-button.active {
            border-color: #16a34a;
            background: #16a34a;
            color: #fff;
        }

        .medical-stock-summary {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding: 16px 20px;
            border: 1px solid #dbeafe;
            border-radius: 15px;
            background: #eff6ff;
        }

        .stock-summary-label {
            color: #475569;
            font-size: 12px;
            font-weight: 800;
        }

        .stock-summary-value {
            color: #1d4ed8;
            font-size: 21px;
            font-weight: 900;
        }

        .stock-summary-note {
            color: #64748b;
            font-size: 11px;
        }

        .medical-table-card {
            overflow: hidden;
        }

        .medical-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 22px 24px;
            border-bottom: 1px solid #eef2f7;
        }

        .medical-card-title {
            margin: 0;
            color: #0f172a;
            font-size: 18px;
            font-weight: 800;
        }

        .medical-card-description {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 13px;
            line-height: 1.7;
        }

        .medical-card-badge {
            min-width: 42px;
            height: 36px;
            padding: 0 10px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0fdf4;
            color: #15803d;
            font-size: 12px;
            font-weight: 900;
        }

        .medical-table-wrapper {
            overflow-x: auto;
        }

        .medical-table {
            width: 100%;
            border-collapse: collapse;
        }

        .medical-table th {
            padding: 14px 20px;
            background: #f8fafc;
            color: #64748b;
            font-size: 12px;
            font-weight: 800;
            text-align: right;
            white-space: nowrap;
        }

        .medical-table td {
            padding: 16px 20px;
            border-top: 1px solid #eef2f7;
            color: #334155;
            font-size: 13px;
            vertical-align: middle;
        }

        .medical-table tbody tr:hover {
            background: #fafafa;
        }

        .medicine-cell {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 200px;
        }

        .medicine-icon {
            width: 40px;
            height: 40px;
            min-width: 40px;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            color: #2563eb;
            font-size: 18px;
            font-weight: 900;
        }

        .medical-table-primary {
            color: #0f172a;
            font-weight: 800;
        }

        .medical-table-secondary {
            margin-top: 4px;
            color: #94a3b8;
            font-size: 11px;
        }

        .medical-batch-number {
            display: inline-flex;
            padding: 6px 9px;
            border-radius: 7px;
            background: #f8fafc;
            color: #475569;
            font-family: monospace;
            font-size: 11px;
            direction: ltr;
        }

        .clinic-name {
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .expiry-cell {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .expiry-cell strong {
            direction: ltr;
            text-align: right;
            font-size: 12px;
        }

        .expiry-normal {
            color: #334155;
        }

        .expiry-warning {
            color: #d97706;
        }

        .expiry-danger {
            color: #dc2626;
        }

        .expiry-label {
            width: fit-content;
            padding: 3px 6px;
            border-radius: 6px;
            font-size: 9px;
            font-weight: 800;
        }

        .expiry-warning-label {
            background: #fff7ed;
            color: #c2410c;
        }

        .expiry-danger-label {
            background: #fef2f2;
            color: #b91c1c;
        }

        .quantity-cell {
            display: flex;
            align-items: baseline;
            gap: 6px;
            white-space: nowrap;
        }

        .quantity-cell strong {
            color: #0f172a;
            font-size: 15px;
            font-weight: 900;
        }

        .quantity-cell span {
            color: #94a3b8;
            font-size: 10px;
        }

        .quantity-zero {
            color: #dc2626 !important;
        }

        .medical-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 9px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .medical-status-green {
            background: #f0fdf4;
            color: #15803d;
        }

        .medical-status-red {
            background: #fef2f2;
            color: #dc2626;
        }

        .medical-status-gray {
            background: #f1f5f9;
            color: #64748b;
        }

        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .medical-pagination {
            padding: 18px 22px;
            border-top: 1px solid #eef2f7;
        }

        .medical-empty {
            padding: 65px 25px;
            text-align: center;
        }

        .medical-empty-icon {
            width: 55px;
            height: 55px;
            margin: 0 auto 15px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            color: #2563eb;
            font-size: 20px;
            font-weight: 900;
        }

        .medical-empty h3 {
            margin: 0;
            color: #0f172a;
            font-size: 16px;
            font-weight: 800;
        }

        .medical-empty p {
            margin: 7px 0 0;
            color: #94a3b8;
            font-size: 13px;
        }

        @media (max-width: 1100px) {

            .medical-stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .medical-filter-inner {
                align-items: flex-start;
                flex-direction: column;
            }

        }

        @media (max-width: 700px) {

            .medical-page-header {
                flex-direction: column;
            }

            .medical-header-action {
                width: 100%;
                justify-content: center;
            }

            .medical-stock-summary {
                flex-wrap: wrap;
            }

        }

        @media (max-width: 600px) {

            .medical-stats-grid {
                grid-template-columns: 1fr;
            }

            .medical-page-title {
                font-size: 25px;
            }

            .medical-card-header {
                padding: 18px;
            }

            .medical-table th,
            .medical-table td {
                padding: 13px 14px;
            }

        }

    </style>

</div>

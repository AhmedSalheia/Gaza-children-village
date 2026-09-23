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
                {{ __('medical.medicine_movements', [], null, 'Medicine Movements') }}
            </h1>

            <p class="medical-page-description">
                {{ __('medical.medicine_movements_description', [], null, 'Track all medicine receipts, issues, adjustments and disposals across medical points.') }}
            </p>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- Statistics --}}
    {{-- ========================================================= --}}

    <div class="medical-stats-grid">

        {{-- Today --}}
        <div class="medical-stat-card">

            <div class="medical-stat-icon medical-stat-icon-blue">
                ↻
            </div>

            <div class="medical-stat-content">

                <span class="medical-stat-label">
                    {{ __('medical.today_movements', [], null, 'Today Movements') }}
                </span>

                <strong class="medical-stat-value">
                    {{ $todayMovements }}
                </strong>

                <span class="medical-stat-description">
                    {{ __('medical.today_movements_description', [], null, 'Transactions recorded today') }}
                </span>

            </div>

        </div>


        {{-- Receipts --}}
        <div class="medical-stat-card">

            <div class="medical-stat-icon medical-stat-icon-green">
                ↓
            </div>

            <div class="medical-stat-content">

                <span class="medical-stat-label">
                    {{ __('medical.receipts', [], null, 'Receipts') }}
                </span>

                <strong class="medical-stat-value">
                    {{ $receipts }}
                </strong>

                <span class="medical-stat-description">
                    {{ __('medical.receipts_description', [], null, 'Medicine stock received') }}
                </span>

            </div>

        </div>


        {{-- Issues --}}
        <div class="medical-stat-card">

            <div class="medical-stat-icon medical-stat-icon-orange">
                ↑
            </div>

            <div class="medical-stat-content">

                <span class="medical-stat-label">
                    {{ __('medical.issues', [], null, 'Issues') }}
                </span>

                <strong class="medical-stat-value">
                    {{ $issues }}
                </strong>

                <span class="medical-stat-description">
                    {{ __('medical.issues_description', [], null, 'Medicine stock issued') }}
                </span>

            </div>

        </div>


        {{-- Adjustments --}}
        <div class="medical-stat-card">

            <div class="medical-stat-icon medical-stat-icon-gray">
                ±
            </div>

            <div class="medical-stat-content">

                <span class="medical-stat-label">
                    {{ __('medical.adjustments', [], null, 'Adjustments') }}
                </span>

                <strong class="medical-stat-value">
                    {{ $adjustments }}
                </strong>

                <span class="medical-stat-description">
                    {{ __('medical.adjustments_description', [], null, 'Adjustments and disposals') }}
                </span>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- Filters --}}
    {{-- ========================================================= --}}

    <section class="medical-card medical-filter-card">

        <div class="medical-filter-inner">

            {{-- Search --}}
            <div class="medical-search">

                <span class="medical-search-icon">
                    ⌕
                </span>

                <input
                    type="search"
                    wire:model.live.debounce.350ms="q"
                    class="medical-search-input"
                    placeholder="{{ __('medical.search_movements', [], null, 'Search by transaction number, medicine or patient code...') }}"
                >

                @if($q !== '')

                    <button
                        type="button"
                        wire:click="$set('q', '')"
                        class="medical-search-clear"
                    >
                        ×
                    </button>

                @endif

            </div>


            {{-- Type --}}
            <div class="medical-filter-select">

                <select
                    wire:model.live="type"
                    class="medical-input"
                >

                    <option value="all">
                        {{ __('medical.all_movements', [], null, 'All Movements') }}
                    </option>

                    <option value="receipt">
                        {{ __('medical.receipt', [], null, 'Receipt') }}
                    </option>

                    <option value="issue">
                        {{ __('medical.issue', [], null, 'Issue') }}
                    </option>

                    <option value="adjustment">
                        {{ __('medical.adjustment', [], null, 'Adjustment') }}
                    </option>

                    <option value="disposal">
                        {{ __('medical.disposal', [], null, 'Disposal') }}
                    </option>

                </select>

            </div>


            {{-- Clear --}}
            @if($q !== '' || $type !== 'all')

                <button
                    type="button"
                    wire:click="clearFilters"
                    class="medical-btn medical-btn-secondary medical-clear-btn"
                >
                    {{ __('medical.clear_filters', [], null, 'Clear Filters') }}
                </button>

            @endif

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- Movements Table --}}
    {{-- ========================================================= --}}

    <section class="medical-card medical-table-card">

        <div class="medical-card-header">

            <div>

                <h2 class="medical-card-title">
                    {{ __('medical.movement_list', [], null, 'Movement List') }}
                </h2>

                <p class="medical-card-description">
                    {{ __('medical.movement_list_description', [], null, 'Detailed history of medicine stock transactions.') }}
                </p>

            </div>

            <div class="medical-card-badge">
                {{ $movements->total() }}
            </div>

        </div>


        @if($movements->count())

            <div class="medical-table-wrapper">

                <table class="medical-table">

                    <thead>

                        <tr>

                            <th>
                                {{ __('medical.transaction', [], null, 'Transaction') }}
                            </th>

                            <th>
                                {{ __('medical.medicine', [], null, 'Medicine') }}
                            </th>

                            <th>
                                {{ __('medical.type', [], null, 'Type') }}
                            </th>

                            <th>
                                {{ __('medical.quantity', [], null, 'Quantity') }}
                            </th>

                            <th>
                                {{ __('medical.medical_point', [], null, 'Medical Point') }}
                            </th>

                            <th>
                                {{ __('medical.patient', [], null, 'Patient') }}
                            </th>

                            <th>
                                {{ __('medical.date', [], null, 'Date') }}
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @foreach($movements as $movement)

                            @php

                                $medicineName = $isArabic
                                    ? $movement->name_ar
                                    : ($movement->name_en ?: $movement->name_ar);

                                $clinicName = $isArabic
                                    ? $movement->clinic_ar
                                    : ($movement->clinic_en ?: $movement->clinic_ar);

                                $movementType = match ($movement->type) {
                                    'receipt' => [
                                        'label' => __('medical.receipt', [], null, 'Receipt'),
                                        'class' => 'medical-status-green',
                                    ],

                                    'issue' => [
                                        'label' => __('medical.issue', [], null, 'Issue'),
                                        'class' => 'medical-status-orange',
                                    ],

                                    'adjustment' => [
                                        'label' => __('medical.adjustment', [], null, 'Adjustment'),
                                        'class' => 'medical-status-blue',
                                    ],

                                    'disposal' => [
                                        'label' => __('medical.disposal', [], null, 'Disposal'),
                                        'class' => 'medical-status-red',
                                    ],

                                    default => [
                                        'label' => $movement->type,
                                        'class' => 'medical-status-gray',
                                    ],
                                };

                            @endphp

                            <tr>

                                {{-- Transaction --}}
                                <td>

                                    <div class="transaction-cell">

                                        <div class="transaction-icon">
                                            ↻
                                        </div>

                                        <div>

                                            <div class="medical-table-primary medical-ltr">

                                                {{ $movement->transaction_number }}

                                            </div>

                                            @if($movement->reference_number)

                                                <div class="medical-table-secondary">

                                                    {{ __('medical.reference', [], null, 'Reference') }}:

                                                    {{ $movement->reference_number }}

                                                </div>

                                            @endif

                                        </div>

                                    </div>

                                </td>


                                {{-- Medicine --}}
                                <td>

                                    <div>

                                        <div class="medical-table-primary">
                                            {{ $medicineName }}
                                        </div>

                                        <div class="medical-table-secondary">

                                            <span class="medical-code">
                                                {{ $movement->code }}
                                            </span>

                                        </div>

                                    </div>

                                </td>


                                {{-- Type --}}
                                <td>

                                    <span class="medical-status {{ $movementType['class'] }}">
                                        {{ $movementType['label'] }}
                                    </span>

                                </td>


                                {{-- Quantity --}}
                                <td>

                                    <div class="quantity-cell">

                                        <strong>
                                            {{ rtrim(rtrim(number_format((float) $movement->quantity, 3, '.', ''), '0'), '.') }}
                                        </strong>

                                        <span>
                                            {{ __('medical.unit_' . ($movement->unit ?? 'unit'), [], null, $movement->unit ?? 'unit') }}
                                        </span>

                                    </div>

                                </td>


                                {{-- Clinic --}}
                                <td>

                                    <span class="clinic-name">
                                        {{ $clinicName ?: '—' }}
                                    </span>

                                </td>


                                {{-- Patient --}}
                                <td>

                                    @if($movement->patient_code)

                                        <span class="patient-code">
                                            {{ $movement->patient_code }}
                                        </span>

                                    @else

                                        <span class="muted-value">
                                            —
                                        </span>

                                    @endif

                                </td>


                                {{-- Date --}}
                                <td>

                                    <div class="date-cell">

                                        <strong>
                                            {{ \Carbon\Carbon::parse($movement->occurred_at)->format('Y-m-d') }}
                                        </strong>

                                        <span>
                                            {{ \Carbon\Carbon::parse($movement->occurred_at)->format('H:i') }}
                                        </span>

                                    </div>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>


            {{-- Pagination --}}
            <div class="medical-pagination">

                {{ $movements->links() }}

            </div>


        @else

            <div class="medical-empty">

                <div class="medical-empty-icon">
                    ↻
                </div>

                <h3>
                    {{ __('medical.no_movements', [], null, 'No medicine movements found') }}
                </h3>

                <p>

                    @if($q !== '' || $type !== 'all')

                        {{ __('medical.no_movements_filter_description', [], null, 'No movements match the selected search or filter.') }}

                    @else

                        {{ __('medical.no_movements_description', [], null, 'No medicine movements have been recorded yet.') }}

                    @endif

                </p>

            </div>

        @endif

    </section>


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

        .medical-card {
            margin-bottom: 20px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            box-shadow: 0 5px 18px rgba(15, 23, 42, .05);
            overflow: hidden;
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
            font-size: 18px;
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

        .medical-stat-icon-gray {
            background: #f1f5f9;
            color: #64748b;
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

        .medical-filter-card {
            padding: 0;
        }

        .medical-filter-inner {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 17px 20px;
        }

        .medical-search {
            position: relative;
            flex: 1;
        }

        .medical-search-icon {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 19px;
            pointer-events: none;
        }

        .medical-search-input {
            width: 100%;
            height: 46px;
            padding: 0 43px 0 42px;
            border: 1px solid #cbd5e1;
            border-radius: 11px;
            background: #fff;
            color: #0f172a;
            font-size: 13px;
            outline: none;
            box-sizing: border-box;
        }

        .medical-search-input:focus {
            border-color: #16a34a;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, .10);
        }

        .medical-search-clear {
            position: absolute;
            left: 9px;
            top: 50%;
            transform: translateY(-50%);
            width: 28px;
            height: 28px;
            border: 0;
            border-radius: 8px;
            background: #f1f5f9;
            color: #64748b;
            cursor: pointer;
            font-size: 17px;
        }

        .medical-filter-select {
            width: 190px;
        }

        .medical-input {
            width: 100%;
            min-height: 45px;
            padding: 10px 13px;
            border: 1px solid #cbd5e1;
            border-radius: 11px;
            background: #fff;
            color: #0f172a;
            font-size: 13px;
            outline: none;
            box-sizing: border-box;
        }

        .medical-input:focus {
            border-color: #16a34a;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, .10);
        }

        .medical-clear-btn {
            white-space: nowrap;
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
            min-width: 40px;
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

        .transaction-cell {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .transaction-icon {
            width: 38px;
            height: 38px;
            min-width: 38px;
            border-radius: 10px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
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

        .medical-code {
            display: inline-flex;
            padding: 4px 7px;
            border-radius: 7px;
            background: #f8fafc;
            color: #64748b;
            font-family: monospace;
            font-size: 11px;
            direction: ltr;
        }

        .medical-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
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

        .medical-status-orange {
            background: #fff7ed;
            color: #c2410c;
        }

        .medical-status-red {
            background: #fef2f2;
            color: #dc2626;
        }

        .medical-status-blue {
            background: #eff6ff;
            color: #2563eb;
        }

        .medical-status-gray {
            background: #f1f5f9;
            color: #64748b;
        }

        .quantity-cell {
            display: flex;
            align-items: baseline;
            gap: 5px;
            direction: ltr;
            white-space: nowrap;
        }

        .quantity-cell strong {
            color: #0f172a;
            font-size: 14px;
            font-weight: 900;
        }

        .quantity-cell span {
            color: #94a3b8;
            font-size: 10px;
        }

        .clinic-name {
            color: #475569;
            white-space: nowrap;
        }

        .patient-code {
            display: inline-flex;
            padding: 5px 8px;
            border-radius: 8px;
            background: #f8fafc;
            color: #475569;
            font-family: monospace;
            font-size: 11px;
            direction: ltr;
        }

        .muted-value {
            color: #cbd5e1;
        }

        .date-cell {
            display: flex;
            flex-direction: column;
            gap: 3px;
            white-space: nowrap;
        }

        .date-cell strong {
            color: #334155;
            font-size: 12px;
            direction: ltr;
        }

        .date-cell span {
            color: #94a3b8;
            font-size: 11px;
            direction: ltr;
        }

        .medical-ltr {
            direction: ltr;
            text-align: left;
        }

        .medical-pagination {
            padding: 18px 22px;
            border-top: 1px solid #eef2f7;
        }

        .medical-empty {
            padding: 60px 25px;
            text-align: center;
        }

        .medical-empty-icon {
            width: 54px;
            height: 54px;
            margin: 0 auto 14px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            color: #2563eb;
            font-size: 19px;
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

        }

        @media (max-width: 800px) {

            .medical-page-header {
                flex-direction: column;
            }

            .medical-filter-inner {
                flex-direction: column;
                align-items: stretch;
            }

            .medical-filter-select {
                width: 100%;
            }

            .medical-clear-btn {
                width: 100%;
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

@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';

    $formatNumber = static function ($value): string {
        return number_format((float) $value, 0);
    };
@endphp

<div class="medical-page">

    {{-- Medical Navigation --}}
    @include('livewire.admin.medical._nav')


    {{-- Page Header --}}
    <div class="medical-page-header">

        <div>
            <div class="medical-eyebrow">
                {{ __('medical.title', [], null, 'Medical Portal') }}
            </div>

            <h1 class="medical-page-title">
                {{ __('medical.reports', [], null, 'Medical Reports') }}
            </h1>

            <p class="medical-page-description">
                {{ __('medical.reports_description', [], null, 'Medical activity, medicines and inventory reports.') }}
            </p>
        </div>

        <div class="medical-page-header-actions">

            <a
                href="{{ route('admin.medical.index') }}"
                wire:navigate
                class="medical-btn medical-btn-secondary"
            >
                <span>←</span>

                {{ __('medical.back_to_dashboard', [], null, 'Back to dashboard') }}
            </a>

        </div>

    </div>


    {{-- Date Filter --}}
    <section class="medical-card medical-filter-card">

        <div class="medical-card-header">

            <div>
                <h2 class="medical-card-title">
                    {{ __('medical.report_period', [], null, 'Report period') }}
                </h2>

                <p class="medical-card-description">
                    {{ __('medical.report_period_description', [], null, 'Select the period for the medical report.') }}
                </p>
            </div>

        </div>


        <div class="medical-form-grid">

            {{-- From --}}
            <div class="medical-field">

                <label for="medical-report-from">
                    {{ __('medical.from_date', [], null, 'From date') }}
                </label>

                <input
                    id="medical-report-from"
                    type="date"
                    wire:model="from"
                    class="medical-input"
                >

                @error('from')
                    <div class="medical-error">
                        {{ $message }}
                    </div>
                @enderror

            </div>


            {{-- To --}}
            <div class="medical-field">

                <label for="medical-report-to">
                    {{ __('medical.to_date', [], null, 'To date') }}
                </label>

                <input
                    id="medical-report-to"
                    type="date"
                    wire:model="to"
                    class="medical-input"
                >

                @error('to')
                    <div class="medical-error">
                        {{ $message }}
                    </div>
                @enderror

            </div>


            {{-- Generate --}}
            <div class="medical-field medical-field-action">

                <button
                    type="button"
                    wire:click="generate"
                    wire:loading.attr="disabled"
                    class="medical-btn medical-btn-primary"
                >
                    <span wire:loading.remove wire:target="generate">
                        ↻
                    </span>

                    <span wire:loading wire:target="generate">
                        ...
                    </span>

                    {{ __('medical.generate_report', [], null, 'Generate report') }}
                </button>

            </div>

        </div>

    </section>


    {{-- Flash Message --}}
    @if(session()->has('medical_message'))

        <div class="medical-alert medical-alert-success">
            {{ session('medical_message') }}
        </div>

    @endif


    {{-- Main Statistics --}}
    <div class="medical-stats-grid">

        {{-- Visits --}}
        <div class="medical-stat-card">

            <div class="medical-stat-icon medical-stat-icon-blue">
                +
            </div>

            <div class="medical-stat-content">

                <span class="medical-stat-label">
                    {{ __('medical.visits', [], null, 'Medical visits') }}
                </span>

                <strong class="medical-stat-value">
                    {{ $formatNumber($visits) }}
                </strong>

                <span class="medical-stat-description">
                    {{ __('medical.report_visits_description', [], null, 'Visits during selected period') }}
                </span>

            </div>

        </div>


        {{-- Receipts --}}
        <div class="medical-stat-card">

            <div class="medical-stat-icon medical-stat-icon-green">
                +
            </div>

            <div class="medical-stat-content">

                <span class="medical-stat-label">
                    {{ __('medical.receipts', [], null, 'Medicine receipts') }}
                </span>

                <strong class="medical-stat-value">
                    {{ $formatNumber($receipts) }}
                </strong>

                <span class="medical-stat-description">
                    {{ __('medical.report_receipts_description', [], null, 'Units received during selected period') }}
                </span>

            </div>

        </div>


        {{-- Issues --}}
        <div class="medical-stat-card">

            <div class="medical-stat-icon medical-stat-icon-orange">
                −
            </div>

            <div class="medical-stat-content">

                <span class="medical-stat-label">
                    {{ __('medical.issues', [], null, 'Medicine issues') }}
                </span>

                <strong class="medical-stat-value">
                    {{ $formatNumber($issues) }}
                </strong>

                <span class="medical-stat-description">
                    {{ __('medical.report_issues_description', [], null, 'Issued, disposed or adjusted units') }}
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
                    {{ __('medical.expired', [], null, 'Expired batches') }}
                </span>

                <strong class="medical-stat-value">
                    {{ $formatNumber($expired) }}
                </strong>

                <span class="medical-stat-description">
                    {{ __('medical.report_expired_description', [], null, 'Expired batches with remaining stock') }}
                </span>

            </div>

        </div>

    </div>


    {{-- Top Medicines --}}
    <section class="medical-card medical-table-card">

        <div class="medical-card-header">

            <div>

                <h2 class="medical-card-title">
                    {{ __('medical.top_medicines', [], null, 'Most used medicines') }}
                </h2>

                <p class="medical-card-description">
                    {{ __('medical.top_medicines_description', [], null, 'Medicines with the highest issue and disposal quantities during the selected period.') }}
                </p>

            </div>

            <div class="medical-card-badge">
                {{ $top->count() }}
            </div>

        </div>


        @if($top->isNotEmpty())

            <div class="medical-table-wrapper">

                <table class="medical-table">

                    <thead>

                        <tr>

                            <th>
                                #
                            </th>

                            <th>
                                {{ __('medical.medicine', [], null, 'Medicine') }}
                            </th>

                            <th>
                                {{ __('medical.quantity', [], null, 'Quantity') }}
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @foreach($top as $index => $medicine)

                            <tr>

                                <td>
                                    <span class="medical-rank">
                                        {{ $index + 1 }}
                                    </span>
                                </td>

                                <td>

                                    <div class="medical-table-primary">

                                        @if($isArabic)
                                            {{ $medicine->name_ar }}
                                        @else
                                            {{ $medicine->name_en ?: $medicine->name_ar }}
                                        @endif

                                    </div>

                                </td>

                                <td>

                                    <strong class="medical-quantity">
                                        {{ $formatNumber($medicine->total) }}
                                    </strong>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        @else

            <div class="medical-empty">

                <div class="medical-empty-icon">
                    ✓
                </div>

                <h3>
                    {{ __('medical.no_report_data', [], null, 'No report data') }}
                </h3>

                <p>
                    {{ __('medical.no_report_data_description', [], null, 'There is no medicine activity during the selected period.') }}
                </p>

            </div>

        @endif

    </section>


    {{-- Report Summary --}}
    <section class="medical-card medical-summary-card">

        <div class="medical-card-header">

            <div>

                <h2 class="medical-card-title">
                    {{ __('medical.report_summary', [], null, 'Report summary') }}
                </h2>

                <p class="medical-card-description">
                    {{ __('medical.report_summary_description', [], null, 'Summary of the selected reporting period.') }}
                </p>

            </div>

        </div>


        <div class="medical-summary-grid">

            <div class="medical-summary-item">

                <span>
                    {{ __('medical.from_date', [], null, 'From date') }}
                </span>

                <strong>
                    {{ $from }}
                </strong>

            </div>


            <div class="medical-summary-item">

                <span>
                    {{ __('medical.to_date', [], null, 'To date') }}
                </span>

                <strong>
                    {{ $to }}
                </strong>

            </div>


            <div class="medical-summary-item">

                <span>
                    {{ __('medical.total_visits', [], null, 'Total visits') }}
                </span>

                <strong>
                    {{ $formatNumber($visits) }}
                </strong>

            </div>


            <div class="medical-summary-item">

                <span>
                    {{ __('medical.total_medicine_movement', [], null, 'Medicine movement') }}
                </span>

                <strong>
                    {{ $formatNumber((float) $receipts + (float) $issues) }}
                </strong>

            </div>

        </div>

    </section>


    <style>
        .medical-page {
            direction: rtl;
            padding-bottom: 40px;
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
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .medical-page-description {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 14px;
            line-height: 1.8;
        }

        .medical-page-header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .medical-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            box-shadow: 0 5px 18px rgba(15, 23, 42, 0.05);
            margin-bottom: 20px;
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

        .medical-filter-card {
            padding-bottom: 22px;
        }

        .medical-form-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            padding: 22px 24px 0;
        }

        .medical-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .medical-field label {
            color: #334155;
            font-size: 13px;
            font-weight: 700;
        }

        .medical-input {
            width: 100%;
            min-height: 44px;
            padding: 0 13px;
            border: 1px solid #cbd5e1;
            border-radius: 11px;
            background: #fff;
            color: #0f172a;
            font-size: 14px;
            outline: none;
            transition: 0.2s ease;
        }

        .medical-input:focus {
            border-color: #16a34a;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.10);
        }

        .medical-field-action {
            justify-content: flex-end;
        }

        .medical-btn {
            min-height: 44px;
            padding: 0 18px;
            border-radius: 11px;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .medical-btn-primary {
            background: #15803d;
            color: #fff;
        }

        .medical-btn-primary:hover {
            background: #166534;
        }

        .medical-btn-secondary {
            background: #fff;
            color: #334155;
            border-color: #cbd5e1;
        }

        .medical-btn-secondary:hover {
            background: #f8fafc;
        }

        .medical-alert {
            border-radius: 13px;
            padding: 13px 16px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 700;
        }

        .medical-alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .medical-error {
            color: #dc2626;
            font-size: 12px;
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
            box-shadow: 0 5px 18px rgba(15, 23, 42, 0.04);
        }

        .medical-stat-icon {
            width: 44px;
            height: 44px;
            min-width: 44px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 900;
        }

        .medical-stat-icon-blue {
            background: #eff6ff;
            color: #2563eb;
        }

        .medical-stat-icon-green {
            background: #f0fdf4;
            color: #16a34a;
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

        .medical-table-card {
            margin-bottom: 20px;
        }

        .medical-card-badge {
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            border-radius: 10px;
            background: #f0fdf4;
            color: #15803d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 800;
        }

        .medical-table-wrapper {
            overflow-x: auto;
        }

        .medical-table {
            width: 100%;
            border-collapse: collapse;
        }

        .medical-table th {
            background: #f8fafc;
            color: #64748b;
            font-size: 12px;
            font-weight: 800;
            padding: 13px 20px;
            text-align: right;
            white-space: nowrap;
        }

        .medical-table td {
            padding: 15px 20px;
            border-top: 1px solid #eef2f7;
            color: #334155;
            font-size: 13px;
        }

        .medical-table tbody tr:hover {
            background: #fafafa;
        }

        .medical-table-primary {
            color: #0f172a;
            font-weight: 800;
        }

        .medical-rank {
            width: 28px;
            height: 28px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            color: #475569;
            font-size: 12px;
            font-weight: 800;
        }

        .medical-quantity {
            color: #15803d;
            font-size: 14px;
        }

        .medical-empty {
            padding: 55px 25px;
            text-align: center;
        }

        .medical-empty-icon {
            width: 52px;
            height: 52px;
            margin: 0 auto 14px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0fdf4;
            color: #16a34a;
            font-size: 22px;
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

        .medical-summary-card {
            margin-bottom: 0;
        }

        .medical-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1px;
            background: #eef2f7;
        }

        .medical-summary-item {
            background: #fff;
            padding: 20px 22px;
        }

        .medical-summary-item span {
            display: block;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .medical-summary-item strong {
            color: #0f172a;
            font-size: 16px;
            font-weight: 900;
        }

        @media (max-width: 1100px) {
            .medical-stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .medical-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 800px) {
            .medical-page-header {
                flex-direction: column;
            }

            .medical-form-grid {
                grid-template-columns: 1fr;
            }

            .medical-field-action {
                justify-content: stretch;
            }

            .medical-field-action .medical-btn {
                width: 100%;
            }
        }

        @media (max-width: 600px) {
            .medical-stats-grid {
                grid-template-columns: 1fr;
            }

            .medical-summary-grid {
                grid-template-columns: 1fr;
            }

            .medical-page-title {
                font-size: 24px;
            }

            .medical-card-header {
                padding: 18px;
            }

            .medical-form-grid {
                padding: 18px;
            }
        }
    </style>

</div>

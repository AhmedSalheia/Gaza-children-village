@php
    /** @var \App\Livewire\Admin\Inventory\Stock\StockOverview $this */
@endphp

<div class="inventory-stock-page">

    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div class="inventory-stock-header">

        <div class="inventory-stock-header-content">

            <div class="inventory-stock-icon">

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

                    <path
                        d="M8.5 14.5h.01M8.5 17h.01"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                    />
                </svg>

            </div>

            <div>

                <div class="inventory-stock-eyebrow">
                    GCV INVENTORY
                </div>

                <h1 class="inventory-stock-title">
                    {{ __('ui.inventory.stock') }}
                </h1>

                <p class="inventory-stock-description">
                    {{ __('ui.inventory.description') }}
                </p>

            </div>

        </div>

    </div>


    {{-- =========================================================
         INVENTORY NAVIGATION
    ========================================================== --}}
    <div class="inventory-stock-navigation">

        @include('livewire.admin.inventory._nav')

    </div>


    {{-- =========================================================
         SUMMARY
    ========================================================== --}}
    <div class="inventory-stock-summary">

        {{-- Stock Lines --}}
        <div class="inventory-stock-summary-card">

            <div class="inventory-stock-summary-icon lines">

                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M5 5h14v14H5z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linejoin="round"
                    />

                    <path
                        d="M8 9h8M8 12h8M8 15h5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                    />
                </svg>

            </div>

            <div>

                <span>
                    {{ __('ui.inventory.stock_lines') }}
                </span>

                <strong>
                    {{ number_format($stocks->total()) }}
                </strong>

            </div>

        </div>


        {{-- Quantity --}}
        <div class="inventory-stock-summary-card">

            <div class="inventory-stock-summary-icon quantity">

                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M4 7.5 12 4l8 3.5v9L12 20l-8-3.5v-9Z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linejoin="round"
                    />

                    <path
                        d="M12 11v9M4 7.5 12 11l8-3.5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                    />
                </svg>

            </div>

            <div>

                <span>
                    {{ __('ui.inventory.total_quantity') }}
                </span>

                <strong>
                    {{ number_format($stocks->sum(fn($s) => (float) $s->quantity), 3) }}
                </strong>

            </div>

        </div>


        {{-- Reserved --}}
        <div class="inventory-stock-summary-card">

            <div class="inventory-stock-summary-icon reserved">

                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <rect
                        x="5"
                        y="4"
                        width="14"
                        height="16"
                        rx="2"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                    />

                    <path
                        d="M8 8h8M8 12h8M8 16h5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                    />
                </svg>

            </div>

            <div>

                <span>
                    {{ __('ui.inventory.reserved') }}
                </span>

                <strong>
                    {{ number_format($stocks->sum(fn($s) => (float) $s->reserved_quantity), 3) }}
                </strong>

            </div>

        </div>


        {{-- Available --}}
        <div class="inventory-stock-summary-card">

            <div class="inventory-stock-summary-icon available">

                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle
                        cx="12"
                        cy="12"
                        r="8.5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                    />

                    <path
                        d="m8.5 12 2.2 2.2 4.8-5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>

            </div>

            <div>

                <span>
                    {{ __('ui.inventory.available') }}
                </span>

                <strong>
                    {{ number_format($stocks->sum(fn($s) => max(0, (float) $s->quantity - (float) $s->reserved_quantity)), 3) }}
                </strong>

            </div>

        </div>

    </div>


    {{-- =========================================================
         FILTERS
    ========================================================== --}}
    <section class="inventory-stock-filter-card">

        <div class="inventory-stock-filter-header">

            <div class="inventory-stock-filter-icon">

                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M4 6h16M7 12h10M10 18h4"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                    />
                </svg>

            </div>

            <div>

                <h2>
                    {{ __('ui.inventory.stock') }}
                </h2>

                <p>
                    {{ __('ui.inventory.search_items') }}
                </p>

            </div>

        </div>


        <div class="inventory-stock-filters">

            {{-- Search --}}
            <div class="inventory-stock-search">

                <label for="stock-search">
                    {{ __('ui.inventory.item') }}
                </label>

                <div class="inventory-stock-input-wrapper">

                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle
                            cx="11"
                            cy="11"
                            r="6.5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                        />

                        <path
                            d="m16 16 4 4"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                        />
                    </svg>

                    <input
                        id="stock-search"
                        type="search"
                        wire:model.live.debounce.300ms="q"
                        placeholder="{{ __('ui.inventory.search_items') }}"
                    >

                </div>

            </div>


            {{-- Warehouse --}}
            <div class="inventory-stock-warehouse-filter">

                <label for="warehouse-filter">
                    {{ __('ui.inventory.warehouse') }}
                </label>

                <select
                    id="warehouse-filter"
                    wire:model.live="warehouseId"
                >

                    <option value="">
                        {{ __('ui.inventory.all_warehouses') }}
                    </option>

                    @foreach($warehouses as $w)

                        <option value="{{ $w->id }}">
                            {{ $w->name_ar }}
                        </option>

                    @endforeach

                </select>

            </div>

        </div>

    </section>


    {{-- =========================================================
         STOCK TABLE
    ========================================================== --}}
    <section class="inventory-stock-table-card">

        <div class="inventory-stock-table-header">

            <div>

                <span class="inventory-stock-table-eyebrow">
                    GCV DATA
                </span>

                <h2>
                    {{ __('ui.inventory.stock') }}
                </h2>

                <p>
                    {{ $stocks->total() }}
                    {{ __('ui.inventory.stock_lines') }}
                </p>

            </div>

            <div class="inventory-stock-table-count">

                {{ number_format($stocks->total()) }}

            </div>

        </div>


        @if($stocks->count())

            <div class="inventory-stock-table-wrapper">

                <table class="inventory-stock-table">

                    <thead>

                        <tr>

                            <th>
                                {{ __('ui.inventory.item') }}
                            </th>

                            <th>
                                {{ __('ui.inventory.warehouse') }}
                            </th>

                            <th>
                                {{ __('ui.inventory.quantity') }}
                            </th>

                            <th>
                                {{ __('ui.inventory.reserved') }}
                            </th>

                            <th>
                                {{ __('ui.inventory.available') }}
                            </th>

                            <th>
                                {{ __('ui.inventory.average_cost') }}
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @foreach($stocks as $s)

                            @php
                                $quantity = (float) $s->quantity;
                                $reserved = (float) $s->reserved_quantity;
                                $available = max(0, $quantity - $reserved);
                            @endphp

                            <tr>

                                {{-- Item --}}
                                <td>

                                    <div class="inventory-stock-item">

                                        <div class="inventory-stock-item-icon">

                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                <path
                                                    d="M4 7.5 12 4l8 3.5v9L12 20l-8-3.5v-9Z"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.6"
                                                    stroke-linejoin="round"
                                                />

                                                <path
                                                    d="M4 7.5 12 11l8-3.5M12 11v9"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.6"
                                                />
                                            </svg>

                                        </div>

                                        <div>

                                            <strong>
                                                {{ $s->item?->name_ar ?? '—' }}
                                            </strong>

                                            @if($s->item?->name_en)

                                                <small dir="ltr">
                                                    {{ $s->item->name_en }}
                                                </small>

                                            @endif

                                            @if($s->item?->sku)

                                                <span class="inventory-stock-sku">
                                                    SKU:
                                                    {{ $s->item->sku }}
                                                </span>

                                            @endif

                                        </div>

                                    </div>

                                </td>


                                {{-- Warehouse --}}
                                <td>

                                    <div class="inventory-stock-warehouse">

                                        <div class="inventory-stock-warehouse-icon">

                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                <path
                                                    d="M4 20h16M6 20V9h12v11M8 9V5h8v4M10 13h.01M14 13h.01M10 17h.01M14 17h.01"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.5"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                />
                                            </svg>

                                        </div>

                                        <span>
                                            {{ $s->warehouse?->name_ar ?? '—' }}
                                        </span>

                                    </div>

                                </td>


                                {{-- Quantity --}}
                                <td>

                                    <span class="inventory-stock-number quantity">
                                        {{ number_format($quantity, 3) }}
                                    </span>

                                </td>


                                {{-- Reserved --}}
                                <td>

                                    @if($reserved > 0)

                                        <span class="inventory-stock-number reserved">
                                            {{ number_format($reserved, 3) }}
                                        </span>

                                    @else

                                        <span class="inventory-stock-zero">
                                            0.000
                                        </span>

                                    @endif

                                </td>


                                {{-- Available --}}
                                <td>

                                    @if($available > 0)

                                        <span class="inventory-stock-available-pill">

                                            <span class="inventory-stock-status-dot"></span>

                                            {{ number_format($available, 3) }}

                                        </span>

                                    @else

                                        <span class="inventory-stock-empty-pill">

                                            <span class="inventory-stock-status-dot"></span>

                                            0.000

                                        </span>

                                    @endif

                                </td>


                                {{-- Average Cost --}}
                                <td>

                                    <span class="inventory-stock-cost">

                                        {{ number_format((float) $s->average_unit_cost, 3) }}

                                    </span>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>


            {{-- =================================================
                 PAGINATION
            ================================================== --}}
            <div class="inventory-stock-pagination">

                {{ $stocks->links() }}

            </div>

        @else

            <div class="inventory-stock-empty">

                <div class="inventory-stock-empty-icon">

                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M4 7.5 12 4l8 3.5v9L12 20l-8-3.5v-9Z"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.6"
                            stroke-linejoin="round"
                        />

                        <path
                            d="M9 12h6"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.6"
                            stroke-linecap="round"
                        />
                    </svg>

                </div>

                <strong>
                    {{ __('ui.inventory.no_data') }}
                </strong>

                <span>
                    {{ __('ui.inventory.stock') }}
                </span>

            </div>

        @endif

    </section>


    {{-- =========================================================
         STYLES
    ========================================================== --}}
    <style>

        .inventory-stock-page {
            direction: rtl;
            width: 100%;
            color: #172033;
        }


        /* =====================================================
           HEADER
        ====================================================== */

        .inventory-stock-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 20px;
            padding: 24px 26px;
            border: 1px solid #e7eaf0;
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 8px 28px rgba(15, 23, 42, .045);
        }

        .inventory-stock-header-content {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
        }

        .inventory-stock-icon {
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

        .inventory-stock-icon svg {
            width: 30px;
            height: 30px;
        }

        .inventory-stock-eyebrow {
            margin-bottom: 4px;
            color: #7b8497;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
        }

        .inventory-stock-title {
            margin: 0;
            color: #172033;
            font-size: 26px;
            line-height: 1.3;
            font-weight: 800;
        }

        .inventory-stock-description {
            max-width: 760px;
            margin: 7px 0 0;
            color: #697386;
            font-size: 13px;
            line-height: 1.7;
        }


        /* =====================================================
           NAVIGATION
        ====================================================== */

        .inventory-stock-navigation {
            margin-bottom: 20px;
        }


        /* =====================================================
           SUMMARY
        ====================================================== */

        .inventory-stock-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }

        .inventory-stock-summary-card {
            min-height: 92px;
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 16px;
            border: 1px solid #e7eaf0;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 5px 18px rgba(15, 23, 42, .035);
        }

        .inventory-stock-summary-icon {
            width: 44px;
            height: 44px;
            flex: 0 0 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
        }

        .inventory-stock-summary-icon svg {
            width: 22px;
            height: 22px;
        }

        .inventory-stock-summary-icon.lines {
            background: #eef4ff;
            color: #315efb;
        }

        .inventory-stock-summary-icon.quantity {
            background: #ecfdf3;
            color: #12b76a;
        }

        .inventory-stock-summary-icon.reserved {
            background: #fff7e8;
            color: #e89419;
        }

        .inventory-stock-summary-icon.available {
            background: #f4efff;
            color: #7a5af8;
        }

        .inventory-stock-summary-card span {
            display: block;
            margin-bottom: 4px;
            color: #7b8497;
            font-size: 11px;
            font-weight: 650;
        }

        .inventory-stock-summary-card strong {
            display: block;
            color: #172033;
            font-size: 21px;
            line-height: 1;
            font-weight: 850;
        }


        /* =====================================================
           FILTER CARD
        ====================================================== */

        .inventory-stock-filter-card {
            margin-bottom: 20px;
            padding: 20px 22px;
            border: 1px solid #e7eaf0;
            border-radius: 17px;
            background: #ffffff;
            box-shadow: 0 7px 25px rgba(15, 23, 42, .04);
        }

        .inventory-stock-filter-header {
            display: flex;
            align-items: center;
            gap: 11px;
            margin-bottom: 17px;
        }

        .inventory-stock-filter-icon {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #f4f6fa;
            color: #667085;
        }

        .inventory-stock-filter-icon svg {
            width: 19px;
            height: 19px;
        }

        .inventory-stock-filter-header h2 {
            margin: 0;
            color: #344054;
            font-size: 13px;
            font-weight: 800;
        }

        .inventory-stock-filter-header p {
            margin: 3px 0 0;
            color: #98a1b2;
            font-size: 10px;
        }

        .inventory-stock-filters {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 14px;
        }

        .inventory-stock-filters label {
            display: block;
            margin-bottom: 7px;
            color: #344054;
            font-size: 11px;
            font-weight: 750;
        }

        .inventory-stock-input-wrapper {
            position: relative;
        }

        .inventory-stock-input-wrapper svg {
            position: absolute;
            top: 50%;
            right: 12px;
            width: 17px;
            height: 17px;
            color: #98a1b2;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .inventory-stock-input-wrapper input,
        .inventory-stock-warehouse-filter select {
            width: 100%;
            min-height: 42px;
            box-sizing: border-box;
            padding: 0 12px;
            border: 1px solid #d9dee7;
            border-radius: 9px;
            outline: none;
            background: #ffffff;
            color: #344054;
            font-family: inherit;
            font-size: 12px;
            transition: .15s ease;
        }

        .inventory-stock-input-wrapper input {
            padding-right: 37px;
        }

        .inventory-stock-input-wrapper input:focus,
        .inventory-stock-warehouse-filter select:focus {
            border-color: #315efb;
            box-shadow: 0 0 0 3px rgba(49, 94, 251, .08);
        }

        .inventory-stock-input-wrapper input::placeholder {
            color: #a0a8b6;
        }


        /* =====================================================
           TABLE CARD
        ====================================================== */

        .inventory-stock-table-card {
            overflow: hidden;
            border: 1px solid #e7eaf0;
            border-radius: 17px;
            background: #ffffff;
            box-shadow: 0 7px 25px rgba(15, 23, 42, .04);
        }

        .inventory-stock-table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 21px 23px;
            border-bottom: 1px solid #edf0f4;
        }

        .inventory-stock-table-eyebrow {
            display: block;
            margin-bottom: 4px;
            color: #315efb;
            font-size: 9px;
            font-weight: 850;
            letter-spacing: .07em;
        }

        .inventory-stock-table-header h2 {
            margin: 0;
            color: #172033;
            font-size: 18px;
            font-weight: 800;
        }

        .inventory-stock-table-header p {
            margin: 4px 0 0;
            color: #7a8394;
            font-size: 11px;
        }

        .inventory-stock-table-count {
            min-width: 39px;
            height: 39px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 10px;
            border-radius: 10px;
            background: #eef4ff;
            color: #315efb;
            font-size: 13px;
            font-weight: 850;
        }


        /* =====================================================
           TABLE
        ====================================================== */

        .inventory-stock-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .inventory-stock-table {
            width: 100%;
            min-width: 950px;
            border-collapse: collapse;
        }

        .inventory-stock-table thead th {
            padding: 12px 16px;
            border-bottom: 1px solid #e9edf2;
            background: #fafbfc;
            color: #7a8394;
            font-size: 10px;
            font-weight: 800;
            text-align: right;
            white-space: nowrap;
        }

        .inventory-stock-table tbody td {
            padding: 14px 16px;
            border-bottom: 1px solid #f0f2f5;
            color: #475467;
            font-size: 11px;
            vertical-align: middle;
        }

        .inventory-stock-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .inventory-stock-table tbody tr {
            transition: .15s ease;
        }

        .inventory-stock-table tbody tr:hover {
            background: #fbfcff;
        }


        /* =====================================================
           ITEM
        ====================================================== */

        .inventory-stock-item {
            display: flex;
            align-items: center;
            gap: 9px;
            min-width: 230px;
        }

        .inventory-stock-item-icon {
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            background: #eef4ff;
            color: #315efb;
        }

        .inventory-stock-item-icon svg {
            width: 19px;
            height: 19px;
        }

        .inventory-stock-item strong {
            display: block;
            color: #344054;
            font-size: 11px;
            font-weight: 800;
        }

        .inventory-stock-item small {
            display: block;
            margin-top: 2px;
            color: #98a1b2;
            font-size: 9px;
        }

        .inventory-stock-sku {
            display: inline-block;
            margin-top: 3px;
            color: #98a1b2;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 8px;
            direction: ltr;
        }


        /* =====================================================
           WAREHOUSE
        ====================================================== */

        .inventory-stock-warehouse {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 150px;
        }

        .inventory-stock-warehouse-icon {
            width: 31px;
            height: 31px;
            flex: 0 0 31px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #f4f6fa;
            color: #667085;
        }

        .inventory-stock-warehouse-icon svg {
            width: 16px;
            height: 16px;
        }

        .inventory-stock-warehouse span {
            color: #475467;
            font-size: 10px;
            font-weight: 700;
        }


        /* =====================================================
           NUMBERS
        ====================================================== */

        .inventory-stock-number {
            display: inline-flex;
            align-items: center;
            min-height: 29px;
            padding: 0 9px;
            border-radius: 7px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 10px;
            font-weight: 800;
            direction: ltr;
        }

        .inventory-stock-number.quantity {
            background: #ecfdf3;
            color: #087443;
        }

        .inventory-stock-number.reserved {
            background: #fff7e8;
            color: #b76e00;
        }

        .inventory-stock-zero {
            color: #98a1b2;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 10px;
        }


        /* =====================================================
           AVAILABLE
        ====================================================== */

        .inventory-stock-available-pill,
        .inventory-stock-empty-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 29px;
            padding: 0 9px;
            border-radius: 999px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 10px;
            font-weight: 800;
            direction: ltr;
        }

        .inventory-stock-available-pill {
            background: #f4efff;
            color: #6941c6;
        }

        .inventory-stock-empty-pill {
            background: #f2f4f7;
            color: #667085;
        }

        .inventory-stock-status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }


        /* =====================================================
           COST
        ====================================================== */

        .inventory-stock-cost {
            display: inline-flex;
            align-items: center;
            min-height: 29px;
            padding: 0 9px;
            border-radius: 7px;
            background: #f4f6f8;
            color: #475467;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 10px;
            font-weight: 750;
            direction: ltr;
        }


        /* =====================================================
           PAGINATION
        ====================================================== */

        .inventory-stock-pagination {
            padding: 16px 20px;
            border-top: 1px solid #edf0f4;
        }


        /* =====================================================
           EMPTY
        ====================================================== */

        .inventory-stock-empty {
            padding: 65px 20px;
            text-align: center;
        }

        .inventory-stock-empty-icon {
            width: 58px;
            height: 58px;
            margin: 0 auto 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: #f5f7fa;
            color: #98a1b2;
        }

        .inventory-stock-empty-icon svg {
            width: 28px;
            height: 28px;
        }

        .inventory-stock-empty strong {
            display: block;
            color: #667085;
            font-size: 13px;
        }

        .inventory-stock-empty span {
            display: block;
            margin-top: 4px;
            color: #98a1b2;
            font-size: 10px;
        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 1050px) {

            .inventory-stock-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

        }

        @media (max-width: 760px) {

            .inventory-stock-header {
                padding: 20px;
            }

            .inventory-stock-filters {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 520px) {

            .inventory-stock-summary {
                grid-template-columns: 1fr;
            }

            .inventory-stock-title {
                font-size: 21px;
            }

            .inventory-stock-header {
                border-radius: 14px;
            }

            .inventory-stock-filter-card,
            .inventory-stock-table-header {
                padding: 17px;
            }

        }

    </style>

</div>

@php
    /**
     * GCV Inventory — Items Index
     *
     * Livewire:
     * App\Livewire\Admin\Inventory\Items\ItemIndex
     */
@endphp

<div class="inventory-items-page">

    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div class="inventory-items-header">

        <div class="inventory-items-header-content">

            <div class="inventory-items-icon">
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

            <div>

                <div class="inventory-items-eyebrow">
                    GCV INVENTORY
                </div>

                <h1 class="inventory-items-title">
                    {{ __('ui.inventory.items') }}
                </h1>

                <p class="inventory-items-description">
                    {{ __('ui.inventory.description') }}
                </p>

            </div>

        </div>

        <div class="inventory-items-header-actions">

            <a
                href="{{ route('admin.inventory.items.create') }}"
                wire:navigate
                class="inventory-items-primary-btn"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M12 5v14M5 12h14"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                    />
                </svg>

                <span>
                    {{ __('ui.inventory.add_item') }}
                </span>
            </a>

        </div>

    </div>


    {{-- =========================================================
         INVENTORY NAVIGATION
    ========================================================== --}}
    @include('livewire.admin.inventory._nav')


    {{-- =========================================================
         SUMMARY
    ========================================================== --}}
    <div class="inventory-items-summary">

        <div class="inventory-items-summary-card">

            <div class="inventory-items-summary-icon">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M4 7.5 12 4l8 3.5v9L12 20l-8-3.5v-9Z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linejoin="round"
                    />
                </svg>
            </div>

            <div>
                <span>
                    {{ __('ui.inventory.items') }}
                </span>

                <strong>
                    {{ number_format($items->total()) }}
                </strong>
            </div>

        </div>

        <div class="inventory-items-summary-card">

            <div class="inventory-items-summary-icon active">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="m5 12 4 4L19 6"
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
                    {{ __('ui.inventory.active') }}
                </span>

                <strong>
                    {{ $status === 'active' ? number_format($items->total()) : '—' }}
                </strong>
            </div>

        </div>

        <div class="inventory-items-summary-card">

            <div class="inventory-items-summary-icon inactive">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M6 6l12 12M18 6 6 18"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                    />
                </svg>
            </div>

            <div>
                <span>
                    {{ __('ui.inventory.inactive') }}
                </span>

                <strong>
                    {{ $status === 'inactive' ? number_format($items->total()) : '—' }}
                </strong>
            </div>

        </div>

    </div>


    {{-- =========================================================
         FILTERS
    ========================================================== --}}
    <section class="inventory-items-card">

        <div class="inventory-items-card-header">

            <div>

                <span class="inventory-items-section-label">
                    SEARCH & FILTER
                </span>

                <h2>
                    {{ __('ui.inventory.search_items') }}
                </h2>

            </div>

            <div class="inventory-items-card-header-icon">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle
                        cx="11"
                        cy="11"
                        r="6"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                    />
                    <path
                        d="m16 16 5 5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                    />
                </svg>
            </div>

        </div>


        <div class="inventory-items-filters">

            {{-- Search --}}
            <div class="inventory-items-search">

                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle
                        cx="11"
                        cy="11"
                        r="6"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                    />

                    <path
                        d="m16 16 5 5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                    />
                </svg>

                <input
                    type="search"
                    wire:model.live.debounce.300ms="q"
                    placeholder="{{ __('ui.inventory.search_items') }}"
                >

                @if ($q !== '')

                    <button
                        type="button"
                        wire:click="$set('q', '')"
                        class="inventory-items-clear"
                        aria-label="{{ __('ui.inventory.cancel') }}"
                    >
                        ×
                    </button>

                @endif

            </div>


            {{-- Status --}}
            <div class="inventory-items-status-filter">

                <label for="inventory-status-filter">
                    {{ __('ui.inventory.status') }}
                </label>

                <select
                    id="inventory-status-filter"
                    wire:model.live="status"
                >

                    <option value="all">
                        {{ __('ui.inventory.all') }}
                    </option>

                    <option value="active">
                        {{ __('ui.inventory.active') }}
                    </option>

                    <option value="inactive">
                        {{ __('ui.inventory.inactive') }}
                    </option>

                </select>

            </div>

        </div>

    </section>


    {{-- =========================================================
         ITEMS TABLE
    ========================================================== --}}
    <section class="inventory-items-card inventory-items-table-card">

        <div class="inventory-items-card-header">

            <div>

                <span class="inventory-items-section-label">
                    GCV DATA
                </span>

                <h2>
                    {{ __('ui.inventory.items') }}
                </h2>

                <p>
                    {{ number_format($items->total()) }}
                    {{ __('ui.inventory.items') }}
                </p>

            </div>

            <div class="inventory-items-card-header-icon">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M5 4h14v16H5z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linejoin="round"
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

        </div>


        <div class="inventory-items-table-wrapper">

            <table class="inventory-items-table">

                <thead>

                    <tr>

                        <th>
                            SKU
                        </th>

                        <th>
                            {{ __('ui.inventory.name') }}
                        </th>

                        <th>
                            {{ __('ui.inventory.category') }}
                        </th>

                        <th>
                            {{ __('ui.inventory.unit') }}
                        </th>

                        <th>
                            {{ __('ui.inventory.status') }}
                        </th>

                        <th class="inventory-items-actions-column">
                            {{ __('ui.inventory.view') }}
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse ($items as $item)

                        <tr>

                            {{-- SKU --}}
                            <td>

                                <span class="inventory-items-sku">
                                    {{ $item->sku }}
                                </span>

                            </td>


                            {{-- Name --}}
                            <td>

                                <div class="inventory-items-name">

                                    <strong>
                                        {{ $item->name_ar }}
                                    </strong>

                                    @if ($item->name_en)

                                        <small>
                                            {{ $item->name_en }}
                                        </small>

                                    @endif

                                </div>

                            </td>


                            {{-- Category --}}
                            <td>

                                <span class="inventory-items-secondary">
                                    {{ $item->category?->name_ar ?? '—' }}
                                </span>

                            </td>


                            {{-- Unit --}}
                            <td>

                                <span class="inventory-items-secondary">
                                    {{ $item->unit?->name_ar ?? '—' }}
                                </span>

                            </td>


                            {{-- Status --}}
                            <td>

                                @if ($item->is_active)

                                    <span class="inventory-items-status inventory-items-status-active">

                                        <span></span>

                                        {{ __('ui.inventory.active') }}

                                    </span>

                                @else

                                    <span class="inventory-items-status inventory-items-status-inactive">

                                        <span></span>

                                        {{ __('ui.inventory.inactive') }}

                                    </span>

                                @endif

                            </td>


                            {{-- Actions --}}
                            <td>

                                <div class="inventory-items-row-actions">

                                    <a
                                        href="{{ route('admin.inventory.items.edit', $item) }}"
                                        wire:navigate
                                        class="inventory-items-edit-btn"
                                    >

                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path
                                                d="m14 6 4 4M5 19l3.5-.7L18.5 8.3a2.12 2.12 0 0 0-3-3L5.5 15.3 5 19Z"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="1.6"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>

                                        {{ __('ui.inventory.edit') }}

                                    </a>


                                    <button
                                        type="button"
                                        wire:click="toggle({{ $item->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="toggle({{ $item->id }})"
                                        class="inventory-items-toggle-btn {{ $item->is_active ? 'deactivate' : 'activate' }}"
                                    >

                                        @if ($item->is_active)

                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                <path
                                                    d="M6 6l12 12M18 6 6 18"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                    stroke-linecap="round"
                                                />
                                            </svg>

                                            {{ __('ui.inventory.inactive') }}

                                        @else

                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                <path
                                                    d="m5 12 4 4L19 6"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                />
                                            </svg>

                                            {{ __('ui.inventory.active') }}

                                        @endif

                                    </button>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="6"
                                class="inventory-items-empty"
                            >

                                <div class="inventory-items-empty-icon">

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

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- Pagination --}}
        @if ($items->hasPages())

            <div class="inventory-items-pagination">

                {{ $items->links() }}

            </div>

        @endif

    </section>


    {{-- =========================================================
         LOADING INDICATOR
    ========================================================== --}}
    <div
        wire:loading
        wire:target="q,status,toggle"
        class="inventory-items-loading"
    >

        <span class="inventory-items-loading-dot"></span>

        <span>
            {{ __('ui.inventory.search_items') }}...
        </span>

    </div>


    {{-- =========================================================
         STYLES
    ========================================================== --}}
    <style>

        .inventory-items-page {
            direction: rtl;
            width: 100%;
            color: #172033;
        }


        /* =====================================================
           HEADER
        ====================================================== */

        .inventory-items-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 24px;
            padding: 24px 26px;
            border: 1px solid #e7eaf0;
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 8px 28px rgba(15, 23, 42, .045);
        }

        .inventory-items-header-content {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
        }

        .inventory-items-icon {
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

        .inventory-items-icon svg {
            width: 30px;
            height: 30px;
        }

        .inventory-items-eyebrow {
            margin-bottom: 4px;
            color: #7b8497;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
        }

        .inventory-items-title {
            margin: 0;
            color: #172033;
            font-size: 26px;
            line-height: 1.3;
            font-weight: 800;
        }

        .inventory-items-description {
            margin: 7px 0 0;
            max-width: 760px;
            color: #697386;
            font-size: 13px;
            line-height: 1.8;
        }

        .inventory-items-header-actions {
            flex-shrink: 0;
        }

        .inventory-items-primary-btn {
            min-height: 43px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 17px;
            border-radius: 10px;
            background: #315efb;
            color: #ffffff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 750;
            box-shadow: 0 7px 18px rgba(49, 94, 251, .18);
            transition: .18s ease;
        }

        .inventory-items-primary-btn:hover {
            background: #264edb;
            color: #ffffff;
            transform: translateY(-1px);
        }

        .inventory-items-primary-btn svg {
            width: 18px;
            height: 18px;
        }


        /* =====================================================
           SUMMARY
        ====================================================== */

        .inventory-items-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .inventory-items-summary-card {
            display: flex;
            align-items: center;
            gap: 13px;
            min-width: 0;
            padding: 17px;
            border: 1px solid #e7eaf0;
            border-radius: 15px;
            background: #ffffff;
            box-shadow: 0 6px 22px rgba(15, 23, 42, .035);
        }

        .inventory-items-summary-icon {
            width: 44px;
            height: 44px;
            flex: 0 0 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #eef3ff;
            color: #315efb;
        }

        .inventory-items-summary-icon.active {
            background: #edf9f2;
            color: #238b57;
        }

        .inventory-items-summary-icon.inactive {
            background: #f5f6f8;
            color: #7d8594;
        }

        .inventory-items-summary-icon svg {
            width: 22px;
            height: 22px;
        }

        .inventory-items-summary-card span {
            display: block;
            margin-bottom: 3px;
            color: #727b8e;
            font-size: 12px;
            font-weight: 600;
        }

        .inventory-items-summary-card strong {
            display: block;
            color: #172033;
            font-size: 21px;
            line-height: 1.2;
            font-weight: 800;
        }


        /* =====================================================
           CARD
        ====================================================== */

        .inventory-items-card {
            margin-bottom: 18px;
            padding: 22px;
            border: 1px solid #e7eaf0;
            border-radius: 17px;
            background: #ffffff;
            box-shadow: 0 7px 25px rgba(15, 23, 42, .04);
        }

        .inventory-items-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding-bottom: 18px;
            margin-bottom: 18px;
            border-bottom: 1px solid #edf0f4;
        }

        .inventory-items-section-label {
            display: block;
            margin-bottom: 5px;
            color: #315efb;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .07em;
        }

        .inventory-items-card-header h2 {
            margin: 0;
            color: #172033;
            font-size: 18px;
            font-weight: 800;
        }

        .inventory-items-card-header p {
            margin: 6px 0 0;
            color: #7a8394;
            font-size: 12px;
        }

        .inventory-items-card-header-icon {
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

        .inventory-items-card-header-icon svg {
            width: 21px;
            height: 21px;
        }


        /* =====================================================
           FILTERS
        ====================================================== */

        .inventory-items-filters {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 230px;
            gap: 14px;
            align-items: end;
        }

        .inventory-items-search {
            position: relative;
            display: flex;
            align-items: center;
        }

        .inventory-items-search > svg {
            position: absolute;
            inset-inline-start: 13px;
            width: 19px;
            height: 19px;
            color: #98a1b2;
            pointer-events: none;
        }

        .inventory-items-search input {
            width: 100%;
            height: 45px;
            box-sizing: border-box;
            padding: 0 42px 0 38px;
            border: 1px solid #dfe3ea;
            border-radius: 10px;
            outline: none;
            background: #ffffff;
            color: #172033;
            font-family: inherit;
            font-size: 13px;
            transition: .18s ease;
        }

        .inventory-items-search input:focus {
            border-color: #8ea7ff;
            box-shadow: 0 0 0 3px rgba(49, 94, 251, .09);
        }

        .inventory-items-clear {
            position: absolute;
            inset-inline-end: 10px;
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 7px;
            background: #f0f2f5;
            color: #667085;
            font-size: 18px;
            line-height: 1;
            cursor: pointer;
        }

        .inventory-items-status-filter label {
            display: block;
            margin-bottom: 7px;
            color: #344054;
            font-size: 12px;
            font-weight: 750;
        }

        .inventory-items-status-filter select {
            width: 100%;
            height: 45px;
            padding: 0 12px;
            border: 1px solid #dfe3ea;
            border-radius: 10px;
            outline: none;
            background: #ffffff;
            color: #172033;
            font-family: inherit;
            font-size: 13px;
        }

        .inventory-items-status-filter select:focus {
            border-color: #8ea7ff;
            box-shadow: 0 0 0 3px rgba(49, 94, 251, .09);
        }


        /* =====================================================
           TABLE
        ====================================================== */

        .inventory-items-table-card {
            overflow: hidden;
        }

        .inventory-items-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .inventory-items-table {
            width: 100%;
            min-width: 850px;
            border-collapse: separate;
            border-spacing: 0;
        }

        .inventory-items-table th {
            padding: 12px 13px;
            border-bottom: 1px solid #e6e9ee;
            background: #fafbfc;
            color: #667085;
            font-size: 11px;
            font-weight: 800;
            text-align: right;
            white-space: nowrap;
        }

        .inventory-items-table th:first-child {
            border-top-right-radius: 9px;
        }

        .inventory-items-table th:last-child {
            border-top-left-radius: 9px;
        }

        .inventory-items-table td {
            padding: 14px 13px;
            border-bottom: 1px solid #edf0f4;
            color: #344054;
            font-size: 12px;
            vertical-align: middle;
        }

        .inventory-items-table tbody tr {
            transition: background .15s ease;
        }

        .inventory-items-table tbody tr:hover {
            background: #fbfcfe;
        }

        .inventory-items-table tbody tr:last-child td {
            border-bottom: 0;
        }


        /* SKU */
        .inventory-items-sku {
            display: inline-flex;
            align-items: center;
            padding: 5px 8px;
            border: 1px solid #e3e7ee;
            border-radius: 7px;
            background: #f8f9fb;
            color: #475467;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 11px;
            font-weight: 700;
            direction: ltr;
        }


        /* Name */
        .inventory-items-name strong {
            display: block;
            color: #263044;
            font-size: 13px;
            font-weight: 800;
        }

        .inventory-items-name small {
            display: block;
            margin-top: 3px;
            color: #98a1b2;
            font-size: 10px;
            direction: ltr;
            text-align: right;
        }

        .inventory-items-secondary {
            color: #667085;
            font-size: 12px;
        }


        /* Status */
        .inventory-items-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 800;
            white-space: nowrap;
        }

        .inventory-items-status > span {
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }

        .inventory-items-status-active {
            background: #edf9f2;
            color: #238b57;
        }

        .inventory-items-status-active > span {
            background: #2eaa6a;
        }

        .inventory-items-status-inactive {
            background: #f1f2f4;
            color: #717987;
        }

        .inventory-items-status-inactive > span {
            background: #98a1b2;
        }


        /* Row actions */
        .inventory-items-actions-column {
            width: 190px;
        }

        .inventory-items-row-actions {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 7px;
            white-space: nowrap;
        }

        .inventory-items-edit-btn,
        .inventory-items-toggle-btn {
            min-height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 0 10px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 10px;
            font-weight: 750;
            text-decoration: none;
            cursor: pointer;
            transition: .18s ease;
        }

        .inventory-items-edit-btn {
            border: 1px solid #dce4ff;
            background: #f3f6ff;
            color: #315efb;
        }

        .inventory-items-edit-btn:hover {
            background: #e9eeff;
            color: #264edb;
        }

        .inventory-items-toggle-btn {
            border: 1px solid #e1e4e9;
            background: #ffffff;
            color: #667085;
        }

        .inventory-items-toggle-btn.deactivate {
            border-color: #f0d4d4;
            background: #fff8f8;
            color: #b54747;
        }

        .inventory-items-toggle-btn.activate {
            border-color: #d4eadc;
            background: #f5fcf7;
            color: #238b57;
        }

        .inventory-items-edit-btn svg,
        .inventory-items-toggle-btn svg {
            width: 15px;
            height: 15px;
        }

        .inventory-items-toggle-btn:disabled {
            opacity: .55;
            cursor: wait;
        }


        /* Empty */
        .inventory-items-empty {
            padding: 55px 20px !important;
            text-align: center !important;
        }

        .inventory-items-empty-icon {
            width: 54px;
            height: 54px;
            margin: 0 auto 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: #f5f7fa;
            color: #98a1b2;
        }

        .inventory-items-empty-icon svg {
            width: 27px;
            height: 27px;
        }

        .inventory-items-empty strong {
            display: block;
            color: #667085;
            font-size: 13px;
        }


        /* =====================================================
           PAGINATION
        ====================================================== */

        .inventory-items-pagination {
            padding-top: 18px;
            margin-top: 2px;
            border-top: 1px solid #edf0f4;
        }


        /* =====================================================
           LOADING
        ====================================================== */

        .inventory-items-loading {
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
            backdrop-filter: blur(8px);
        }

        .inventory-items-loading-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #315efb;
            animation: inventoryItemsPulse 1s infinite ease-in-out;
        }

        @keyframes inventoryItemsPulse {

            0%,
            100% {
                opacity: .35;
                transform: scale(.85);
            }

            50% {
                opacity: 1;
                transform: scale(1);
            }

        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 900px) {

            .inventory-items-summary {
                grid-template-columns: 1fr;
            }

            .inventory-items-filters {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 760px) {

            .inventory-items-header {
                flex-direction: column;
                align-items: stretch;
            }

            .inventory-items-header-actions {
                width: 100%;
            }

            .inventory-items-primary-btn {
                width: 100%;
            }

            .inventory-items-card {
                padding: 18px;
            }

            .inventory-items-title {
                font-size: 21px;
            }

            .inventory-items-description {
                font-size: 12px;
            }

        }

        @media (max-width: 520px) {

            .inventory-items-header {
                padding: 18px;
                border-radius: 14px;
            }

            .inventory-items-header-content {
                align-items: flex-start;
            }

            .inventory-items-icon {
                width: 48px;
                height: 48px;
                flex-basis: 48px;
            }

            .inventory-items-summary-card {
                padding: 14px;
            }

            .inventory-items-card-header h2 {
                font-size: 16px;
            }

        }

    </style>

</div>

<div class="inventory-movements-page">

    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div class="inventory-page-header">

        <div class="inventory-page-header-main">

            <div class="inventory-page-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M8 6h13"/>
                    <path d="M8 12h13"/>
                    <path d="M8 18h13"/>
                    <path d="M3 6h.01"/>
                    <path d="M3 12h.01"/>
                    <path d="M3 18h.01"/>
                </svg>
            </div>

            <div>
                <div class="inventory-eyebrow">
                    GCV INVENTORY
                </div>

                <h1 class="inventory-page-title">
                    {{ __('ui.inventory.movements') }}
                </h1>

                <p class="inventory-page-description">
                    {{ __('ui.inventory.description') }}
                </p>
            </div>

        </div>

        <a
            class="inventory-primary-btn"
            href="{{ route('admin.inventory.movements.index') }}"
            wire:navigate
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14"/>
                <path d="M5 12h14"/>
            </svg>

            <span>{{ __('ui.inventory.new_movement') }}</span>
        </a>

    </div>


    {{-- =========================================================
         INVENTORY NAVIGATION
    ========================================================== --}}
    @include('livewire.admin.inventory._nav')


    {{-- =========================================================
         SUMMARY CARDS
    ========================================================== --}}
    <div class="movement-summary-grid">

        <div class="movement-summary-card">
            <div class="movement-summary-icon total">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M4 5h16v14H4z"/>
                    <path d="M8 9h8"/>
                    <path d="M8 13h8"/>
                    <path d="M8 17h5"/>
                </svg>
            </div>

            <div class="movement-summary-content">
                <span>{{ __('ui.inventory.movements') }}</span>
                <strong>{{ number_format($movements->total()) }}</strong>
            </div>
        </div>


        <div class="movement-summary-card">
            <div class="movement-summary-icon receipt">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M12 19V5"/>
                    <path d="m6 11 6-6 6 6"/>
                </svg>
            </div>

            <div class="movement-summary-content">
                <span>{{ __('ui.inventory.types.receipt') }}</span>

                <strong>
                    {{ number_format(
                        $movements->getCollection()->where('type', 'receipt')->count()
                    ) }}
                </strong>
            </div>
        </div>


        <div class="movement-summary-card">
            <div class="movement-summary-icon issue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M12 5v14"/>
                    <path d="m18 13-6 6-6-6"/>
                </svg>
            </div>

            <div class="movement-summary-content">
                <span>{{ __('ui.inventory.types.issue') }}</span>

                <strong>
                    {{ number_format(
                        $movements->getCollection()->where('type', 'issue')->count()
                    ) }}
                </strong>
            </div>
        </div>


        <div class="movement-summary-card">
            <div class="movement-summary-icon transfer">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M7 7h11"/>
                    <path d="m14 4 3 3-3 3"/>
                    <path d="M17 17H6"/>
                    <path d="m10 14-3 3 3 3"/>
                </svg>
            </div>

            <div class="movement-summary-content">
                <span>{{ __('ui.inventory.transfer') }}</span>

                <strong>
                    {{ number_format(
                        $movements->getCollection()
                            ->whereIn('type', ['transfer_out', 'transfer_in'])
                            ->count()
                    ) }}
                </strong>
            </div>
        </div>

    </div>


    {{-- =========================================================
         FILTERS
    ========================================================== --}}
    <div class="inventory-filter-card">

        <div class="inventory-filter-header">

            <div class="inventory-filter-title">
                <div class="inventory-filter-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M4 5h16"/>
                        <path d="M7 12h10"/>
                        <path d="M10 19h4"/>
                    </svg>
                </div>

                <div>
                    <strong>{{ __('ui.inventory.movements') }}</strong>

                    <span>
                        {{ __('ui.inventory.search_movement') }}
                    </span>
                </div>
            </div>

        </div>


        <div class="inventory-filter-grid">

            {{-- Search --}}
            <div class="inventory-field">

                <label>
                    {{ __('ui.inventory.search_movement') }}
                </label>

                <div class="inventory-input-wrapper">

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m20 20-4-4"/>
                    </svg>

                    <input
                        type="text"
                        class="inventory-input"
                        wire:model.live.debounce.300ms="q"
                        placeholder="{{ __('ui.inventory.search_movement') }}"
                    >

                </div>

            </div>


            {{-- Type --}}
            <div class="inventory-field">

                <label>
                    {{ __('ui.inventory.type') }}
                </label>

                <div class="inventory-select-wrapper">

                    <select
                        class="inventory-select"
                        wire:model.live="type"
                    >

                        <option value="all">
                            {{ __('ui.inventory.all') }}
                        </option>

                        @foreach ([
                            'receipt',
                            'issue',
                            'transfer_out',
                            'transfer_in',
                            'adjustment'
                        ] as $t)

                            <option value="{{ $t }}">
                                {{ __('ui.inventory.types.' . $t) }}
                            </option>

                        @endforeach

                    </select>

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="m6 9 6 6 6-6"/>
                    </svg>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         TABLE
    ========================================================== --}}
    <div class="inventory-table-card">

        <div class="inventory-table-header">

            <div>

                <div class="inventory-table-kicker">
                    GCV DATA
                </div>

                <h2>
                    {{ __('ui.inventory.movements') }}
                </h2>

            </div>

            <div class="inventory-result-count">

                <span>
                    {{ __('ui.inventory.count') }}
                </span>

                <strong>
                    {{ number_format($movements->total()) }}
                </strong>

            </div>

        </div>


        <div class="inventory-table-scroll">

            <table class="inventory-data-table">

                <thead>

                    <tr>

                        <th>
                            {{ __('ui.inventory.movement_number') }}
                        </th>

                        <th>
                            {{ __('ui.inventory.item') }}
                        </th>

                        <th>
                            {{ __('ui.inventory.warehouse') }}
                        </th>

                        <th>
                            {{ __('ui.inventory.type') }}
                        </th>

                        <th>
                            {{ __('ui.inventory.quantity') }}
                        </th>

                        <th>
                            {{ __('ui.inventory.date') }}
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse ($movements as $m)

                        <tr>

                            {{-- Movement number --}}
                            <td>

                                <div class="movement-number-cell">

                                    <div class="movement-number-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path d="M7 3h10l4 4v14H3V3z"/>
                                            <path d="M7 3v5h10V3"/>
                                            <path d="M8 13h8"/>
                                            <path d="M8 17h6"/>
                                        </svg>
                                    </div>

                                    <div>
                                        <strong>
                                            {{ $m->movement_number }}
                                        </strong>

                                        <small>
                                            GCV
                                        </small>
                                    </div>

                                </div>

                            </td>


                            {{-- Item --}}
                            <td>

                                <div class="movement-item-cell">

                                    <div class="movement-item-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path d="m12 3 8 4.5v9L12 21l-8-4.5v-9z"/>
                                            <path d="m4 7.5 8 4.5 8-4.5"/>
                                            <path d="M12 12v9"/>
                                        </svg>
                                    </div>

                                    <div>

                                        <strong>
                                            {{ $m->item?->name_ar ?? '—' }}
                                        </strong>

                                        @if($m->item?->name_en)
                                            <small>
                                                {{ $m->item->name_en }}
                                            </small>
                                        @endif

                                        @if($m->item?->sku)
                                            <small class="movement-item-sku">
                                                SKU: {{ $m->item->sku }}
                                            </small>
                                        @endif

                                    </div>

                                </div>

                            </td>


                            {{-- Warehouse --}}
                            <td>

                                <div class="movement-warehouse-cell">

                                    <div class="movement-warehouse-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path d="M3 21h18"/>
                                            <path d="M5 21V8l7-5 7 5v13"/>
                                            <path d="M9 21v-6h6v6"/>
                                            <path d="M9 10h.01"/>
                                            <path d="M12 10h.01"/>
                                            <path d="M15 10h.01"/>
                                        </svg>
                                    </div>

                                    <span>
                                        {{ $m->warehouse?->name_ar ?? '—' }}
                                    </span>

                                </div>

                            </td>


                            {{-- Type --}}
                            <td>

                                @php
                                    $movementTypeClass = match ($m->type) {
                                        'receipt' => 'receipt',
                                        'issue' => 'issue',
                                        'transfer_out',
                                        'transfer_in' => 'transfer',
                                        'adjustment' => 'adjustment',
                                        default => 'default',
                                    };
                                @endphp

                                <span class="movement-type-badge {{ $movementTypeClass }}">

                                    @if($m->type === 'receipt')

                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 19V5"/>
                                            <path d="m6 11 6-6 6 6"/>
                                        </svg>

                                    @elseif($m->type === 'issue')

                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 5v14"/>
                                            <path d="m18 13-6 6-6-6"/>
                                        </svg>

                                    @elseif(in_array($m->type, ['transfer_out', 'transfer_in'], true))

                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M7 7h11"/>
                                            <path d="m14 4 3 3-3 3"/>
                                            <path d="M17 17H6"/>
                                            <path d="m10 14-3 3 3 3"/>
                                        </svg>

                                    @else

                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 3v18"/>
                                            <path d="M3 12h18"/>
                                        </svg>

                                    @endif

                                    {{ __('ui.inventory.types.' . $m->type) }}

                                </span>

                            </td>


                            {{-- Quantity --}}
                            <td>

                                <span class="movement-quantity">
                                    {{ number_format((float) $m->quantity, 3) }}
                                </span>

                            </td>


                            {{-- Date --}}
                            <td>

                                <div class="movement-date-cell">

                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <rect x="3" y="4" width="18" height="17" rx="2"/>
                                        <path d="M16 2v4"/>
                                        <path d="M8 2v4"/>
                                        <path d="M3 10h18"/>
                                    </svg>

                                    <span>
                                        {{ $m->posted_at?->format('Y-m-d H:i') ?? '—' }}
                                    </span>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="6">

                                <div class="inventory-empty-state">

                                    <div class="inventory-empty-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path d="M7 3h10l4 4v14H3V3z"/>
                                            <path d="M7 3v5h10V3"/>
                                            <path d="M8 13h8"/>
                                            <path d="M8 17h5"/>
                                        </svg>
                                    </div>

                                    <h3>
                                        {{ __('ui.inventory.no_data') }}
                                    </h3>

                                    <p>
                                        {{ __('ui.inventory.search_movement') }}
                                    </p>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- Pagination --}}
        @if($movements->hasPages())

            <div class="inventory-pagination">

                {{ $movements->links() }}

            </div>

        @endif

    </div>


    {{-- =========================================================
         LOADING
    ========================================================== --}}
    <div
        wire:loading
        wire:target="q,type"
        class="inventory-loading"
    >
        <span class="inventory-loading-spinner"></span>
        <span>{{ __('ui.inventory.saving') }}</span>
    </div>


    {{-- =========================================================
         STYLES
    ========================================================== --}}
    <style>

        .inventory-movements-page {
            direction: rtl;
            width: 100%;
            max-width: 1600px;
            margin: 0 auto;
            padding: 8px 0 40px;
        }

        /* ---------------------------------------------------------
           HEADER
        --------------------------------------------------------- */

        .inventory-page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding: 28px;
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 22px;
            background:
                linear-gradient(
                    135deg,
                    rgba(255,255,255,.98),
                    rgba(248,250,252,.96)
                );
            box-shadow: 0 10px 35px rgba(15,23,42,.06);
        }

        .inventory-page-header-main {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
        }

        .inventory-page-icon {
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

        .inventory-page-icon svg {
            width: 28px;
            height: 28px;
        }

        .inventory-eyebrow {
            margin-bottom: 4px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .16em;
            color: #6b7280;
            direction: ltr;
            text-align: right;
        }

        .inventory-page-title {
            margin: 0;
            color: #111827;
            font-size: 25px;
            font-weight: 900;
            line-height: 1.35;
        }

        .inventory-page-description {
            margin: 6px 0 0;
            color: #6b7280;
            font-size: 13px;
            line-height: 1.7;
        }

        .inventory-primary-btn {
            min-height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 0 18px;
            border-radius: 12px;
            background: #111827;
            color: #fff !important;
            text-decoration: none !important;
            font-size: 13px;
            font-weight: 800;
            white-space: nowrap;
            transition: .2s ease;
        }

        .inventory-primary-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(17,24,39,.16);
        }

        .inventory-primary-btn svg {
            width: 18px;
            height: 18px;
        }


        /* ---------------------------------------------------------
           SUMMARY
        --------------------------------------------------------- */

        .movement-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .movement-summary-card {
            min-height: 110px;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 18px;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 7px 24px rgba(15,23,42,.045);
        }

        .movement-summary-icon {
            width: 46px;
            height: 46px;
            flex: 0 0 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
        }

        .movement-summary-icon svg {
            width: 22px;
            height: 22px;
        }

        .movement-summary-icon.total {
            background: #f3f4f6;
            color: #374151;
        }

        .movement-summary-icon.receipt {
            background: #ecfdf5;
            color: #047857;
        }

        .movement-summary-icon.issue {
            background: #fff1f2;
            color: #be123c;
        }

        .movement-summary-icon.transfer {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .movement-summary-content {
            min-width: 0;
        }

        .movement-summary-content span {
            display: block;
            margin-bottom: 5px;
            color: #6b7280;
            font-size: 12px;
            font-weight: 700;
        }

        .movement-summary-content strong {
            display: block;
            color: #111827;
            font-size: 22px;
            font-weight: 900;
            line-height: 1.2;
        }


        /* ---------------------------------------------------------
           FILTER CARD
        --------------------------------------------------------- */

        .inventory-filter-card {
            margin-bottom: 20px;
            padding: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 8px 28px rgba(15,23,42,.045);
        }

        .inventory-filter-header {
            margin-bottom: 18px;
        }

        .inventory-filter-title {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .inventory-filter-icon {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            background: #f3f4f6;
            color: #374151;
        }

        .inventory-filter-icon svg {
            width: 19px;
            height: 19px;
        }

        .inventory-filter-title strong {
            display: block;
            color: #111827;
            font-size: 14px;
            font-weight: 850;
        }

        .inventory-filter-title span {
            display: block;
            margin-top: 2px;
            color: #9ca3af;
            font-size: 11px;
        }

        .inventory-filter-grid {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(220px, 1fr);
            gap: 16px;
        }

        .inventory-field label {
            display: block;
            margin-bottom: 7px;
            color: #374151;
            font-size: 12px;
            font-weight: 800;
        }

        .inventory-input-wrapper,
        .inventory-select-wrapper {
            position: relative;
        }

        .inventory-input-wrapper > svg,
        .inventory-select-wrapper > svg {
            position: absolute;
            top: 50%;
            width: 18px;
            height: 18px;
            color: #9ca3af;
            pointer-events: none;
            transform: translateY(-50%);
        }

        .inventory-input-wrapper > svg {
            right: 13px;
        }

        .inventory-select-wrapper > svg {
            left: 13px;
        }

        .inventory-input,
        .inventory-select {
            width: 100%;
            height: 46px;
            border: 1px solid #dfe3e8;
            border-radius: 11px;
            background: #fff;
            color: #111827;
            outline: none;
            font-size: 13px;
            transition: .2s ease;
        }

        .inventory-input {
            padding: 0 42px 0 13px;
        }

        .inventory-select {
            padding: 0 13px 0 40px;
            appearance: none;
            cursor: pointer;
        }

        .inventory-input:focus,
        .inventory-select:focus {
            border-color: #9ca3af;
            box-shadow: 0 0 0 3px rgba(156,163,175,.12);
        }


        /* ---------------------------------------------------------
           TABLE CARD
        --------------------------------------------------------- */

        .inventory-table-card {
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 8px 28px rgba(15,23,42,.045);
        }

        .inventory-table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 20px 22px;
            border-bottom: 1px solid #eef0f2;
        }

        .inventory-table-kicker {
            margin-bottom: 3px;
            color: #9ca3af;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .16em;
            direction: ltr;
        }

        .inventory-table-header h2 {
            margin: 0;
            color: #111827;
            font-size: 16px;
            font-weight: 900;
        }

        .inventory-result-count {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 11px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #f9fafb;
        }

        .inventory-result-count span {
            color: #6b7280;
            font-size: 11px;
            font-weight: 700;
        }

        .inventory-result-count strong {
            color: #111827;
            font-size: 13px;
            font-weight: 900;
        }

        .inventory-table-scroll {
            width: 100%;
            overflow-x: auto;
        }

        .inventory-data-table {
            width: 100%;
            min-width: 900px;
            border-collapse: separate;
            border-spacing: 0;
        }

        .inventory-data-table th {
            padding: 13px 18px;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 11px;
            font-weight: 850;
            text-align: right;
            white-space: nowrap;
        }

        .inventory-data-table td {
            padding: 15px 18px;
            border-bottom: 1px solid #f1f3f5;
            color: #374151;
            font-size: 13px;
            vertical-align: middle;
        }

        .inventory-data-table tbody tr {
            transition: background .15s ease;
        }

        .inventory-data-table tbody tr:hover {
            background: #fafafa;
        }

        .inventory-data-table tbody tr:last-child td {
            border-bottom: 0;
        }


        /* ---------------------------------------------------------
           MOVEMENT NUMBER
        --------------------------------------------------------- */

        .movement-number-cell {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 170px;
        }

        .movement-number-icon {
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #f3f4f6;
            color: #374151;
        }

        .movement-number-icon svg {
            width: 18px;
            height: 18px;
        }

        .movement-number-cell strong {
            display: block;
            color: #111827;
            font-size: 12px;
            font-weight: 850;
            direction: ltr;
            text-align: right;
        }

        .movement-number-cell small {
            display: block;
            margin-top: 3px;
            color: #9ca3af;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: .12em;
            direction: ltr;
        }


        /* ---------------------------------------------------------
           ITEM
        --------------------------------------------------------- */

        .movement-item-cell {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 210px;
        }

        .movement-item-icon {
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #f8fafc;
            color: #4b5563;
        }

        .movement-item-icon svg {
            width: 19px;
            height: 19px;
        }

        .movement-item-cell strong {
            display: block;
            color: #111827;
            font-size: 13px;
            font-weight: 850;
        }

        .movement-item-cell small {
            display: block;
            margin-top: 2px;
            color: #9ca3af;
            font-size: 10px;
        }

        .movement-item-cell .movement-item-sku {
            color: #6b7280;
            direction: ltr;
            text-align: right;
            font-weight: 700;
        }


        /* ---------------------------------------------------------
           WAREHOUSE
        --------------------------------------------------------- */

        .movement-warehouse-cell {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 160px;
            color: #374151;
            font-weight: 700;
        }

        .movement-warehouse-icon {
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            background: #f9fafb;
            color: #6b7280;
        }

        .movement-warehouse-icon svg {
            width: 17px;
            height: 17px;
        }


        /* ---------------------------------------------------------
           TYPE BADGES
        --------------------------------------------------------- */

        .movement-type-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 30px;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 850;
            white-space: nowrap;
        }

        .movement-type-badge svg {
            width: 14px;
            height: 14px;
        }

        .movement-type-badge.receipt {
            background: #ecfdf5;
            color: #047857;
        }

        .movement-type-badge.issue {
            background: #fff1f2;
            color: #be123c;
        }

        .movement-type-badge.transfer {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .movement-type-badge.adjustment {
            background: #fffbeb;
            color: #b45309;
        }

        .movement-type-badge.default {
            background: #f3f4f6;
            color: #4b5563;
        }


        /* ---------------------------------------------------------
           QUANTITY
        --------------------------------------------------------- */

        .movement-quantity {
            display: inline-flex;
            min-width: 70px;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            border: 1px solid #e5e7eb;
            border-radius: 9px;
            background: #f9fafb;
            color: #111827;
            font-size: 12px;
            font-weight: 900;
            direction: ltr;
        }


        /* ---------------------------------------------------------
           DATE
        --------------------------------------------------------- */

        .movement-date-cell {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #6b7280;
            font-size: 11px;
            white-space: nowrap;
            direction: ltr;
        }

        .movement-date-cell svg {
            width: 16px;
            height: 16px;
            flex: 0 0 16px;
        }


        /* ---------------------------------------------------------
           EMPTY STATE
        --------------------------------------------------------- */

        .inventory-empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 260px;
            padding: 30px;
            text-align: center;
        }

        .inventory-empty-icon {
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            border-radius: 18px;
            background: #f3f4f6;
            color: #9ca3af;
        }

        .inventory-empty-icon svg {
            width: 30px;
            height: 30px;
        }

        .inventory-empty-state h3 {
            margin: 0;
            color: #374151;
            font-size: 15px;
            font-weight: 850;
        }

        .inventory-empty-state p {
            margin: 6px 0 0;
            color: #9ca3af;
            font-size: 12px;
        }


        /* ---------------------------------------------------------
           PAGINATION
        --------------------------------------------------------- */

        .inventory-pagination {
            padding: 16px 20px;
            border-top: 1px solid #eef0f2;
            background: #fff;
        }


        /* ---------------------------------------------------------
           LOADING
        --------------------------------------------------------- */

        .inventory-loading {
            position: fixed;
            z-index: 1000;
            left: 24px;
            bottom: 24px;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 10px 14px;
            border: 1px solid #e5e7eb;
            border-radius: 11px;
            background: rgba(255,255,255,.96);
            box-shadow: 0 10px 30px rgba(15,23,42,.12);
            color: #374151;
            font-size: 11px;
            font-weight: 800;
            direction: rtl;
        }

        .inventory-loading-spinner {
            width: 16px;
            height: 16px;
            border: 2px solid #d1d5db;
            border-top-color: #111827;
            border-radius: 50%;
            animation: inventory-spin .7s linear infinite;
        }

        @keyframes inventory-spin {
            to {
                transform: rotate(360deg);
            }
        }


        /* ---------------------------------------------------------
           RESPONSIVE
        --------------------------------------------------------- */

        @media (max-width: 1100px) {

            .movement-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .inventory-filter-grid {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 700px) {

            .inventory-movements-page {
                padding-bottom: 24px;
            }

            .inventory-page-header {
                flex-direction: column;
                align-items: stretch;
                padding: 20px;
            }

            .inventory-page-header-main {
                align-items: flex-start;
            }

            .inventory-primary-btn {
                width: 100%;
            }

            .inventory-page-title {
                font-size: 21px;
            }

            .inventory-page-description {
                font-size: 12px;
            }

            .movement-summary-grid {
                grid-template-columns: 1fr;
            }

            .inventory-filter-card {
                padding: 16px;
            }

            .inventory-table-header {
                padding: 16px;
            }

            .inventory-loading {
                right: 16px;
                left: 16px;
                bottom: 16px;
                justify-content: center;
            }

        }

    </style>

</div>
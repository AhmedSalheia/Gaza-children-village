@php
    /** @var \App\Livewire\Admin\Inventory\Reallocation\ReallocationMap $this */
@endphp

<div class="inventory-reallocation-page">

    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div class="reallocation-header">

        <div class="reallocation-header-content">

            <div class="reallocation-header-icon">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M12 21s7-5.2 7-11a7 7 0 1 0-14 0c0 5.8 7 11 7 11Z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    />
                    <circle
                        cx="12"
                        cy="10"
                        r="2.5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    />
                </svg>
            </div>

            <div>
                <div class="reallocation-eyebrow">
                    GCV INVENTORY
                </div>

                <h1 class="reallocation-title">
                    {{ __('ui.inventory.reallocation') }}
                </h1>

                <p class="reallocation-description">
                    {{ __('ui.inventory.reallocation_description') }}
                </p>
            </div>

        </div>

        <button
            type="button"
            class="reallocation-primary-btn"
            wire:click="generate"
            wire:loading.attr="disabled"
            wire:target="generate"
        >
            <span wire:loading.remove wire:target="generate">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M12 3v4M12 17v4M3 12h4M17 12h4M5.6 5.6l2.8 2.8M15.6 15.6l2.8 2.8M18.4 5.6l-2.8 2.8M8.4 15.6l-2.8 2.8"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                    />
                </svg>

                {{ __('ui.inventory.generate_suggestions') }}
            </span>

            <span wire:loading wire:target="generate">
                <span class="reallocation-spinner"></span>
                {{ __('ui.inventory.saving') }}
            </span>
        </button>

    </div>


    {{-- =========================================================
         INVENTORY NAVIGATION
    ========================================================== --}}
    @include('livewire.admin.inventory._nav')


    {{-- =========================================================
         FLASH MESSAGE
    ========================================================== --}}
    @if (session()->has('success'))
        <div class="reallocation-alert reallocation-alert-success">
            <div class="reallocation-alert-icon">
                ✓
            </div>

            <div>
                {{ session('success') }}
            </div>
        </div>
    @endif


    {{-- =========================================================
         SUMMARY CARDS
    ========================================================== --}}
    @php
        $suggestionCount = $suggestions->count();

        $totalSuggestedQuantity = $suggestions->sum(
            fn ($suggestion) => (float) $suggestion->suggested_quantity
        );

        $warehouseCount = $warehouses->filter(
            fn ($warehouse) =>
                $warehouse->latitude !== null &&
                $warehouse->longitude !== null
        )->count();

        $highestPriority = $suggestions->max(
            fn ($suggestion) => (float) $suggestion->priority_score
        ) ?? 0;
    @endphp

    <div class="reallocation-stats">

        <div class="reallocation-stat-card">
            <div class="reallocation-stat-icon reallocation-stat-icon-blue">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                    />
                    <path
                        d="M4.5 7.5 12 12l7.5-4.5M12 12v9"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                    />
                </svg>
            </div>

            <div>
                <span class="reallocation-stat-label">
                    {{ __('ui.inventory.reallocation') }}
                </span>

                <strong class="reallocation-stat-value">
                    {{ $suggestionCount }}
                </strong>

                <span class="reallocation-stat-note">
                    {{ __('ui.inventory.suggested_quantity') }}
                </span>
            </div>
        </div>


        <div class="reallocation-stat-card">
            <div class="reallocation-stat-icon reallocation-stat-icon-green">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M5 12h14M13 6l6 6-6 6"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </div>

            <div>
                <span class="reallocation-stat-label">
                    {{ __('ui.inventory.suggested_quantity') }}
                </span>

                <strong class="reallocation-stat-value">
                    {{ number_format($totalSuggestedQuantity, 2) }}
                </strong>

                <span class="reallocation-stat-note">
                    {{ __('ui.inventory.quantity') }}
                </span>
            </div>
        </div>


        <div class="reallocation-stat-card">
            <div class="reallocation-stat-icon reallocation-stat-icon-purple">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M3.5 21h17M5 21V7h5v14M14 21V3h5v18"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linejoin="round"
                    />
                </svg>
            </div>

            <div>
                <span class="reallocation-stat-label">
                    {{ __('ui.inventory.warehouses') }}
                </span>

                <strong class="reallocation-stat-value">
                    {{ $warehouseCount }}
                </strong>

                <span class="reallocation-stat-note">
                    {{ __('ui.inventory.latitude') }} /
                    {{ __('ui.inventory.longitude') }}
                </span>
            </div>
        </div>


        <div class="reallocation-stat-card">
            <div class="reallocation-stat-icon reallocation-stat-icon-orange">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M12 3 4.5 6v5c0 4.8 3.2 8.2 7.5 10 4.3-1.8 7.5-5.2 7.5-10V6L12 3Z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linejoin="round"
                    />
                    <path
                        d="m9 12 2 2 4-4"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </div>

            <div>
                <span class="reallocation-stat-label">
                    {{ __('ui.inventory.priority_score') }}
                </span>

                <strong class="reallocation-stat-value">
                    {{ number_format((float) $highestPriority, 2) }}
                </strong>

                <span class="reallocation-stat-note">
                    {{ __('ui.inventory.priority') }}
                </span>
            </div>
        </div>

    </div>


    {{-- =========================================================
         MAP CARD
    ========================================================== --}}
    <section class="reallocation-section-card">

        <div class="reallocation-section-header">

            <div class="reallocation-section-title-wrap">

                <div class="reallocation-section-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M3.5 6.5 9 4l6 2.5L20.5 4v13.5L15 20l-6-2.5-5.5 2.5V6.5Z"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linejoin="round"
                        />
                        <path
                            d="M9 4v13.5M15 6.5V20"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                        />
                    </svg>
                </div>

                <div>
                    <h2>
                        {{ __('ui.inventory.reallocation') }}
                    </h2>

                    <p>
                        {{ __('ui.inventory.from') }}
                        →
                        {{ __('ui.inventory.to') }}
                    </p>
                </div>

            </div>

            <div class="reallocation-map-badge">
                {{ $warehouseCount }}
                {{ __('ui.inventory.warehouses') }}
            </div>

        </div>

        <div class="reallocation-map-wrapper">
            <div
                id="inventory-map"
                class="inventory-map"
            ></div>

            @if ($warehouseCount === 0)
                <div class="reallocation-map-empty">
                    <div class="reallocation-empty-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path
                                d="M12 21s7-5.2 7-11a7 7 0 1 0-14 0c0 5.8 7 11 7 11Z"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.7"
                            />
                            <circle
                                cx="12"
                                cy="10"
                                r="2.5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.7"
                            />
                        </svg>
                    </div>

                    <strong>
                        {{ __('ui.inventory.no_data') }}
                    </strong>

                    <span>
                        {{ __('ui.inventory.warehouses') }}
                    </span>
                </div>
            @endif
        </div>

    </section>


    {{-- =========================================================
         SUGGESTIONS TABLE
    ========================================================== --}}
    <section class="reallocation-section-card">

        <div class="reallocation-section-header">

            <div class="reallocation-section-title-wrap">

                <div class="reallocation-section-icon">
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

                <div>
                    <h2>
                        {{ __('ui.inventory.generate_suggestions') }}
                    </h2>

                    <p>
                        {{ __('ui.inventory.reallocation_description') }}
                    </p>
                </div>

            </div>

            <div class="reallocation-count-badge">
                {{ $suggestionCount }}
            </div>

        </div>


        @if ($suggestions->isNotEmpty())

            <div class="reallocation-table-wrap">

                <table class="reallocation-table">

                    <thead>
                        <tr>
                            <th>
                                {{ __('ui.inventory.item') }}
                            </th>

                            <th>
                                {{ __('ui.inventory.from') }}
                            </th>

                            <th>
                                {{ __('ui.inventory.to') }}
                            </th>

                            <th>
                                {{ __('ui.inventory.suggested_quantity') }}
                            </th>

                            <th>
                                {{ __('ui.inventory.distance') }}
                            </th>

                            <th>
                                {{ __('ui.inventory.priority_score') }}
                            </th>

                            <th class="reallocation-action-column">
                                {{ __('ui.inventory.action') }}
                            </th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach ($suggestions as $suggestion)

                            <tr wire:key="reallocation-suggestion-{{ $suggestion->id }}">

                                {{-- ITEM --}}
                                <td>
                                    <div class="reallocation-item-cell">

                                        <div class="reallocation-item-icon">
                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                <path
                                                    d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.7"
                                                />
                                                <path
                                                    d="M4.5 7.5 12 12l7.5-4.5M12 12v9"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.7"
                                                />
                                            </svg>
                                        </div>

                                        <div>
                                            <strong>
                                                {{ $suggestion->item?->name_ar ?? '—' }}
                                            </strong>

                                            @if ($suggestion->item?->sku)
                                                <span>
                                                    {{ $suggestion->item->sku }}
                                                </span>
                                            @endif
                                        </div>

                                    </div>
                                </td>


                                {{-- FROM --}}
                                <td>
                                    <div class="reallocation-warehouse">

                                        <span class="reallocation-warehouse-dot reallocation-dot-from"></span>

                                        <span>
                                            {{ $suggestion->fromWarehouse?->name_ar ?? '—' }}
                                        </span>

                                    </div>
                                </td>


                                {{-- TO --}}
                                <td>
                                    <div class="reallocation-warehouse">

                                        <span class="reallocation-warehouse-dot reallocation-dot-to"></span>

                                        <span>
                                            {{ $suggestion->toWarehouse?->name_ar ?? '—' }}
                                        </span>

                                    </div>
                                </td>


                                {{-- QUANTITY --}}
                                <td>
                                    <span class="reallocation-quantity">
                                        {{ number_format((float) $suggestion->suggested_quantity, 2) }}
                                    </span>
                                </td>


                                {{-- DISTANCE --}}
                                <td>
                                    <span class="reallocation-distance">
                                        {{ number_format((float) $suggestion->distance_km, 2) }}
                                        <small>KM</small>
                                    </span>
                                </td>


                                {{-- PRIORITY --}}
                                <td>
                                    <span class="reallocation-priority">
                                        {{ number_format((float) $suggestion->priority_score, 2) }}
                                    </span>
                                </td>


                                {{-- ACTION --}}
                                <td class="reallocation-action-column">

                                    <button
                                        type="button"
                                        class="reallocation-execute-btn"
                                        wire:click="execute({{ $suggestion->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="execute({{ $suggestion->id }})"
                                    >
                                        <span
                                            wire:loading.remove
                                            wire:target="execute({{ $suggestion->id }})"
                                        >
                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                <path
                                                    d="M5 12h14M13 6l6 6-6 6"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                />
                                            </svg>

                                            {{ __('ui.inventory.execute_transfer') }}
                                        </span>

                                        <span
                                            wire:loading
                                            wire:target="execute({{ $suggestion->id }})"
                                        >
                                            <span class="reallocation-spinner"></span>
                                        </span>
                                    </button>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        @else

            <div class="reallocation-empty-state">

                <div class="reallocation-empty-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                        />
                        <path
                            d="M4.5 7.5 12 12l7.5-4.5M12 12v9"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                        />
                    </svg>
                </div>

                <h3>
                    {{ __('ui.inventory.no_data') }}
                </h3>

                <p>
                    {{ __('ui.inventory.reallocation_description') }}
                </p>

                <button
                    type="button"
                    class="reallocation-primary-btn reallocation-empty-btn"
                    wire:click="generate"
                    wire:loading.attr="disabled"
                    wire:target="generate"
                >
                    <span wire:loading.remove wire:target="generate">
                        {{ __('ui.inventory.generate_suggestions') }}
                    </span>

                    <span wire:loading wire:target="generate">
                        <span class="reallocation-spinner"></span>
                        {{ __('ui.inventory.saving') }}
                    </span>
                </button>

            </div>

        @endif

    </section>


    {{-- =========================================================
         LEAFLET
    ========================================================== --}}
    @once
        <link
            rel="stylesheet"
            href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIINfQ3qMZQn9Qq6uM7L9mL7qM4z5J6M4J4="
            crossorigin=""
        />

        <script
            src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""
        ></script>
    @endonce


    {{-- =========================================================
         PAGE STYLES
    ========================================================== --}}
    <style>

        .inventory-reallocation-page {
            direction: rtl;
            color: var(--text-primary, #172033);
            padding-bottom: 32px;
        }

        .reallocation-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding: 28px;
            margin-bottom: 18px;
            border-radius: 24px;
            background:
                linear-gradient(
                    135deg,
                    rgba(255,255,255,.98),
                    rgba(247,250,255,.96)
                );
            border: 1px solid rgba(148,163,184,.18);
            box-shadow: 0 16px 45px rgba(15,23,42,.07);
        }

        .reallocation-header-content {
            display: flex;
            align-items: center;
            gap: 18px;
            min-width: 0;
        }

        .reallocation-header-icon {
            width: 62px;
            height: 62px;
            flex: 0 0 62px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 19px;
            background: rgba(37,99,235,.10);
            color: #2563eb;
        }

        .reallocation-header-icon svg {
            width: 31px;
            height: 31px;
        }

        .reallocation-eyebrow {
            margin-bottom: 5px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .14em;
            color: #64748b;
            direction: ltr;
            text-align: right;
        }

        .reallocation-title {
            margin: 0;
            font-size: clamp(24px, 3vw, 32px);
            line-height: 1.2;
            font-weight: 850;
            color: #172033;
        }

        .reallocation-description {
            margin: 8px 0 0;
            max-width: 760px;
            color: #64748b;
            line-height: 1.8;
            font-size: 14px;
        }

        .reallocation-primary-btn {
            min-height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 0 18px;
            border: 0;
            border-radius: 13px;
            background: #2563eb;
            color: #fff;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            white-space: nowrap;
            box-shadow: 0 10px 24px rgba(37,99,235,.20);
            transition:
                transform .18s ease,
                box-shadow .18s ease,
                opacity .18s ease;
        }

        .reallocation-primary-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(37,99,235,.25);
        }

        .reallocation-primary-btn:disabled {
            opacity: .65;
            cursor: wait;
            transform: none;
        }

        .reallocation-primary-btn svg {
            width: 18px;
            height: 18px;
        }

        .reallocation-alert {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 18px 0;
            padding: 14px 17px;
            border-radius: 14px;
            font-size: 13px;
            font-weight: 700;
        }

        .reallocation-alert-success {
            color: #166534;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
        }

        .reallocation-alert-icon {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #dcfce7;
            font-weight: 900;
        }


        /* =====================================================
           STATS
        ====================================================== */

        .reallocation-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin: 18px 0;
        }

        .reallocation-stat-card {
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 18px;
            border-radius: 18px;
            background: #fff;
            border: 1px solid rgba(148,163,184,.18);
            box-shadow: 0 10px 30px rgba(15,23,42,.05);
        }

        .reallocation-stat-icon {
            width: 46px;
            height: 46px;
            flex: 0 0 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
        }

        .reallocation-stat-icon svg {
            width: 23px;
            height: 23px;
        }

        .reallocation-stat-icon-blue {
            color: #2563eb;
            background: #eff6ff;
        }

        .reallocation-stat-icon-green {
            color: #16a34a;
            background: #f0fdf4;
        }

        .reallocation-stat-icon-purple {
            color: #7c3aed;
            background: #f5f3ff;
        }

        .reallocation-stat-icon-orange {
            color: #ea580c;
            background: #fff7ed;
        }

        .reallocation-stat-label {
            display: block;
            margin-bottom: 4px;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }

        .reallocation-stat-value {
            display: block;
            color: #172033;
            font-size: 23px;
            line-height: 1.2;
            font-weight: 850;
        }

        .reallocation-stat-note {
            display: block;
            margin-top: 3px;
            color: #94a3b8;
            font-size: 11px;
        }


        /* =====================================================
           SECTION
        ====================================================== */

        .reallocation-section-card {
            overflow: hidden;
            margin-top: 18px;
            border-radius: 20px;
            background: #fff;
            border: 1px solid rgba(148,163,184,.18);
            box-shadow: 0 10px 32px rgba(15,23,42,.05);
        }

        .reallocation-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 20px 22px;
            border-bottom: 1px solid #eef2f7;
        }

        .reallocation-section-title-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .reallocation-section-icon {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 13px;
            color: #2563eb;
            background: #eff6ff;
        }

        .reallocation-section-icon svg {
            width: 21px;
            height: 21px;
        }

        .reallocation-section-header h2 {
            margin: 0;
            color: #172033;
            font-size: 17px;
            font-weight: 850;
        }

        .reallocation-section-header p {
            margin: 4px 0 0;
            color: #94a3b8;
            font-size: 12px;
        }

        .reallocation-map-badge,
        .reallocation-count-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 30px;
            padding: 0 11px;
            border-radius: 999px;
            color: #2563eb;
            background: #eff6ff;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .reallocation-map-wrapper {
            position: relative;
            min-height: 430px;
        }

        .inventory-map {
            width: 100%;
            height: 430px;
            min-height: 430px;
            background: #eef2f7;
        }

        .reallocation-map-empty {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 8px;
            background: rgba(248,250,252,.90);
            pointer-events: none;
        }

        .reallocation-map-empty strong {
            color: #334155;
            font-size: 14px;
        }

        .reallocation-map-empty span {
            color: #94a3b8;
            font-size: 12px;
        }


        /* =====================================================
           TABLE
        ====================================================== */

        .reallocation-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .reallocation-table {
            width: 100%;
            min-width: 980px;
            border-collapse: collapse;
        }

        .reallocation-table th {
            padding: 14px 17px;
            text-align: right;
            color: #64748b;
            background: #f8fafc;
            border-bottom: 1px solid #e9eef5;
            font-size: 11px;
            font-weight: 850;
            white-space: nowrap;
        }

        .reallocation-table td {
            padding: 15px 17px;
            border-bottom: 1px solid #eef2f7;
            color: #334155;
            font-size: 13px;
            vertical-align: middle;
        }

        .reallocation-table tbody tr {
            transition: background .15s ease;
        }

        .reallocation-table tbody tr:hover {
            background: #fafcff;
        }

        .reallocation-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .reallocation-action-column {
            text-align: center !important;
        }

        .reallocation-item-cell {
            display: flex;
            align-items: center;
            gap: 11px;
            min-width: 180px;
        }

        .reallocation-item-icon {
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            color: #2563eb;
            background: #eff6ff;
        }

        .reallocation-item-icon svg {
            width: 19px;
            height: 19px;
        }

        .reallocation-item-cell strong {
            display: block;
            color: #172033;
            font-size: 13px;
            font-weight: 800;
        }

        .reallocation-item-cell span {
            display: block;
            margin-top: 3px;
            color: #94a3b8;
            font-size: 10px;
            direction: ltr;
            text-align: right;
        }

        .reallocation-warehouse {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            font-weight: 700;
            color: #475569;
        }

        .reallocation-warehouse-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
        }

        .reallocation-dot-from {
            background: #f97316;
            box-shadow: 0 0 0 4px rgba(249,115,22,.10);
        }

        .reallocation-dot-to {
            background: #16a34a;
            box-shadow: 0 0 0 4px rgba(22,163,74,.10);
        }

        .reallocation-quantity {
            display: inline-flex;
            align-items: center;
            min-height: 30px;
            padding: 0 10px;
            border-radius: 9px;
            color: #1d4ed8;
            background: #eff6ff;
            font-weight: 850;
            direction: ltr;
        }

        .reallocation-distance {
            color: #475569;
            font-weight: 700;
            direction: ltr;
        }

        .reallocation-distance small {
            color: #94a3b8;
            font-size: 9px;
            margin-inline-start: 3px;
        }

        .reallocation-priority {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 58px;
            min-height: 29px;
            padding: 0 8px;
            border-radius: 999px;
            color: #c2410c;
            background: #fff7ed;
            font-size: 12px;
            font-weight: 850;
            direction: ltr;
        }

        .reallocation-execute-btn {
            min-height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 0 12px;
            border: 0;
            border-radius: 10px;
            color: #fff;
            background: #2563eb;
            font-size: 11px;
            font-weight: 800;
            cursor: pointer;
            white-space: nowrap;
            transition: .18s ease;
        }

        .reallocation-execute-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .reallocation-execute-btn:disabled {
            opacity: .55;
            cursor: wait;
            transform: none;
        }

        .reallocation-execute-btn svg {
            width: 16px;
            height: 16px;
        }


        /* =====================================================
           EMPTY STATE
        ====================================================== */

        .reallocation-empty-state {
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 10px;
            padding: 40px 20px;
            text-align: center;
        }

        .reallocation-empty-icon {
            width: 58px;
            height: 58px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 3px;
            border-radius: 17px;
            color: #64748b;
            background: #f1f5f9;
        }

        .reallocation-empty-icon svg {
            width: 28px;
            height: 28px;
        }

        .reallocation-empty-state h3 {
            margin: 0;
            color: #334155;
            font-size: 15px;
            font-weight: 850;
        }

        .reallocation-empty-state p {
            max-width: 500px;
            margin: 0 0 10px;
            color: #94a3b8;
            font-size: 12px;
            line-height: 1.8;
        }

        .reallocation-empty-btn {
            min-height: 40px;
        }

        .reallocation-spinner {
            width: 15px;
            height: 15px;
            display: inline-block;
            border: 2px solid rgba(255,255,255,.45);
            border-top-color: #fff;
            border-radius: 50%;
            animation: reallocation-spin .7s linear infinite;
        }

        @keyframes reallocation-spin {
            to {
                transform: rotate(360deg);
            }
        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 1100px) {

            .reallocation-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

        }

        @media (max-width: 760px) {

            .reallocation-header {
                align-items: stretch;
                flex-direction: column;
                padding: 20px;
            }

            .reallocation-header-content {
                align-items: flex-start;
            }

            .reallocation-primary-btn {
                width: 100%;
            }

            .reallocation-stats {
                grid-template-columns: 1fr;
            }

            .reallocation-section-header {
                align-items: flex-start;
            }

            .reallocation-map-wrapper,
            .inventory-map {
                min-height: 350px;
                height: 350px;
            }

        }

        @media (max-width: 480px) {

            .reallocation-header-icon {
                width: 52px;
                height: 52px;
                flex-basis: 52px;
            }

            .reallocation-title {
                font-size: 22px;
            }

            .reallocation-description {
                font-size: 12px;
            }

            .reallocation-section-header {
                padding: 16px;
            }

            .reallocation-stat-card {
                padding: 15px;
            }

        }

    </style>


    {{-- =========================================================
         MAP SCRIPT
    ========================================================== --}}
    <script>
        document.addEventListener('livewire:navigated', function () {
            initializeInventoryReallocationMap();
        });

        document.addEventListener('DOMContentLoaded', function () {
            initializeInventoryReallocationMap();
        });

        function initializeInventoryReallocationMap() {

            const el = document.getElementById('inventory-map');

            if (!el || typeof L === 'undefined') {
                return;
            }

            if (el.dataset.ready === '1') {
                return;
            }

            el.dataset.ready = '1';

            const map = L.map(el).setView([31.95, 35.23], 10);

            L.tileLayer(
                'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                {
                    attribution: '&copy; OpenStreetMap contributors',
                    maxZoom: 19,
                }
            ).addTo(map);

            const markers = [];

            @foreach ($warehouses as $warehouse)

                @if ($warehouse->latitude !== null && $warehouse->longitude !== null)

                    const marker = L.marker([
                        {{ (float) $warehouse->latitude }},
                        {{ (float) $warehouse->longitude }}
                    ])
                    .addTo(map)
                    .bindPopup(
                        @js($warehouse->name_ar)
                    );

                    markers.push(marker);

                @endif

            @endforeach

            if (markers.length > 0) {

                const group = L.featureGroup(markers);

                map.fitBounds(
                    group.getBounds().pad(0.12)
                );

            }

            setTimeout(function () {
                map.invalidateSize();
            }, 250);
        }
    </script>

</div>

@php
    /** @var \App\Livewire\Admin\Inventory\Counts\InventoryCount $this */
@endphp

<div class="inventory-count-page">

    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div class="count-header">

        <div class="count-header-content">

            <div class="count-header-icon">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M5 4h14v16H5z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M8 8h8M8 12h8M8 16h5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                    />
                </svg>
            </div>

            <div>
                <div class="count-eyebrow">
                    GCV INVENTORY
                </div>

                <h1 class="count-title">
                    {{ __('ui.inventory.counts') }}
                </h1>

                <p class="count-description">
                    {{ __('ui.inventory.stock_lines') }}
                    —
                    {{ __('ui.inventory.system_quantity') }}
                    /
                    {{ __('ui.inventory.counted_quantity') }}
                </p>
            </div>

        </div>

        @if ($countId)
            <div class="count-active-badge">
                <span class="count-status-dot"></span>
                {{ __('ui.inventory.active_count') }}
            </div>
        @endif

    </div>


    {{-- =========================================================
         INVENTORY NAVIGATION
    ========================================================== --}}
    @include('livewire.admin.inventory._nav')


    {{-- =========================================================
         SUMMARY
    ========================================================== --}}
    @php
        $warehouseCount = $warehouses->count();
        $historyCount = $counts->total();

        $lineCount = is_array($lines)
            ? count($lines)
            : 0;

        $systemTotal = collect($lines)->sum(
            fn ($line) => (float) ($line['system'] ?? 0)
        );

        $countedTotal = collect($lines)->sum(
            fn ($line) => (float) ($line['counted'] ?? 0)
        );

        $differenceTotal = $countedTotal - $systemTotal;
    @endphp

    <div class="count-stats">

        {{-- Warehouses --}}
        <div class="count-stat-card">

            <div class="count-stat-icon count-stat-icon-blue">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M3 21h18M5 21V7l7-4 7 4v14M8 10h2M14 10h2M8 14h2M14 14h2"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </div>

            <div>
                <span class="count-stat-label">
                    {{ __('ui.inventory.warehouses') }}
                </span>

                <strong class="count-stat-value">
                    {{ $warehouseCount }}
                </strong>

                <span class="count-stat-note">
                    {{ __('ui.inventory.active') }}
                </span>
            </div>

        </div>


        {{-- History --}}
        <div class="count-stat-card">

            <div class="count-stat-icon count-stat-icon-purple">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M4 5h16v14H4z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M8 9h8M8 13h8M8 17h5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                    />
                </svg>
            </div>

            <div>
                <span class="count-stat-label">
                    {{ __('ui.inventory.counts') }}
                </span>

                <strong class="count-stat-value">
                    {{ $historyCount }}
                </strong>

                <span class="count-stat-note">
                    {{ __('ui.inventory.date') }}
                </span>
            </div>

        </div>


        {{-- Lines --}}
        <div class="count-stat-card">

            <div class="count-stat-icon count-stat-icon-green">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M4 6h16M4 12h16M4 18h16"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                    />
                    <circle
                        cx="7"
                        cy="6"
                        r="1"
                        fill="currentColor"
                    />
                    <circle
                        cx="7"
                        cy="12"
                        r="1"
                        fill="currentColor"
                    />
                    <circle
                        cx="7"
                        cy="18"
                        r="1"
                        fill="currentColor"
                    />
                </svg>
            </div>

            <div>
                <span class="count-stat-label">
                    {{ __('ui.inventory.stock_lines') }}
                </span>

                <strong class="count-stat-value">
                    {{ $lineCount }}
                </strong>

                <span class="count-stat-note">
                    {{ __('ui.inventory.active_count') }}
                </span>
            </div>

        </div>


        {{-- Difference --}}
        <div class="count-stat-card">

            <div class="count-stat-icon count-stat-icon-orange">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M12 3v18M5 8l7-5 7 5M5 16l7 5 7-5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </div>

            <div>
                <span class="count-stat-label">
                    {{ __('ui.inventory.counted_quantity') }}
                </span>

                <strong class="count-stat-value">
                    {{ number_format($differenceTotal, 3) }}
                </strong>

                <span class="count-stat-note">
                    {{ __('ui.inventory.quantity') }}
                </span>
            </div>

        </div>

    </div>


    {{-- =========================================================
         START NEW COUNT
    ========================================================== --}}
    <section class="count-section-card">

        <div class="count-section-header">

            <div class="count-section-title-wrap">

                <div class="count-section-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M4 5h16v14H4z"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linejoin="round"
                        />
                        <path
                            d="M8 9h8M8 13h5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                        />
                    </svg>
                </div>

                <div>
                    <h2>
                        {{ __('ui.inventory.start_count') }}
                    </h2>

                    <p>
                        {{ __('ui.inventory.warehouse') }}
                    </p>
                </div>

            </div>

        </div>


        <div class="count-start-body">

            <div class="count-form-group">

                <label for="count-warehouse">
                    {{ __('ui.inventory.warehouse') }}
                </label>

                <div class="count-select-wrapper">

                    <select
                        id="count-warehouse"
                        class="count-select"
                        wire:model="warehouseId"
                    >
                        <option value="">
                            — {{ __('ui.inventory.warehouse') }} —
                        </option>

                        @foreach ($warehouses as $warehouse)

                            <option value="{{ $warehouse->id }}">
                                {{ $warehouse->name_ar }}
                            </option>

                        @endforeach

                    </select>

                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="m6 9 6 6 6-6"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>

                </div>

                @error('warehouseId')
                    <div class="count-field-error">
                        {{ $message }}
                    </div>
                @enderror

            </div>


            <button
                type="button"
                class="count-primary-btn"
                wire:click="start"
                wire:loading.attr="disabled"
                wire:target="start"
            >

                <span wire:loading.remove wire:target="start">

                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M12 5v14M5 12h14"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                        />
                    </svg>

                    {{ __('ui.inventory.start_count') }}

                </span>

                <span wire:loading wire:target="start">
                    <span class="count-spinner"></span>
                    {{ __('ui.inventory.saving') }}
                </span>

            </button>

        </div>

    </section>


    {{-- =========================================================
         ACTIVE COUNT
    ========================================================== --}}
    @if ($countId)

        <section class="count-section-card count-active-card">

            <div class="count-section-header">

                <div class="count-section-title-wrap">

                    <div class="count-section-icon count-section-icon-green">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path
                                d="M5 4h14v16H5z"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linejoin="round"
                            />
                            <path
                                d="M8 8h8M8 12h8M8 16h5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                            />
                        </svg>
                    </div>

                    <div>
                        <h2>
                            {{ __('ui.inventory.active_count') }}
                        </h2>

                        <p>
                            {{ __('ui.inventory.system_quantity') }}
                            /
                            {{ __('ui.inventory.counted_quantity') }}
                        </p>
                    </div>

                </div>

                <div class="count-current-badge">
                    <span></span>
                    {{ __('ui.inventory.active') }}
                </div>

            </div>


            {{-- Active count summary --}}
            <div class="count-active-summary">

                <div>
                    <span>
                        {{ __('ui.inventory.stock_lines') }}
                    </span>

                    <strong>
                        {{ $lineCount }}
                    </strong>
                </div>

                <div>
                    <span>
                        {{ __('ui.inventory.system_quantity') }}
                    </span>

                    <strong>
                        {{ number_format($systemTotal, 3) }}
                    </strong>
                </div>

                <div>
                    <span>
                        {{ __('ui.inventory.counted_quantity') }}
                    </span>

                    <strong>
                        {{ number_format($countedTotal, 3) }}
                    </strong>
                </div>

                <div>
                    <span>
                        {{ __('ui.inventory.quantity') }}
                    </span>

                    <strong class="{{ $differenceTotal == 0 ? 'difference-zero' : 'difference-value' }}">
                        {{ number_format($differenceTotal, 3) }}
                    </strong>
                </div>

            </div>


            {{-- Active count table --}}
            @if (count($lines) > 0)

                <div class="count-table-wrapper">

                    <table class="count-table">

                        <thead>
                            <tr>

                                <th class="count-index-column">
                                    #
                                </th>

                                <th>
                                    {{ __('ui.inventory.item') }}
                                </th>

                                <th>
                                    {{ __('ui.inventory.system_quantity') }}
                                </th>

                                <th>
                                    {{ __('ui.inventory.counted_quantity') }}
                                </th>

                                <th>
                                    {{ __('ui.inventory.quantity') }}
                                </th>

                            </tr>
                        </thead>

                        <tbody>

                            @foreach ($lines as $key => $line)

                                @php
                                    $lineDifference =
                                        (float) ($line['counted'] ?? 0)
                                        -
                                        (float) ($line['system'] ?? 0);
                                @endphp

                                <tr wire:key="count-line-{{ $line['id'] ?? $key }}">

                                    <td class="count-index-column">
                                        <span class="count-row-number">
                                            {{ $key + 1 }}
                                        </span>
                                    </td>


                                    {{-- ITEM --}}
                                    <td>

                                        <div class="count-item-cell">

                                            <div class="count-item-icon">
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

                                            <strong>
                                                {{ $line['item'] ?? '—' }}
                                            </strong>

                                        </div>

                                    </td>


                                    {{-- SYSTEM --}}
                                    <td>

                                        <span class="count-system-value">
                                            {{ number_format((float) ($line['system'] ?? 0), 3) }}
                                        </span>

                                    </td>


                                    {{-- COUNTED --}}
                                    <td>

                                        <input
                                            type="number"
                                            step="0.001"
                                            min="0"
                                            class="count-quantity-input"
                                            wire:model.live="lines.{{ $key }}.counted"
                                        >

                                    </td>


                                    {{-- DIFFERENCE --}}
                                    <td>

                                        @if ($lineDifference == 0)

                                            <span class="count-difference count-difference-zero">
                                                0.000
                                            </span>

                                        @elseif ($lineDifference > 0)

                                            <span class="count-difference count-difference-positive">
                                                +{{ number_format($lineDifference, 3) }}
                                            </span>

                                        @else

                                            <span class="count-difference count-difference-negative">
                                                {{ number_format($lineDifference, 3) }}
                                            </span>

                                        @endif

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>


                {{-- Actions --}}
                <div class="count-action-bar">

                    <div class="count-action-info">

                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle
                                cx="12"
                                cy="12"
                                r="9"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.7"
                            />
                            <path
                                d="M12 11v5M12 8h.01"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                            />
                        </svg>

                        <span>
                            {{ __('ui.inventory.system_quantity') }}
                            /
                            {{ __('ui.inventory.counted_quantity') }}
                        </span>

                    </div>


                    <div class="count-actions">

                        <button
                            type="button"
                            class="count-save-btn"
                            wire:click="save"
                            wire:loading.attr="disabled"
                            wire:target="save"
                        >

                            <span wire:loading.remove wire:target="save">

                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path
                                        d="M5 4h12l2 2v14H5z"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        stroke-linejoin="round"
                                    />
                                    <path
                                        d="M8 4v6h7V4M8 20v-6h8v6"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    />
                                </svg>

                                {{ __('ui.inventory.save_count') }}

                            </span>

                            <span wire:loading wire:target="save">
                                <span class="count-spinner count-spinner-dark"></span>
                                {{ __('ui.inventory.saving') }}
                            </span>

                        </button>


                        <button
                            type="button"
                            class="count-close-btn"
                            wire:click="close"
                            wire:loading.attr="disabled"
                            wire:target="close"
                        >

                            <span wire:loading.remove wire:target="close">

                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path
                                        d="m5 12 4 4L19 6"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>

                                {{ __('ui.inventory.close_count') }}

                            </span>

                            <span wire:loading wire:target="close">
                                <span class="count-spinner"></span>
                            </span>

                        </button>

                    </div>

                </div>

            @else

                <div class="count-empty-state">

                    <div class="count-empty-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path
                                d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.7"
                            />
                        </svg>
                    </div>

                    <h3>
                        {{ __('ui.inventory.no_data') }}
                    </h3>

                </div>

            @endif

        </section>

    @endif


    {{-- =========================================================
         COUNT HISTORY
    ========================================================== --}}
    <section class="count-section-card">

        <div class="count-section-header">

            <div class="count-section-title-wrap">

                <div class="count-section-icon count-section-icon-purple">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M4 5h16v14H4z"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linejoin="round"
                        />
                        <path
                            d="M8 9h8M8 13h8M8 17h5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                        />
                    </svg>
                </div>

                <div>
                    <h2>
                        {{ __('ui.inventory.counts') }}
                    </h2>

                    <p>
                        {{ __('ui.inventory.date') }}
                        /
                        {{ __('ui.inventory.warehouse') }}
                        /
                        {{ __('ui.inventory.status') }}
                    </p>
                </div>

            </div>

            <div class="count-history-badge">
                {{ $historyCount }}
            </div>

        </div>


        @if ($counts->count() > 0)

            <div class="count-table-wrapper">

                <table class="count-table count-history-table">

                    <thead>
                        <tr>

                            <th>
                                {{ __('ui.inventory.count_number') }}
                            </th>

                            <th>
                                {{ __('ui.inventory.warehouse') }}
                            </th>

                            <th>
                                {{ __('ui.inventory.status') }}
                            </th>

                            <th>
                                {{ __('ui.inventory.date') }}
                            </th>

                        </tr>
                    </thead>

                    <tbody>

                        @foreach ($counts as $count)

                            <tr wire:key="count-history-{{ $count->id }}">

                                <td>

                                    <div class="count-number-cell">

                                        <div class="count-number-icon">
                                            #
                                        </div>

                                        <span>
                                            {{ $count->count_number }}
                                        </span>

                                    </div>

                                </td>


                                <td>

                                    <div class="count-warehouse-cell">

                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path
                                                d="M3 21h18M5 21V7l7-4 7 4v14"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="1.7"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>

                                        {{ $count->warehouse?->name_ar ?? '—' }}

                                    </div>

                                </td>


                                <td>

                                    @if ($count->status === 'open')

                                        <span class="count-status count-status-open">
                                            <span></span>
                                            {{ __('ui.inventory.active') }}
                                        </span>

                                    @elseif ($count->status === 'closed')

                                        <span class="count-status count-status-closed">
                                            <span></span>
                                            {{ __('ui.inventory.close_count') }}
                                        </span>

                                    @else

                                        <span class="count-status count-status-other">
                                            {{ $count->status }}
                                        </span>

                                    @endif

                                </td>


                                <td>

                                    <span class="count-date">
                                        {{ $count->created_at?->format('Y-m-d H:i') ?? '—' }}
                                    </span>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>


            <div class="count-pagination">
                {{ $counts->links() }}
            </div>

        @else

            <div class="count-empty-state">

                <div class="count-empty-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M4 5h16v14H4z"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                        />
                        <path
                            d="M8 9h8M8 13h5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                        />
                    </svg>
                </div>

                <h3>
                    {{ __('ui.inventory.no_data') }}
                </h3>

            </div>

        @endif

    </section>


    {{-- =========================================================
         STYLES
    ========================================================== --}}
    <style>

        .inventory-count-page {
            direction: rtl;
            padding-bottom: 32px;
            color: var(--text-primary, #172033);
        }

        /* =====================================================
           HEADER
        ====================================================== */

        .count-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 28px;
            margin-bottom: 18px;
            border: 1px solid rgba(148, 163, 184, .18);
            border-radius: 24px;
            background:
                linear-gradient(
                    135deg,
                    rgba(255,255,255,.98),
                    rgba(247,250,255,.96)
                );
            box-shadow: 0 16px 45px rgba(15,23,42,.07);
        }

        .count-header-content {
            display: flex;
            align-items: center;
            gap: 17px;
            min-width: 0;
        }

        .count-header-icon {
            width: 62px;
            height: 62px;
            flex: 0 0 62px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 19px;
            color: #2563eb;
            background: rgba(37,99,235,.10);
        }

        .count-header-icon svg {
            width: 31px;
            height: 31px;
        }

        .count-eyebrow {
            margin-bottom: 5px;
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .14em;
            direction: ltr;
        }

        .count-title {
            margin: 0;
            color: #172033;
            font-size: clamp(24px, 3vw, 32px);
            line-height: 1.2;
            font-weight: 850;
        }

        .count-description {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 13px;
            line-height: 1.7;
        }

        .count-active-badge,
        .count-current-badge,
        .count-history-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 32px;
            padding: 0 12px;
            border-radius: 999px;
            white-space: nowrap;
            font-size: 11px;
            font-weight: 800;
        }

        .count-active-badge {
            color: #166534;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
        }

        .count-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 0 4px rgba(34,197,94,.12);
        }


        /* =====================================================
           STATS
        ====================================================== */

        .count-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin: 18px 0;
        }

        .count-stat-card {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
            padding: 18px;
            border: 1px solid rgba(148,163,184,.18);
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 10px 30px rgba(15,23,42,.05);
        }

        .count-stat-icon {
            width: 46px;
            height: 46px;
            flex: 0 0 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
        }

        .count-stat-icon svg {
            width: 23px;
            height: 23px;
        }

        .count-stat-icon-blue {
            color: #2563eb;
            background: #eff6ff;
        }

        .count-stat-icon-purple {
            color: #7c3aed;
            background: #f5f3ff;
        }

        .count-stat-icon-green {
            color: #16a34a;
            background: #f0fdf4;
        }

        .count-stat-icon-orange {
            color: #ea580c;
            background: #fff7ed;
        }

        .count-stat-label {
            display: block;
            margin-bottom: 4px;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }

        .count-stat-value {
            display: block;
            color: #172033;
            font-size: 23px;
            line-height: 1.2;
            font-weight: 850;
            direction: ltr;
            text-align: right;
        }

        .count-stat-note {
            display: block;
            margin-top: 3px;
            color: #94a3b8;
            font-size: 10px;
        }


        /* =====================================================
           SECTION CARDS
        ====================================================== */

        .count-section-card {
            overflow: hidden;
            margin-top: 18px;
            border: 1px solid rgba(148,163,184,.18);
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 10px 32px rgba(15,23,42,.05);
        }

        .count-active-card {
            border-color: rgba(37,99,235,.15);
        }

        .count-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 20px 22px;
            border-bottom: 1px solid #eef2f7;
        }

        .count-section-title-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .count-section-icon {
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

        .count-section-icon-green {
            color: #16a34a;
            background: #f0fdf4;
        }

        .count-section-icon-purple {
            color: #7c3aed;
            background: #f5f3ff;
        }

        .count-section-icon svg {
            width: 21px;
            height: 21px;
        }

        .count-section-header h2 {
            margin: 0;
            color: #172033;
            font-size: 17px;
            font-weight: 850;
        }

        .count-section-header p {
            margin: 4px 0 0;
            color: #94a3b8;
            font-size: 12px;
        }

        .count-current-badge {
            color: #166534;
            background: #f0fdf4;
        }

        .count-current-badge span {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #22c55e;
        }

        .count-history-badge {
            color: #7c3aed;
            background: #f5f3ff;
        }


        /* =====================================================
           START FORM
        ====================================================== */

        .count-start-body {
            display: flex;
            align-items: flex-end;
            gap: 14px;
            padding: 22px;
        }

        .count-form-group {
            flex: 1;
            max-width: 560px;
        }

        .count-form-group label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-size: 12px;
            font-weight: 800;
        }

        .count-select-wrapper {
            position: relative;
        }

        .count-select-wrapper svg {
            position: absolute;
            top: 50%;
            left: 14px;
            width: 18px;
            height: 18px;
            color: #94a3b8;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .count-select {
            width: 100%;
            min-height: 46px;
            padding: 0 14px 0 42px;
            border: 1px solid #dbe3ed;
            border-radius: 12px;
            outline: none;
            background: #fff;
            color: #334155;
            font-size: 13px;
            font-family: inherit;
            cursor: pointer;
            appearance: none;
            transition: .18s ease;
        }

        .count-select:focus {
            border-color: #93c5fd;
            box-shadow: 0 0 0 4px rgba(37,99,235,.08);
        }

        .count-field-error {
            margin-top: 7px;
            color: #dc2626;
            font-size: 11px;
            font-weight: 700;
        }

        .count-primary-btn {
            min-height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 18px;
            border: 0;
            border-radius: 12px;
            color: #fff;
            background: #2563eb;
            font-family: inherit;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            white-space: nowrap;
            box-shadow: 0 9px 22px rgba(37,99,235,.18);
            transition: .18s ease;
        }

        .count-primary-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .count-primary-btn:disabled {
            opacity: .6;
            cursor: wait;
            transform: none;
        }

        .count-primary-btn svg {
            width: 18px;
            height: 18px;
        }


        /* =====================================================
           ACTIVE SUMMARY
        ====================================================== */

        .count-active-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1px;
            background: #e9eef5;
            border-bottom: 1px solid #e9eef5;
        }

        .count-active-summary > div {
            padding: 15px 18px;
            background: #f8fafc;
        }

        .count-active-summary span {
            display: block;
            margin-bottom: 5px;
            color: #64748b;
            font-size: 10px;
            font-weight: 700;
        }

        .count-active-summary strong {
            display: block;
            color: #172033;
            font-size: 17px;
            font-weight: 850;
            direction: ltr;
            text-align: right;
        }

        .count-active-summary .difference-zero {
            color: #16a34a;
        }

        .count-active-summary .difference-value {
            color: #ea580c;
        }


        /* =====================================================
           TABLE
        ====================================================== */

        .count-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .count-table {
            width: 100%;
            min-width: 760px;
            border-collapse: collapse;
        }

        .count-table th {
            padding: 14px 17px;
            text-align: right;
            color: #64748b;
            background: #f8fafc;
            border-bottom: 1px solid #e9eef5;
            font-size: 11px;
            font-weight: 850;
            white-space: nowrap;
        }

        .count-table td {
            padding: 14px 17px;
            color: #334155;
            border-bottom: 1px solid #eef2f7;
            font-size: 13px;
            vertical-align: middle;
        }

        .count-table tbody tr {
            transition: background .15s ease;
        }

        .count-table tbody tr:hover {
            background: #fafcff;
        }

        .count-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .count-index-column {
            width: 55px;
            text-align: center !important;
        }

        .count-row-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 27px;
            height: 27px;
            border-radius: 8px;
            color: #64748b;
            background: #f1f5f9;
            font-size: 10px;
            font-weight: 800;
        }

        .count-item-cell {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 220px;
        }

        .count-item-icon {
            width: 37px;
            height: 37px;
            flex: 0 0 37px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            color: #2563eb;
            background: #eff6ff;
        }

        .count-item-icon svg {
            width: 19px;
            height: 19px;
        }

        .count-item-cell strong {
            color: #172033;
            font-size: 13px;
            font-weight: 800;
        }

        .count-system-value {
            display: inline-flex;
            min-width: 75px;
            color: #475569;
            font-weight: 800;
            direction: ltr;
        }

        .count-quantity-input {
            width: 130px;
            height: 38px;
            padding: 0 11px;
            border: 1px solid #dbe3ed;
            border-radius: 10px;
            outline: none;
            color: #172033;
            background: #fff;
            font-size: 13px;
            font-weight: 750;
            direction: ltr;
            transition: .18s ease;
        }

        .count-quantity-input:focus {
            border-color: #93c5fd;
            box-shadow: 0 0 0 4px rgba(37,99,235,.08);
        }

        .count-difference {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 70px;
            min-height: 29px;
            padding: 0 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 850;
            direction: ltr;
        }

        .count-difference-zero {
            color: #166534;
            background: #f0fdf4;
        }

        .count-difference-positive {
            color: #166534;
            background: #dcfce7;
        }

        .count-difference-negative {
            color: #b91c1c;
            background: #fef2f2;
        }


        /* =====================================================
           ACTION BAR
        ====================================================== */

        .count-action-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 17px 20px;
            border-top: 1px solid #eef2f7;
            background: #fafcff;
        }

        .count-action-info {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #94a3b8;
            font-size: 11px;
        }

        .count-action-info svg {
            width: 18px;
            height: 18px;
        }

        .count-actions {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .count-save-btn,
        .count-close-btn {
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 0 14px;
            border-radius: 10px;
            font-family: inherit;
            font-size: 11px;
            font-weight: 800;
            cursor: pointer;
            transition: .18s ease;
        }

        .count-save-btn {
            color: #2563eb;
            background: #eff6ff;
            border: 1px solid #dbeafe;
        }

        .count-save-btn:hover {
            background: #dbeafe;
        }

        .count-close-btn {
            color: #fff;
            background: #16a34a;
            border: 1px solid #16a34a;
        }

        .count-close-btn:hover {
            background: #15803d;
        }

        .count-save-btn:disabled,
        .count-close-btn:disabled {
            opacity: .55;
            cursor: wait;
        }

        .count-save-btn svg,
        .count-close-btn svg {
            width: 17px;
            height: 17px;
        }


        /* =====================================================
           HISTORY
        ====================================================== */

        .count-number-cell {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .count-number-icon {
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            color: #7c3aed;
            background: #f5f3ff;
            font-size: 12px;
            font-weight: 900;
            direction: ltr;
        }

        .count-number-cell span:last-child {
            color: #334155;
            font-size: 11px;
            font-weight: 750;
            direction: ltr;
        }

        .count-warehouse-cell {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #475569;
            font-weight: 700;
        }

        .count-warehouse-cell svg {
            width: 18px;
            height: 18px;
            color: #64748b;
        }

        .count-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 28px;
            padding: 0 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
        }

        .count-status span {
            width: 7px;
            height: 7px;
            border-radius: 50%;
        }

        .count-status-open {
            color: #166534;
            background: #f0fdf4;
        }

        .count-status-open span {
            background: #22c55e;
        }

        .count-status-closed {
            color: #475569;
            background: #f1f5f9;
        }

        .count-status-closed span {
            background: #64748b;
        }

        .count-status-other {
            color: #92400e;
            background: #fffbeb;
        }

        .count-date {
            color: #64748b;
            font-size: 11px;
            direction: ltr;
            display: inline-block;
        }

        .count-pagination {
            padding: 15px 20px;
            border-top: 1px solid #eef2f7;
        }


        /* =====================================================
           EMPTY
        ====================================================== */

        .count-empty-state {
            min-height: 220px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 9px;
            padding: 35px 20px;
            text-align: center;
        }

        .count-empty-icon {
            width: 55px;
            height: 55px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 3px;
            border-radius: 16px;
            color: #64748b;
            background: #f1f5f9;
        }

        .count-empty-icon svg {
            width: 27px;
            height: 27px;
        }

        .count-empty-state h3 {
            margin: 0;
            color: #475569;
            font-size: 14px;
            font-weight: 850;
        }


        /* =====================================================
           SPINNER
        ====================================================== */

        .count-spinner {
            width: 15px;
            height: 15px;
            display: inline-block;
            border: 2px solid rgba(255,255,255,.45);
            border-top-color: #fff;
            border-radius: 50%;
            animation: count-spin .7s linear infinite;
        }

        .count-spinner-dark {
            border-color: rgba(37,99,235,.25);
            border-top-color: #2563eb;
        }

        @keyframes count-spin {
            to {
                transform: rotate(360deg);
            }
        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 1100px) {

            .count-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .count-active-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

        }

        @media (max-width: 760px) {

            .count-header {
                align-items: stretch;
                flex-direction: column;
                padding: 20px;
            }

            .count-header-content {
                align-items: flex-start;
            }

            .count-active-badge {
                align-self: flex-start;
            }

            .count-stats {
                grid-template-columns: 1fr;
            }

            .count-start-body {
                align-items: stretch;
                flex-direction: column;
            }

            .count-form-group {
                max-width: none;
            }

            .count-primary-btn {
                width: 100%;
            }

            .count-section-header {
                align-items: flex-start;
            }

            .count-action-bar {
                align-items: stretch;
                flex-direction: column;
            }

            .count-actions {
                width: 100%;
            }

            .count-save-btn,
            .count-close-btn {
                flex: 1;
            }

        }

        @media (max-width: 480px) {

            .count-header-icon {
                width: 52px;
                height: 52px;
                flex-basis: 52px;
            }

            .count-title {
                font-size: 22px;
            }

            .count-description {
                font-size: 12px;
            }

            .count-section-header {
                padding: 16px;
            }

            .count-active-summary {
                grid-template-columns: 1fr 1fr;
            }

            .count-active-summary > div {
                padding: 12px;
            }

            .count-actions {
                flex-direction: column;
            }

            .count-save-btn,
            .count-close-btn {
                width: 100%;
                flex: none;
            }

        }

    </style>

</div>

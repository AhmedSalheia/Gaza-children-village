<div class="inventory-reports-page">

    @include('livewire.admin.inventory._nav')

    <style>
        .inventory-reports-page {
            direction: rtl;
            width: 100%;
        }

        .inventory-reports-page .reports-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .inventory-reports-page .reports-header-content {
            min-width: 0;
        }

        .inventory-reports-page .reports-title {
            margin: 0;
            font-size: 1.65rem;
            font-weight: 800;
            line-height: 1.4;
            color: var(--text-primary, #172033);
        }

        .inventory-reports-page .reports-description {
            margin: .4rem 0 0;
            color: var(--text-secondary, #667085);
            font-size: .92rem;
            line-height: 1.7;
        }

        .inventory-reports-page .reports-actions {
            display: flex;
            align-items: center;
            gap: .65rem;
            flex-wrap: wrap;
        }

        .inventory-reports-page .reports-export-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            min-height: 42px;
            padding: .65rem 1rem;
            border: 1px solid var(--primary, #2563eb);
            border-radius: .7rem;
            background: var(--primary, #2563eb);
            color: #fff;
            font-size: .9rem;
            font-weight: 700;
            cursor: pointer;
            transition:
                transform .15s ease,
                opacity .15s ease,
                box-shadow .15s ease;
        }

        .inventory-reports-page .reports-export-btn:hover {
            opacity: .94;
            transform: translateY(-1px);
            box-shadow: 0 5px 14px rgba(0, 0, 0, .08);
        }

        .inventory-reports-page .reports-export-btn:disabled {
            opacity: .6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .inventory-reports-page .reports-stat-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .inventory-reports-page .reports-stat-card {
            position: relative;
            overflow: hidden;
            min-height: 125px;
            padding: 1.15rem 1.2rem;
            border: 1px solid var(--border-color, #e5e7eb);
            border-radius: 1rem;
            background: var(--surface, #fff);
            box-shadow: 0 2px 10px rgba(0, 0, 0, .035);
        }

        .inventory-reports-page .reports-stat-card::before {
            content: "";
            position: absolute;
            inset-block: 0;
            inset-inline-end: 0;
            width: 4px;
            background: var(--primary, #2563eb);
        }

        .inventory-reports-page .reports-stat-label {
            display: block;
            margin-bottom: .65rem;
            color: var(--text-secondary, #667085);
            font-size: .84rem;
            font-weight: 600;
        }

        .inventory-reports-page .reports-stat-value {
            display: block;
            color: var(--text-primary, #172033);
            font-size: 1.7rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .inventory-reports-page .reports-stat-meta {
            display: block;
            margin-top: .35rem;
            color: var(--text-secondary, #667085);
            font-size: .76rem;
        }

        .inventory-reports-page .reports-section {
            overflow: hidden;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color, #e5e7eb);
            border-radius: 1rem;
            background: var(--surface, #fff);
            box-shadow: 0 2px 10px rgba(0, 0, 0, .035);
        }

        .inventory-reports-page .reports-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.2rem;
            border-bottom: 1px solid var(--border-color, #e5e7eb);
            background: var(--surface-subtle, #f8fafc);
        }

        .inventory-reports-page .reports-section-title {
            margin: 0;
            color: var(--text-primary, #172033);
            font-size: 1rem;
            font-weight: 800;
        }

        .inventory-reports-page .reports-section-description {
            margin: .2rem 0 0;
            color: var(--text-secondary, #667085);
            font-size: .78rem;
        }

        .inventory-reports-page .reports-section-body {
            padding: 0;
        }

        .inventory-reports-page .reports-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .inventory-reports-page .reports-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 560px;
        }

        .inventory-reports-page .reports-table th,
        .inventory-reports-page .reports-table td {
            padding: .85rem 1rem;
            text-align: right;
            border-bottom: 1px solid var(--border-color, #edf0f3);
            white-space: nowrap;
        }

        .inventory-reports-page .reports-table th {
            background: var(--surface-subtle, #f8fafc);
            color: var(--text-secondary, #667085);
            font-size: .78rem;
            font-weight: 800;
        }

        .inventory-reports-page .reports-table td {
            color: var(--text-primary, #344054);
            font-size: .86rem;
        }

        .inventory-reports-page .reports-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .inventory-reports-page .reports-table tbody tr:hover {
            background: var(--surface-hover, #f9fafb);
        }

        .inventory-reports-page .reports-number {
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }

        .inventory-reports-page .reports-type-badge {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: .25rem .6rem;
            border-radius: 999px;
            background: var(--surface-subtle, #f2f4f7);
            color: var(--text-primary, #344054);
            font-size: .75rem;
            font-weight: 700;
        }

        .inventory-reports-page .reports-low-stock {
            color: var(--danger, #b42318);
            font-weight: 800;
        }

        .inventory-reports-page .reports-empty {
            padding: 2.5rem 1rem;
            text-align: center;
            color: var(--text-secondary, #667085);
        }

        .inventory-reports-page .reports-empty-title {
            margin: 0 0 .35rem;
            font-size: .95rem;
            font-weight: 700;
            color: var(--text-primary, #344054);
        }

        .inventory-reports-page .reports-empty-description {
            margin: 0;
            font-size: .8rem;
        }

        @media (max-width: 900px) {
            .inventory-reports-page .reports-stat-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .inventory-reports-page .reports-title {
                font-size: 1.35rem;
            }

            .inventory-reports-page .reports-header {
                align-items: stretch;
            }

            .inventory-reports-page .reports-actions {
                width: 100%;
            }

            .inventory-reports-page .reports-export-btn {
                width: 100%;
            }

            .inventory-reports-page .reports-section-header {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>


    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div class="reports-header">

        <div class="reports-header-content">

            <h1 class="reports-title">
                {{ __('ui.inventory.reports') }}
            </h1>

            <p class="reports-description">
                {{ __('ui.inventory.description') }}
            </p>

        </div>

        <div class="reports-actions">

            <button
                type="button"
                class="reports-export-btn"
                wire:click="export"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="export">
                    Excel
                </span>

                <span wire:loading wire:target="export">
                    {{ __('ui.inventory.saving') }}
                </span>
            </button>

        </div>

    </div>


    {{-- =========================================================
         SUMMARY CARDS
    ========================================================== --}}
    <div class="reports-stat-grid">

        <div class="reports-stat-card">

            <span class="reports-stat-label">
                {{ __('ui.inventory.stock_lines') }}
            </span>

            <strong class="reports-stat-value">
                {{ number_format((float) ($summary->total_lines ?? 0)) }}
            </strong>

            <span class="reports-stat-meta">
                {{ __('ui.inventory.count') }}
            </span>

        </div>


        <div class="reports-stat-card">

            <span class="reports-stat-label">
                {{ __('ui.inventory.total_quantity') }}
            </span>

            <strong class="reports-stat-value">
                {{ number_format((float) ($summary->total_quantity ?? 0), 2) }}
            </strong>

            <span class="reports-stat-meta">
                {{ __('ui.inventory.quantity') }}
            </span>

        </div>


        <div class="reports-stat-card">

            <span class="reports-stat-label">
                {{ __('ui.inventory.stock_value') }}
            </span>

            <strong class="reports-stat-value">
                {{ number_format((float) ($summary->total_value ?? 0), 2) }}
            </strong>

            <span class="reports-stat-meta">
                {{ __('ui.inventory.average_cost') }}
            </span>

        </div>

    </div>


    {{-- =========================================================
         MOVEMENT SUMMARY
    ========================================================== --}}
    <section class="reports-section">

        <div class="reports-section-header">

            <div>
                <h2 class="reports-section-title">
                    {{ __('ui.inventory.movement_summary') }}
                </h2>

                <p class="reports-section-description">
                    {{ __('ui.inventory.movements') }}
                </p>
            </div>

        </div>

        <div class="reports-section-body">

            <div class="reports-table-wrapper">

                <table class="reports-table">

                    <thead>
                        <tr>

                            <th>
                                {{ __('ui.inventory.type') }}
                            </th>

                            <th>
                                {{ __('ui.inventory.count') }}
                            </th>

                            <th>
                                {{ __('ui.inventory.quantity') }}
                            </th>

                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($movementSummary as $movement)

                            <tr>

                                <td>
                                    <span class="reports-type-badge">
                                        {{ __('ui.inventory.types.' . $movement->type) }}
                                    </span>
                                </td>

                                <td class="reports-number">
                                    {{ number_format((int) $movement->movement_count) }}
                                </td>

                                <td class="reports-number">
                                    {{ number_format((float) $movement->movement_quantity, 2) }}
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="3">

                                    <div class="reports-empty">

                                        <p class="reports-empty-title">
                                            {{ __('ui.inventory.no_data') }}
                                        </p>

                                        <p class="reports-empty-description">
                                            {{ __('ui.inventory.movement_summary') }}
                                        </p>

                                    </div>

                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </section>


    {{-- =========================================================
         LOW STOCK
    ========================================================== --}}
    <section class="reports-section">

        <div class="reports-section-header">

            <div>
                <h2 class="reports-section-title">
                    {{ __('ui.inventory.low_stock') }}
                </h2>

                <p class="reports-section-description">
                    {{ __('ui.inventory.reorder_level') }}
                </p>
            </div>

        </div>

        <div class="reports-section-body">

            <div class="reports-table-wrapper">

                <table class="reports-table">

                    <thead>
                        <tr>

                            <th>
                                {{ __('ui.inventory.item') }}
                            </th>

                            <th>
                                {{ __('ui.inventory.quantity') }}
                            </th>

                            <th>
                                {{ __('ui.inventory.reorder_level') }}
                            </th>

                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($low as $stock)

                            <tr>

                                <td>
                                    <strong>
                                        {{ $stock->name_ar }}
                                    </strong>
                                </td>

                                <td class="reports-number reports-low-stock">
                                    {{ number_format((float) $stock->quantity, 2) }}
                                </td>

                                <td class="reports-number">
                                    {{ number_format((float) $stock->reorder_level, 2) }}
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="3">

                                    <div class="reports-empty">

                                        <p class="reports-empty-title">
                                            {{ __('ui.inventory.no_data') }}
                                        </p>

                                        <p class="reports-empty-description">
                                            {{ __('ui.inventory.low_stock') }}
                                        </p>

                                    </div>

                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </section>

</div>

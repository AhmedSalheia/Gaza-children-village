@php
    /**
     * GCV Inventory — Item Labels
     *
     * Livewire:
     * App\Livewire\Admin\Inventory\Items\ItemLabels
     */
@endphp

<div class="inventory-labels-page">

    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div class="inventory-labels-header">

        <div class="inventory-labels-header-content">

            <div class="inventory-labels-icon">

                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M4 5.5A1.5 1.5 0 0 1 5.5 4h6.4a2 2 0 0 1 1.4.6l6.1 6.1a2 2 0 0 1 0 2.8l-5.9 5.9a2 2 0 0 1-2.8 0l-6.1-6.1a2 2 0 0 1-.6-1.4V5.5Z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linejoin="round"
                    />

                    <circle
                        cx="8.5"
                        cy="8.5"
                        r="1.3"
                        fill="currentColor"
                    />
                </svg>

            </div>

            <div>

                <div class="inventory-labels-eyebrow">
                    GCV INVENTORY
                </div>

                <h1 class="inventory-labels-title">
                    {{ __('ui.inventory.labels') }}
                </h1>

                <p class="inventory-labels-description">
                    {{ __('ui.inventory.items') }}
                    —
                    {{ __('ui.inventory.qr_code') }}
                    /
                    {{ __('ui.inventory.barcode') }}
                </p>

            </div>

        </div>


        <div class="inventory-labels-header-actions">

            <button
                type="button"
                onclick="window.print()"
                class="inventory-labels-print-btn"
            >

                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M6 9V4h12v5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linejoin="round"
                    />

                    <path
                        d="M6 18H4.5A1.5 1.5 0 0 1 3 16.5v-5A1.5 1.5 0 0 1 4.5 10h15a1.5 1.5 0 0 1 1.5 1.5v5a1.5 1.5 0 0 1-1.5 1.5H18"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linejoin="round"
                    />

                    <path
                        d="M6 14h12v6H6z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linejoin="round"
                    />
                </svg>

                <span>
                    {{ __('ui.inventory.labels') }}
                </span>

            </button>

        </div>

    </div>


    {{-- =========================================================
         INVENTORY NAVIGATION
    ========================================================== --}}
    <div class="inventory-labels-navigation">

        @include('livewire.admin.inventory._nav')

    </div>


    {{-- =========================================================
         INFORMATION BAR
    ========================================================== --}}
    <div class="inventory-labels-info">

        <div class="inventory-labels-info-icon">

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
                    d="M12 10v6"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                    stroke-linecap="round"
                />

                <circle
                    cx="12"
                    cy="7"
                    r="1"
                    fill="currentColor"
                />
            </svg>

        </div>

        <div>

            <strong>
                {{ __('ui.inventory.labels') }}
            </strong>

            <span>
                {{ $items->count() }}
                {{ __('ui.inventory.items') }}
            </span>

        </div>

    </div>


    {{-- =========================================================
         LABELS
    ========================================================== --}}
    <section class="inventory-labels-card">

        <div class="inventory-labels-card-header">

            <div>

                <span class="inventory-labels-section-label">
                    GCV DATA
                </span>

                <h2>
                    {{ __('ui.inventory.labels') }}
                </h2>

            </div>

            <div class="inventory-labels-card-count">

                {{ $items->count() }}

            </div>

        </div>


        <div class="inventory-labels-grid">

            @forelse ($items as $item)

                <article
                    class="inventory-label"
                    data-item-id="{{ $item->id }}"
                >

                    {{-- Brand --}}
                    <div class="inventory-label-brand">

                        <div class="inventory-label-brand-mark">
                            GCV
                        </div>

                        <div class="inventory-label-brand-text">
                            <strong>
                                {{ __('ui.inventory.title') }}
                            </strong>

                            <small>
                                INVENTORY
                            </small>
                        </div>

                    </div>


                    {{-- Item name --}}
                    <div class="inventory-label-item">

                        <strong>
                            {{ $item->name_ar }}
                        </strong>

                        @if ($item->name_en)

                            <small>
                                {{ $item->name_en }}
                            </small>

                        @endif

                    </div>


                    {{-- SKU / barcode --}}
                    <div class="inventory-label-meta">

                        <div>

                            <span>
                                SKU
                            </span>

                            <strong>
                                {{ $item->sku }}
                            </strong>

                        </div>

                        @if ($item->barcode)

                            <div>

                                <span>
                                    {{ __('ui.inventory.barcode') }}
                                </span>

                                <strong>
                                    {{ $item->barcode }}
                                </strong>

                            </div>

                        @endif

                    </div>


                    {{-- Codes --}}
                    <div class="inventory-label-codes">

                        <div class="inventory-label-barcode-wrapper">

                            <svg
                                class="inventory-label-barcode"
                                data-value="{{ $item->barcode ?: $item->sku }}"
                            ></svg>

                        </div>


                        <div class="inventory-label-qrcode-wrapper">

                            <div
                                class="inventory-label-qrcode"
                                data-value="{{ $item->qr_token }}"
                            ></div>

                        </div>

                    </div>


                    {{-- Footer --}}
                    <div class="inventory-label-footer">

                        <span>
                            {{ __('ui.inventory.qr_code') }}
                        </span>

                        <span>
                            GCV
                        </span>

                    </div>

                </article>

            @empty

                <div class="inventory-labels-empty">

                    <div class="inventory-labels-empty-icon">

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

                </div>

            @endforelse

        </div>

    </section>


    {{-- =========================================================
         BARCODE / QR LIBRARIES
    ========================================================== --}}
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>


    {{-- =========================================================
         GENERATE CODES
    ========================================================== --}}
    <script>

        function initializeInventoryLabels() {

            if (typeof JsBarcode !== 'undefined') {

                document
                    .querySelectorAll('.inventory-label-barcode')
                    .forEach(function (element) {

                        const value = element.dataset.value;

                        if (!value) {
                            return;
                        }

                        try {

                            JsBarcode(
                                element,
                                value,
                                {
                                    format: 'CODE128',
                                    width: 1.6,
                                    height: 42,
                                    margin: 0,
                                    displayValue: true,
                                    fontSize: 10,
                                    textMargin: 3
                                }
                            );

                        } catch (error) {

                            console.warn(
                                'Unable to generate barcode:',
                                error
                            );

                        }

                    });

            }


            if (typeof QRCode !== 'undefined') {

                document
                    .querySelectorAll('.inventory-label-qrcode')
                    .forEach(function (element) {

                        if (element.dataset.generated === '1') {
                            return;
                        }

                        const value = element.dataset.value;

                        if (!value) {
                            return;
                        }

                        try {

                            new QRCode(
                                element,
                                {
                                    text: value,
                                    width: 82,
                                    height: 82,
                                    correctLevel: QRCode.CorrectLevel.M
                                }
                            );

                            element.dataset.generated = '1';

                        } catch (error) {

                            console.warn(
                                'Unable to generate QR code:',
                                error
                            );

                        }

                    });

            }

        }


        document.addEventListener(
            'DOMContentLoaded',
            initializeInventoryLabels
        );


        document.addEventListener(
            'livewire:navigated',
            initializeInventoryLabels
        );

    </script>


    {{-- =========================================================
         STYLES
    ========================================================== --}}
    <style>

        .inventory-labels-page {
            direction: rtl;
            width: 100%;
            color: #172033;
        }


        /* =====================================================
           HEADER
        ====================================================== */

        .inventory-labels-header {
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

        .inventory-labels-header-content {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
        }

        .inventory-labels-icon {
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

        .inventory-labels-icon svg {
            width: 30px;
            height: 30px;
        }

        .inventory-labels-eyebrow {
            margin-bottom: 4px;
            color: #7b8497;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
        }

        .inventory-labels-title {
            margin: 0;
            color: #172033;
            font-size: 26px;
            line-height: 1.3;
            font-weight: 800;
        }

        .inventory-labels-description {
            margin: 7px 0 0;
            color: #697386;
            font-size: 13px;
            line-height: 1.7;
        }

        .inventory-labels-header-actions {
            flex-shrink: 0;
        }

        .inventory-labels-print-btn {
            min-height: 43px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 17px;
            border: 0;
            border-radius: 10px;
            background: #315efb;
            color: #ffffff;
            font-family: inherit;
            font-size: 13px;
            font-weight: 750;
            cursor: pointer;
            box-shadow: 0 7px 18px rgba(49, 94, 251, .18);
            transition: .18s ease;
        }

        .inventory-labels-print-btn:hover {
            background: #264edb;
            transform: translateY(-1px);
        }

        .inventory-labels-print-btn svg {
            width: 18px;
            height: 18px;
        }


        /* =====================================================
           NAVIGATION
        ====================================================== */

        .inventory-labels-navigation {
            margin-bottom: 20px;
        }


        /* =====================================================
           INFO
        ====================================================== */

        .inventory-labels-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
            padding: 14px 17px;
            border: 1px solid #e5e9f0;
            border-radius: 13px;
            background: #f9fafc;
        }

        .inventory-labels-info-icon {
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #eef4ff;
            color: #315efb;
        }

        .inventory-labels-info-icon svg {
            width: 20px;
            height: 20px;
        }

        .inventory-labels-info strong {
            display: block;
            color: #344054;
            font-size: 12px;
            font-weight: 800;
        }

        .inventory-labels-info span {
            display: block;
            margin-top: 2px;
            color: #7a8394;
            font-size: 11px;
        }


        /* =====================================================
           MAIN CARD
        ====================================================== */

        .inventory-labels-card {
            padding: 22px;
            border: 1px solid #e7eaf0;
            border-radius: 17px;
            background: #ffffff;
            box-shadow: 0 7px 25px rgba(15, 23, 42, .04);
        }

        .inventory-labels-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding-bottom: 18px;
            margin-bottom: 20px;
            border-bottom: 1px solid #edf0f4;
        }

        .inventory-labels-section-label {
            display: block;
            margin-bottom: 5px;
            color: #315efb;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .07em;
        }

        .inventory-labels-card-header h2 {
            margin: 0;
            color: #172033;
            font-size: 18px;
            font-weight: 800;
        }

        .inventory-labels-card-count {
            min-width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 10px;
            border-radius: 10px;
            background: #eef4ff;
            color: #315efb;
            font-size: 13px;
            font-weight: 800;
        }


        /* =====================================================
           LABEL GRID
        ====================================================== */

        .inventory-labels-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }


        /* =====================================================
           LABEL
        ====================================================== */

        .inventory-label {
            position: relative;
            overflow: hidden;
            min-height: 275px;
            padding: 17px;
            border: 1px solid #dfe4eb;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 5px 18px rgba(15, 23, 42, .045);
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .inventory-label::before {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            height: 4px;
            background: #315efb;
        }


        /* Brand */
        .inventory-label-brand {
            display: flex;
            align-items: center;
            gap: 9px;
            padding-bottom: 11px;
            border-bottom: 1px dashed #e4e7ec;
        }

        .inventory-label-brand-mark {
            width: 31px;
            height: 31px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #315efb;
            color: #ffffff;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: .03em;
        }

        .inventory-label-brand-text strong {
            display: block;
            color: #344054;
            font-size: 9px;
            font-weight: 800;
        }

        .inventory-label-brand-text small {
            display: block;
            margin-top: 1px;
            color: #98a1b2;
            font-size: 7px;
            font-weight: 800;
            letter-spacing: .07em;
            direction: ltr;
        }


        /* Item */
        .inventory-label-item {
            padding: 12px 0 9px;
            text-align: right;
        }

        .inventory-label-item strong {
            display: block;
            color: #172033;
            font-size: 15px;
            line-height: 1.5;
            font-weight: 850;
        }

        .inventory-label-item small {
            display: block;
            margin-top: 3px;
            color: #7a8394;
            font-size: 9px;
            direction: ltr;
            text-align: right;
        }


        /* Meta */
        .inventory-label-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 11px;
        }

        .inventory-label-meta > div {
            min-width: 0;
            padding: 7px 8px;
            border-radius: 8px;
            background: #f7f8fa;
        }

        .inventory-label-meta span {
            display: block;
            margin-bottom: 2px;
            color: #98a1b2;
            font-size: 7px;
            font-weight: 800;
        }

        .inventory-label-meta strong {
            display: block;
            overflow: hidden;
            color: #475467;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 9px;
            font-weight: 750;
            text-overflow: ellipsis;
            white-space: nowrap;
            direction: ltr;
            text-align: right;
        }


        /* Codes */
        .inventory-label-codes {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 82px;
            align-items: center;
            gap: 10px;
            padding-top: 8px;
            border-top: 1px dashed #e4e7ec;
        }

        .inventory-label-barcode-wrapper {
            min-width: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .inventory-label-barcode {
            width: 100%;
            max-width: 190px;
            height: 50px;
        }

        .inventory-label-qrcode-wrapper {
            width: 82px;
            height: 82px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .inventory-label-qrcode {
            width: 82px;
            height: 82px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .inventory-label-qrcode img,
        .inventory-label-qrcode canvas {
            max-width: 82px;
            max-height: 82px;
        }


        /* Footer */
        .inventory-label-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid #edf0f4;
            color: #98a1b2;
            font-size: 7px;
            font-weight: 800;
        }

        .inventory-label-footer span:last-child {
            direction: ltr;
            letter-spacing: .08em;
        }


        /* =====================================================
           EMPTY
        ====================================================== */

        .inventory-labels-empty {
            grid-column: 1 / -1;
            padding: 65px 20px;
            text-align: center;
        }

        .inventory-labels-empty-icon {
            width: 56px;
            height: 56px;
            margin: 0 auto 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: #f5f7fa;
            color: #98a1b2;
        }

        .inventory-labels-empty-icon svg {
            width: 28px;
            height: 28px;
        }

        .inventory-labels-empty strong {
            color: #667085;
            font-size: 13px;
        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 1050px) {

            .inventory-labels-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

        }

        @media (max-width: 760px) {

            .inventory-labels-header {
                flex-direction: column;
                align-items: stretch;
            }

            .inventory-labels-header-actions {
                width: 100%;
            }

            .inventory-labels-print-btn {
                width: 100%;
            }

            .inventory-labels-grid {
                grid-template-columns: 1fr;
            }

            .inventory-labels-card {
                padding: 17px;
            }

        }

        @media (max-width: 520px) {

            .inventory-labels-header {
                padding: 18px;
                border-radius: 14px;
            }

            .inventory-labels-title {
                font-size: 21px;
            }

            .inventory-label {
                min-height: 260px;
            }

        }


        /* =====================================================
           PRINT
        ====================================================== */

        @media print {

            @page {
                size: A4 portrait;
                margin: 8mm;
            }

            html,
            body {
                background: #ffffff !important;
            }

            .inventory-labels-page {
                width: 100%;
                background: #ffffff !important;
            }

            .inventory-labels-header,
            .inventory-labels-navigation,
            .inventory-labels-info,
            .inventory-labels-card-header {
                display: none !important;
            }

            .inventory-labels-card {
                margin: 0 !important;
                padding: 0 !important;
                border: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
            }

            .inventory-labels-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 7mm;
            }

            .inventory-label {
                min-height: 0;
                height: 58mm;
                padding: 4mm;
                border: 1px solid #cfd4dc;
                border-radius: 2mm;
                box-shadow: none;
            }

            .inventory-label::before {
                height: 1.2mm;
            }

            .inventory-label-brand {
                padding-bottom: 2.5mm;
            }

            .inventory-label-brand-mark {
                width: 8mm;
                height: 8mm;
                border-radius: 1.5mm;
            }

            .inventory-label-item {
                padding: 3mm 0 2mm;
            }

            .inventory-label-item strong {
                font-size: 12pt;
            }

            .inventory-label-item small {
                font-size: 7pt;
            }

            .inventory-label-meta {
                margin-bottom: 2mm;
            }

            .inventory-label-codes {
                grid-template-columns: minmax(0, 1fr) 24mm;
                gap: 3mm;
            }

            .inventory-label-qrcode-wrapper,
            .inventory-label-qrcode {
                width: 24mm;
                height: 24mm;
            }

            .inventory-label-qrcode img,
            .inventory-label-qrcode canvas {
                width: 24mm !important;
                height: 24mm !important;
            }

            .inventory-label-barcode {
                max-width: 100%;
                height: 17mm;
            }

            .inventory-label-footer {
                margin-top: 2mm;
            }

        }

    </style>

</div>

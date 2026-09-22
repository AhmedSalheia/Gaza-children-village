@php
    /**
     * GCV Inventory — Item Form
     *
     * Livewire:
     * App\Livewire\Admin\Inventory\Items\ItemForm
     */
@endphp

<div class="inventory-item-form-page">

    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div class="inventory-item-form-header">

        <div class="inventory-item-form-header-content">

            <div class="inventory-item-form-icon">
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

                <div class="inventory-item-form-eyebrow">
                    GCV INVENTORY
                </div>

                <h1 class="inventory-item-form-title">
                    {{ $itemId ? __('ui.inventory.edit_item') : __('ui.inventory.add_item') }}
                </h1>

                <p class="inventory-item-form-description">
                    {{ __('ui.inventory.description') }}
                </p>

            </div>

        </div>

        <a
            href="{{ route('admin.inventory.items.index') }}"
            wire:navigate
            class="inventory-item-form-back"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path
                    d="M15 6 9 12l6 6"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>

            <span>
                {{ __('ui.inventory.back') }}
            </span>
        </a>

    </div>


    {{-- =========================================================
         INVENTORY NAVIGATION
    ========================================================== --}}
    @include('livewire.admin.inventory._nav')


    {{-- =========================================================
         VALIDATION SUMMARY
    ========================================================== --}}
    @if ($errors->any())

        <div class="inventory-item-form-errors">

            <div class="inventory-item-form-errors-icon">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M12 4 21 20H3L12 4Z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M12 9v5M12 17h.01"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                    />
                </svg>
            </div>

            <div>

                <strong>
                    {{ __('validation.required') }}
                </strong>

                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>

            </div>

        </div>

    @endif


    {{-- =========================================================
         FORM
    ========================================================== --}}
    <form
        wire:submit="save"
        class="inventory-item-form"
    >

        {{-- =====================================================
             BASIC INFORMATION
        ====================================================== --}}
        <section class="inventory-item-form-card">

            <div class="inventory-item-form-card-header">

                <div>

                    <span class="inventory-item-form-section-label">
                        GCV INVENTORY
                    </span>

                    <h2>
                        {{ __('ui.inventory.item') }}
                    </h2>

                    <p>
                        {{ __('ui.inventory.name') }}
                        — SKU — {{ __('ui.inventory.category') }}
                    </p>

                </div>

                <div class="inventory-item-form-card-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M4 7.5 12 4l8 3.5v9L12 20l-8-3.5v-9Z"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linejoin="round"
                        />
                        <path
                            d="M4 7.5 12 11l8-3.5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                        />
                    </svg>
                </div>

            </div>


            <div class="inventory-item-form-grid">

                {{-- Category --}}
                <div class="inventory-field">

                    <label for="category_id">
                        {{ __('ui.inventory.category') }}

                        <span>*</span>
                    </label>

                    <select
                        id="category_id"
                        wire:model="category_id"
                        class="@error('category_id') is-invalid @enderror"
                    >

                        <option value="">
                            —
                        </option>

                        @foreach ($categories as $x)

                            <option value="{{ $x->id }}">
                                {{ $x->name_ar }}
                                /
                                {{ $x->name_en }}
                            </option>

                        @endforeach

                    </select>

                    @error('category_id')
                        <small class="inventory-field-error">
                            {{ $message }}
                        </small>
                    @enderror

                </div>


                {{-- Unit --}}
                <div class="inventory-field">

                    <label for="base_unit_id">
                        {{ __('ui.inventory.unit') }}

                        <span>*</span>
                    </label>

                    <select
                        id="base_unit_id"
                        wire:model="base_unit_id"
                        class="@error('base_unit_id') is-invalid @enderror"
                    >

                        <option value="">
                            —
                        </option>

                        @foreach ($units as $x)

                            <option value="{{ $x->id }}">
                                {{ $x->name_ar }}
                                /
                                {{ $x->name_en }}
                            </option>

                        @endforeach

                    </select>

                    @error('base_unit_id')
                        <small class="inventory-field-error">
                            {{ $message }}
                        </small>
                    @enderror

                </div>


                {{-- SKU --}}
                <div class="inventory-field">

                    <label for="sku">
                        SKU

                        <span>*</span>
                    </label>

                    <input
                        id="sku"
                        type="text"
                        wire:model="sku"
                        class="@error('sku') is-invalid @enderror"
                        placeholder="SKU"
                    >

                    @error('sku')
                        <small class="inventory-field-error">
                            {{ $message }}
                        </small>
                    @enderror

                </div>


                {{-- Barcode --}}
                <div class="inventory-field">

                    <label for="barcode">
                        {{ __('ui.inventory.barcode') }}
                    </label>

                    <input
                        id="barcode"
                        type="text"
                        wire:model="barcode"
                        class="@error('barcode') is-invalid @enderror"
                        placeholder="{{ __('ui.inventory.barcode') }}"
                    >

                    @error('barcode')
                        <small class="inventory-field-error">
                            {{ $message }}
                        </small>
                    @enderror

                </div>


                {{-- Arabic Name --}}
                <div class="inventory-field">

                    <label for="name_ar">
                        {{ __('ui.inventory.name_ar') }}

                        <span>*</span>
                    </label>

                    <input
                        id="name_ar"
                        type="text"
                        wire:model="name_ar"
                        dir="rtl"
                        class="@error('name_ar') is-invalid @enderror"
                    >

                    @error('name_ar')
                        <small class="inventory-field-error">
                            {{ $message }}
                        </small>
                    @enderror

                </div>


                {{-- English Name --}}
                <div class="inventory-field">

                    <label for="name_en">
                        {{ __('ui.inventory.name_en') }}

                        <span>*</span>
                    </label>

                    <input
                        id="name_en"
                        type="text"
                        wire:model="name_en"
                        dir="ltr"
                        class="@error('name_en') is-invalid @enderror"
                    >

                    @error('name_en')
                        <small class="inventory-field-error">
                            {{ $message }}
                        </small>
                    @enderror

                </div>

            </div>

        </section>


        {{-- =====================================================
             STOCK CONTROL
        ====================================================== --}}
        <section class="inventory-item-form-card">

            <div class="inventory-item-form-card-header">

                <div>

                    <span class="inventory-item-form-section-label">
                        STOCK CONTROL
                    </span>

                    <h2>
                        {{ __('ui.inventory.stock') }}
                    </h2>

                    <p>
                        {{ __('ui.inventory.reorder_level') }}
                        —
                        {{ __('ui.inventory.minimum_stock') }}
                        —
                        {{ __('ui.inventory.maximum_stock') }}
                    </p>

                </div>

                <div class="inventory-item-form-card-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M4 19V5M4 19h16"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                        />
                        <path
                            d="m7 15 3-4 3 2 5-7"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </div>

            </div>


            <div class="inventory-item-form-grid inventory-item-form-grid-four">

                {{-- Reorder level --}}
                <div class="inventory-field">

                    <label for="reorder_level">
                        {{ __('ui.inventory.reorder_level') }}
                    </label>

                    <div class="inventory-number-wrapper">

                        <input
                            id="reorder_level"
                            type="number"
                            step=".001"
                            min="0"
                            wire:model="reorder_level"
                            class="@error('reorder_level') is-invalid @enderror"
                        >

                        <span>
                            {{ __('ui.inventory.unit') }}
                        </span>

                    </div>

                    @error('reorder_level')
                        <small class="inventory-field-error">
                            {{ $message }}
                        </small>
                    @enderror

                </div>


                {{-- Minimum --}}
                <div class="inventory-field">

                    <label for="minimum_stock">
                        {{ __('ui.inventory.minimum_stock') }}
                    </label>

                    <div class="inventory-number-wrapper">

                        <input
                            id="minimum_stock"
                            type="number"
                            step=".001"
                            min="0"
                            wire:model="minimum_stock"
                            class="@error('minimum_stock') is-invalid @enderror"
                        >

                        <span>
                            {{ __('ui.inventory.unit') }}
                        </span>

                    </div>

                    @error('minimum_stock')
                        <small class="inventory-field-error">
                            {{ $message }}
                        </small>
                    @enderror

                </div>


                {{-- Maximum --}}
                <div class="inventory-field">

                    <label for="maximum_stock">
                        {{ __('ui.inventory.maximum_stock') }}
                    </label>

                    <div class="inventory-number-wrapper">

                        <input
                            id="maximum_stock"
                            type="number"
                            step=".001"
                            min="0"
                            wire:model="maximum_stock"
                            class="@error('maximum_stock') is-invalid @enderror"
                        >

                        <span>
                            {{ __('ui.inventory.unit') }}
                        </span>

                    </div>

                    @error('maximum_stock')
                        <small class="inventory-field-error">
                            {{ $message }}
                        </small>
                    @enderror

                </div>


                {{-- Safety --}}
                <div class="inventory-field">

                    <label for="safety_stock">
                        {{ __('ui.inventory.safety_stock') }}
                    </label>

                    <div class="inventory-number-wrapper">

                        <input
                            id="safety_stock"
                            type="number"
                            step=".001"
                            min="0"
                            wire:model="safety_stock"
                            class="@error('safety_stock') is-invalid @enderror"
                        >

                        <span>
                            {{ __('ui.inventory.unit') }}
                        </span>

                    </div>

                    @error('safety_stock')
                        <small class="inventory-field-error">
                            {{ $message }}
                        </small>
                    @enderror

                </div>

            </div>

        </section>


        {{-- =====================================================
             DESCRIPTION
        ====================================================== --}}
        <section class="inventory-item-form-card">

            <div class="inventory-item-form-card-header">

                <div>

                    <span class="inventory-item-form-section-label">
                        DESCRIPTION
                    </span>

                    <h2>
                        {{ __('ui.inventory.description_ar') }}
                    </h2>

                    <p>
                        {{ __('ui.inventory.description_en') }}
                    </p>

                </div>

                <div class="inventory-item-form-card-icon">
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


            <div class="inventory-item-form-grid">

                {{-- Arabic description --}}
                <div class="inventory-field inventory-field-full">

                    <label for="description_ar">
                        {{ __('ui.inventory.description_ar') }}
                    </label>

                    <textarea
                        id="description_ar"
                        wire:model="description_ar"
                        dir="rtl"
                        rows="5"
                        class="@error('description_ar') is-invalid @enderror"
                    ></textarea>

                    @error('description_ar')
                        <small class="inventory-field-error">
                            {{ $message }}
                        </small>
                    @enderror

                </div>


                {{-- English description --}}
                <div class="inventory-field inventory-field-full">

                    <label for="description_en">
                        {{ __('ui.inventory.description_en') }}
                    </label>

                    <textarea
                        id="description_en"
                        wire:model="description_en"
                        dir="ltr"
                        rows="5"
                        class="@error('description_en') is-invalid @enderror"
                    ></textarea>

                    @error('description_en')
                        <small class="inventory-field-error">
                            {{ $message }}
                        </small>
                    @enderror

                </div>

            </div>

        </section>


        {{-- =====================================================
             OPTIONS
        ====================================================== --}}
        <section class="inventory-item-form-card">

            <div class="inventory-item-form-card-header">

                <div>

                    <span class="inventory-item-form-section-label">
                        SETTINGS
                    </span>

                    <h2>
                        {{ __('ui.inventory.status') }}
                    </h2>

                    <p>
                        {{ __('ui.inventory.transferable') }}
                    </p>

                </div>

                <div class="inventory-item-form-card-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M12 3v18M3 12h18"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                        />
                    </svg>
                </div>

            </div>


            <div class="inventory-item-form-options">

                {{-- Transferable --}}
                <label class="inventory-item-form-option">

                    <input
                        type="checkbox"
                        wire:model="is_transferable"
                    >

                    <span class="inventory-custom-checkbox">
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
                    </span>

                    <span class="inventory-item-form-option-text">

                        <strong>
                            {{ __('ui.inventory.transferable') }}
                        </strong>

                        <small>
                            {{ __('ui.inventory.transferable') }}
                        </small>

                    </span>

                </label>


                {{-- Active --}}
                <label class="inventory-item-form-option">

                    <input
                        type="checkbox"
                        wire:model="is_active"
                    >

                    <span class="inventory-custom-checkbox">
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
                    </span>

                    <span class="inventory-item-form-option-text">

                        <strong>
                            {{ __('ui.inventory.active') }}
                        </strong>

                        <small>
                            {{ __('ui.inventory.status') }}
                        </small>

                    </span>

                </label>

            </div>

        </section>


        {{-- =====================================================
             FORM ACTIONS
        ====================================================== --}}
        <div class="inventory-item-form-actions">

            <a
                href="{{ route('admin.inventory.items.index') }}"
                wire:navigate
                class="inventory-item-form-cancel"
            >
                {{ __('ui.inventory.cancel') }}
            </a>

            <button
                type="submit"
                class="inventory-item-form-save"
                wire:loading.attr="disabled"
            >

                <span wire:loading.remove wire:target="save">

                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M5 12.5 9.5 17 19 7.5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>

                    {{ __('ui.inventory.save') }}

                </span>

                <span
                    wire:loading
                    wire:target="save"
                    class="inventory-item-form-saving"
                >
                    <span class="inventory-item-form-spinner"></span>
                    {{ __('ui.inventory.save') }}...
                </span>

            </button>

        </div>

    </form>


    {{-- =========================================================
         STYLES
    ========================================================== --}}
    <style>
        .inventory-item-form-page {
            direction: rtl;
            width: 100%;
            color: #172033;
        }

        /* Header */
        .inventory-item-form-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 24px;
            padding: 24px 26px;
            border: 1px solid #e7eaf0;
            border-radius: 18px;
            background: #ffffff;
            box-shadow: 0 8px 28px rgba(15, 23, 42, 0.045);
        }

        .inventory-item-form-header-content {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
        }

        .inventory-item-form-icon {
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

        .inventory-item-form-icon svg {
            width: 30px;
            height: 30px;
        }

        .inventory-item-form-eyebrow {
            margin-bottom: 4px;
            color: #7b8497;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
        }

        .inventory-item-form-title {
            margin: 0;
            color: #172033;
            font-size: 25px;
            line-height: 1.3;
            font-weight: 800;
        }

        .inventory-item-form-description {
            margin: 7px 0 0;
            color: #697386;
            font-size: 13px;
            line-height: 1.8;
        }

        .inventory-item-form-back {
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 0 15px;
            flex-shrink: 0;
            border: 1px solid #e1e5ec;
            border-radius: 10px;
            background: #ffffff;
            color: #344054;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            transition: .18s ease;
        }

        .inventory-item-form-back svg {
            width: 18px;
            height: 18px;
        }

        .inventory-item-form-back:hover {
            background: #f8f9fb;
            border-color: #d2d8e2;
            color: #172033;
            transform: translateY(-1px);
        }


        /* Validation */
        .inventory-item-form-errors {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 20px;
            padding: 15px 17px;
            border: 1px solid #f0d1d1;
            border-radius: 13px;
            background: #fff8f8;
            color: #8d3030;
        }

        .inventory-item-form-errors-icon {
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            background: #ffe8e8;
        }

        .inventory-item-form-errors-icon svg {
            width: 20px;
            height: 20px;
        }

        .inventory-item-form-errors strong {
            display: block;
            margin-bottom: 5px;
            font-size: 13px;
        }

        .inventory-item-form-errors ul {
            margin: 0;
            padding-inline-start: 18px;
            font-size: 12px;
            line-height: 1.8;
        }


        /* Form */
        .inventory-item-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .inventory-item-form-card {
            padding: 22px;
            border: 1px solid #e7eaf0;
            border-radius: 17px;
            background: #ffffff;
            box-shadow: 0 7px 25px rgba(15, 23, 42, 0.04);
        }

        .inventory-item-form-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding-bottom: 18px;
            margin-bottom: 20px;
            border-bottom: 1px solid #edf0f4;
        }

        .inventory-item-form-section-label {
            display: block;
            margin-bottom: 5px;
            color: #315efb;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .07em;
        }

        .inventory-item-form-card-header h2 {
            margin: 0;
            color: #172033;
            font-size: 18px;
            font-weight: 800;
        }

        .inventory-item-form-card-header p {
            margin: 6px 0 0;
            color: #7a8394;
            font-size: 12px;
            line-height: 1.7;
        }

        .inventory-item-form-card-icon {
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

        .inventory-item-form-card-icon svg {
            width: 21px;
            height: 21px;
        }


        /* Grid */
        .inventory-item-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .inventory-item-form-grid-four {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .inventory-field {
            min-width: 0;
        }

        .inventory-field-full {
            grid-column: 1 / -1;
        }


        /* Labels */
        .inventory-field > label {
            display: block;
            margin-bottom: 7px;
            color: #344054;
            font-size: 12px;
            font-weight: 750;
        }

        .inventory-field > label span {
            color: #d04444;
        }


        /* Inputs */
        .inventory-field input:not([type="checkbox"]),
        .inventory-field select,
        .inventory-field textarea {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #dfe3ea;
            border-radius: 10px;
            outline: none;
            background: #ffffff;
            color: #172033;
            font-family: inherit;
            font-size: 13px;
            transition:
                border-color .18s ease,
                box-shadow .18s ease,
                background .18s ease;
        }

        .inventory-field input:not([type="checkbox"]),
        .inventory-field select {
            height: 44px;
            padding: 0 12px;
        }

        .inventory-field textarea {
            min-height: 120px;
            padding: 11px 12px;
            resize: vertical;
            line-height: 1.8;
        }

        .inventory-field input:not([type="checkbox"]):focus,
        .inventory-field select:focus,
        .inventory-field textarea:focus {
            border-color: #8ea7ff;
            box-shadow: 0 0 0 3px rgba(49, 94, 251, .09);
        }

        .inventory-field input::placeholder,
        .inventory-field textarea::placeholder {
            color: #a4acb9;
        }

        .inventory-field input.is-invalid,
        .inventory-field select.is-invalid,
        .inventory-field textarea.is-invalid {
            border-color: #e35d5d;
            background: #fffafa;
        }


        /* Number input */
        .inventory-number-wrapper {
            position: relative;
        }

        .inventory-number-wrapper input {
            padding-inline-end: 54px !important;
        }

        .inventory-number-wrapper span {
            position: absolute;
            top: 50%;
            inset-inline-end: 12px;
            transform: translateY(-50%);
            color: #98a1b2;
            font-size: 10px;
            font-weight: 700;
            pointer-events: none;
        }


        /* Error */
        .inventory-field-error {
            display: block;
            margin-top: 5px;
            color: #d04444;
            font-size: 11px;
            line-height: 1.5;
        }


        /* Options */
        .inventory-item-form-options {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .inventory-item-form-option {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 72px;
            padding: 12px 14px;
            border: 1px solid #e8ebf0;
            border-radius: 12px;
            background: #fafbfc;
            cursor: pointer;
            transition: .18s ease;
        }

        .inventory-item-form-option:hover {
            border-color: #cfd7e5;
            background: #ffffff;
        }

        .inventory-item-form-option > input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .inventory-custom-checkbox {
            width: 22px;
            height: 22px;
            flex: 0 0 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1.5px solid #c8ced9;
            border-radius: 6px;
            background: #ffffff;
            color: #ffffff;
            transition: .18s ease;
        }

        .inventory-custom-checkbox svg {
            width: 15px;
            height: 15px;
            opacity: 0;
            transform: scale(.7);
            transition: .15s ease;
        }

        .inventory-item-form-option > input:checked + .inventory-custom-checkbox {
            border-color: #315efb;
            background: #315efb;
        }

        .inventory-item-form-option > input:checked + .inventory-custom-checkbox svg {
            opacity: 1;
            transform: scale(1);
        }

        .inventory-item-form-option-text {
            min-width: 0;
        }

        .inventory-item-form-option-text strong {
            display: block;
            color: #344054;
            font-size: 13px;
            font-weight: 800;
        }

        .inventory-item-form-option-text small {
            display: block;
            margin-top: 3px;
            color: #98a1b2;
            font-size: 11px;
        }


        /* Actions */
        .inventory-item-form-actions {
            position: sticky;
            bottom: 12px;
            z-index: 20;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            padding: 13px 15px;
            border: 1px solid #e5e8ee;
            border-radius: 14px;
            background: rgba(255, 255, 255, .95);
            box-shadow: 0 12px 35px rgba(15, 23, 42, .10);
            backdrop-filter: blur(10px);
        }

        .inventory-item-form-cancel,
        .inventory-item-form-save {
            min-height: 43px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 18px;
            border-radius: 10px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 750;
            text-decoration: none;
            cursor: pointer;
            transition: .18s ease;
        }

        .inventory-item-form-cancel {
            border: 1px solid #dfe3ea;
            background: #ffffff;
            color: #475467;
        }

        .inventory-item-form-cancel:hover {
            background: #f8f9fb;
            color: #172033;
        }

        .inventory-item-form-save {
            border: 1px solid #315efb;
            background: #315efb;
            color: #ffffff;
            box-shadow: 0 7px 18px rgba(49, 94, 251, .18);
        }

        .inventory-item-form-save:hover {
            border-color: #264edb;
            background: #264edb;
            color: #ffffff;
            transform: translateY(-1px);
        }

        .inventory-item-form-save:disabled {
            opacity: .7;
            cursor: wait;
            transform: none;
        }

        .inventory-item-form-save svg {
            width: 18px;
            height: 18px;
        }

        .inventory-item-form-saving {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .inventory-item-form-spinner {
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255,255,255,.4);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: inventoryItemFormSpin .7s linear infinite;
        }

        @keyframes inventoryItemFormSpin {
            to {
                transform: rotate(360deg);
            }
        }


        /* Responsive */
        @media (max-width: 1050px) {

            .inventory-item-form-grid-four {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

        }

        @media (max-width: 760px) {

            .inventory-item-form-header {
                flex-direction: column;
                align-items: stretch;
            }

            .inventory-item-form-back {
                width: 100%;
            }

            .inventory-item-form-grid,
            .inventory-item-form-grid-four,
            .inventory-item-form-options {
                grid-template-columns: 1fr;
            }

            .inventory-item-form-card {
                padding: 18px;
            }

            .inventory-item-form-actions {
                justify-content: stretch;
            }

            .inventory-item-form-cancel,
            .inventory-item-form-save {
                flex: 1;
            }

        }

        @media (max-width: 520px) {

            .inventory-item-form-header {
                padding: 18px;
                border-radius: 14px;
            }

            .inventory-item-form-header-content {
                align-items: flex-start;
            }

            .inventory-item-form-icon {
                width: 48px;
                height: 48px;
                flex-basis: 48px;
            }

            .inventory-item-form-title {
                font-size: 21px;
            }

            .inventory-item-form-description {
                font-size: 12px;
            }

            .inventory-item-form-card-header {
                gap: 10px;
            }

            .inventory-item-form-card-header h2 {
                font-size: 16px;
            }

            .inventory-item-form-actions {
                bottom: 7px;
            }

        }
    </style>

</div>

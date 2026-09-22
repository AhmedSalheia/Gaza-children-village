<div class="inventory-movement-entry-page">

    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div class="inventory-page-header">

        <div class="inventory-page-header-main">

            <div class="inventory-page-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M12 5v14"/>
                    <path d="M5 12h14"/>
                </svg>
            </div>

            <div>
                <div class="inventory-eyebrow">
                    GCV INVENTORY
                </div>

                <h1 class="inventory-page-title">
                    {{ __('ui.inventory.new_movement') }}
                </h1>

                <p class="inventory-page-description">
                    {{ __('ui.inventory.description') }}
                </p>
            </div>

        </div>

        <a
            href="{{ route('admin.inventory.movements.index') }}"
            wire:navigate
            class="inventory-secondary-btn"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M19 12H5"/>
                <path d="m12 19-7-7 7-7"/>
            </svg>

            <span>{{ __('ui.inventory.back') }}</span>
        </a>

    </div>


    {{-- =========================================================
         NAVIGATION
    ========================================================== --}}
    @include('livewire.admin.inventory._nav')


    {{-- =========================================================
         SUCCESS MESSAGE
    ========================================================== --}}
    @if(session('success'))

        <div class="inventory-success-alert">

            <div class="inventory-success-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m5 12 4 4L19 6"/>
                </svg>
            </div>

            <div>
                <strong>
                    {{ __('ui.inventory.saved') }}
                </strong>

                <span>
                    {{ session('success') }}
                </span>
            </div>

        </div>

    @endif


    {{-- =========================================================
         MOVEMENT TYPE
    ========================================================== --}}
    <div class="movement-type-card">

        <div class="movement-type-header">

            <div class="movement-type-title">

                <div class="movement-type-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M7 7h11"/>
                        <path d="m14 4 4 3-4 3"/>
                        <path d="M17 17H6"/>
                        <path d="m10 14-4 3 4 3"/>
                    </svg>
                </div>

                <div>
                    <strong>{{ __('ui.inventory.type') }}</strong>

                    <span>
                        {{ __('ui.inventory.new_movement') }}
                    </span>
                </div>

            </div>

        </div>


        <div class="movement-type-options">

            {{-- Receipt --}}
            <button
                type="button"
                wire:click="$set('mode','receipt')"
                class="movement-type-option {{ $mode === 'receipt' ? 'active receipt' : '' }}"
            >

                <div class="movement-option-icon">

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 19V5"/>
                        <path d="m6 11 6-6 6 6"/>
                    </svg>

                </div>

                <div class="movement-option-content">

                    <strong>
                        {{ __('ui.inventory.receipt') }}
                    </strong>

                    <span>
                        {{ __('ui.inventory.types.receipt') }}
                    </span>

                </div>

                @if($mode === 'receipt')

                    <div class="movement-option-check">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m5 12 4 4L19 6"/>
                        </svg>
                    </div>

                @endif

            </button>


            {{-- Transfer --}}
            <button
                type="button"
                wire:click="$set('mode','transfer')"
                class="movement-type-option {{ $mode === 'transfer' ? 'active transfer' : '' }}"
            >

                <div class="movement-option-icon">

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M7 7h11"/>
                        <path d="m14 4 4 3-4 3"/>
                        <path d="M17 17H6"/>
                        <path d="m10 14-4 3 4 3"/>
                    </svg>

                </div>

                <div class="movement-option-content">

                    <strong>
                        {{ __('ui.inventory.transfer') }}
                    </strong>

                    <span>
                        {{ __('ui.inventory.types.transfer_out') }}
                        /
                        {{ __('ui.inventory.types.transfer_in') }}
                    </span>

                </div>

                @if($mode === 'transfer')

                    <div class="movement-option-check">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m5 12 4 4L19 6"/>
                        </svg>
                    </div>

                @endif

            </button>

        </div>

    </div>


    {{-- =========================================================
         FORM
    ========================================================== --}}
    <form
        wire:submit="save"
        class="movement-form"
    >

        {{-- =====================================================
             BASIC INFORMATION
        ====================================================== --}}
        <div class="movement-form-card">

            <div class="movement-form-card-header">

                <div class="movement-form-card-title">

                    <div class="movement-form-card-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 5h16v14H4z"/>
                            <path d="M8 9h8"/>
                            <path d="M8 13h6"/>
                        </svg>
                    </div>

                    <div>
                        <h2>
                            {{ __('ui.inventory.item') }}
                        </h2>

                        <span>
                            {{ __('ui.inventory.warehouse') }}
                        </span>
                    </div>

                </div>

            </div>


            <div class="movement-form-grid">

                {{-- Warehouse --}}
                <div class="movement-field">

                    <label for="warehouseId">
                        {{ __('ui.inventory.warehouse') }}

                        <span class="required">*</span>
                    </label>

                    <div class="movement-select-wrapper">

                        <select
                            id="warehouseId"
                            wire:model.live="warehouseId"
                            class="movement-select @error('warehouseId') is-invalid @enderror"
                        >

                            <option value="">—</option>

                            @foreach($warehouses as $w)

                                <option value="{{ $w->id }}">
                                    {{ $w->name_ar }}
                                </option>

                            @endforeach

                        </select>

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>

                    </div>

                    @error('warehouseId')
                        <div class="movement-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                {{-- Destination Warehouse --}}
                @if($mode === 'transfer')

                    <div class="movement-field">

                        <label for="toWarehouseId">
                            {{ __('ui.inventory.to') ?? 'إلى' }}
                            {{ __('ui.inventory.warehouse') }}

                            <span class="required">*</span>
                        </label>

                        <div class="movement-select-wrapper">

                            <select
                                id="toWarehouseId"
                                wire:model.live="toWarehouseId"
                                class="movement-select @error('toWarehouseId') is-invalid @enderror"
                            >

                                <option value="">—</option>

                                @foreach($warehouses as $w)

                                    <option value="{{ $w->id }}">
                                        {{ $w->name_ar }}
                                    </option>

                                @endforeach

                            </select>

                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="m6 9 6 6 6-6"/>
                            </svg>

                        </div>

                        @error('toWarehouseId')
                            <div class="movement-error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                @endif


                {{-- Item --}}
                <div class="movement-field">

                    <label for="itemId">
                        {{ __('ui.inventory.item') }}

                        <span class="required">*</span>
                    </label>

                    <div class="movement-select-wrapper">

                        <select
                            id="itemId"
                            wire:model.live="itemId"
                            class="movement-select @error('itemId') is-invalid @enderror"
                        >

                            <option value="">—</option>

                            @foreach($items as $i)

                                <option value="{{ $i->id }}">
                                    {{ $i->name_ar }} — {{ $i->sku }}
                                </option>

                            @endforeach

                        </select>

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>

                    </div>

                    @error('itemId')
                        <div class="movement-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                {{-- Quantity --}}
                <div class="movement-field">

                    <label for="quantity">
                        {{ __('ui.inventory.quantity') }}

                        <span class="required">*</span>
                    </label>

                    <div class="movement-number-wrapper">

                        <input
                            id="quantity"
                            type="number"
                            step=".001"
                            min="0"
                            wire:model.live="quantity"
                            class="movement-input movement-number-input @error('quantity') is-invalid @enderror"
                            placeholder="0.000"
                        >

                        <span class="movement-input-suffix">
                            {{ __('ui.inventory.quantity') }}
                        </span>

                    </div>

                    @error('quantity')
                        <div class="movement-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                {{-- Unit Cost --}}
                @if($mode === 'receipt')

                    <div class="movement-field">

                        <label for="unitCost">
                            {{ __('ui.inventory.average_cost') }}
                        </label>

                        <div class="movement-number-wrapper">

                            <input
                                id="unitCost"
                                type="number"
                                step=".0001"
                                min="0"
                                wire:model.live="unitCost"
                                class="movement-input movement-number-input @error('unitCost') is-invalid @enderror"
                                placeholder="0.0000"
                            >

                            <span class="movement-input-suffix">
                                {{ __('ui.inventory.value') }}
                            </span>

                        </div>

                        @error('unitCost')
                            <div class="movement-error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                @endif

            </div>

        </div>


        {{-- =====================================================
             REASON
        ====================================================== --}}
        <div class="movement-form-card">

            <div class="movement-form-card-header">

                <div class="movement-form-card-title">

                    <div class="movement-form-card-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 5h16v14H4z"/>
                            <path d="M8 9h8"/>
                            <path d="M8 13h8"/>
                            <path d="M8 17h5"/>
                        </svg>
                    </div>

                    <div>
                        <h2>
                            {{ __('ui.inventory.reason') }}
                        </h2>

                        <span>
                            {{ __('ui.inventory.description') }}
                        </span>
                    </div>

                </div>

            </div>


            <div class="movement-field">

                <label for="reason_ar">
                    {{ __('ui.inventory.reason') }}
                </label>

                <textarea
                    id="reason_ar"
                    wire:model.live="reason_ar"
                    class="movement-textarea @error('reason_ar') is-invalid @enderror"
                    rows="5"
                    placeholder="{{ __('ui.inventory.reason') }}"
                ></textarea>

                @error('reason_ar')
                    <div class="movement-error">
                        {{ $message }}
                    </div>
                @enderror

            </div>

        </div>


        {{-- =====================================================
             ACTION BAR
        ====================================================== --}}
        <div class="movement-action-bar">

            <a
                href="{{ route('admin.inventory.movements.index') }}"
                wire:navigate
                class="movement-cancel-btn"
            >
                {{ __('ui.inventory.cancel') }}
            </a>


            <button
                type="submit"
                class="movement-save-btn"
                wire:loading.attr="disabled"
                wire:target="save"
            >

                <span wire:loading.remove wire:target="save">

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14"/>
                        <path d="m13 6 6 6-6 6"/>
                    </svg>

                    {{ __('ui.inventory.save') }}

                </span>

                <span wire:loading wire:target="save" class="movement-saving-state">

                    <span class="movement-spinner"></span>

                    {{ __('ui.inventory.saving') }}

                </span>

            </button>

        </div>

    </form>


    {{-- =========================================================
         LOADING
    ========================================================== --}}
    <div
        wire:loading
        wire:target="mode"
        class="movement-loading"
    >
        <span class="movement-spinner"></span>
    </div>


    {{-- =========================================================
         STYLES
    ========================================================== --}}
    <style>

        .inventory-movement-entry-page {
            direction: rtl;
            width: 100%;
            max-width: 1500px;
            margin: 0 auto;
            padding: 8px 0 45px;
        }


        /* =====================================================
           HEADER
        ====================================================== */

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
            color: #6b7280;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .16em;
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

        .inventory-secondary-btn {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 16px;
            border: 1px solid #dfe3e8;
            border-radius: 11px;
            background: #fff;
            color: #374151 !important;
            text-decoration: none !important;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
            transition: .2s ease;
        }

        .inventory-secondary-btn:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
            transform: translateY(-1px);
        }

        .inventory-secondary-btn svg {
            width: 17px;
            height: 17px;
        }


        /* =====================================================
           SUCCESS
        ====================================================== */

        .inventory-success-alert {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding: 14px 16px;
            border: 1px solid #bbf7d0;
            border-radius: 15px;
            background: #f0fdf4;
            color: #166534;
        }

        .inventory-success-icon {
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            background: #dcfce7;
        }

        .inventory-success-icon svg {
            width: 20px;
            height: 20px;
        }

        .inventory-success-alert strong {
            display: block;
            font-size: 12px;
            font-weight: 900;
        }

        .inventory-success-alert span {
            display: block;
            margin-top: 2px;
            font-size: 12px;
        }


        /* =====================================================
           MOVEMENT TYPE
        ====================================================== */

        .movement-type-card {
            margin-bottom: 20px;
            padding: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 8px 28px rgba(15,23,42,.045);
        }

        .movement-type-header {
            margin-bottom: 16px;
        }

        .movement-type-title {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .movement-type-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            background: #f3f4f6;
            color: #374151;
        }

        .movement-type-icon svg {
            width: 20px;
            height: 20px;
        }

        .movement-type-title strong {
            display: block;
            color: #111827;
            font-size: 14px;
            font-weight: 900;
        }

        .movement-type-title span {
            display: block;
            margin-top: 2px;
            color: #9ca3af;
            font-size: 11px;
        }

        .movement-type-options {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .movement-type-option {
            position: relative;
            display: flex;
            align-items: center;
            gap: 13px;
            width: 100%;
            min-height: 82px;
            padding: 14px;
            border: 1px solid #e5e7eb;
            border-radius: 15px;
            background: #fff;
            color: #374151;
            text-align: right;
            cursor: pointer;
            transition: .2s ease;
        }

        .movement-type-option:hover {
            border-color: #cbd5e1;
            background: #fafafa;
        }

        .movement-type-option.active {
            border-width: 1.5px;
        }

        .movement-type-option.active.receipt {
            border-color: #86efac;
            background: #f0fdf4;
        }

        .movement-type-option.active.transfer {
            border-color: #93c5fd;
            background: #eff6ff;
        }

        .movement-option-icon {
            width: 44px;
            height: 44px;
            flex: 0 0 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #f3f4f6;
            color: #4b5563;
        }

        .movement-type-option.active.receipt .movement-option-icon {
            background: #dcfce7;
            color: #047857;
        }

        .movement-type-option.active.transfer .movement-option-icon {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .movement-option-icon svg {
            width: 21px;
            height: 21px;
        }

        .movement-option-content {
            min-width: 0;
        }

        .movement-option-content strong {
            display: block;
            color: #111827;
            font-size: 13px;
            font-weight: 900;
        }

        .movement-option-content span {
            display: block;
            margin-top: 3px;
            color: #9ca3af;
            font-size: 10px;
        }

        .movement-option-check {
            position: absolute;
            top: 12px;
            left: 12px;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #111827;
            color: #fff;
        }

        .movement-option-check svg {
            width: 13px;
            height: 13px;
        }


        /* =====================================================
           FORM
        ====================================================== */

        .movement-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .movement-form-card {
            padding: 22px;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 8px 28px rgba(15,23,42,.045);
        }

        .movement-form-card-header {
            padding-bottom: 16px;
            margin-bottom: 20px;
            border-bottom: 1px solid #eef0f2;
        }

        .movement-form-card-title {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .movement-form-card-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            background: #f3f4f6;
            color: #374151;
        }

        .movement-form-card-icon svg {
            width: 20px;
            height: 20px;
        }

        .movement-form-card-title h2 {
            margin: 0;
            color: #111827;
            font-size: 14px;
            font-weight: 900;
        }

        .movement-form-card-title span {
            display: block;
            margin-top: 2px;
            color: #9ca3af;
            font-size: 11px;
        }

        .movement-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .movement-field {
            min-width: 0;
        }

        .movement-field label {
            display: block;
            margin-bottom: 7px;
            color: #374151;
            font-size: 12px;
            font-weight: 850;
        }

        .movement-field .required {
            color: #dc2626;
        }

        .movement-select-wrapper,
        .movement-number-wrapper {
            position: relative;
        }

        .movement-select-wrapper > svg {
            position: absolute;
            left: 13px;
            top: 50%;
            width: 17px;
            height: 17px;
            color: #9ca3af;
            pointer-events: none;
            transform: translateY(-50%);
        }

        .movement-select,
        .movement-input,
        .movement-textarea {
            width: 100%;
            border: 1px solid #dfe3e8;
            border-radius: 11px;
            background: #fff;
            color: #111827;
            outline: none;
            font-size: 13px;
            transition: .2s ease;
            box-sizing: border-box;
        }

        .movement-select {
            height: 46px;
            padding: 0 13px 0 40px;
            appearance: none;
            cursor: pointer;
        }

        .movement-input {
            height: 46px;
            padding: 0 13px;
        }

        .movement-number-input {
            padding-left: 105px;
        }

        .movement-input-suffix {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            padding: 5px 7px;
            border-radius: 7px;
            background: #f3f4f6;
            color: #6b7280;
            font-size: 9px;
            font-weight: 800;
            pointer-events: none;
        }

        .movement-textarea {
            min-height: 125px;
            padding: 13px;
            resize: vertical;
            line-height: 1.8;
        }

        .movement-select:focus,
        .movement-input:focus,
        .movement-textarea:focus {
            border-color: #9ca3af;
            box-shadow: 0 0 0 3px rgba(156,163,175,.12);
        }

        .movement-select.is-invalid,
        .movement-input.is-invalid,
        .movement-textarea.is-invalid {
            border-color: #ef4444;
        }

        .movement-error {
            margin-top: 6px;
            color: #dc2626;
            font-size: 11px;
            font-weight: 700;
        }


        /* =====================================================
           ACTION BAR
        ====================================================== */

        .movement-action-bar {
            position: sticky;
            bottom: 14px;
            z-index: 20;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            padding: 14px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: rgba(255,255,255,.94);
            box-shadow: 0 12px 35px rgba(15,23,42,.12);
            backdrop-filter: blur(10px);
        }

        .movement-cancel-btn,
        .movement-save-btn {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 18px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 850;
            cursor: pointer;
            text-decoration: none !important;
            transition: .2s ease;
        }

        .movement-cancel-btn {
            border: 1px solid #dfe3e8;
            background: #fff;
            color: #4b5563 !important;
        }

        .movement-cancel-btn:hover {
            background: #f9fafb;
        }

        .movement-save-btn {
            border: 0;
            background: #111827;
            color: #fff;
        }

        .movement-save-btn:hover {
            background: #1f2937;
            transform: translateY(-1px);
        }

        .movement-save-btn:disabled {
            opacity: .65;
            cursor: wait;
        }

        .movement-save-btn svg {
            width: 17px;
            height: 17px;
        }

        .movement-saving-state {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .movement-spinner {
            width: 15px;
            height: 15px;
            display: inline-block;
            border: 2px solid rgba(255,255,255,.35);
            border-top-color: currentColor;
            border-radius: 50%;
            animation: movement-spin .7s linear infinite;
        }

        .movement-loading {
            position: fixed;
            z-index: 1000;
            left: 24px;
            bottom: 24px;
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: rgba(255,255,255,.96);
            box-shadow: 0 8px 25px rgba(15,23,42,.12);
            color: #111827;
        }

        .movement-loading .movement-spinner {
            border-color: #d1d5db;
            border-top-color: #111827;
        }

        @keyframes movement-spin {
            to {
                transform: rotate(360deg);
            }
        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 900px) {

            .movement-form-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 700px) {

            .inventory-movement-entry-page {
                padding-bottom: 25px;
            }

            .inventory-page-header {
                flex-direction: column;
                align-items: stretch;
                padding: 20px;
            }

            .inventory-page-header-main {
                align-items: flex-start;
            }

            .inventory-secondary-btn {
                width: 100%;
            }

            .inventory-page-title {
                font-size: 21px;
            }

            .inventory-page-description {
                font-size: 12px;
            }

            .movement-type-card,
            .movement-form-card {
                padding: 16px;
            }

            .movement-type-options {
                grid-template-columns: 1fr;
            }

            .movement-action-bar {
                bottom: 8px;
                justify-content: stretch;
            }

            .movement-cancel-btn,
            .movement-save-btn {
                flex: 1;
            }

            .movement-loading {
                left: 16px;
                bottom: 75px;
            }

        }

    </style>

</div>

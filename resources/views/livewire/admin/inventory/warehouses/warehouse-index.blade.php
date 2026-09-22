@php
    /** @var \App\Livewire\Admin\Inventory\Warehouses\WarehouseIndex $this */
@endphp

<div class="inventory-warehouses-page">

    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div class="inventory-warehouses-header">

        <div class="inventory-warehouses-header-content">

            <div class="inventory-warehouses-icon">

                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M3.5 10.5 12 4l8.5 6.5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />

                    <path
                        d="M5 9.8V20h14V9.8"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linejoin="round"
                    />

                    <path
                        d="M8 20v-6h8v6M8 10h.01M12 10h.01M16 10h.01"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                    />
                </svg>

            </div>

            <div>

                <div class="inventory-warehouses-eyebrow">
                    GCV INVENTORY
                </div>

                <h1 class="inventory-warehouses-title">
                    {{ __('ui.inventory.warehouses') }}
                </h1>

                <p class="inventory-warehouses-description">
                    {{ __('ui.inventory.description') }}
                </p>

            </div>

        </div>

        <div class="inventory-warehouses-header-actions">

            <button
                type="button"
                class="inventory-primary-btn"
                wire:click="open"
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
                    {{ __('ui.inventory.add_warehouse') }}
                </span>

            </button>

        </div>

    </div>


    {{-- =========================================================
         INVENTORY NAVIGATION
    ========================================================== --}}
    <div class="inventory-warehouses-navigation">

        @include('livewire.admin.inventory._nav')

    </div>


    {{-- =========================================================
         SUMMARY
    ========================================================== --}}
    @php
        $totalWarehouses = $warehouses->count();
        $activeWarehouses = $warehouses->where('status', 'active')->count();
        $inactiveWarehouses = $warehouses->where('status', 'inactive')->count();
        $centralWarehouses = $warehouses->where('warehouse_type', 'central')->count();
        $institutionalWarehouses = $warehouses->where('warehouse_type', 'institutional')->count();
    @endphp

    <div class="inventory-warehouses-summary">

        {{-- Total --}}
        <div class="inventory-summary-card">

            <div class="inventory-summary-icon total">

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

                <span>
                    {{ __('ui.inventory.warehouses') }}
                </span>

                <strong>
                    {{ number_format($totalWarehouses) }}
                </strong>

            </div>

        </div>


        {{-- Active --}}
        <div class="inventory-summary-card">

            <div class="inventory-summary-icon active">

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
                    {{ __('ui.inventory.active') }}
                </span>

                <strong>
                    {{ number_format($activeWarehouses) }}
                </strong>

            </div>

        </div>


        {{-- Central --}}
        <div class="inventory-summary-card">

            <div class="inventory-summary-icon central">

                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M4 20h16M6 20V9h12v11M8 9V5h8v4M10 12h.01M14 12h.01M10 16h.01M14 16h.01"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.6"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>

            </div>

            <div>

                <span>
                    {{ __('ui.inventory.central') }}
                </span>

                <strong>
                    {{ number_format($centralWarehouses) }}
                </strong>

            </div>

        </div>


        {{-- Institutional --}}
        <div class="inventory-summary-card">

            <div class="inventory-summary-icon institutional">

                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M4 20h16M6 20v-9l6-4 6 4v9"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.6"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />

                    <path
                        d="M9 20v-5h6v5M9 11h.01M12 11h.01M15 11h.01"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.6"
                        stroke-linecap="round"
                    />
                </svg>

            </div>

            <div>

                <span>
                    {{ __('ui.inventory.institutional') }}
                </span>

                <strong>
                    {{ number_format($institutionalWarehouses) }}
                </strong>

            </div>

        </div>

    </div>


    {{-- =========================================================
         CREATE / EDIT FORM
    ========================================================== --}}
    @if($show)

        <section class="inventory-warehouse-form-card">

            <div class="inventory-form-header">

                <div class="inventory-form-heading">

                    <div class="inventory-form-heading-icon">

                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            @if($id)

                                <path
                                    d="m14.5 5.5 4 4M4 20l4.2-1 9.9-9.9a2.8 2.8 0 0 0-4-4L4.2 15.1 4 20Z"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linejoin="round"
                                />

                            @else

                                <path
                                    d="M12 5v14M5 12h14"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                />

                            @endif
                        </svg>

                    </div>

                    <div>

                        <span class="inventory-form-eyebrow">
                            GCV DATA
                        </span>

                        <h2>
                            @if($id)
                                {{ __('ui.inventory.edit') }}
                                —
                                {{ __('ui.inventory.warehouse') }}
                            @else
                                {{ __('ui.inventory.add_warehouse') }}
                            @endif
                        </h2>

                        <p>
                            {{ __('ui.inventory.description') }}
                        </p>

                    </div>

                </div>


                <button
                    type="button"
                    class="inventory-close-btn"
                    wire:click="$set('show', false)"
                    aria-label="{{ __('ui.inventory.cancel') }}"
                >

                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="m7 7 10 10M17 7 7 17"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />
                    </svg>

                </button>

            </div>


            {{-- Validation --}}
            @if($errors->any())

                <div class="inventory-validation-summary">

                    <div class="inventory-validation-icon">

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
                            {{ __('ui.inventory.validation_error') }}
                        </strong>

                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>

                    </div>

                </div>

            @endif


            <form
                wire:submit="save"
                class="inventory-warehouse-form"
            >

                {{-- =================================================
                     BASIC INFORMATION
                ================================================== --}}
                <div class="inventory-form-section">

                    <div class="inventory-section-title">

                        <div class="inventory-section-title-icon">

                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path
                                    d="M4 6h16M4 12h16M4 18h10"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                />
                            </svg>

                        </div>

                        <div>

                            <h3>
                                {{ __('ui.inventory.warehouse') }}
                            </h3>

                            <p>
                                {{ __('ui.inventory.institution') }}
                                —
                                {{ __('ui.inventory.code') }}
                            </p>

                        </div>

                    </div>


                    <div class="inventory-form-grid">

                        {{-- Institution --}}
                        <div class="inventory-field">

                            <label for="institution_id">

                                {{ __('ui.inventory.institution') }}

                                <span>*</span>

                            </label>

                            <select
                                id="institution_id"
                                wire:model="institution_id"
                                class="@error('institution_id') is-invalid @enderror"
                            >

                                <option value="">
                                    —
                                </option>

                                @foreach($institutions as $i)

                                    <option value="{{ $i->id }}">
                                        {{ $i->name_ar }}
                                        @if($i->name_en)
                                            / {{ $i->name_en }}
                                        @endif
                                    </option>

                                @endforeach

                            </select>

                            @error('institution_id')
                                <small class="inventory-error">
                                    {{ $message }}
                                </small>
                            @enderror

                        </div>


                        {{-- Code --}}
                        <div class="inventory-field">

                            <label for="code">

                                {{ __('ui.inventory.code') }}

                                <span>*</span>

                            </label>

                            <input
                                id="code"
                                type="text"
                                wire:model="code"
                                placeholder="{{ __('ui.inventory.code') }}"
                                class="@error('code') is-invalid @enderror"
                            >

                            @error('code')
                                <small class="inventory-error">
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
                                placeholder="{{ __('ui.inventory.name_ar') }}"
                                class="@error('name_ar') is-invalid @enderror"
                            >

                            @error('name_ar')
                                <small class="inventory-error">
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
                                placeholder="{{ __('ui.inventory.name_en') }}"
                                class="@error('name_en') is-invalid @enderror"
                            >

                            @error('name_en')
                                <small class="inventory-error">
                                    {{ $message }}
                                </small>
                            @enderror

                        </div>

                    </div>

                </div>


                {{-- =================================================
                     CLASSIFICATION
                ================================================== --}}
                <div class="inventory-form-section">

                    <div class="inventory-section-title">

                        <div class="inventory-section-title-icon">

                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path
                                    d="M5 5h14v14H5z"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linejoin="round"
                                />

                                <path
                                    d="M9 9h6v6H9z"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                />
                            </svg>

                        </div>

                        <div>

                            <h3>
                                {{ __('ui.inventory.status') }}
                            </h3>

                            <p>
                                {{ __('ui.inventory.warehouse_type') }}
                            </p>

                        </div>

                    </div>


                    <div class="inventory-form-grid">

                        {{-- Warehouse Type --}}
                        <div class="inventory-field">

                            <label for="warehouse_type">

                                {{ __('ui.inventory.warehouse_type') }}

                                <span>*</span>

                            </label>

                            <select
                                id="warehouse_type"
                                wire:model="warehouse_type"
                                class="@error('warehouse_type') is-invalid @enderror"
                            >

                                <option value="central">
                                    {{ __('ui.inventory.central') }}
                                </option>

                                <option value="institutional">
                                    {{ __('ui.inventory.institutional') }}
                                </option>

                            </select>

                            @error('warehouse_type')
                                <small class="inventory-error">
                                    {{ $message }}
                                </small>
                            @enderror

                        </div>


                        {{-- Status --}}
                        <div class="inventory-field">

                            <label for="status">

                                {{ __('ui.inventory.status') }}

                                <span>*</span>

                            </label>

                            <select
                                id="status"
                                wire:model="status"
                                class="@error('status') is-invalid @enderror"
                            >

                                <option value="active">
                                    {{ __('ui.inventory.active') }}
                                </option>

                                <option value="inactive">
                                    {{ __('ui.inventory.inactive') }}
                                </option>

                            </select>

                            @error('status')
                                <small class="inventory-error">
                                    {{ $message }}
                                </small>
                            @enderror

                        </div>

                    </div>

                </div>


                {{-- =================================================
                     LOCATION
                ================================================== --}}
                <div class="inventory-form-section">

                    <div class="inventory-section-title">

                        <div class="inventory-section-title-icon">

                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path
                                    d="M12 21s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12Z"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linejoin="round"
                                />

                                <circle
                                    cx="12"
                                    cy="9"
                                    r="2.3"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                />
                            </svg>

                        </div>

                        <div>

                            <h3>
                                {{ __('ui.inventory.address_ar') }}
                            </h3>

                            <p>
                                {{ __('ui.inventory.latitude') }}
                                /
                                {{ __('ui.inventory.longitude') }}
                            </p>

                        </div>

                    </div>


                    <div class="inventory-form-grid">

                        {{-- Address Arabic --}}
                        <div class="inventory-field">

                            <label for="address_ar">
                                {{ __('ui.inventory.address_ar') }}
                            </label>

                            <input
                                id="address_ar"
                                type="text"
                                wire:model="address_ar"
                                dir="rtl"
                                placeholder="{{ __('ui.inventory.address_ar') }}"
                                class="@error('address_ar') is-invalid @enderror"
                            >

                            @error('address_ar')
                                <small class="inventory-error">
                                    {{ $message }}
                                </small>
                            @enderror

                        </div>


                        {{-- Address English --}}
                        <div class="inventory-field">

                            <label for="address_en">
                                {{ __('ui.inventory.address_en') }}
                            </label>

                            <input
                                id="address_en"
                                type="text"
                                wire:model="address_en"
                                dir="ltr"
                                placeholder="{{ __('ui.inventory.address_en') }}"
                                class="@error('address_en') is-invalid @enderror"
                            >

                            @error('address_en')
                                <small class="inventory-error">
                                    {{ $message }}
                                </small>
                            @enderror

                        </div>


                        {{-- Latitude --}}
                        <div class="inventory-field">

                            <label for="latitude">
                                {{ __('ui.inventory.latitude') }}
                            </label>

                            <input
                                id="latitude"
                                type="number"
                                step="any"
                                wire:model="latitude"
                                dir="ltr"
                                placeholder="31.5000"
                                class="@error('latitude') is-invalid @enderror"
                            >

                            @error('latitude')
                                <small class="inventory-error">
                                    {{ $message }}
                                </small>
                            @enderror

                        </div>


                        {{-- Longitude --}}
                        <div class="inventory-field">

                            <label for="longitude">
                                {{ __('ui.inventory.longitude') }}
                            </label>

                            <input
                                id="longitude"
                                type="number"
                                step="any"
                                wire:model="longitude"
                                dir="ltr"
                                placeholder="34.4667"
                                class="@error('longitude') is-invalid @enderror"
                            >

                            @error('longitude')
                                <small class="inventory-error">
                                    {{ $message }}
                                </small>
                            @enderror

                        </div>

                    </div>

                </div>


                {{-- =================================================
                     ACTION BAR
                ================================================== --}}
                <div class="inventory-form-actions">

                    <button
                        type="button"
                        class="inventory-secondary-btn"
                        wire:click="$set('show', false)"
                    >

                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path
                                d="m7 7 10 10M17 7 7 17"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                            />
                        </svg>

                        {{ __('ui.inventory.cancel') }}

                    </button>


                    <button
                        type="submit"
                        class="inventory-primary-btn"
                        wire:loading.attr="disabled"
                        wire:target="save"
                    >

                        <span wire:loading.remove wire:target="save">

                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path
                                    d="M5 4h11l3 3v13H5V4Z"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linejoin="round"
                                />

                                <path
                                    d="M8 4v6h7V4M8 16h8"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linejoin="round"
                                />
                            </svg>

                            {{ __('ui.inventory.save') }}

                        </span>


                        <span
                            wire:loading
                            wire:target="save"
                            class="inventory-loading"
                        >

                            <span class="inventory-spinner"></span>

                            {{ __('ui.inventory.saving') }}

                        </span>

                    </button>

                </div>

            </form>

        </section>

    @endif


    {{-- =========================================================
         WAREHOUSES TABLE
    ========================================================== --}}
    <section class="inventory-warehouses-table-card">

        <div class="inventory-table-header">

            <div>

                <span class="inventory-table-eyebrow">
                    GCV DATA
                </span>

                <h2>
                    {{ __('ui.inventory.warehouses') }}
                </h2>

                <p>
                    {{ $totalWarehouses }}
                    {{ __('ui.inventory.warehouses') }}
                </p>

            </div>

            <div class="inventory-table-count">
                {{ number_format($totalWarehouses) }}
            </div>

        </div>


        @if($warehouses->count())

            <div class="inventory-table-wrapper">

                <table class="inventory-warehouses-table">

                    <thead>

                        <tr>

                            <th>
                                {{ __('ui.inventory.code') }}
                            </th>

                            <th>
                                {{ __('ui.inventory.warehouse') }}
                            </th>

                            <th>
                                {{ __('ui.inventory.institution') }}
                            </th>

                            <th>
                                {{ __('ui.inventory.warehouse_type') }}
                            </th>

                            <th>
                                {{ __('ui.inventory.status') }}
                            </th>

                            <th class="inventory-action-column">
                                {{ __('ui.inventory.edit') }}
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @foreach($warehouses as $w)

                            <tr>

                                {{-- Code --}}
                                <td>

                                    <span class="inventory-code">
                                        {{ $w->code }}
                                    </span>

                                </td>


                                {{-- Warehouse --}}
                                <td>

                                    <div class="inventory-warehouse-name">

                                        <div class="inventory-warehouse-avatar">

                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                <path
                                                    d="M4 20h16M6 20V9h12v11M8 9V5h8v4M10 13h.01M14 13h.01M10 17h.01M14 17h.01"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.6"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                />
                                            </svg>

                                        </div>

                                        <div>

                                            <strong>
                                                {{ $w->name_ar }}
                                            </strong>

                                            @if($w->name_en)

                                                <small dir="ltr">
                                                    {{ $w->name_en }}
                                                </small>

                                            @endif

                                        </div>

                                    </div>

                                </td>


                                {{-- Institution --}}
                                <td>

                                    @if($w->institution)

                                        <div class="inventory-institution">

                                            <strong>
                                                {{ $w->institution->name_ar }}
                                            </strong>

                                            @if($w->institution->name_en)

                                                <small dir="ltr">
                                                    {{ $w->institution->name_en }}
                                                </small>

                                            @endif

                                        </div>

                                    @else

                                        <span class="inventory-empty-value">
                                            —
                                        </span>

                                    @endif

                                </td>


                                {{-- Type --}}
                                <td>

                                    @if($w->warehouse_type === 'central')

                                        <span class="inventory-type-pill central">
                                            {{ __('ui.inventory.central') }}
                                        </span>

                                    @else

                                        <span class="inventory-type-pill institutional">
                                            {{ __('ui.inventory.institutional') }}
                                        </span>

                                    @endif

                                </td>


                                {{-- Status --}}
                                <td>

                                    @if($w->status === 'active')

                                        <span class="inventory-status-pill active">

                                            <span class="inventory-status-dot"></span>

                                            {{ __('ui.inventory.active') }}

                                        </span>

                                    @else

                                        <span class="inventory-status-pill inactive">

                                            <span class="inventory-status-dot"></span>

                                            {{ __('ui.inventory.inactive') }}

                                        </span>

                                    @endif

                                </td>


                                {{-- Action --}}
                                <td class="inventory-action-column">

                                    <button
                                        type="button"
                                        class="inventory-edit-btn"
                                        wire:click="open({{ $w->id }})"
                                    >

                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path
                                                d="m14.5 5.5 4 4M4 20l4.2-1 9.9-9.9a2.8 2.8 0 0 0-4-4L4.2 15.1 4 20Z"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="1.7"
                                                stroke-linejoin="round"
                                            />
                                        </svg>

                                        {{ __('ui.inventory.edit') }}

                                    </button>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        @else

            <div class="inventory-empty-state">

                <div class="inventory-empty-icon">

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
                    {{ __('ui.inventory.warehouses') }}
                </span>

            </div>

        @endif

    </section>


    {{-- =========================================================
         STYLES
    ========================================================== --}}
    <style>

        .inventory-warehouses-page {
            direction: rtl;
            width: 100%;
            color: #172033;
        }


        /* =====================================================
           HEADER
        ====================================================== */

        .inventory-warehouses-header {
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

        .inventory-warehouses-header-content {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
        }

        .inventory-warehouses-icon {
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

        .inventory-warehouses-icon svg {
            width: 30px;
            height: 30px;
        }

        .inventory-warehouses-eyebrow {
            margin-bottom: 4px;
            color: #7b8497;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
        }

        .inventory-warehouses-title {
            margin: 0;
            color: #172033;
            font-size: 26px;
            line-height: 1.3;
            font-weight: 800;
        }

        .inventory-warehouses-description {
            max-width: 750px;
            margin: 7px 0 0;
            color: #697386;
            font-size: 13px;
            line-height: 1.7;
        }


        /* =====================================================
           BUTTONS
        ====================================================== */

        .inventory-primary-btn {
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

        .inventory-primary-btn:hover {
            background: #264edb;
            transform: translateY(-1px);
        }

        .inventory-primary-btn svg {
            width: 18px;
            height: 18px;
        }

        .inventory-secondary-btn {
            min-height: 43px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 17px;
            border: 1px solid #d9dee7;
            border-radius: 10px;
            background: #ffffff;
            color: #475467;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: .18s ease;
        }

        .inventory-secondary-btn:hover {
            background: #f7f8fa;
            border-color: #c7cdd8;
        }

        .inventory-secondary-btn svg {
            width: 17px;
            height: 17px;
        }


        /* =====================================================
           NAVIGATION
        ====================================================== */

        .inventory-warehouses-navigation {
            margin-bottom: 20px;
        }


        /* =====================================================
           SUMMARY
        ====================================================== */

        .inventory-warehouses-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }

        .inventory-summary-card {
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

        .inventory-summary-icon {
            width: 44px;
            height: 44px;
            flex: 0 0 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
        }

        .inventory-summary-icon svg {
            width: 22px;
            height: 22px;
        }

        .inventory-summary-icon.total {
            background: #eef4ff;
            color: #315efb;
        }

        .inventory-summary-icon.active {
            background: #ecfdf3;
            color: #12b76a;
        }

        .inventory-summary-icon.central {
            background: #fff7e8;
            color: #e89419;
        }

        .inventory-summary-icon.institutional {
            background: #f4efff;
            color: #7a5af8;
        }

        .inventory-summary-card span {
            display: block;
            margin-bottom: 4px;
            color: #7b8497;
            font-size: 11px;
            font-weight: 650;
        }

        .inventory-summary-card strong {
            display: block;
            color: #172033;
            font-size: 23px;
            line-height: 1;
            font-weight: 850;
        }


        /* =====================================================
           FORM CARD
        ====================================================== */

        .inventory-warehouse-form-card {
            margin-bottom: 20px;
            overflow: hidden;
            border: 1px solid #e2e7ee;
            border-radius: 17px;
            background: #ffffff;
            box-shadow: 0 8px 25px rgba(15, 23, 42, .045);
        }

        .inventory-form-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 21px 23px;
            border-bottom: 1px solid #edf0f4;
            background: #fbfcfe;
        }

        .inventory-form-heading {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .inventory-form-heading-icon {
            width: 45px;
            height: 45px;
            flex: 0 0 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #eef4ff;
            color: #315efb;
        }

        .inventory-form-heading-icon svg {
            width: 22px;
            height: 22px;
        }

        .inventory-form-eyebrow {
            display: block;
            margin-bottom: 3px;
            color: #315efb;
            font-size: 9px;
            font-weight: 850;
            letter-spacing: .07em;
        }

        .inventory-form-heading h2 {
            margin: 0;
            color: #172033;
            font-size: 18px;
            font-weight: 800;
        }

        .inventory-form-heading p {
            margin: 4px 0 0;
            color: #7a8394;
            font-size: 11px;
        }

        .inventory-close-btn {
            width: 37px;
            height: 37px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e1e5eb;
            border-radius: 9px;
            background: #ffffff;
            color: #7a8394;
            cursor: pointer;
        }

        .inventory-close-btn:hover {
            background: #f5f6f8;
            color: #344054;
        }

        .inventory-close-btn svg {
            width: 18px;
            height: 18px;
        }


        /* =====================================================
           VALIDATION
        ====================================================== */

        .inventory-validation-summary {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            margin: 18px 23px 0;
            padding: 13px 14px;
            border: 1px solid #fecdca;
            border-radius: 10px;
            background: #fff6f5;
            color: #b42318;
        }

        .inventory-validation-icon {
            width: 27px;
            height: 27px;
            flex: 0 0 27px;
        }

        .inventory-validation-icon svg {
            width: 27px;
            height: 27px;
        }

        .inventory-validation-summary strong {
            display: block;
            margin-bottom: 4px;
            font-size: 12px;
        }

        .inventory-validation-summary ul {
            margin: 0;
            padding-inline-start: 18px;
            font-size: 11px;
            line-height: 1.8;
        }


        /* =====================================================
           FORM
        ====================================================== */

        .inventory-warehouse-form {
            padding: 23px;
        }

        .inventory-form-section {
            margin-bottom: 25px;
            padding-bottom: 23px;
            border-bottom: 1px solid #edf0f4;
        }

        .inventory-form-section:last-of-type {
            margin-bottom: 0;
        }

        .inventory-section-title {
            display: flex;
            align-items: center;
            gap: 11px;
            margin-bottom: 17px;
        }

        .inventory-section-title-icon {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            background: #f4f6fa;
            color: #667085;
        }

        .inventory-section-title-icon svg {
            width: 18px;
            height: 18px;
        }

        .inventory-section-title h3 {
            margin: 0;
            color: #344054;
            font-size: 13px;
            font-weight: 800;
        }

        .inventory-section-title p {
            margin: 3px 0 0;
            color: #98a1b2;
            font-size: 10px;
        }

        .inventory-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 17px;
        }

        .inventory-field {
            min-width: 0;
        }

        .inventory-field label {
            display: block;
            margin-bottom: 7px;
            color: #344054;
            font-size: 11px;
            font-weight: 750;
        }

        .inventory-field label span {
            color: #d92d20;
        }

        .inventory-field input,
        .inventory-field select {
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

        .inventory-field input:focus,
        .inventory-field select:focus {
            border-color: #315efb;
            box-shadow: 0 0 0 3px rgba(49, 94, 251, .08);
        }

        .inventory-field input::placeholder {
            color: #a0a8b6;
        }

        .inventory-field input.is-invalid,
        .inventory-field select.is-invalid {
            border-color: #f04438;
            background: #fffafa;
        }

        .inventory-error {
            display: block;
            margin-top: 5px;
            color: #d92d20;
            font-size: 10px;
            line-height: 1.5;
        }


        /* =====================================================
           FORM ACTIONS
        ====================================================== */

        .inventory-form-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 20px;
        }

        .inventory-loading {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .inventory-spinner {
            width: 14px;
            height: 14px;
            display: inline-block;
            border: 2px solid rgba(255,255,255,.45);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: inventory-spin .7s linear infinite;
        }

        @keyframes inventory-spin {
            to {
                transform: rotate(360deg);
            }
        }


        /* =====================================================
           TABLE CARD
        ====================================================== */

        .inventory-warehouses-table-card {
            overflow: hidden;
            border: 1px solid #e7eaf0;
            border-radius: 17px;
            background: #ffffff;
            box-shadow: 0 7px 25px rgba(15, 23, 42, .04);
        }

        .inventory-table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 21px 23px;
            border-bottom: 1px solid #edf0f4;
        }

        .inventory-table-eyebrow {
            display: block;
            margin-bottom: 4px;
            color: #315efb;
            font-size: 9px;
            font-weight: 850;
            letter-spacing: .07em;
        }

        .inventory-table-header h2 {
            margin: 0;
            color: #172033;
            font-size: 18px;
            font-weight: 800;
        }

        .inventory-table-header p {
            margin: 4px 0 0;
            color: #7a8394;
            font-size: 11px;
        }

        .inventory-table-count {
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

        .inventory-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .inventory-warehouses-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        .inventory-warehouses-table thead th {
            padding: 12px 16px;
            border-bottom: 1px solid #e9edf2;
            background: #fafbfc;
            color: #7a8394;
            font-size: 10px;
            font-weight: 800;
            text-align: right;
            white-space: nowrap;
        }

        .inventory-warehouses-table tbody td {
            padding: 14px 16px;
            border-bottom: 1px solid #f0f2f5;
            color: #475467;
            font-size: 11px;
            vertical-align: middle;
        }

        .inventory-warehouses-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .inventory-warehouses-table tbody tr {
            transition: .15s ease;
        }

        .inventory-warehouses-table tbody tr:hover {
            background: #fbfcff;
        }


        /* Code */
        .inventory-code {
            display: inline-flex;
            align-items: center;
            min-height: 29px;
            padding: 0 9px;
            border-radius: 7px;
            background: #f4f6f8;
            color: #475467;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 9px;
            font-weight: 800;
            direction: ltr;
        }


        /* Warehouse */
        .inventory-warehouse-name {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .inventory-warehouse-avatar {
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            background: #eef4ff;
            color: #315efb;
        }

        .inventory-warehouse-avatar svg {
            width: 18px;
            height: 18px;
        }

        .inventory-warehouse-name strong {
            display: block;
            color: #344054;
            font-size: 11px;
            font-weight: 800;
        }

        .inventory-warehouse-name small {
            display: block;
            margin-top: 2px;
            color: #98a1b2;
            font-size: 9px;
        }


        /* Institution */
        .inventory-institution strong {
            display: block;
            color: #475467;
            font-size: 10px;
            font-weight: 700;
        }

        .inventory-institution small {
            display: block;
            margin-top: 2px;
            color: #98a1b2;
            font-size: 8px;
        }

        .inventory-empty-value {
            color: #a0a8b6;
        }


        /* Type */
        .inventory-type-pill {
            display: inline-flex;
            align-items: center;
            min-height: 27px;
            padding: 0 9px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 750;
        }

        .inventory-type-pill.central {
            background: #fff7e8;
            color: #b76e00;
        }

        .inventory-type-pill.institutional {
            background: #f4efff;
            color: #6941c6;
        }


        /* Status */
        .inventory-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 27px;
            padding: 0 9px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 750;
        }

        .inventory-status-pill.active {
            background: #ecfdf3;
            color: #087443;
        }

        .inventory-status-pill.inactive {
            background: #f2f4f7;
            color: #667085;
        }

        .inventory-status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }


        /* Action */
        .inventory-action-column {
            width: 105px;
            text-align: center !important;
        }

        .inventory-edit-btn {
            min-height: 31px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 0 9px;
            border: 1px solid #d9dee7;
            border-radius: 7px;
            background: #ffffff;
            color: #475467;
            font-family: inherit;
            font-size: 9px;
            font-weight: 750;
            cursor: pointer;
            transition: .15s ease;
        }

        .inventory-edit-btn:hover {
            border-color: #b9c7ed;
            background: #f5f8ff;
            color: #315efb;
        }

        .inventory-edit-btn svg {
            width: 14px;
            height: 14px;
        }


        /* =====================================================
           EMPTY STATE
        ====================================================== */

        .inventory-empty-state {
            padding: 65px 20px;
            text-align: center;
        }

        .inventory-empty-icon {
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

        .inventory-empty-icon svg {
            width: 28px;
            height: 28px;
        }

        .inventory-empty-state strong {
            display: block;
            color: #667085;
            font-size: 13px;
        }

        .inventory-empty-state span {
            display: block;
            margin-top: 4px;
            color: #98a1b2;
            font-size: 10px;
        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 1050px) {

            .inventory-warehouses-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

        }

        @media (max-width: 760px) {

            .inventory-warehouses-header {
                flex-direction: column;
                align-items: stretch;
            }

            .inventory-warehouses-header-actions {
                width: 100%;
            }

            .inventory-primary-btn {
                width: 100%;
            }

            .inventory-form-grid {
                grid-template-columns: 1fr;
            }

            .inventory-form-header {
                align-items: flex-start;
            }

        }

        @media (max-width: 520px) {

            .inventory-warehouses-summary {
                grid-template-columns: 1fr;
            }

            .inventory-warehouses-header {
                padding: 18px;
                border-radius: 14px;
            }

            .inventory-warehouses-title {
                font-size: 21px;
            }

            .inventory-warehouse-form {
                padding: 17px;
            }

            .inventory-form-header {
                padding: 17px;
            }

            .inventory-form-actions {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .inventory-form-actions button {
                width: 100%;
            }

        }

    </style>

</div>

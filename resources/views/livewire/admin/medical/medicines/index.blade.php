@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';
@endphp

<div class="medical-page">

    {{-- Medical Navigation --}}
    @include('livewire.admin.medical._nav')


    {{-- ========================================================= --}}
    {{-- Header --}}
    {{-- ========================================================= --}}

    <div class="medical-page-header">

        <div>

            <div class="medical-eyebrow">
                {{ __('medical.medical_portal', [], null, 'Medical Portal') }}
            </div>

            <h1 class="medical-page-title">
                {{ __('medical.medicines', [], null, 'Medicines') }}
            </h1>

            <p class="medical-page-description">
                {{ __('medical.medicines_description', [], null, 'Manage medicines, stock thresholds, manufacturers and pharmacy inventory information.') }}
            </p>

        </div>


        <div class="medical-page-header-actions">

            @if(!$showForm)

                <button
                    type="button"
                    wire:click="openForm"
                    class="medical-btn medical-btn-primary"
                >
                    <span>+</span>

                    {{ __('medical.new_medicine', [], null, 'Add Medicine') }}
                </button>

            @else

                <button
                    type="button"
                    wire:click="resetForm"
                    class="medical-btn medical-btn-secondary"
                >
                    ←

                    {{ __('medical.back_to_list', [], null, 'Back to list') }}
                </button>

            @endif

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- Flash --}}
    {{-- ========================================================= --}}

    @if(session()->has('medical_message'))

        <div class="medical-alert medical-alert-success">

            <span class="medical-alert-icon">
                ✓
            </span>

            <span>
                {{ session('medical_message') }}
            </span>

        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- FORM --}}
    {{-- ========================================================= --}}

    @if($showForm)

        <form
            wire:submit="save"
            class="medical-medicine-form"
        >

            {{-- Basic Information --}}
            <section class="medical-card">

                <div class="medical-card-header">

                    <div>

                        <h2 class="medical-card-title">
                            {{ __('medical.medicine_information', [], null, 'Medicine Information') }}
                        </h2>

                        <p class="medical-card-description">
                            {{ __('medical.medicine_information_description', [], null, 'Enter the basic identification and clinical information for the medicine.') }}
                        </p>

                    </div>

                    <div class="medical-section-icon">
                        +
                    </div>

                </div>


                <div class="medical-form-grid">

                    {{-- Code --}}
                    <div class="medical-field">

                        <label for="code">

                            {{ __('medical.medicine_code', [], null, 'Medicine Code') }}

                            <span class="required">*</span>

                        </label>

                        <input
                            id="code"
                            type="text"
                            wire:model="code"
                            class="medical-input medical-ltr"
                            maxlength="80"
                            placeholder="MED-001"
                        >

                        @error('code')
                            <div class="medical-error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Unit --}}
                    <div class="medical-field">

                        <label for="unit">

                            {{ __('medical.unit', [], null, 'Unit') }}

                            <span class="required">*</span>

                        </label>

                        <select
                            id="unit"
                            wire:model="unit"
                            class="medical-input"
                        >

                            <option value="unit">
                                {{ __('medical.unit_unit', [], null, 'Unit') }}
                            </option>

                            <option value="tablet">
                                {{ __('medical.unit_tablet', [], null, 'Tablet') }}
                            </option>

                            <option value="capsule">
                                {{ __('medical.unit_capsule', [], null, 'Capsule') }}
                            </option>

                            <option value="bottle">
                                {{ __('medical.unit_bottle', [], null, 'Bottle') }}
                            </option>

                            <option value="box">
                                {{ __('medical.unit_box', [], null, 'Box') }}
                            </option>

                            <option value="ampoule">
                                {{ __('medical.unit_ampoule', [], null, 'Ampoule') }}
                            </option>

                            <option value="tube">
                                {{ __('medical.unit_tube', [], null, 'Tube') }}
                            </option>

                        </select>

                        @error('unit')
                            <div class="medical-error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Arabic Name --}}
                    <div class="medical-field">

                        <label for="nameAr">

                            {{ __('medical.name_ar', [], null, 'Arabic Name') }}

                            <span class="required">*</span>

                        </label>

                        <input
                            id="nameAr"
                            type="text"
                            wire:model="nameAr"
                            class="medical-input"
                            maxlength="255"
                            dir="rtl"
                            placeholder="{{ __('medical.medicine_name_ar_placeholder', [], null, 'Medicine name in Arabic') }}"
                        >

                        @error('nameAr')
                            <div class="medical-error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- English Name --}}
                    <div class="medical-field">

                        <label for="nameEn">

                            {{ __('medical.name_en', [], null, 'English Name') }}

                            <span class="required">*</span>

                        </label>

                        <input
                            id="nameEn"
                            type="text"
                            wire:model="nameEn"
                            class="medical-input medical-ltr"
                            maxlength="255"
                            dir="ltr"
                            placeholder="Medicine name"
                        >

                        @error('nameEn')
                            <div class="medical-error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Generic Name --}}
                    <div class="medical-field">

                        <label for="genericName">
                            {{ __('medical.generic_name', [], null, 'Generic Name') }}
                        </label>

                        <input
                            id="genericName"
                            type="text"
                            wire:model="genericName"
                            class="medical-input"
                            maxlength="255"
                        >

                        @error('genericName')
                            <div class="medical-error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Form --}}
                    <div class="medical-field">

                        <label for="form">
                            {{ __('medical.form', [], null, 'Dosage Form') }}
                        </label>

                        <input
                            id="form"
                            type="text"
                            wire:model="form"
                            class="medical-input"
                            maxlength="120"
                            placeholder="{{ __('medical.form_placeholder', [], null, 'Tablet, syrup, injection...') }}"
                        >

                        @error('form')
                            <div class="medical-error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Strength --}}
                    <div class="medical-field">

                        <label for="strength">
                            {{ __('medical.strength', [], null, 'Strength') }}
                        </label>

                        <input
                            id="strength"
                            type="text"
                            wire:model="strength"
                            class="medical-input medical-ltr"
                            maxlength="120"
                            dir="ltr"
                            placeholder="500 mg"
                        >

                        @error('strength')
                            <div class="medical-error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Manufacturer --}}
                    <div class="medical-field">

                        <label for="manufacturer">
                            {{ __('medical.manufacturer', [], null, 'Manufacturer') }}
                        </label>

                        <input
                            id="manufacturer"
                            type="text"
                            wire:model="manufacturer"
                            class="medical-input"
                            maxlength="255"
                        >

                        @error('manufacturer')
                            <div class="medical-error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                </div>

            </section>


            {{-- Stock Settings --}}
            <section class="medical-card">

                <div class="medical-card-header">

                    <div>

                        <h2 class="medical-card-title">
                            {{ __('medical.stock_settings', [], null, 'Stock Settings') }}
                        </h2>

                        <p class="medical-card-description">
                            {{ __('medical.stock_settings_description', [], null, 'Define the stock levels used for inventory monitoring and alerts.') }}
                        </p>

                    </div>

                    <div class="medical-section-icon">
                        #
                    </div>

                </div>


                <div class="medical-form-grid">

                    {{-- Reorder Level --}}
                    <div class="medical-field">

                        <label for="reorderLevel">

                            {{ __('medical.reorder_level', [], null, 'Reorder Level') }}

                            <span class="required">*</span>

                        </label>

                        <input
                            id="reorderLevel"
                            type="number"
                            step="0.001"
                            min="0"
                            wire:model="reorderLevel"
                            class="medical-input medical-ltr"
                            dir="ltr"
                        >

                        <small class="medical-help">
                            {{ __('medical.reorder_level_help', [], null, 'An alert is generated when stock reaches this level.') }}
                        </small>

                        @error('reorderLevel')
                            <div class="medical-error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Minimum --}}
                    <div class="medical-field">

                        <label for="minimumStock">

                            {{ __('medical.minimum_stock', [], null, 'Minimum Stock') }}

                            <span class="required">*</span>

                        </label>

                        <input
                            id="minimumStock"
                            type="number"
                            step="0.001"
                            min="0"
                            wire:model="minimumStock"
                            class="medical-input medical-ltr"
                            dir="ltr"
                        >

                        @error('minimumStock')
                            <div class="medical-error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Maximum --}}
                    <div class="medical-field">

                        <label for="maximumStock">
                            {{ __('medical.maximum_stock', [], null, 'Maximum Stock') }}
                        </label>

                        <input
                            id="maximumStock"
                            type="number"
                            step="0.001"
                            min="0"
                            wire:model="maximumStock"
                            class="medical-input medical-ltr"
                            dir="ltr"
                        >

                        <small class="medical-help">
                            {{ __('medical.maximum_stock_help', [], null, 'Optional upper stock limit.') }}
                        </small>

                        @error('maximumStock')
                            <div class="medical-error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                </div>

            </section>


            {{-- Notes --}}
            <section class="medical-card">

                <div class="medical-card-header">

                    <div>

                        <h2 class="medical-card-title">
                            {{ __('medical.notes', [], null, 'Notes') }}
                        </h2>

                        <p class="medical-card-description">
                            {{ __('medical.medicine_notes_description', [], null, 'Additional information about this medicine.') }}
                        </p>

                    </div>

                </div>


                <div class="medical-form-grid">

                    <div class="medical-field medical-field-full">

                        <textarea
                            id="notes"
                            wire:model="notes"
                            class="medical-input medical-textarea"
                            rows="5"
                            maxlength="4000"
                            placeholder="{{ __('medical.notes_placeholder', [], null, 'Additional notes...') }}"
                        ></textarea>

                        @error('notes')
                            <div class="medical-error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                </div>

            </section>


            {{-- Form Actions --}}
            <div class="medical-form-actions">

                <button
                    type="button"
                    wire:click="resetForm"
                    class="medical-btn medical-btn-secondary"
                >
                    {{ __('medical.cancel', [], null, 'Cancel') }}
                </button>


                <button
                    type="submit"
                    class="medical-btn medical-btn-primary"
                    wire:loading.attr="disabled"
                >

                    <span wire:loading.remove wire:target="save">
                        ✓
                    </span>

                    <span wire:loading wire:target="save">
                        ...
                    </span>

                    {{ __('medical.save_medicine', [], null, 'Save Medicine') }}

                </button>

            </div>

        </form>


    {{-- ========================================================= --}}
    {{-- LIST --}}
    {{-- ========================================================= --}}

    @else

        {{-- Statistics --}}
        <div class="medical-stats-grid">

            <div class="medical-stat-card">

                <div class="medical-stat-icon medical-stat-icon-blue">
                    +
                </div>

                <div class="medical-stat-content">

                    <span class="medical-stat-label">
                        {{ __('medical.active_medicines', [], null, 'Active Medicines') }}
                    </span>

                    <strong class="medical-stat-value">
                        {{ $activeMedicines }}
                    </strong>

                    <span class="medical-stat-description">
                        {{ __('medical.active_medicines_description', [], null, 'Medicines currently available in the catalogue') }}
                    </span>

                </div>

            </div>


            <div class="medical-stat-card">

                <div class="medical-stat-icon medical-stat-icon-warning">
                    !
                </div>

                <div class="medical-stat-content">

                    <span class="medical-stat-label">
                        {{ __('medical.low_stock', [], null, 'Low Stock') }}
                    </span>

                    <strong class="medical-stat-value">
                        {{ $lowStock }}
                    </strong>

                    <span class="medical-stat-description">
                        {{ __('medical.low_stock_description', [], null, 'Medicines at or below reorder level') }}
                    </span>

                </div>

            </div>


            <div class="medical-stat-card">

                <div class="medical-stat-icon medical-stat-icon-danger">
                    0
                </div>

                <div class="medical-stat-content">

                    <span class="medical-stat-label">
                        {{ __('medical.out_of_stock', [], null, 'Out of Stock') }}
                    </span>

                    <strong class="medical-stat-value">
                        {{ $outOfStock }}
                    </strong>

                    <span class="medical-stat-description">
                        {{ __('medical.out_of_stock_description', [], null, 'Medicines with no available stock') }}
                    </span>

                </div>

            </div>


            <div class="medical-stat-card">

                <div class="medical-stat-icon medical-stat-icon-gray">
                    •
                </div>

                <div class="medical-stat-content">

                    <span class="medical-stat-label">
                        {{ __('medical.inactive_medicines', [], null, 'Inactive Medicines') }}
                    </span>

                    <strong class="medical-stat-value">
                        {{ $inactiveMedicines }}
                    </strong>

                    <span class="medical-stat-description">
                        {{ __('medical.inactive_medicines_description', [], null, 'Inactive medicine records') }}
                    </span>

                </div>

            </div>

        </div>


        {{-- Search --}}
        <section class="medical-card medical-filter-card">

            <div class="medical-filter-inner">

                <div class="medical-search">

                    <span class="medical-search-icon">
                        ⌕
                    </span>

                    <input
                        type="search"
                        wire:model.live.debounce.350ms="q"
                        class="medical-search-input"
                        placeholder="{{ __('medical.search_medicines', [], null, 'Search by medicine name, code or generic name...') }}"
                    >

                    @if($q !== '')

                        <button
                            type="button"
                            wire:click="$set('q', '')"
                            class="medical-search-clear"
                        >
                            ×
                        </button>

                    @endif

                </div>


                <div class="medical-filter-result">

                    {{ $medicines->count() }}

                    {{ __('medical.medicine_records', [], null, 'medicines') }}

                </div>

            </div>

        </section>


        {{-- Table --}}
        <section class="medical-card medical-table-card">

            <div class="medical-card-header">

                <div>

                    <h2 class="medical-card-title">
                        {{ __('medical.medicine_list', [], null, 'Medicine List') }}
                    </h2>

                    <p class="medical-card-description">
                        {{ __('medical.medicine_list_description', [], null, 'Registered medicines and their current available stock.') }}
                    </p>

                </div>

                <div class="medical-card-badge">
                    {{ $medicines->count() }}
                </div>

            </div>


            @if($medicines->count())

                <div class="medical-table-wrapper">

                    <table class="medical-table">

                        <thead>

                            <tr>

                                <th>
                                    {{ __('medical.medicine', [], null, 'Medicine') }}
                                </th>

                                <th>
                                    {{ __('medical.form', [], null, 'Form') }}
                                </th>

                                <th>
                                    {{ __('medical.manufacturer', [], null, 'Manufacturer') }}
                                </th>

                                <th>
                                    {{ __('medical.current_stock', [], null, 'Current Stock') }}
                                </th>

                                <th>
                                    {{ __('medical.reorder_level', [], null, 'Reorder Level') }}
                                </th>

                                <th>
                                    {{ __('medical.status', [], null, 'Status') }}
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @foreach($medicines as $medicine)

                                @php

                                    $medicineName = $isArabic
                                        ? $medicine->name_ar
                                        : ($medicine->name_en ?: $medicine->name_ar);

                                    $stock = (float) $medicine->stock;

                                    $reorder = (float) $medicine->reorder_level;

                                    $isOut = $stock <= 0;

                                    $isLow = !$isOut && $stock <= $reorder;

                                @endphp

                                <tr>

                                    {{-- Medicine --}}
                                    <td>

                                        <div class="medicine-cell">

                                            <div class="medicine-mini-icon">
                                                +
                                            </div>

                                            <div>

                                                <div class="medical-table-primary">
                                                    {{ $medicineName }}
                                                </div>

                                                <div class="medical-table-secondary">

                                                    <span class="medical-code">
                                                        {{ $medicine->code }}
                                                    </span>

                                                    @if($medicine->generic_name)

                                                        <span class="medicine-generic">
                                                            {{ $medicine->generic_name }}
                                                        </span>

                                                    @endif

                                                </div>

                                            </div>

                                        </div>

                                    </td>


                                    {{-- Form --}}
                                    <td>

                                        <div class="medicine-form-cell">

                                            @if($medicine->form)
                                                {{ $medicine->form }}
                                            @else
                                                —
                                            @endif

                                            @if($medicine->strength)

                                                <span class="medicine-strength">
                                                    {{ $medicine->strength }}
                                                </span>

                                            @endif

                                        </div>

                                    </td>


                                    {{-- Manufacturer --}}
                                    <td>

                                        {{ $medicine->manufacturer ?: '—' }}

                                    </td>


                                    {{-- Stock --}}
                                    <td>

                                        <div class="stock-cell">

                                            <strong
                                                class="
                                                    stock-number
                                                    {{ $isOut ? 'stock-danger' : '' }}
                                                    {{ $isLow ? 'stock-warning' : '' }}
                                                "
                                            >
                                                {{ rtrim(rtrim(number_format($stock, 3, '.', ''), '0'), '.') }}
                                            </strong>

                                            <span class="stock-unit">
                                                {{ __('medical.unit_' . $medicine->unit, [], null, $medicine->unit) }}
                                            </span>

                                        </div>

                                    </td>


                                    {{-- Reorder --}}
                                    <td>

                                        <span class="reorder-value">
                                            {{ rtrim(rtrim(number_format($reorder, 3, '.', ''), '0'), '.') }}
                                        </span>

                                    </td>


                                    {{-- Status --}}
                                    <td>

                                        @if($isOut)

                                            <span class="medical-status medical-status-red">

                                                {{ __('medical.out_of_stock', [], null, 'Out of Stock') }}

                                            </span>

                                        @elseif($isLow)

                                            <span class="medical-status medical-status-orange">

                                                {{ __('medical.low_stock', [], null, 'Low Stock') }}

                                            </span>

                                        @elseif(!$medicine->is_active)

                                            <span class="medical-status medical-status-gray">

                                                {{ __('medical.inactive', [], null, 'Inactive') }}

                                            </span>

                                        @else

                                            <span class="medical-status medical-status-green">

                                                {{ __('medical.available', [], null, 'Available') }}

                                            </span>

                                        @endif

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>


            @else

                <div class="medical-empty">

                    <div class="medical-empty-icon">
                        +
                    </div>

                    <h3>
                        {{ __('medical.no_medicines', [], null, 'No medicines found') }}
                    </h3>

                    <p>

                        @if($q !== '')

                            {{ __('medical.no_medicines_search_description', [], null, 'No medicine records match your search.') }}

                        @else

                            {{ __('medical.no_medicines_description', [], null, 'No medicines have been registered yet.') }}

                        @endif

                    </p>

                    @if($q === '')

                        <button
                            type="button"
                            wire:click="openForm"
                            class="medical-btn medical-btn-primary medical-empty-button"
                        >
                            +

                            {{ __('medical.new_medicine', [], null, 'Add Medicine') }}

                        </button>

                    @endif

                </div>

            @endif

        </section>

    @endif


    <style>

        .medical-page {
            direction: rtl;
            padding-bottom: 45px;
        }

        .medical-page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            margin: 28px 0;
        }

        .medical-eyebrow {
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .medical-page-title {
            margin: 0;
            color: #0f172a;
            font-size: 30px;
            font-weight: 850;
            letter-spacing: -.5px;
        }

        .medical-page-description {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 14px;
            line-height: 1.8;
        }

        .medical-page-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .medical-card {
            margin-bottom: 20px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            box-shadow: 0 5px 18px rgba(15, 23, 42, .05);
            overflow: hidden;
        }

        .medical-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .medical-stat-card {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 17px;
            padding: 20px;
            box-shadow: 0 5px 18px rgba(15, 23, 42, .04);
        }

        .medical-stat-icon {
            width: 44px;
            height: 44px;
            min-width: 44px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 900;
        }

        .medical-stat-icon-blue {
            background: #eff6ff;
            color: #2563eb;
        }

        .medical-stat-icon-warning {
            background: #fff7ed;
            color: #ea580c;
        }

        .medical-stat-icon-danger {
            background: #fef2f2;
            color: #dc2626;
        }

        .medical-stat-icon-gray {
            background: #f1f5f9;
            color: #64748b;
        }

        .medical-stat-content {
            min-width: 0;
        }

        .medical-stat-label {
            display: block;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .medical-stat-value {
            display: block;
            color: #0f172a;
            font-size: 27px;
            line-height: 1.1;
            font-weight: 900;
        }

        .medical-stat-description {
            display: block;
            margin-top: 6px;
            color: #94a3b8;
            font-size: 11px;
            line-height: 1.5;
        }

        .medical-filter-card {
            padding: 0;
        }

        .medical-filter-inner {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 17px 20px;
        }

        .medical-search {
            position: relative;
            flex: 1;
            max-width: 650px;
        }

        .medical-search-icon {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 19px;
            pointer-events: none;
        }

        .medical-search-input {
            width: 100%;
            height: 46px;
            padding: 0 43px 0 42px;
            border: 1px solid #cbd5e1;
            border-radius: 11px;
            background: #fff;
            color: #0f172a;
            font-size: 13px;
            outline: none;
            box-sizing: border-box;
            transition: .2s ease;
        }

        .medical-search-input:focus {
            border-color: #16a34a;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, .10);
        }

        .medical-search-clear {
            position: absolute;
            left: 9px;
            top: 50%;
            transform: translateY(-50%);
            width: 28px;
            height: 28px;
            border: 0;
            border-radius: 8px;
            background: #f1f5f9;
            color: #64748b;
            cursor: pointer;
            font-size: 17px;
            line-height: 1;
        }

        .medical-filter-result {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .medical-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 22px 24px;
            border-bottom: 1px solid #eef2f7;
        }

        .medical-card-title {
            margin: 0;
            color: #0f172a;
            font-size: 18px;
            font-weight: 800;
        }

        .medical-card-description {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 13px;
            line-height: 1.7;
        }

        .medical-card-badge {
            min-width: 36px;
            height: 36px;
            padding: 0 10px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0fdf4;
            color: #15803d;
            font-size: 13px;
            font-weight: 900;
        }

        .medical-section-icon {
            width: 44px;
            height: 44px;
            min-width: 44px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0fdf4;
            color: #15803d;
            font-size: 17px;
            font-weight: 900;
        }

        .medical-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            padding: 24px;
        }

        .medical-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .medical-field-full {
            grid-column: 1 / -1;
        }

        .medical-field label {
            color: #334155;
            font-size: 13px;
            font-weight: 800;
        }

        .required {
            color: #dc2626;
            margin-right: 3px;
        }

        .medical-input {
            width: 100%;
            min-height: 45px;
            padding: 10px 13px;
            border: 1px solid #cbd5e1;
            border-radius: 11px;
            background: #fff;
            color: #0f172a;
            font-size: 14px;
            outline: none;
            box-sizing: border-box;
            transition: .2s ease;
        }

        .medical-input:focus {
            border-color: #16a34a;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, .10);
        }

        .medical-textarea {
            min-height: 120px;
            resize: vertical;
            line-height: 1.8;
        }

        .medical-ltr {
            direction: ltr;
            text-align: left;
        }

        .medical-help {
            color: #94a3b8;
            font-size: 11px;
            line-height: 1.6;
        }

        .medical-error {
            color: #dc2626;
            font-size: 12px;
            font-weight: 600;
        }

        .medical-form-actions {
            display: flex;
            justify-content: flex-start;
            gap: 12px;
            margin-top: 4px;
        }

        .medical-btn {
            min-height: 44px;
            padding: 0 19px;
            border-radius: 11px;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
            transition: .2s ease;
        }

        .medical-btn-primary {
            background: #15803d;
            color: #fff;
        }

        .medical-btn-primary:hover {
            background: #166534;
        }

        .medical-btn-primary:disabled {
            opacity: .65;
            cursor: wait;
        }

        .medical-btn-secondary {
            background: #fff;
            color: #334155;
            border-color: #cbd5e1;
        }

        .medical-btn-secondary:hover {
            background: #f8fafc;
        }

        .medical-table-card {
            margin-bottom: 0;
        }

        .medical-table-wrapper {
            overflow-x: auto;
        }

        .medical-table {
            width: 100%;
            border-collapse: collapse;
        }

        .medical-table th {
            padding: 14px 20px;
            background: #f8fafc;
            color: #64748b;
            font-size: 12px;
            font-weight: 800;
            text-align: right;
            white-space: nowrap;
        }

        .medical-table td {
            padding: 16px 20px;
            border-top: 1px solid #eef2f7;
            color: #334155;
            font-size: 13px;
            vertical-align: middle;
        }

        .medical-table tbody tr:hover {
            background: #fafafa;
        }

        .medicine-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .medicine-mini-icon {
            width: 40px;
            height: 40px;
            min-width: 40px;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0fdf4;
            color: #15803d;
            font-size: 17px;
            font-weight: 900;
        }

        .medical-table-primary {
            color: #0f172a;
            font-weight: 800;
        }

        .medical-table-secondary {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 4px;
            color: #94a3b8;
            font-size: 11px;
        }

        .medical-code {
            display: inline-flex;
            padding: 4px 7px;
            border-radius: 7px;
            background: #f8fafc;
            color: #64748b;
            font-family: monospace;
            font-size: 11px;
            direction: ltr;
        }

        .medicine-generic {
            color: #94a3b8;
        }

        .medicine-form-cell {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .medicine-strength {
            color: #94a3b8;
            font-size: 11px;
            direction: ltr;
        }

        .stock-cell {
            display: flex;
            align-items: baseline;
            gap: 6px;
            direction: ltr;
            justify-content: flex-end;
        }

        .stock-number {
            color: #15803d;
            font-size: 15px;
            font-weight: 900;
        }

        .stock-warning {
            color: #ea580c;
        }

        .stock-danger {
            color: #dc2626;
        }

        .stock-unit {
            color: #94a3b8;
            font-size: 10px;
        }

        .reorder-value {
            color: #64748b;
            font-family: monospace;
            direction: ltr;
        }

        .medical-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            border-radius: 9px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .medical-status-green {
            background: #f0fdf4;
            color: #15803d;
        }

        .medical-status-orange {
            background: #fff7ed;
            color: #c2410c;
        }

        .medical-status-red {
            background: #fef2f2;
            color: #dc2626;
        }

        .medical-status-gray {
            background: #f1f5f9;
            color: #64748b;
        }

        .medical-alert {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding: 14px 16px;
            border-radius: 13px;
            font-size: 14px;
            font-weight: 700;
        }

        .medical-alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .medical-alert-icon {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            background: #dcfce7;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .medical-empty {
            padding: 60px 25px;
            text-align: center;
        }

        .medical-empty-icon {
            width: 54px;
            height: 54px;
            margin: 0 auto 14px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0fdf4;
            color: #15803d;
            font-size: 19px;
            font-weight: 900;
        }

        .medical-empty h3 {
            margin: 0;
            color: #0f172a;
            font-size: 16px;
            font-weight: 800;
        }

        .medical-empty p {
            margin: 7px 0 18px;
            color: #94a3b8;
            font-size: 13px;
        }

        .medical-empty-button {
            display: inline-flex;
        }

        @media (max-width: 1100px) {

            .medical-stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

        }

        @media (max-width: 850px) {

            .medical-page-header {
                flex-direction: column;
            }

            .medical-page-header-actions,
            .medical-page-header-actions .medical-btn {
                width: 100%;
            }

            .medical-form-grid {
                grid-template-columns: 1fr;
            }

            .medical-field-full {
                grid-column: auto;
            }

            .medical-filter-inner {
                flex-direction: column;
                align-items: stretch;
            }

            .medical-search {
                max-width: none;
            }

            .medical-filter-result {
                text-align: right;
            }

        }

        @media (max-width: 650px) {

            .medical-stats-grid {
                grid-template-columns: 1fr;
            }

            .medical-card-header {
                padding: 18px;
            }

            .medical-form-grid {
                padding: 18px;
            }

            .medical-form-actions {
                flex-direction: column-reverse;
            }

            .medical-form-actions .medical-btn {
                width: 100%;
            }

        }

        @media (max-width: 500px) {

            .medical-page-title {
                font-size: 24px;
            }

            .medical-filter-inner {
                padding: 14px;
            }

            .medical-table th,
            .medical-table td {
                padding: 13px 14px;
            }

        }

    </style>

</div>

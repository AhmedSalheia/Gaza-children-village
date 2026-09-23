@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';
@endphp

<div class="medical-page">

    {{-- Medical Navigation --}}
    @include('livewire.admin.medical._nav')


    {{-- Header --}}
    <div class="medical-page-header">

        <div>

            <div class="medical-eyebrow">
                {{ __('medical.medicines', [], null, 'Medicines') }}
            </div>

            <h1 class="medical-page-title">
                {{ __('medical.new_receipt', [], null, 'New Medicine Receipt') }}
            </h1>

            <p class="medical-page-description">
                {{ __('medical.new_receipt_description', [], null, 'Register a new medicine receipt and update the medical stock.') }}
            </p>

        </div>


        <div class="medical-page-header-actions">

            <a
                href="{{ route('admin.medical.medicines.index') }}"
                wire:navigate
                class="medical-btn medical-btn-secondary"
            >
                <span>←</span>

                {{ __('medical.back', [], null, 'Back') }}
            </a>

        </div>

    </div>


    {{-- Success Message --}}
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


    {{-- Main Form --}}
    <form
        wire:submit="save"
        class="medical-receipt-form"
    >

        {{-- Medicine & Location --}}
        <section class="medical-card">

            <div class="medical-card-header">

                <div>

                    <h2 class="medical-card-title">
                        {{ __('medical.receipt_information', [], null, 'Receipt Information') }}
                    </h2>

                    <p class="medical-card-description">
                        {{ __('medical.receipt_information_description', [], null, 'Select the medicine and medical point receiving the stock.') }}
                    </p>

                </div>

                <div class="medical-section-icon">
                    +
                </div>

            </div>


            <div class="medical-form-grid">

                {{-- Medicine --}}
                <div class="medical-field medical-field-wide">

                    <label for="medicineId">
                        {{ __('medical.medicine', [], null, 'Medicine') }}

                        <span class="required">*</span>
                    </label>

                    <select
                        id="medicineId"
                        wire:model="medicineId"
                        class="medical-input"
                    >

                        <option value="">
                            {{ __('medical.select_medicine', [], null, 'Select medicine') }}
                        </option>

                        @foreach($medicines as $medicine)

                            <option value="{{ $medicine->id }}">

                                {{ $isArabic
                                    ? $medicine->name_ar
                                    : ($medicine->name_en ?: $medicine->name_ar)
                                }}

                                @if($medicine->generic_name)
                                    — {{ $medicine->generic_name }}
                                @endif

                            </option>

                        @endforeach

                    </select>

                    @error('medicineId')
                        <div class="medical-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                {{-- Medical Point --}}
                <div class="medical-field">

                    <label for="institutionId">
                        {{ __('medical.medical_point', [], null, 'Medical Point') }}

                        <span class="required">*</span>
                    </label>

                    <select
                        id="institutionId"
                        wire:model="institutionId"
                        class="medical-input"
                    >

                        <option value="">
                            {{ __('medical.select_medical_point', [], null, 'Select medical point') }}
                        </option>

                        @foreach($clinics as $clinic)

                            <option value="{{ $clinic->id }}">

                                {{ $isArabic
                                    ? $clinic->name_ar
                                    : ($clinic->name_en ?: $clinic->name_ar)
                                }}

                            </option>

                        @endforeach

                    </select>

                    @error('institutionId')
                        <div class="medical-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

            </div>

        </section>


        {{-- Batch Information --}}
        <section class="medical-card">

            <div class="medical-card-header">

                <div>

                    <h2 class="medical-card-title">
                        {{ __('medical.batch_information', [], null, 'Batch Information') }}
                    </h2>

                    <p class="medical-card-description">
                        {{ __('medical.batch_information_description', [], null, 'Enter the batch, expiry date and quantity received.') }}
                    </p>

                </div>

                <div class="medical-section-icon">
                    #
                </div>

            </div>


            <div class="medical-form-grid">

                {{-- Batch Number --}}
                <div class="medical-field">

                    <label for="batchNumber">

                        {{ __('medical.batch_number', [], null, 'Batch Number') }}

                        <span class="required">*</span>

                    </label>

                    <input
                        id="batchNumber"
                        type="text"
                        wire:model="batchNumber"
                        class="medical-input"
                        maxlength="120"
                        placeholder="{{ __('medical.batch_number_placeholder', [], null, 'Enter batch number') }}"
                    >

                    @error('batchNumber')
                        <div class="medical-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                {{-- Expiry --}}
                <div class="medical-field">

                    <label for="expiryDate">

                        {{ __('medical.expiry_date', [], null, 'Expiry Date') }}

                        <span class="required">*</span>

                    </label>

                    <input
                        id="expiryDate"
                        type="date"
                        wire:model="expiryDate"
                        class="medical-input"
                    >

                    @error('expiryDate')
                        <div class="medical-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                {{-- Quantity --}}
                <div class="medical-field">

                    <label for="quantity">

                        {{ __('medical.quantity', [], null, 'Quantity') }}

                        <span class="required">*</span>

                    </label>

                    <input
                        id="quantity"
                        type="number"
                        step="0.001"
                        min="0.001"
                        wire:model="quantity"
                        class="medical-input"
                        placeholder="0"
                    >

                    @error('quantity')
                        <div class="medical-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                {{-- Unit Cost --}}
                <div class="medical-field">

                    <label for="unitCost">
                        {{ __('medical.unit_cost', [], null, 'Unit Cost') }}
                    </label>

                    <input
                        id="unitCost"
                        type="number"
                        step="0.01"
                        min="0"
                        wire:model="unitCost"
                        class="medical-input"
                        placeholder="0.00"
                    >

                    @error('unitCost')
                        <div class="medical-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

            </div>

        </section>


        {{-- Supplier & Reference --}}
        <section class="medical-card">

            <div class="medical-card-header">

                <div>

                    <h2 class="medical-card-title">
                        {{ __('medical.receipt_source', [], null, 'Receipt Source') }}
                    </h2>

                    <p class="medical-card-description">
                        {{ __('medical.receipt_source_description', [], null, 'Optional information about the supplier and receipt reference.') }}
                    </p>

                </div>

                <div class="medical-section-icon">
                    ✓
                </div>

            </div>


            <div class="medical-form-grid">

                {{-- Supplier --}}
                <div class="medical-field">

                    <label for="supplier">
                        {{ __('medical.supplier', [], null, 'Supplier') }}
                    </label>

                    <input
                        id="supplier"
                        type="text"
                        wire:model="supplier"
                        class="medical-input"
                        maxlength="255"
                        placeholder="{{ __('medical.supplier_placeholder', [], null, 'Supplier name') }}"
                    >

                    @error('supplier')
                        <div class="medical-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                {{-- Reference --}}
                <div class="medical-field">

                    <label for="referenceNumber">
                        {{ __('medical.reference_number', [], null, 'Reference Number') }}
                    </label>

                    <input
                        id="referenceNumber"
                        type="text"
                        wire:model="referenceNumber"
                        class="medical-input"
                        maxlength="120"
                        placeholder="{{ __('medical.reference_placeholder', [], null, 'Invoice or document number') }}"
                    >

                    @error('referenceNumber')
                        <div class="medical-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                {{-- Received At --}}
                <div class="medical-field">

                    <label for="receivedAt">
                        {{ __('medical.received_at', [], null, 'Received At') }}
                    </label>

                    <input
                        id="receivedAt"
                        type="datetime-local"
                        wire:model="receivedAt"
                        class="medical-input"
                    >

                    @error('receivedAt')
                        <div class="medical-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                {{-- Reason --}}
                <div class="medical-field medical-field-full">

                    <label for="reason">
                        {{ __('medical.reason', [], null, 'Reason / Notes') }}
                    </label>

                    <textarea
                        id="reason"
                        wire:model="reason"
                        class="medical-input medical-textarea"
                        rows="4"
                        maxlength="2000"
                        placeholder="{{ __('medical.reason_placeholder', [], null, 'Add any relevant notes about this receipt...') }}"
                    ></textarea>

                    @error('reason')
                        <div class="medical-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

            </div>

        </section>


        {{-- Actions --}}
        <div class="medical-form-actions">

            <a
                href="{{ route('admin.medical.medicines.index') }}"
                wire:navigate
                class="medical-btn medical-btn-secondary"
            >
                {{ __('medical.cancel', [], null, 'Cancel') }}
            </a>


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

                {{ __('medical.save_receipt', [], null, 'Save Receipt') }}

            </button>

        </div>

    </form>


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
            letter-spacing: -0.5px;
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
            box-shadow: 0 5px 18px rgba(15, 23, 42, 0.05);
            overflow: hidden;
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

        .medical-section-icon {
            width: 42px;
            height: 42px;
            min-width: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0fdf4;
            color: #15803d;
            font-size: 18px;
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

        .medical-field-wide {
            grid-column: span 2;
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
            transition: border-color .2s ease, box-shadow .2s ease;
            box-sizing: border-box;
        }

        select.medical-input {
            cursor: pointer;
        }

        .medical-input:focus {
            border-color: #16a34a;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.10);
        }

        .medical-textarea {
            resize: vertical;
            min-height: 110px;
            line-height: 1.8;
        }

        .medical-error {
            color: #dc2626;
            font-size: 12px;
            font-weight: 600;
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

        .medical-form-actions {
            display: flex;
            justify-content: flex-start;
            gap: 12px;
            margin-top: 5px;
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

        @media (max-width: 850px) {

            .medical-page-header {
                flex-direction: column;
            }

            .medical-form-grid {
                grid-template-columns: 1fr;
            }

            .medical-field-wide,
            .medical-field-full {
                grid-column: auto;
            }

            .medical-page-header-actions {
                width: 100%;
            }

            .medical-page-header-actions .medical-btn {
                width: 100%;
            }
        }

        @media (max-width: 600px) {

            .medical-page-title {
                font-size: 24px;
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
    </style>

</div>

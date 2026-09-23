@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';
@endphp

<div class="medical-page">

    {{-- ========================================================= --}}
    {{-- Medical Navigation --}}
    {{-- ========================================================= --}}

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
                {{ __('medical.medicine_issue', [], null, 'Medicine Issue') }}
            </h1>

            <p class="medical-page-description">
                {{ __('medical.medicine_issue_description', [], null, 'Record medicine issues, disposals and stock adjustments from the medical inventory.') }}
            </p>

        </div>


        <div class="medical-page-header-actions">

            <a
                href="{{ route('admin.medical.movements.index') }}"
                wire:navigate
                class="medical-btn medical-btn-secondary"
            >
                ←

                {{ __('medical.movement_history', [], null, 'Movement History') }}

            </a>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- Flash Message --}}
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
    {{-- Main Form --}}
    {{-- ========================================================= --}}

    <form
        wire:submit="save"
        class="medical-issue-form"
    >

        {{-- ===================================================== --}}
        {{-- Transaction Information --}}
        {{-- ===================================================== --}}

        <section class="medical-card">

            <div class="medical-card-header">

                <div>

                    <h2 class="medical-card-title">
                        {{ __('medical.movement_information', [], null, 'Movement Information') }}
                    </h2>

                    <p class="medical-card-description">
                        {{ __('medical.movement_information_description', [], null, 'Select the medicine, medical point and type of stock movement.') }}
                    </p>

                </div>

                <div class="medical-section-icon">
                    ↻
                </div>

            </div>


            <div class="medical-form-grid">

                {{-- Medicine --}}
                <div class="medical-field medical-field-full">

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

                                — {{ $medicine->code }}

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


                {{-- Movement Type --}}
                <div class="medical-field">

                    <label for="type">

                        {{ __('medical.movement_type', [], null, 'Movement Type') }}

                        <span class="required">*</span>

                    </label>

                    <select
                        id="type"
                        wire:model="type"
                        class="medical-input"
                    >

                        <option value="issue">
                            {{ __('medical.issue', [], null, 'Issue') }}
                        </option>

                        <option value="disposal">
                            {{ __('medical.disposal', [], null, 'Disposal') }}
                        </option>

                        <option value="adjustment">
                            {{ __('medical.adjustment', [], null, 'Adjustment') }}
                        </option>

                    </select>

                    @error('type')

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
                        class="medical-input medical-ltr"
                        dir="ltr"
                        placeholder="0"
                    >

                    @error('quantity')

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
                        class="medical-input medical-ltr"
                        maxlength="120"
                        dir="ltr"
                        placeholder="REF-0001"
                    >

                    @error('referenceNumber')

                        <div class="medical-error">
                            {{ $message }}
                        </div>

                    @enderror

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- Patient / Visit --}}
        {{-- ===================================================== --}}

        <section class="medical-card">

            <div class="medical-card-header">

                <div>

                    <h2 class="medical-card-title">
                        {{ __('medical.patient_visit_information', [], null, 'Patient & Visit Information') }}
                    </h2>

                    <p class="medical-card-description">
                        {{ __('medical.patient_visit_information_description', [], null, 'Link the movement to a patient or medical visit when applicable.') }}
                    </p>

                </div>

                <div class="medical-section-icon">
                    +
                </div>

            </div>


            <div class="medical-form-grid">

                {{-- Patient --}}
                <div class="medical-field">

                    <label for="patientId">

                        {{ __('medical.patient', [], null, 'Patient') }}

                    </label>

                    <select
                        id="patientId"
                        wire:model="patientId"
                        class="medical-input"
                    >

                        <option value="">
                            {{ __('medical.no_patient', [], null, 'No specific patient') }}
                        </option>

                        @foreach($patients as $patient)

                            <option value="{{ $patient->id }}">

                                {{ $isArabic
                                    ? $patient->full_name_ar
                                    : ($patient->full_name_en ?: $patient->full_name_ar)
                                }}

                                — {{ $patient->patient_code }}

                            </option>

                        @endforeach

                    </select>

                    @error('patientId')

                        <div class="medical-error">
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                {{-- Visit --}}
                <div class="medical-field">

                    <label for="visitId">

                        {{ __('medical.visit', [], null, 'Medical Visit') }}

                    </label>

                    <select
                        id="visitId"
                        wire:model="visitId"
                        class="medical-input"
                    >

                        <option value="">
                            {{ __('medical.no_visit', [], null, 'No specific visit') }}
                        </option>

                        @foreach($visits as $visit)

                            <option value="{{ $visit->id }}">

                                {{ \Carbon\Carbon::parse($visit->visited_at)->format('Y-m-d H:i') }}

                                —

                                {{ $isArabic
                                    ? $visit->full_name_ar
                                    : ($visit->full_name_en ?: $visit->full_name_ar)
                                }}

                                —

                                {{ $visit->patient_code }}

                            </option>

                        @endforeach

                    </select>

                    @error('visitId')

                        <div class="medical-error">
                            {{ $message }}
                        </div>

                    @enderror

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- Reason --}}
        {{-- ===================================================== --}}

        <section class="medical-card">

            <div class="medical-card-header">

                <div>

                    <h2 class="medical-card-title">
                        {{ __('medical.reason_and_notes', [], null, 'Reason & Notes') }}
                    </h2>

                    <p class="medical-card-description">
                        {{ __('medical.reason_and_notes_description', [], null, 'Provide a reason or additional information for this stock movement.') }}
                    </p>

                </div>

            </div>


            <div class="medical-form-grid">

                <div class="medical-field medical-field-full">

                    <label for="reason">
                        {{ __('medical.reason', [], null, 'Reason') }}
                    </label>

                    <textarea
                        id="reason"
                        wire:model="reason"
                        class="medical-input medical-textarea"
                        rows="5"
                        maxlength="4000"
                        placeholder="{{ __('medical.reason_placeholder', [], null, 'Enter the reason for this movement...') }}"
                    ></textarea>

                    @error('reason')

                        <div class="medical-error">
                            {{ $message }}
                        </div>

                    @enderror

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- Warning --}}
        {{-- ===================================================== --}}

        <div class="medical-warning">

            <div class="medical-warning-icon">
                !
            </div>

            <div>

                <strong>
                    {{ __('medical.stock_movement_warning_title', [], null, 'Important') }}
                </strong>

                <p>
                    {{ __('medical.stock_movement_warning', [], null, 'This operation changes the available medicine stock and will be recorded in the movement history.') }}
                </p>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- Actions --}}
        {{-- ===================================================== --}}

        <div class="medical-form-actions">

            <button
                type="button"
                wire:click="resetForm"
                class="medical-btn medical-btn-secondary"
            >
                {{ __('medical.clear', [], null, 'Clear') }}
            </button>


            <button
                type="submit"
                class="medical-btn medical-btn-primary"
                wire:loading.attr="disabled"
            >

                <span
                    wire:loading.remove
                    wire:target="save"
                >
                    ✓
                </span>

                <span
                    wire:loading
                    wire:target="save"
                >
                    ...
                </span>

                {{ __('medical.save_movement', [], null, 'Save Movement') }}

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

        .medical-ltr {
            direction: ltr;
            text-align: left;
        }

        .medical-textarea {
            min-height: 125px;
            resize: vertical;
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

        .medical-warning {
            display: flex;
            align-items: flex-start;
            gap: 13px;
            margin-bottom: 20px;
            padding: 17px 18px;
            border: 1px solid #fed7aa;
            border-radius: 14px;
            background: #fff7ed;
            color: #9a3412;
        }

        .medical-warning-icon {
            width: 34px;
            height: 34px;
            min-width: 34px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffedd5;
            color: #ea580c;
            font-weight: 900;
        }

        .medical-warning strong {
            display: block;
            margin-bottom: 3px;
            font-size: 13px;
        }

        .medical-warning p {
            margin: 0;
            font-size: 12px;
            line-height: 1.7;
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

        @media (max-width: 800px) {

            .medical-page-header {
                flex-direction: column;
            }

            .medical-page-header-actions {
                width: 100%;
            }

            .medical-page-header-actions .medical-btn {
                width: 100%;
            }

            .medical-form-grid {
                grid-template-columns: 1fr;
            }

            .medical-field-full {
                grid-column: auto;
            }

        }

        @media (max-width: 600px) {

            .medical-page-title {
                font-size: 25px;
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

            .medical-warning {
                padding: 14px;
            }

        }

    </style>

</div>

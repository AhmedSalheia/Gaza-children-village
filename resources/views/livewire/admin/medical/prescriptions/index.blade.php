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
                {{ __('medical.medicines', [], null, 'Medical') }}
            </div>

            <h1 class="medical-page-title">
                {{ __('medical.prescriptions', [], null, 'Prescriptions') }}
            </h1>

            <p class="medical-page-description">
                {{ __('medical.prescriptions_description', [], null, 'Manage medical prescriptions and prescribed medicines.') }}
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

                    {{ __('medical.new_prescription', [], null, 'New Prescription') }}
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


    {{-- Flash --}}
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
    {{-- Create Prescription --}}
    {{-- ========================================================= --}}

    @if($showForm)

        <form
            wire:submit="save"
            class="medical-prescription-form"
        >

            {{-- Patient --}}
            <section class="medical-card">

                <div class="medical-card-header">

                    <div>

                        <h2 class="medical-card-title">
                            {{ __('medical.prescription_information', [], null, 'Prescription Information') }}
                        </h2>

                        <p class="medical-card-description">
                            {{ __('medical.prescription_information_description', [], null, 'Select the patient and prescribing doctor.') }}
                        </p>

                    </div>

                    <div class="medical-section-icon">
                        Rx
                    </div>

                </div>


                <div class="medical-form-grid">

                    {{-- Patient --}}
                    <div class="medical-field">

                        <label for="patientId">

                            {{ __('medical.patient', [], null, 'Patient') }}

                            <span class="required">*</span>

                        </label>

                        <select
                            id="patientId"
                            wire:model="patientId"
                            class="medical-input"
                        >

                            <option value="">
                                {{ __('medical.select_patient', [], null, 'Select patient') }}
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


                    {{-- Doctor --}}
                    <div class="medical-field">

                        <label for="doctorId">
                            {{ __('medical.doctor', [], null, 'Doctor') }}
                        </label>

                        <select
                            id="doctorId"
                            wire:model="doctorId"
                            class="medical-input"
                        >

                            <option value="">
                                {{ __('medical.select_doctor', [], null, 'Select doctor') }}
                            </option>

                            @foreach($doctors as $doctor)

                                <option value="{{ $doctor->id }}">

                                    {{ $isArabic
                                        ? $doctor->full_name_ar
                                        : ($doctor->full_name_en ?: $doctor->full_name_ar)
                                    }}

                                </option>

                            @endforeach

                        </select>

                        @error('doctorId')
                            <div class="medical-error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                </div>

            </section>


            {{-- Medicine --}}
            <section class="medical-card">

                <div class="medical-card-header">

                    <div>

                        <h2 class="medical-card-title">
                            {{ __('medical.prescribed_medicine', [], null, 'Prescribed Medicine') }}
                        </h2>

                        <p class="medical-card-description">
                            {{ __('medical.prescribed_medicine_description', [], null, 'Specify the medicine, dose, frequency and duration.') }}
                        </p>

                    </div>

                    <div class="medical-section-icon">
                        +
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


                    {{-- Dose --}}
                    <div class="medical-field">

                        <label for="dose">
                            {{ __('medical.dose', [], null, 'Dose') }}
                        </label>

                        <input
                            id="dose"
                            type="text"
                            wire:model="dose"
                            class="medical-input"
                            maxlength="120"
                            placeholder="{{ __('medical.dose_placeholder', [], null, 'e.g. 500 mg') }}"
                        >

                        @error('dose')
                            <div class="medical-error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Frequency --}}
                    <div class="medical-field">

                        <label for="frequency">
                            {{ __('medical.frequency', [], null, 'Frequency') }}
                        </label>

                        <input
                            id="frequency"
                            type="text"
                            wire:model="frequency"
                            class="medical-input"
                            maxlength="120"
                            placeholder="{{ __('medical.frequency_placeholder', [], null, 'e.g. 3 times daily') }}"
                        >

                        @error('frequency')
                            <div class="medical-error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Duration --}}
                    <div class="medical-field">

                        <label for="duration">
                            {{ __('medical.duration', [], null, 'Duration') }}
                        </label>

                        <input
                            id="duration"
                            type="text"
                            wire:model="duration"
                            class="medical-input"
                            maxlength="120"
                            placeholder="{{ __('medical.duration_placeholder', [], null, 'e.g. 7 days') }}"
                        >

                        @error('duration')
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


                    {{-- Instructions --}}
                    <div class="medical-field medical-field-full">

                        <label for="instructions">
                            {{ __('medical.instructions', [], null, 'Instructions') }}
                        </label>

                        <textarea
                            id="instructions"
                            wire:model="instructions"
                            class="medical-input medical-textarea"
                            rows="4"
                            maxlength="2000"
                            placeholder="{{ __('medical.instructions_placeholder', [], null, 'Instructions for taking the medicine...') }}"
                        ></textarea>

                        @error('instructions')
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
                            {{ __('medical.prescription_notes_description', [], null, 'Additional clinical notes related to the prescription.') }}
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
                            placeholder="{{ __('medical.notes_placeholder', [], null, 'Add additional notes...') }}"
                        ></textarea>

                        @error('notes')
                            <div class="medical-error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                </div>

            </section>


            {{-- Actions --}}
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

                    {{ __('medical.save_prescription', [], null, 'Save Prescription') }}

                </button>

            </div>

        </form>


    {{-- ========================================================= --}}
    {{-- Prescription List --}}
    {{-- ========================================================= --}}

    @else

        <section class="medical-card medical-table-card">

            <div class="medical-card-header">

                <div>

                    <h2 class="medical-card-title">
                        {{ __('medical.prescription_list', [], null, 'Prescription List') }}
                    </h2>

                    <p class="medical-card-description">
                        {{ __('medical.prescription_list_description', [], null, 'Review prescriptions recorded in the medical portal.') }}
                    </p>

                </div>

                <div class="medical-card-badge">
                    {{ $rx->total() }}
                </div>

            </div>


            @if($rx->count())

                <div class="medical-table-wrapper">

                    <table class="medical-table">

                        <thead>

                            <tr>

                                <th>
                                    {{ __('medical.prescription_number', [], null, 'Prescription #') }}
                                </th>

                                <th>
                                    {{ __('medical.patient', [], null, 'Patient') }}
                                </th>

                                <th>
                                    {{ __('medical.doctor', [], null, 'Doctor') }}
                                </th>

                                <th>
                                    {{ __('medical.date', [], null, 'Date') }}
                                </th>

                                <th>
                                    {{ __('medical.status', [], null, 'Status') }}
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @foreach($rx as $prescription)

                                <tr>

                                    <td>

                                        <span class="medical-code">
                                            {{ $prescription->prescription_number }}
                                        </span>

                                    </td>


                                    <td>

                                        <div class="medical-table-primary">

                                            {{ $isArabic
                                                ? $prescription->full_name_ar
                                                : ($prescription->full_name_en ?: $prescription->full_name_ar)
                                            }}

                                        </div>

                                        <div class="medical-table-secondary">
                                            {{ $prescription->patient_code }}
                                        </div>

                                    </td>


                                    <td>

                                        @if($prescription->doctor_ar)

                                            {{ $isArabic
                                                ? $prescription->doctor_ar
                                                : ($prescription->doctor_en ?: $prescription->doctor_ar)
                                            }}

                                        @else

                                            <span class="medical-muted">
                                                —
                                            </span>

                                        @endif

                                    </td>


                                    <td>

                                        <div class="medical-date">
                                            {{ \Illuminate\Support\Carbon::parse($prescription->prescribed_at)->format('Y-m-d') }}
                                        </div>

                                        <div class="medical-table-secondary">
                                            {{ \Illuminate\Support\Carbon::parse($prescription->prescribed_at)->format('H:i') }}
                                        </div>

                                    </td>


                                    <td>

                                        @php
                                            $statusClass = match($prescription->status) {
                                                'prescribed' => 'medical-status-blue',
                                                'dispensed' => 'medical-status-green',
                                                'cancelled' => 'medical-status-red',
                                                default => 'medical-status-gray',
                                            };
                                        @endphp

                                        <span class="medical-status {{ $statusClass }}">
                                            {{ __('medical.' . $prescription->status, [], null, ucfirst($prescription->status)) }}
                                        </span>

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>


                <div class="medical-pagination">
                    {{ $rx->links() }}
                </div>


            @else

                <div class="medical-empty">

                    <div class="medical-empty-icon">
                        Rx
                    </div>

                    <h3>
                        {{ __('medical.no_prescriptions', [], null, 'No prescriptions') }}
                    </h3>

                    <p>
                        {{ __('medical.no_prescriptions_description', [], null, 'No medical prescriptions have been recorded yet.') }}
                    </p>

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
            font-size: 15px;
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
            min-height: 110px;
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

        .medical-form-actions {
            display: flex;
            justify-content: flex-start;
            gap: 12px;
            margin-top: 4px;
        }

        .medical-table-card {
            margin-bottom: 0;
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

        .medical-table-primary {
            color: #0f172a;
            font-weight: 800;
        }

        .medical-table-secondary {
            margin-top: 4px;
            color: #94a3b8;
            font-size: 11px;
        }

        .medical-code {
            display: inline-flex;
            padding: 6px 9px;
            border-radius: 8px;
            background: #f8fafc;
            color: #475569;
            font-family: monospace;
            font-size: 12px;
            font-weight: 800;
            direction: ltr;
        }

        .medical-date {
            color: #334155;
            font-weight: 700;
            direction: ltr;
            text-align: right;
        }

        .medical-muted {
            color: #94a3b8;
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

        .medical-status-blue {
            background: #eff6ff;
            color: #2563eb;
        }

        .medical-status-green {
            background: #f0fdf4;
            color: #15803d;
        }

        .medical-status-red {
            background: #fef2f2;
            color: #dc2626;
        }

        .medical-status-gray {
            background: #f1f5f9;
            color: #64748b;
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
            font-size: 17px;
            font-weight: 900;
        }

        .medical-empty h3 {
            margin: 0;
            color: #0f172a;
            font-size: 16px;
            font-weight: 800;
        }

        .medical-empty p {
            margin: 7px 0 0;
            color: #94a3b8;
            font-size: 13px;
        }

        .medical-pagination {
            padding: 18px 20px;
            border-top: 1px solid #eef2f7;
        }

        @media (max-width: 850px) {

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

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
    {{-- Page Header --}}
    {{-- ========================================================= --}}

    <div class="medical-page-header">

        <div>

            <div class="medical-eyebrow">
                {{ __('medical.medical_portal', [], null, 'Medical Portal') }}
            </div>

            <h1 class="medical-page-title">
                {{ __('medical.new_visit', [], null, 'New Medical Visit') }}
            </h1>

            <p class="medical-page-description">
                {{ __('medical.new_visit_description', [], null, 'Register a new patient medical visit and record the clinical information.') }}
            </p>

        </div>

        <a
            class="medical-btn medical-btn-secondary"
            href="{{ route('admin.medical.visits.index') }}"
            wire:navigate
        >
            <span>←</span>
            {{ __('medical.back', [], null, 'Back') }}
        </a>

    </div>


    {{-- ========================================================= --}}
    {{-- Validation Errors --}}
    {{-- ========================================================= --}}

    @if($errors->any())

        <div class="medical-alert medical-alert-danger">

            <div class="medical-alert-icon">
                !
            </div>

            <div>

                <strong>
                    {{ __('medical.validation_error', [], null, 'Please review the entered information.') }}
                </strong>

                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>

            </div>

        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- Success Message --}}
    {{-- ========================================================= --}}

    @if(session()->has('medical_message'))

        <div class="medical-alert medical-alert-success">

            <div class="medical-alert-icon">
                ✓
            </div>

            <div>
                {{ session('medical_message') }}
            </div>

        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- Main Form --}}
    {{-- ========================================================= --}}

    <form
        wire:submit="save"
        class="medical-form-page"
    >

        {{-- ===================================================== --}}
        {{-- Visit Information --}}
        {{-- ===================================================== --}}

        <section class="medical-card">

            <div class="medical-card-header">

                <div class="medical-section-heading">

                    <div class="medical-section-icon medical-section-icon-blue">
                        +
                    </div>

                    <div>

                        <h2 class="medical-card-title">
                            {{ __('medical.visit_information', [], null, 'Visit Information') }}
                        </h2>

                        <p class="medical-card-description">
                            {{ __('medical.visit_information_description', [], null, 'Select the patient, medical point and clinical staff.') }}
                        </p>

                    </div>

                </div>

            </div>


            <div class="medical-form-grid">

                {{-- Patient --}}
                <div class="medical-field medical-field-large">

                    <label for="patientId">
                        {{ __('medical.patient', [], null, 'Patient') }}
                        <span class="required">*</span>
                    </label>

                    <select
                        id="patientId"
                        wire:model="patientId"
                        class="@error('patientId') medical-input-error @enderror"
                    >
                        <option value="">
                            — {{ __('medical.select_patient', [], null, 'Select Patient') }} —
                        </option>

                        @foreach($patients as $patient)

                            @php
                                $patientName = $isArabic
                                    ? ($patient->full_name_ar ?: $patient->full_name_en)
                                    : ($patient->full_name_en ?: $patient->full_name_ar);
                            @endphp

                            <option value="{{ $patient->id }}">
                                {{ $patientName }} — {{ $patient->patient_code }}
                            </option>

                        @endforeach

                    </select>

                    @error('patientId')
                        <span class="medical-error">{{ $message }}</span>
                    @enderror

                </div>


                {{-- Clinic --}}
                <div class="medical-field">

                    <label for="institutionId">
                        {{ __('medical.clinic', [], null, 'Medical Point') }}
                        <span class="required">*</span>
                    </label>

                    <select
                        id="institutionId"
                        wire:model="institutionId"
                        class="@error('institutionId') medical-input-error @enderror"
                    >
                        <option value="">
                            — {{ __('medical.select_clinic', [], null, 'Select Medical Point') }} —
                        </option>

                        @foreach($clinics as $clinic)

                            @php
                                $clinicName = $isArabic
                                    ? ($clinic->name_ar ?: $clinic->name_en)
                                    : ($clinic->name_en ?: $clinic->name_ar);
                            @endphp

                            <option value="{{ $clinic->id }}">
                                {{ $clinicName }}
                            </option>

                        @endforeach

                    </select>

                    @error('institutionId')
                        <span class="medical-error">{{ $message }}</span>
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
                        class="@error('doctorId') medical-input-error @enderror"
                    >
                        <option value="">
                            — {{ __('medical.select_doctor', [], null, 'Select Doctor') }} —
                        </option>

                        @foreach($staff->where('profession', 'doctor') as $member)

                            @php
                                $staffName = $isArabic
                                    ? ($member->full_name_ar ?: $member->full_name_en)
                                    : ($member->full_name_en ?: $member->full_name_ar);
                            @endphp

                            <option value="{{ $member->id }}">
                                {{ $staffName }}
                            </option>

                        @endforeach

                    </select>

                    @error('doctorId')
                        <span class="medical-error">{{ $message }}</span>
                    @enderror

                </div>


                {{-- Nurse --}}
                <div class="medical-field">

                    <label for="nurseId">
                        {{ __('medical.nurse', [], null, 'Nurse') }}
                    </label>

                    <select
                        id="nurseId"
                        wire:model="nurseId"
                        class="@error('nurseId') medical-input-error @enderror"
                    >
                        <option value="">
                            — {{ __('medical.select_nurse', [], null, 'Select Nurse') }} —
                        </option>

                        @foreach($staff->where('profession', 'nurse') as $member)

                            @php
                                $staffName = $isArabic
                                    ? ($member->full_name_ar ?: $member->full_name_en)
                                    : ($member->full_name_en ?: $member->full_name_ar);
                            @endphp

                            <option value="{{ $member->id }}">
                                {{ $staffName }}
                            </option>

                        @endforeach

                    </select>

                    @error('nurseId')
                        <span class="medical-error">{{ $message }}</span>
                    @enderror

                </div>


                {{-- Date --}}
                <div class="medical-field">

                    <label for="visitedAt">
                        {{ __('medical.date', [], null, 'Visit Date') }}
                        <span class="required">*</span>
                    </label>

                    <input
                        id="visitedAt"
                        type="datetime-local"
                        wire:model="visitedAt"
                        class="@error('visitedAt') medical-input-error @enderror"
                    >

                    @error('visitedAt')
                        <span class="medical-error">{{ $message }}</span>
                    @enderror

                </div>


                {{-- Status --}}
                <div class="medical-field">

                    <label for="status">
                        {{ __('medical.status', [], null, 'Status') }}
                        <span class="required">*</span>
                    </label>

                    <select
                        id="status"
                        wire:model="status"
                        class="@error('status') medical-input-error @enderror"
                    >

                        <option value="open">
                            {{ __('medical.open', [], null, 'Open') }}
                        </option>

                        <option value="in_progress">
                            {{ __('medical.in_progress', [], null, 'In Progress') }}
                        </option>

                        <option value="completed">
                            {{ __('medical.completed', [], null, 'Completed') }}
                        </option>

                        <option value="cancelled">
                            {{ __('medical.cancelled', [], null, 'Cancelled') }}
                        </option>

                    </select>

                    @error('status')
                        <span class="medical-error">{{ $message }}</span>
                    @enderror

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- Clinical Assessment --}}
        {{-- ===================================================== --}}

        <section class="medical-card">

            <div class="medical-card-header">

                <div class="medical-section-heading">

                    <div class="medical-section-icon medical-section-icon-green">
                        ✓
                    </div>

                    <div>

                        <h2 class="medical-card-title">
                            {{ __('medical.clinical_assessment', [], null, 'Clinical Assessment') }}
                        </h2>

                        <p class="medical-card-description">
                            {{ __('medical.clinical_assessment_description', [], null, 'Record the patient complaint and clinical diagnosis.') }}
                        </p>

                    </div>

                </div>

            </div>


            <div class="medical-form-grid">

                {{-- Chief Complaint --}}
                <div class="medical-field medical-field-full">

                    <label for="chiefComplaint">
                        {{ __('medical.chief_complaint', [], null, 'Chief Complaint') }}
                    </label>

                    <textarea
                        id="chiefComplaint"
                        wire:model="chiefComplaint"
                        rows="4"
                        placeholder="{{ __('medical.chief_complaint_placeholder', [], null, 'Describe the main reason for the visit...') }}"
                        class="@error('chiefComplaint') medical-input-error @enderror"
                    ></textarea>

                    @error('chiefComplaint')
                        <span class="medical-error">{{ $message }}</span>
                    @enderror

                </div>


                {{-- Diagnosis --}}
                <div class="medical-field medical-field-full">

                    <label for="diagnosis">
                        {{ __('medical.diagnosis', [], null, 'Diagnosis') }}
                    </label>

                    <textarea
                        id="diagnosis"
                        wire:model="diagnosis"
                        rows="4"
                        placeholder="{{ __('medical.diagnosis_placeholder', [], null, 'Enter the clinical diagnosis...') }}"
                        class="@error('diagnosis') medical-input-error @enderror"
                    ></textarea>

                    @error('diagnosis')
                        <span class="medical-error">{{ $message }}</span>
                    @enderror

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- Vital Signs --}}
        {{-- ===================================================== --}}

        <section class="medical-card">

            <div class="medical-card-header">

                <div class="medical-section-heading">

                    <div class="medical-section-icon medical-section-icon-orange">
                        +
                    </div>

                    <div>

                        <h2 class="medical-card-title">
                            {{ __('medical.vital_signs', [], null, 'Vital Signs') }}
                        </h2>

                        <p class="medical-card-description">
                            {{ __('medical.vital_signs_description', [], null, 'Record the patient vital measurements during the visit.') }}
                        </p>

                    </div>

                </div>

            </div>


            <div class="medical-vitals-grid">

                {{-- Temperature --}}
                <div class="medical-vital-card">

                    <div class="medical-vital-icon">
                        °C
                    </div>

                    <div class="medical-vital-content">

                        <label for="temperature">
                            {{ __('medical.temperature', [], null, 'Temperature') }}
                        </label>

                        <div class="medical-vital-input">

                            <input
                                id="temperature"
                                type="number"
                                step="0.1"
                                wire:model="temperature"
                                placeholder="36.5"
                            >

                            <span>°C</span>

                        </div>

                    </div>

                </div>


                {{-- Blood Pressure --}}
                <div class="medical-vital-card">

                    <div class="medical-vital-icon">
                        BP
                    </div>

                    <div class="medical-vital-content">

                        <label for="bloodPressure">
                            {{ __('medical.blood_pressure', [], null, 'Blood Pressure') }}
                        </label>

                        <div class="medical-vital-input">

                            <input
                                id="bloodPressure"
                                type="text"
                                wire:model="bloodPressure"
                                placeholder="120/80"
                            >

                            <span>mmHg</span>

                        </div>

                    </div>

                </div>


                {{-- Pulse --}}
                <div class="medical-vital-card">

                    <div class="medical-vital-icon">
                        ♥
                    </div>

                    <div class="medical-vital-content">

                        <label for="pulse">
                            {{ __('medical.pulse', [], null, 'Pulse') }}
                        </label>

                        <div class="medical-vital-input">

                            <input
                                id="pulse"
                                type="number"
                                wire:model="pulse"
                                placeholder="72"
                            >

                            <span>BPM</span>

                        </div>

                    </div>

                </div>


                {{-- Weight --}}
                <div class="medical-vital-card">

                    <div class="medical-vital-icon">
                        KG
                    </div>

                    <div class="medical-vital-content">

                        <label for="weight">
                            {{ __('medical.weight', [], null, 'Weight') }}
                        </label>

                        <div class="medical-vital-input">

                            <input
                                id="weight"
                                type="number"
                                step="0.1"
                                wire:model="weight"
                                placeholder="60"
                            >

                            <span>kg</span>

                        </div>

                    </div>

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- Treatment --}}
        {{-- ===================================================== --}}

        <section class="medical-card">

            <div class="medical-card-header">

                <div class="medical-section-heading">

                    <div class="medical-section-icon medical-section-icon-purple">
                        +
                    </div>

                    <div>

                        <h2 class="medical-card-title">
                            {{ __('medical.treatment_information', [], null, 'Treatment Information') }}
                        </h2>

                        <p class="medical-card-description">
                            {{ __('medical.treatment_information_description', [], null, 'Record the treatment plan and any additional clinical notes.') }}
                        </p>

                    </div>

                </div>

            </div>


            <div class="medical-form-grid">

                {{-- Treatment Plan --}}
                <div class="medical-field medical-field-full">

                    <label for="treatmentPlan">
                        {{ __('medical.treatment_plan', [], null, 'Treatment Plan') }}
                    </label>

                    <textarea
                        id="treatmentPlan"
                        wire:model="treatmentPlan"
                        rows="5"
                        placeholder="{{ __('medical.treatment_plan_placeholder', [], null, 'Describe the recommended treatment plan...') }}"
                        class="@error('treatmentPlan') medical-input-error @enderror"
                    ></textarea>

                    @error('treatmentPlan')
                        <span class="medical-error">{{ $message }}</span>
                    @enderror

                </div>


                {{-- Notes --}}
                <div class="medical-field medical-field-full">

                    <label for="notes">
                        {{ __('medical.notes', [], null, 'Notes') }}
                    </label>

                    <textarea
                        id="notes"
                        wire:model="notes"
                        rows="4"
                        placeholder="{{ __('medical.notes_placeholder', [], null, 'Add any additional notes...') }}"
                        class="@error('notes') medical-input-error @enderror"
                    ></textarea>

                    @error('notes')
                        <span class="medical-error">{{ $message }}</span>
                    @enderror

                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- Actions --}}
        {{-- ===================================================== --}}

        <div class="medical-form-actions">

            <a
                href="{{ route('admin.medical.visits.index') }}"
                wire:navigate
                class="medical-btn medical-btn-secondary"
            >
                {{ __('medical.cancel', [], null, 'Cancel') }}
            </a>

            <button
                type="submit"
                class="medical-btn medical-btn-primary medical-save-button"
                wire:loading.attr="disabled"
                wire:target="save"
            >

                <span wire:loading.remove wire:target="save">
                    ✓
                </span>

                <span wire:loading wire:target="save" class="medical-loading">
                    ↻
                </span>

                <span wire:loading.remove wire:target="save">
                    {{ __('medical.save_visit', [], null, 'Save Visit') }}
                </span>

                <span wire:loading wire:target="save">
                    {{ __('medical.saving', [], null, 'Saving...') }}
                </span>

            </button>

        </div>

    </form>


    {{-- ========================================================= --}}
    {{-- Styles --}}
    {{-- ========================================================= --}}

    <style>

        .medical-page {
            direction: rtl;
            padding-bottom: 50px;
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

        .medical-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 43px;
            padding: 0 17px;
            border-radius: 10px;
            border: 1px solid transparent;
            text-decoration: none;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            transition: .15s ease;
            white-space: nowrap;
        }

        .medical-btn-primary {
            background: #15803d;
            color: #fff;
            border-color: #15803d;
            box-shadow: 0 5px 12px rgba(21, 128, 61, .15);
        }

        .medical-btn-primary:hover {
            background: #166534;
            color: #fff;
        }

        .medical-btn-secondary {
            background: #fff;
            color: #475569;
            border-color: #dbe3ec;
        }

        .medical-btn-secondary:hover {
            background: #f8fafc;
            color: #15803d;
            border-color: #bbf7d0;
        }

        .medical-alert {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 20px;
            padding: 15px 17px;
            border-radius: 13px;
            font-size: 12px;
            line-height: 1.7;
        }

        .medical-alert-icon {
            width: 31px;
            height: 31px;
            min-width: 31px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            font-weight: 900;
        }

        .medical-alert-danger {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .medical-alert-danger .medical-alert-icon {
            background: #fee2e2;
            color: #dc2626;
        }

        .medical-alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .medical-alert-success .medical-alert-icon {
            background: #dcfce7;
            color: #15803d;
        }

        .medical-alert ul {
            margin: 7px 0 0;
            padding-right: 18px;
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
            padding: 21px 24px;
            border-bottom: 1px solid #eef2f7;
        }

        .medical-section-heading {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .medical-section-icon {
            width: 42px;
            height: 42px;
            min-width: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 900;
        }

        .medical-section-icon-blue {
            background: #eff6ff;
            color: #2563eb;
        }

        .medical-section-icon-green {
            background: #f0fdf4;
            color: #15803d;
        }

        .medical-section-icon-orange {
            background: #fff7ed;
            color: #ea580c;
        }

        .medical-section-icon-purple {
            background: #faf5ff;
            color: #9333ea;
        }

        .medical-card-title {
            margin: 0;
            color: #0f172a;
            font-size: 17px;
            font-weight: 850;
        }

        .medical-card-description {
            margin: 4px 0 0;
            color: #94a3b8;
            font-size: 11px;
            line-height: 1.7;
        }

        .medical-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            padding: 23px 24px;
        }

        .medical-field {
            min-width: 0;
        }

        .medical-field-large {
            grid-column: span 2;
        }

        .medical-field-full {
            grid-column: 1 / -1;
        }

        .medical-field label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-size: 12px;
            font-weight: 800;
        }

        .required {
            color: #dc2626;
            margin-right: 2px;
        }

        .medical-field input,
        .medical-field select,
        .medical-field textarea {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #dbe3ec;
            border-radius: 10px;
            background: #fff;
            color: #0f172a;
            font-size: 12px;
            outline: none;
            transition: .15s ease;
        }

        .medical-field input,
        .medical-field select {
            height: 44px;
            padding: 0 13px;
        }

        .medical-field textarea {
            min-height: 100px;
            padding: 12px 13px;
            line-height: 1.8;
            resize: vertical;
        }

        .medical-field input:focus,
        .medical-field select:focus,
        .medical-field textarea:focus {
            border-color: #86efac;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, .08);
        }

        .medical-field input::placeholder,
        .medical-field textarea::placeholder {
            color: #cbd5e1;
        }

        .medical-input-error {
            border-color: #fca5a5 !important;
            background: #fffafa !important;
        }

        .medical-error {
            display: block;
            margin-top: 6px;
            color: #dc2626;
            font-size: 10px;
            font-weight: 700;
        }

        .medical-vitals-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 15px;
            padding: 23px 24px;
        }

        .medical-vital-card {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 14px;
            border: 1px solid #e2e8f0;
            border-radius: 13px;
            background: #fafafa;
        }

        .medical-vital-icon {
            width: 39px;
            height: 39px;
            min-width: 39px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #fff7ed;
            color: #ea580c;
            font-size: 10px;
            font-weight: 900;
        }

        .medical-vital-content {
            min-width: 0;
            width: 100%;
        }

        .medical-vital-content label {
            display: block;
            margin-bottom: 6px;
            color: #475569;
            font-size: 10px;
            font-weight: 800;
        }

        .medical-vital-input {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .medical-vital-input input {
            width: 100%;
            height: 36px;
            min-width: 0;
            padding: 0 8px;
            border: 1px solid #dbe3ec;
            border-radius: 8px;
            background: #fff;
            color: #0f172a;
            font-size: 11px;
            outline: none;
        }

        .medical-vital-input input:focus {
            border-color: #fdba74;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, .07);
        }

        .medical-vital-input span {
            color: #94a3b8;
            font-size: 9px;
            font-weight: 700;
            white-space: nowrap;
        }

        .medical-form-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            padding: 4px 0 10px;
        }

        .medical-save-button {
            min-width: 135px;
        }

        .medical-save-button:disabled {
            opacity: .65;
            cursor: not-allowed;
        }

        .medical-loading {
            display: inline-block;
            animation: medical-spin .8s linear infinite;
        }

        @keyframes medical-spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 1100px) {

            .medical-vitals-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

        }

        @media (max-width: 800px) {

            .medical-page-header {
                flex-direction: column;
            }

            .medical-btn {
                width: 100%;
            }

            .medical-form-grid {
                grid-template-columns: 1fr;
            }

            .medical-field-large,
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

            .medical-form-grid,
            .medical-vitals-grid {
                grid-template-columns: 1fr;
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

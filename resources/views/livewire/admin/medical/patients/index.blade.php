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
                {{ __('medical.medical_portal', [], null, 'Medical Portal') }}
            </div>

            <h1 class="medical-page-title">
                {{ __('medical.patients', [], null, 'Patients') }}
            </h1>

            <p class="medical-page-description">
                {{ __('medical.patients_description', [], null, 'Manage medical patient records and access their complete medical profiles.') }}
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

                    {{ __('medical.new_patient', [], null, 'Add Patient') }}
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


    {{-- Flash Message --}}
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
    {{-- Add Patient --}}
    {{-- ========================================================= --}}

    @if($showForm)

        <form
            wire:submit="save"
            class="medical-patient-form"
        >

            {{-- Patient Registration --}}
            <section class="medical-card">

                <div class="medical-card-header">

                    <div>

                        <h2 class="medical-card-title">
                            {{ __('medical.patient_registration', [], null, 'Patient Registration') }}
                        </h2>

                        <p class="medical-card-description">
                            {{ __('medical.patient_registration_description', [], null, 'Register an existing student in the medical portal.') }}
                        </p>

                    </div>

                    <div class="medical-section-icon">
                        +
                    </div>

                </div>


                <div class="medical-form-grid">

                    {{-- Student --}}
                    <div class="medical-field medical-field-full">

                        <label for="studentId">

                            {{ __('medical.student', [], null, 'Student') }}

                            <span class="required">*</span>

                        </label>

                        <select
                            id="studentId"
                            wire:model="studentId"
                            class="medical-input"
                        >

                            <option value="">
                                {{ __('medical.select_student', [], null, 'Select student') }}
                            </option>

                            @foreach($students as $student)

                                <option value="{{ $student->id }}">

                                    {{ $isArabic
                                        ? $student->full_name_ar
                                        : ($student->full_name_en ?: $student->full_name_ar)
                                    }}

                                    — {{ $student->student_code }}

                                </option>

                            @endforeach

                        </select>

                        @error('studentId')
                            <div class="medical-error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Medical Point --}}
                    <div class="medical-field">

                        <label for="institutionId">
                            {{ __('medical.medical_point', [], null, 'Medical Point') }}
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


            {{-- Medical History --}}
            <section class="medical-card">

                <div class="medical-card-header">

                    <div>

                        <h2 class="medical-card-title">
                            {{ __('medical.medical_history', [], null, 'Medical History') }}
                        </h2>

                        <p class="medical-card-description">
                            {{ __('medical.medical_history_description', [], null, 'Record known allergies and chronic medical conditions.') }}
                        </p>

                    </div>

                    <div class="medical-section-icon">
                        i
                    </div>

                </div>


                <div class="medical-form-grid">

                    {{-- Allergies --}}
                    <div class="medical-field">

                        <label for="allergies">
                            {{ __('medical.allergies', [], null, 'Allergies') }}
                        </label>

                        <textarea
                            id="allergies"
                            wire:model="allergies"
                            class="medical-input medical-textarea"
                            rows="5"
                            maxlength="4000"
                            placeholder="{{ __('medical.allergies_placeholder', [], null, 'Known allergies...') }}"
                        ></textarea>

                        @error('allergies')
                            <div class="medical-error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Chronic Conditions --}}
                    <div class="medical-field">

                        <label for="chronicConditions">
                            {{ __('medical.chronic_conditions', [], null, 'Chronic Conditions') }}
                        </label>

                        <textarea
                            id="chronicConditions"
                            wire:model="chronicConditions"
                            class="medical-input medical-textarea"
                            rows="5"
                            maxlength="4000"
                            placeholder="{{ __('medical.chronic_conditions_placeholder', [], null, 'Known chronic conditions...') }}"
                        ></textarea>

                        @error('chronicConditions')
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

                    {{ __('medical.save_patient', [], null, 'Save Patient') }}

                </button>

            </div>

        </form>


    {{-- ========================================================= --}}
    {{-- Patients List --}}
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
                        {{ __('medical.active_patients', [], null, 'Active Patients') }}
                    </span>

                    <strong class="medical-stat-value">
                        {{ $activePatients }}
                    </strong>

                    <span class="medical-stat-description">
                        {{ __('medical.active_patients_description', [], null, 'Currently registered patients') }}
                    </span>

                </div>

            </div>


            <div class="medical-stat-card">

                <div class="medical-stat-icon medical-stat-icon-gray">
                    •
                </div>

                <div class="medical-stat-content">

                    <span class="medical-stat-label">
                        {{ __('medical.inactive_patients', [], null, 'Inactive Patients') }}
                    </span>

                    <strong class="medical-stat-value">
                        {{ $inactivePatients }}
                    </strong>

                    <span class="medical-stat-description">
                        {{ __('medical.inactive_patients_description', [], null, 'Inactive medical records') }}
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
                        placeholder="{{ __('medical.search_patients', [], null, 'Search by patient name or code...') }}"
                    >

                    @if($q !== '')

                        <button
                            type="button"
                            wire:click="$set('q', '')"
                            class="medical-search-clear"
                            title="{{ __('medical.clear', [], null, 'Clear') }}"
                        >
                            ×
                        </button>

                    @endif

                </div>


                <div class="medical-filter-result">

                    {{ $patients->total() }}

                    {{ __('medical.patient_records', [], null, 'records') }}

                </div>

            </div>

        </section>


        {{-- Patient Table --}}
        <section class="medical-card medical-table-card">

            <div class="medical-card-header">

                <div>

                    <h2 class="medical-card-title">
                        {{ __('medical.patient_list', [], null, 'Patient List') }}
                    </h2>

                    <p class="medical-card-description">
                        {{ __('medical.patient_list_description', [], null, 'Registered patients in the medical portal.') }}
                    </p>

                </div>

                <div class="medical-card-badge">
                    {{ $patients->total() }}
                </div>

            </div>


            @if($patients->count())

                <div class="medical-table-wrapper">

                    <table class="medical-table">

                        <thead>

                            <tr>

                                <th>
                                    {{ __('medical.patient', [], null, 'Patient') }}
                                </th>

                                <th>
                                    {{ __('medical.student_code', [], null, 'Student Code') }}
                                </th>

                                <th>
                                    {{ __('medical.medical_point', [], null, 'Medical Point') }}
                                </th>

                                <th>
                                    {{ __('medical.status', [], null, 'Status') }}
                                </th>

                                <th>
                                    {{ __('medical.action', [], null, 'Action') }}
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @foreach($patients as $patient)

                                @php

                                    $patientName = $isArabic
                                        ? $patient->full_name_ar
                                        : ($patient->full_name_en ?: $patient->full_name_ar);

                                    $clinicName = $isArabic
                                        ? ($patient->clinic_ar ?: '—')
                                        : ($patient->clinic_en ?: $patient->clinic_ar ?: '—');

                                    $statusClass = match($patient->status) {
                                        'active' => 'medical-status-green',
                                        'inactive' => 'medical-status-gray',
                                        default => 'medical-status-gray',
                                    };

                                @endphp

                                <tr>

                                    {{-- Patient --}}
                                    <td>

                                        <div class="patient-cell">

                                            <div class="patient-mini-avatar">
                                                {{ mb_substr($patientName, 0, 1) }}
                                            </div>

                                            <div>

                                                <div class="medical-table-primary">
                                                    {{ $patientName }}
                                                </div>

                                                <div class="medical-table-secondary">

                                                    <span class="medical-code">
                                                        {{ $patient->patient_code }}
                                                    </span>

                                                </div>

                                            </div>

                                        </div>

                                    </td>


                                    {{-- Student Code --}}
                                    <td>

                                        <span class="student-code">
                                            {{ $patient->student_code }}
                                        </span>

                                    </td>


                                    {{-- Medical Point --}}
                                    <td>

                                        {{ $clinicName }}

                                    </td>


                                    {{-- Status --}}
                                    <td>

                                        <span class="medical-status {{ $statusClass }}">

                                            {{ __('medical.' . $patient->status, [], null, ucfirst($patient->status)) }}

                                        </span>

                                    </td>


                                    {{-- Action --}}
                                    <td>

                                        <a
                                            href="{{ route('admin.medical.patients.show', $patient->id) }}"
                                            wire:navigate
                                            class="medical-view-btn"
                                        >
                                            {{ __('medical.view_profile', [], null, 'View Profile') }}

                                            <span>
                                                ←
                                            </span>

                                        </a>

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>


                <div class="medical-pagination">
                    {{ $patients->links() }}
                </div>


            @else

                <div class="medical-empty">

                    <div class="medical-empty-icon">
                        +
                    </div>

                    <h3>
                        {{ __('medical.no_patients', [], null, 'No patients found') }}
                    </h3>

                    <p>
                        {{ $q !== ''
                            ? __('medical.no_patients_search_description', [], null, 'No patient records match your search.')
                            : __('medical.no_patients_description', [], null, 'No medical patients have been registered yet.')
                        }}
                    </p>

                    @if($q === '')

                        <button
                            type="button"
                            wire:click="openForm"
                            class="medical-btn medical-btn-primary medical-empty-button"
                        >
                            + {{ __('medical.new_patient', [], null, 'Add Patient') }}
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
            grid-template-columns: repeat(2, minmax(0, 1fr));
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
            max-width: 600px;
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

        .patient-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .patient-mini-avatar {
            width: 39px;
            height: 39px;
            min-width: 39px;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0fdf4;
            color: #15803d;
            font-size: 15px;
            font-weight: 900;
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
            padding: 4px 7px;
            border-radius: 7px;
            background: #f8fafc;
            color: #64748b;
            font-family: monospace;
            font-size: 11px;
            direction: ltr;
        }

        .student-code {
            color: #475569;
            font-family: monospace;
            font-size: 12px;
            font-weight: 700;
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

        .medical-status-gray {
            background: #f1f5f9;
            color: #64748b;
        }

        .medical-view-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 11px;
            border: 1px solid #dcfce7;
            border-radius: 9px;
            background: #f0fdf4;
            color: #15803d;
            font-size: 11px;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
            transition: .2s ease;
        }

        .medical-view-btn:hover {
            background: #dcfce7;
        }

        .medical-view-btn span {
            font-size: 14px;
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

        .medical-pagination {
            padding: 18px 20px;
            border-top: 1px solid #eef2f7;
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

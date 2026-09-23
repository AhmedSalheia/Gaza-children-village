<div class="medical-page">

    @include('livewire.admin.medical._nav')

    @php
        $locale = app()->getLocale();
        $isArabic = $locale === 'ar';

        $totalStaff = $staff->count();

        $activeStaff = $staff->filter(
            fn ($member) => $member->status === 'active'
        )->count();

        $inactiveStaff = $staff->filter(
            fn ($member) => $member->status !== 'active'
        )->count();

        $doctors = $staff->filter(
            fn ($member) => $member->profession === 'doctor'
        )->count();

        $professionLabel = function ($profession) {
            return __('medical.' . $profession, [], null, ucfirst((string) $profession));
        };

        $personName = function ($person) use ($isArabic) {
            return $isArabic
                ? ($person->full_name_ar ?: $person->full_name_en)
                : ($person->full_name_en ?: $person->full_name_ar);
        };

        $clinicName = function ($clinic) use ($isArabic) {
            return $isArabic
                ? ($clinic->clinic_ar ?: $clinic->clinic_en)
                : ($clinic->clinic_en ?: $clinic->clinic_ar);
        };
    @endphp


    {{-- =========================================================
        Page Header
    ========================================================== --}}
    <div class="medical-page-header">

        <div>
            <div class="medical-eyebrow">
                {{ __('medical.staff', [], null, 'Medical Staff') }}
            </div>

            <h1 class="medical-page-title">
                {{ __('medical.staff', [], null, 'Medical Staff') }}
            </h1>

            <p class="medical-page-description">
                {{ __('medical.staff_description', [], null, 'Manage doctors, nurses, pharmacists and medical staff assigned to medical points.') }}
            </p>
        </div>

        <div class="medical-header-actions">

            <button
                type="button"
                class="medical-btn medical-btn-primary"
                wire:click="$set('showForm', true)"
            >
                <span class="medical-btn-icon">+</span>
                {{ __('medical.add_staff', [], null, 'Add Medical Staff') }}
            </button>

        </div>

    </div>


    {{-- =========================================================
        Flash Message
    ========================================================== --}}
    @if(session('medical_message'))

        <div class="medical-alert medical-alert-success">
            <span class="medical-alert-icon">✓</span>

            <div>
                {{ session('medical_message') }}
            </div>
        </div>

    @endif


    {{-- =========================================================
        Validation Errors
    ========================================================== --}}
    @if($errors->any())

        <div class="medical-alert medical-alert-danger">

            <span class="medical-alert-icon">!</span>

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


    {{-- =========================================================
        Statistics
    ========================================================== --}}
    <div class="medical-stats-grid">

        <div class="medical-stat-card">

            <div class="medical-stat-icon medical-stat-blue">
                👨‍⚕️
            </div>

            <div>
                <div class="medical-stat-label">
                    {{ __('medical.total_staff', [], null, 'Total Staff') }}
                </div>

                <div class="medical-stat-value">
                    {{ $totalStaff }}
                </div>
            </div>

        </div>


        <div class="medical-stat-card">

            <div class="medical-stat-icon medical-stat-green">
                ✓
            </div>

            <div>
                <div class="medical-stat-label">
                    {{ __('medical.active_staff', [], null, 'Active Staff') }}
                </div>

                <div class="medical-stat-value">
                    {{ $activeStaff }}
                </div>
            </div>

        </div>


        <div class="medical-stat-card">

            <div class="medical-stat-icon medical-stat-red">
                !
            </div>

            <div>
                <div class="medical-stat-label">
                    {{ __('medical.inactive_staff', [], null, 'Inactive Staff') }}
                </div>

                <div class="medical-stat-value">
                    {{ $inactiveStaff }}
                </div>
            </div>

        </div>


        <div class="medical-stat-card">

            <div class="medical-stat-icon medical-stat-purple">
                ✚
            </div>

            <div>
                <div class="medical-stat-label">
                    {{ __('medical.doctors', [], null, 'Doctors') }}
                </div>

                <div class="medical-stat-value">
                    {{ $doctors }}
                </div>
            </div>

        </div>

    </div>


    {{-- =========================================================
        Add Staff Form
    ========================================================== --}}
    @if($showForm)

        <div class="medical-card medical-form-card">

            <div class="medical-card-header">

                <div>
                    <h2 class="medical-card-title">
                        {{ __('medical.add_staff', [], null, 'Add Medical Staff') }}
                    </h2>

                    <p class="medical-card-description">
                        {{ __('medical.staff_form_description', [], null, 'Add a medical staff member and assign them to a medical point.') }}
                    </p>
                </div>

                <button
                    type="button"
                    class="medical-close-btn"
                    wire:click="resetForm"
                    title="{{ __('medical.close', [], null, 'Close') }}"
                >
                    ×
                </button>

            </div>


            <form wire:submit="save">

                <div class="medical-form-grid">

                    {{-- Staff Member --}}
                    <div class="medical-field">

                        <label for="staffProfileId">
                            {{ __('medical.staff_member', [], null, 'Staff Member') }}

                            <span class="required">*</span>
                        </label>

                        <select
                            id="staffProfileId"
                            wire:model="staffProfileId"
                            class="@error('staffProfileId') medical-input-error @enderror"
                        >
                            <option value="">
                                —
                            </option>

                            @foreach($available as $p)

                                <option value="{{ $p->id }}">
                                    {{ $personName($p) }}

                                    @if(!empty($p->staff_code))
                                        — {{ $p->staff_code }}
                                    @endif
                                </option>

                            @endforeach
                        </select>

                        @error('staffProfileId')
                            <small class="medical-error">
                                {{ $message }}
                            </small>
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
                                —
                            </option>

                            @foreach($clinics as $c)

                                <option value="{{ $c->id }}">
                                    {{ $isArabic
                                        ? ($c->name_ar ?: $c->name_en)
                                        : ($c->name_en ?: $c->name_ar)
                                    }}
                                </option>

                            @endforeach
                        </select>

                        @error('institutionId')
                            <small class="medical-error">
                                {{ $message }}
                            </small>
                        @enderror

                    </div>


                    {{-- Profession --}}
                    <div class="medical-field">

                        <label for="profession">
                            {{ __('medical.profession', [], null, 'Profession') }}

                            <span class="required">*</span>
                        </label>

                        <select
                            id="profession"
                            wire:model="profession"
                            class="@error('profession') medical-input-error @enderror"
                        >
                            <option value="doctor">
                                {{ __('medical.doctor', [], null, 'Doctor') }}
                            </option>

                            <option value="nurse">
                                {{ __('medical.nurse', [], null, 'Nurse') }}
                            </option>

                            <option value="pharmacist">
                                {{ __('medical.pharmacist', [], null, 'Pharmacist') }}
                            </option>

                            <option value="other">
                                {{ __('medical.other', [], null, 'Other') }}
                            </option>
                        </select>

                        @error('profession')
                            <small class="medical-error">
                                {{ $message }}
                            </small>
                        @enderror

                    </div>


                    {{-- License --}}
                    <div class="medical-field">

                        <label for="licenseNumber">
                            {{ __('medical.license_number', [], null, 'License Number') }}
                        </label>

                        <input
                            id="licenseNumber"
                            type="text"
                            wire:model="licenseNumber"
                            placeholder="{{ __('medical.license_number_placeholder', [], null, 'Enter license number') }}"
                            class="@error('licenseNumber') medical-input-error @enderror"
                        >

                        @error('licenseNumber')
                            <small class="medical-error">
                                {{ $message }}
                            </small>
                        @enderror

                    </div>


                    {{-- Specialization --}}
                    <div class="medical-field">

                        <label for="specialization">
                            {{ __('medical.specialization', [], null, 'Specialization') }}
                        </label>

                        <input
                            id="specialization"
                            type="text"
                            wire:model="specialization"
                            placeholder="{{ __('medical.specialization_placeholder', [], null, 'e.g. Pediatrics, General Medicine...') }}"
                            class="@error('specialization') medical-input-error @enderror"
                        >

                        @error('specialization')
                            <small class="medical-error">
                                {{ $message }}
                            </small>
                        @enderror

                    </div>


                    {{-- Started On --}}
                    <div class="medical-field">

                        <label for="startedOn">
                            {{ __('medical.started_on', [], null, 'Started On') }}
                        </label>

                        <input
                            id="startedOn"
                            type="date"
                            wire:model="startedOn"
                            class="@error('startedOn') medical-input-error @enderror"
                        >

                        @error('startedOn')
                            <small class="medical-error">
                                {{ $message }}
                            </small>
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
                            <small class="medical-error">
                                {{ $message }}
                            </small>
                        @enderror

                    </div>

                </div>


                {{-- Form Actions --}}
                <div class="medical-form-actions">

                    <button
                        type="submit"
                        class="medical-btn medical-btn-primary"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="save">
                            ✓
                        </span>

                        <span wire:loading wire:target="save">
                            …
                        </span>

                        <span>
                            {{ __('medical.save', [], null, 'Save') }}
                        </span>
                    </button>

                    <button
                        type="button"
                        class="medical-btn medical-btn-secondary"
                        wire:click="resetForm"
                        wire:loading.attr="disabled"
                    >
                        {{ __('medical.cancel', [], null, 'Cancel') }}
                    </button>

                </div>

            </form>

        </div>

    @endif


    {{-- =========================================================
        Staff List
    ========================================================== --}}
    <div class="medical-card">

        <div class="medical-card-header">

            <div>
                <h2 class="medical-card-title">
                    {{ __('medical.staff_list', [], null, 'Medical Staff List') }}
                </h2>

                <p class="medical-card-description">
                    {{ __('medical.staff_list_description', [], null, 'Medical staff currently registered in the medical portal.') }}
                </p>
            </div>

            <div class="medical-record-count">
                {{ $totalStaff }}
                {{ __('medical.records', [], null, 'records') }}
            </div>

        </div>


        <div class="medical-table-wrapper">

            <table class="medical-table">

                <thead>
                    <tr>

                        <th>
                            {{ __('medical.name', [], null, 'Name') }}
                        </th>

                        <th>
                            {{ __('medical.profession', [], null, 'Profession') }}
                        </th>

                        <th>
                            {{ __('medical.specialization', [], null, 'Specialization') }}
                        </th>

                        <th>
                            {{ __('medical.clinic', [], null, 'Medical Point') }}
                        </th>

                        <th>
                            {{ __('medical.status', [], null, 'Status') }}
                        </th>

                    </tr>
                </thead>


                <tbody>

                    @forelse($staff as $s)

                        <tr>

                            {{-- Name --}}
                            <td>

                                <div class="medical-person">

                                    <div class="medical-avatar">
                                        {{ mb_substr($personName($s), 0, 1) }}
                                    </div>

                                    <div>

                                        <div class="medical-person-name">
                                            {{ $personName($s) }}
                                        </div>

                                        @if(!empty($s->staff_code))

                                            <div class="medical-person-code">
                                                {{ $s->staff_code }}
                                            </div>

                                        @endif

                                    </div>

                                </div>

                            </td>


                            {{-- Profession --}}
                            <td>

                                <span class="medical-profession">
                                    {{ $professionLabel($s->profession) }}
                                </span>

                            </td>


                            {{-- Specialization --}}
                            <td>

                                @if($s->specialization)

                                    <span class="medical-text">
                                        {{ $s->specialization }}
                                    </span>

                                @else

                                    <span class="medical-empty-value">
                                        —
                                    </span>

                                @endif

                            </td>


                            {{-- Clinic --}}
                            <td>

                                <div class="medical-clinic">

                                    <span class="medical-clinic-icon">
                                        +
                                    </span>

                                    <span>
                                        {{ $clinicName($s) }}
                                    </span>

                                </div>

                            </td>


                            {{-- Status --}}
                            <td>

                                @if($s->status === 'active')

                                    <span class="medical-status medical-status-active">
                                        <span class="medical-status-dot"></span>
                                        {{ __('medical.active', [], null, 'Active') }}
                                    </span>

                                @else

                                    <span class="medical-status medical-status-inactive">
                                        <span class="medical-status-dot"></span>
                                        {{ __('medical.inactive', [], null, 'Inactive') }}
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="5">

                                <div class="medical-empty-state">

                                    <div class="medical-empty-icon">
                                        👨‍⚕️
                                    </div>

                                    <h3>
                                        {{ __('medical.no_data', [], null, 'No data available') }}
                                    </h3>

                                    <p>
                                        {{ __('medical.no_staff_found', [], null, 'No medical staff members have been registered yet.') }}
                                    </p>

                                    <button
                                        type="button"
                                        class="medical-btn medical-btn-primary"
                                        wire:click="$set('showForm', true)"
                                    >
                                        + {{ __('medical.add_staff', [], null, 'Add Medical Staff') }}
                                    </button>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>


    {{-- =========================================================
        Styles
    ========================================================== --}}
    <style>

        .medical-page {
            direction: rtl;
            padding: 24px;
            max-width: 1600px;
            margin: 0 auto;
        }

        .medical-page-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 22px;
        }

        .medical-eyebrow {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 999px;
            background: #f0fdf4;
            color: #15803d;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .medical-page-title {
            margin: 0;
            font-size: 29px;
            line-height: 1.25;
            font-weight: 850;
            color: #0f172a;
        }

        .medical-page-description {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 14px;
            line-height: 1.8;
        }

        .medical-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .medical-btn {
            border: 0;
            min-height: 43px;
            padding: 0 16px;
            border-radius: 11px;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: .18s ease;
            text-decoration: none;
        }

        .medical-btn:hover {
            transform: translateY(-1px);
        }

        .medical-btn:disabled {
            opacity: .65;
            cursor: not-allowed;
            transform: none;
        }

        .medical-btn-primary {
            background: #15803d;
            color: #fff;
        }

        .medical-btn-primary:hover {
            background: #166534;
        }

        .medical-btn-secondary {
            background: #f8fafc;
            color: #334155;
            border: 1px solid #cbd5e1;
        }

        .medical-btn-secondary:hover {
            background: #f1f5f9;
        }

        .medical-btn-icon {
            font-size: 18px;
            line-height: 1;
        }


        /* Alerts */

        .medical-alert {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            padding: 13px 15px;
            border-radius: 13px;
            margin-bottom: 18px;
            font-size: 13px;
            line-height: 1.8;
        }

        .medical-alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .medical-alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .medical-alert-icon {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 25px;
            font-weight: 900;
        }

        .medical-alert ul {
            margin: 6px 0 0;
            padding-right: 18px;
        }


        /* Stats */

        .medical-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .medical-stat-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 17px;
            padding: 17px;
            display: flex;
            align-items: center;
            gap: 13px;
            box-shadow: 0 5px 18px rgba(15, 23, 42, .04);
        }

        .medical-stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 900;
            flex: 0 0 45px;
        }

        .medical-stat-blue {
            background: #eff6ff;
            color: #2563eb;
        }

        .medical-stat-green {
            background: #f0fdf4;
            color: #15803d;
        }

        .medical-stat-red {
            background: #fef2f2;
            color: #dc2626;
        }

        .medical-stat-purple {
            background: #faf5ff;
            color: #9333ea;
        }

        .medical-stat-label {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .medical-stat-value {
            color: #0f172a;
            font-size: 23px;
            line-height: 1;
            font-weight: 850;
        }


        /* Cards */

        .medical-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            box-shadow: 0 5px 20px rgba(15, 23, 42, .04);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .medical-form-card {
            padding: 0;
        }

        .medical-card-header {
            padding: 19px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .medical-card-title {
            margin: 0;
            color: #0f172a;
            font-size: 17px;
            font-weight: 850;
        }

        .medical-card-description {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.7;
        }

        .medical-close-btn {
            width: 35px;
            height: 35px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #64748b;
            border-radius: 10px;
            cursor: pointer;
            font-size: 22px;
            line-height: 1;
        }

        .medical-close-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
        }


        /* Form */

        .medical-form-grid {
            padding: 20px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 17px;
        }

        .medical-field {
            display: flex;
            flex-direction: column;
            gap: 7px;
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

        .medical-field input,
        .medical-field select,
        .medical-field textarea {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #cbd5e1;
            border-radius: 11px;
            background: #fff;
            color: #0f172a;
            padding: 11px 12px;
            font-size: 13px;
            outline: none;
            transition: .18s ease;
        }

        .medical-field input,
        .medical-field select {
            min-height: 43px;
        }

        .medical-field textarea {
            resize: vertical;
            min-height: 105px;
            line-height: 1.8;
        }

        .medical-field input:focus,
        .medical-field select:focus,
        .medical-field textarea:focus {
            border-color: #15803d;
            box-shadow: 0 0 0 3px rgba(21, 128, 61, .09);
        }

        .medical-input-error {
            border-color: #dc2626 !important;
        }

        .medical-error {
            color: #dc2626;
            font-size: 11px;
            font-weight: 700;
        }

        .medical-form-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 16px 20px;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
        }


        /* Table */

        .medical-table-wrapper {
            overflow-x: auto;
        }

        .medical-table {
            width: 100%;
            min-width: 850px;
            border-collapse: collapse;
        }

        .medical-table thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 11px;
            font-weight: 850;
            white-space: nowrap;
            padding: 13px 15px;
            text-align: right;
            border-bottom: 1px solid #e2e8f0;
        }

        .medical-table tbody td {
            padding: 14px 15px;
            color: #334155;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .medical-table tbody tr {
            transition: .15s ease;
        }

        .medical-table tbody tr:hover {
            background: #fafafa;
        }

        .medical-table tbody tr:last-child td {
            border-bottom: 0;
        }


        /* Person */

        .medical-person {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .medical-avatar {
            width: 39px;
            height: 39px;
            border-radius: 11px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 900;
            flex: 0 0 39px;
        }

        .medical-person-name {
            color: #0f172a;
            font-weight: 800;
            line-height: 1.5;
        }

        .medical-person-code {
            color: #94a3b8;
            font-size: 11px;
            margin-top: 2px;
        }

        .medical-text {
            color: #475569;
        }

        .medical-empty-value {
            color: #94a3b8;
        }


        /* Profession */

        .medical-profession {
            display: inline-flex;
            align-items: center;
            min-height: 29px;
            padding: 4px 9px;
            border-radius: 8px;
            background: #f8fafc;
            color: #475569;
            border: 1px solid #e2e8f0;
            font-size: 11px;
            font-weight: 800;
        }


        /* Clinic */

        .medical-clinic {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
        }

        .medical-clinic-icon {
            width: 25px;
            height: 25px;
            border-radius: 8px;
            background: #f0fdf4;
            color: #15803d;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
        }


        /* Status */

        .medical-status {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-height: 29px;
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 850;
            white-space: nowrap;
        }

        .medical-status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            display: inline-block;
        }

        .medical-status-active {
            background: #f0fdf4;
            color: #15803d;
        }

        .medical-status-active .medical-status-dot {
            background: #16a34a;
        }

        .medical-status-inactive {
            background: #fef2f2;
            color: #dc2626;
        }

        .medical-status-inactive .medical-status-dot {
            background: #dc2626;
        }


        /* Record count */

        .medical-record-count {
            padding: 6px 10px;
            border-radius: 8px;
            background: #f8fafc;
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }


        /* Empty */

        .medical-empty-state {
            text-align: center;
            padding: 55px 20px;
        }

        .medical-empty-icon {
            width: 58px;
            height: 58px;
            margin: 0 auto 13px;
            border-radius: 17px;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
        }

        .medical-empty-state h3 {
            margin: 0;
            color: #334155;
            font-size: 15px;
            font-weight: 850;
        }

        .medical-empty-state p {
            margin: 7px 0 17px;
            color: #94a3b8;
            font-size: 12px;
        }


        /* Responsive */

        @media (max-width: 1100px) {

            .medical-stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

        }

        @media (max-width: 800px) {

            .medical-page {
                padding: 16px;
            }

            .medical-page-header {
                align-items: stretch;
                flex-direction: column;
            }

            .medical-header-actions {
                width: 100%;
            }

            .medical-header-actions .medical-btn {
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

            .medical-stats-grid {
                grid-template-columns: 1fr;
            }

            .medical-page-title {
                font-size: 24px;
            }

            .medical-card-header {
                align-items: flex-start;
            }

            .medical-form-actions {
                flex-direction: column;
            }

            .medical-form-actions .medical-btn {
                width: 100%;
            }

        }

    </style>

</div>

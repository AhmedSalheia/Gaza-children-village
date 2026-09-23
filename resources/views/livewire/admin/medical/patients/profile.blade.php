@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';

    $patientName = $isArabic
        ? $p->full_name_ar
        : ($p->full_name_en ?: $p->full_name_ar);

    $clinicName = $isArabic
        ? $p->clinic_ar
        : ($p->clinic_en ?: $p->clinic_ar);

    $genderLabel = match($p->gender) {
        'male' => __('medical.male', [], null, 'Male'),
        'female' => __('medical.female', [], null, 'Female'),
        default => $p->gender ?: '—',
    };

    $statusClass = match($p->status) {
        'active' => 'medical-status-green',
        'inactive' => 'medical-status-gray',
        default => 'medical-status-gray',
    };
@endphp

<div class="medical-page">

    {{-- Medical Navigation --}}
    @include('livewire.admin.medical._nav')


    {{-- Header --}}
    <div class="medical-page-header">

        <div>

            <div class="medical-eyebrow">
                {{ __('medical.patients', [], null, 'Patients') }}
            </div>

            <h1 class="medical-page-title">
                {{ $patientName }}
            </h1>

            <p class="medical-page-description">
                {{ __('medical.patient_profile_description', [], null, 'Medical profile, visits and prescriptions.') }}
            </p>

        </div>


        <div class="medical-page-header-actions">

            <a
                href="{{ route('admin.medical.patients.index') }}"
                wire:navigate
                class="medical-btn medical-btn-secondary"
            >
                <span>←</span>

                {{ __('medical.back_to_patients', [], null, 'Back to patients') }}
            </a>

        </div>

    </div>


    {{-- Patient Identity --}}
    <section class="medical-card patient-identity-card">

        <div class="patient-identity">

            <div class="patient-avatar">
                {{ mb_substr($patientName, 0, 1) }}
            </div>


            <div class="patient-identity-main">

                <div class="patient-name">
                    {{ $patientName }}
                </div>

                <div class="patient-code">
                    {{ $p->patient_code }}
                </div>

            </div>


            <div class="patient-status">

                <span class="medical-status {{ $statusClass }}">
                    {{ __('medical.' . $p->status, [], null, ucfirst($p->status)) }}
                </span>

            </div>

        </div>

    </section>


    {{-- Patient Information --}}
    <section class="medical-card">

        <div class="medical-card-header">

            <div>

                <h2 class="medical-card-title">
                    {{ __('medical.patient_information', [], null, 'Patient Information') }}
                </h2>

                <p class="medical-card-description">
                    {{ __('medical.patient_information_description', [], null, 'Basic identification and medical profile information.') }}
                </p>

            </div>

            <div class="medical-section-icon">
                i
            </div>

        </div>


        <div class="patient-info-grid">

            {{-- Patient Code --}}
            <div class="patient-info-item">

                <span>
                    {{ __('medical.patient_code', [], null, 'Patient Code') }}
                </span>

                <strong class="medical-code">
                    {{ $p->patient_code }}
                </strong>

            </div>


            {{-- Student Code --}}
            <div class="patient-info-item">

                <span>
                    {{ __('medical.student_code', [], null, 'Student Code') }}
                </span>

                <strong>
                    {{ $p->student_code ?: '—' }}
                </strong>

            </div>


            {{-- Gender --}}
            <div class="patient-info-item">

                <span>
                    {{ __('medical.gender', [], null, 'Gender') }}
                </span>

                <strong>
                    {{ $genderLabel }}
                </strong>

            </div>


            {{-- Birth Date --}}
            <div class="patient-info-item">

                <span>
                    {{ __('medical.birth_date', [], null, 'Birth Date') }}
                </span>

                <strong>
                    {{ $p->birth_date ?: '—' }}
                </strong>

            </div>


            {{-- Medical Point --}}
            <div class="patient-info-item">

                <span>
                    {{ __('medical.medical_point', [], null, 'Medical Point') }}
                </span>

                <strong>
                    {{ $clinicName ?: '—' }}
                </strong>

            </div>


            {{-- Created --}}
            <div class="patient-info-item">

                <span>
                    {{ __('medical.registered_at', [], null, 'Registered At') }}
                </span>

                <strong>
                    {{ $p->created_at
                        ? \Illuminate\Support\Carbon::parse($p->created_at)->format('Y-m-d')
                        : '—'
                    }}
                </strong>

            </div>

        </div>

    </section>


    {{-- Summary --}}
    <div class="medical-stats-grid medical-profile-stats">

        <div class="medical-stat-card">

            <div class="medical-stat-icon medical-stat-icon-blue">
                +
            </div>

            <div class="medical-stat-content">

                <span class="medical-stat-label">
                    {{ __('medical.visits', [], null, 'Medical Visits') }}
                </span>

                <strong class="medical-stat-value">
                    {{ $visitsCount }}
                </strong>

                <span class="medical-stat-description">
                    {{ __('medical.patient_visits_description', [], null, 'Recorded medical visits') }}
                </span>

            </div>

        </div>


        <div class="medical-stat-card">

            <div class="medical-stat-icon medical-stat-icon-green">
                Rx
            </div>

            <div class="medical-stat-content">

                <span class="medical-stat-label">
                    {{ __('medical.prescriptions', [], null, 'Prescriptions') }}
                </span>

                <strong class="medical-stat-value">
                    {{ $prescriptionsCount }}
                </strong>

                <span class="medical-stat-description">
                    {{ __('medical.patient_prescriptions_description', [], null, 'Recorded prescriptions') }}
                </span>

            </div>

        </div>

    </div>


    {{-- Visits --}}
    <section class="medical-card medical-table-card">

        <div class="medical-card-header">

            <div>

                <h2 class="medical-card-title">
                    {{ __('medical.visit_history', [], null, 'Visit History') }}
                </h2>

                <p class="medical-card-description">
                    {{ __('medical.visit_history_description', [], null, 'Recent medical visits for this patient.') }}
                </p>

            </div>

            <div class="medical-card-badge">
                {{ $visitsCount }}
            </div>

        </div>


        @if($visits->isNotEmpty())

            <div class="medical-table-wrapper">

                <table class="medical-table">

                    <thead>

                        <tr>

                            <th>
                                {{ __('medical.date', [], null, 'Date') }}
                            </th>

                            <th>
                                {{ __('medical.medical_point', [], null, 'Medical Point') }}
                            </th>

                            <th>
                                {{ __('medical.doctor', [], null, 'Doctor') }}
                            </th>

                            <th>
                                {{ __('medical.chief_complaint', [], null, 'Chief Complaint') }}
                            </th>

                            <th>
                                {{ __('medical.status', [], null, 'Status') }}
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @foreach($visits as $visit)

                            @php
                                $visitStatusClass = match($visit->status) {
                                    'completed' => 'medical-status-green',
                                    'open',
                                    'in_progress' => 'medical-status-blue',
                                    'cancelled' => 'medical-status-red',
                                    default => 'medical-status-gray',
                                };
                            @endphp

                            <tr>

                                <td>

                                    <div class="medical-date">
                                        {{ \Illuminate\Support\Carbon::parse($visit->visited_at)->format('Y-m-d') }}
                                    </div>

                                    <div class="medical-table-secondary">
                                        {{ \Illuminate\Support\Carbon::parse($visit->visited_at)->format('H:i') }}
                                    </div>

                                </td>


                                <td>

                                    {{ $isArabic
                                        ? ($visit->clinic_ar ?: '—')
                                        : ($visit->clinic_en ?: $visit->clinic_ar ?: '—')
                                    }}

                                </td>


                                <td>

                                    @if($visit->doctor_ar)

                                        {{ $isArabic
                                            ? $visit->doctor_ar
                                            : ($visit->doctor_en ?: $visit->doctor_ar)
                                        }}

                                    @else

                                        <span class="medical-muted">
                                            —
                                        </span>

                                    @endif

                                </td>


                                <td>

                                    <div class="visit-complaint">

                                        {{ $visit->chief_complaint ?: '—' }}

                                    </div>

                                </td>


                                <td>

                                    <span class="medical-status {{ $visitStatusClass }}">

                                        {{ __('medical.' . $visit->status, [], null, ucfirst($visit->status)) }}

                                    </span>

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
                    {{ __('medical.no_visits', [], null, 'No visits') }}
                </h3>

                <p>
                    {{ __('medical.no_visits_description', [], null, 'No medical visits have been recorded for this patient.') }}
                </p>

            </div>

        @endif

    </section>


    {{-- Prescriptions --}}
    <section class="medical-card medical-table-card">

        <div class="medical-card-header">

            <div>

                <h2 class="medical-card-title">
                    {{ __('medical.prescription_history', [], null, 'Prescription History') }}
                </h2>

                <p class="medical-card-description">
                    {{ __('medical.prescription_history_description', [], null, 'Recent prescriptions issued for this patient.') }}
                </p>

            </div>

            <div class="medical-card-badge">
                {{ $prescriptionsCount }}
            </div>

        </div>


        @if($rx->isNotEmpty())

            <div class="medical-table-wrapper">

                <table class="medical-table">

                    <thead>

                        <tr>

                            <th>
                                {{ __('medical.prescription_number', [], null, 'Prescription #') }}
                            </th>

                            <th>
                                {{ __('medical.date', [], null, 'Date') }}
                            </th>

                            <th>
                                {{ __('medical.doctor', [], null, 'Doctor') }}
                            </th>

                            <th>
                                {{ __('medical.status', [], null, 'Status') }}
                            </th>

                            <th>
                                {{ __('medical.notes', [], null, 'Notes') }}
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @foreach($rx as $prescription)

                            @php
                                $rxStatusClass = match($prescription->status) {
                                    'prescribed' => 'medical-status-blue',
                                    'dispensed' => 'medical-status-green',
                                    'cancelled' => 'medical-status-red',
                                    default => 'medical-status-gray',
                                };
                            @endphp

                            <tr>

                                <td>

                                    <span class="medical-code">
                                        {{ $prescription->prescription_number }}
                                    </span>

                                </td>


                                <td>

                                    <div class="medical-date">

                                        {{ \Illuminate\Support\Carbon::parse(
                                            $prescription->prescribed_at
                                        )->format('Y-m-d') }}

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

                                    <span class="medical-status {{ $rxStatusClass }}">

                                        {{ __('medical.' . $prescription->status, [], null, ucfirst($prescription->status)) }}

                                    </span>

                                </td>


                                <td>

                                    <div class="medical-notes">

                                        {{ $prescription->notes ?: '—' }}

                                    </div>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

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
                    {{ __('medical.no_prescriptions_description', [], null, 'No prescriptions have been recorded for this patient.') }}
                </p>

            </div>

        @endif

    </section>


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

        .patient-identity-card {
            padding: 22px 24px;
        }

        .patient-identity {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .patient-avatar {
            width: 58px;
            height: 58px;
            min-width: 58px;
            border-radius: 17px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0fdf4;
            color: #15803d;
            font-size: 23px;
            font-weight: 900;
        }

        .patient-identity-main {
            flex: 1;
            min-width: 0;
        }

        .patient-name {
            color: #0f172a;
            font-size: 21px;
            font-weight: 900;
        }

        .patient-code {
            margin-top: 4px;
            color: #64748b;
            font-family: monospace;
            font-size: 12px;
            direction: ltr;
            text-align: right;
        }

        .patient-status {
            margin-right: auto;
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
            background: #eff6ff;
            color: #2563eb;
            font-size: 16px;
            font-weight: 900;
        }

        .patient-info-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1px;
            background: #eef2f7;
        }

        .patient-info-item {
            background: #fff;
            padding: 20px 22px;
        }

        .patient-info-item span {
            display: block;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .patient-info-item strong {
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
        }

        .medical-code {
            display: inline-flex;
            padding: 6px 9px;
            border-radius: 8px;
            background: #f8fafc;
            color: #475569;
            font-family: monospace;
            font-size: 12px;
            direction: ltr;
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

        .medical-stat-icon-green {
            background: #f0fdf4;
            color: #16a34a;
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

        .medical-table-card {
            margin-bottom: 20px;
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

        .medical-table-secondary {
            margin-top: 4px;
            color: #94a3b8;
            font-size: 11px;
        }

        .medical-date {
            color: #334155;
            font-weight: 700;
            direction: ltr;
            text-align: right;
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

        .medical-status-blue {
            background: #eff6ff;
            color: #2563eb;
        }

        .medical-status-red {
            background: #fef2f2;
            color: #dc2626;
        }

        .medical-status-gray {
            background: #f1f5f9;
            color: #64748b;
        }

        .medical-muted {
            color: #94a3b8;
        }

        .visit-complaint,
        .medical-notes {
            max-width: 260px;
            line-height: 1.7;
            color: #475569;
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

        .medical-btn {
            min-height: 44px;
            padding: 0 18px;
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

        .medical-btn-secondary {
            background: #fff;
            color: #334155;
            border-color: #cbd5e1;
        }

        .medical-btn-secondary:hover {
            background: #f8fafc;
        }

        @media (max-width: 900px) {
            .patient-info-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            .medical-page-header {
                flex-direction: column;
            }

            .medical-page-header-actions,
            .medical-page-header-actions .medical-btn {
                width: 100%;
            }

            .patient-identity {
                align-items: flex-start;
            }

            .patient-status {
                margin-right: 0;
            }

            .patient-info-grid {
                grid-template-columns: 1fr;
            }

            .medical-stats-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 550px) {
            .medical-page-title {
                font-size: 24px;
            }

            .patient-identity {
                flex-wrap: wrap;
            }

            .patient-status {
                width: 100%;
            }

            .medical-card-header {
                padding: 18px;
            }
        }
    </style>

</div>

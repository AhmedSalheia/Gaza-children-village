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
                {{ __('medical.clinics', [], null, 'Medical Points') }}
            </h1>

            <p class="medical-page-description">
                {{ __('medical.clinics_description', [], null, 'Manage and monitor the medical points serving GCV institutions.') }}
            </p>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- Statistics --}}
    {{-- ========================================================= --}}

    <div class="medical-stats-grid">

        {{-- Total --}}
        <div class="medical-stat-card">

            <div class="medical-stat-icon medical-stat-icon-blue">
                +
            </div>

            <div class="medical-stat-content">

                <span class="medical-stat-label">
                    {{ __('medical.total_clinics', [], null, 'Total Medical Points') }}
                </span>

                <strong class="medical-stat-value">
                    {{ $totalClinics }}
                </strong>

                <span class="medical-stat-description">
                    {{ __('medical.total_clinics_description', [], null, 'Registered medical points') }}
                </span>

            </div>

        </div>


        {{-- Active --}}
        <div class="medical-stat-card">

            <div class="medical-stat-icon medical-stat-icon-green">
                ✓
            </div>

            <div class="medical-stat-content">

                <span class="medical-stat-label">
                    {{ __('medical.active_clinics', [], null, 'Active Medical Points') }}
                </span>

                <strong class="medical-stat-value">
                    {{ $activeClinics }}
                </strong>

                <span class="medical-stat-description">
                    {{ __('medical.active_clinics_description', [], null, 'Currently operational') }}
                </span>

            </div>

        </div>


        {{-- Inactive --}}
        <div class="medical-stat-card">

            <div class="medical-stat-icon medical-stat-icon-gray">
                —
            </div>

            <div class="medical-stat-content">

                <span class="medical-stat-label">
                    {{ __('medical.inactive_clinics', [], null, 'Inactive Medical Points') }}
                </span>

                <strong class="medical-stat-value">
                    {{ $inactiveClinics }}
                </strong>

                <span class="medical-stat-description">
                    {{ __('medical.inactive_clinics_description', [], null, 'Currently inactive') }}
                </span>

            </div>

        </div>


        {{-- Active staff --}}
        <div class="medical-stat-card">

            <div class="medical-stat-icon medical-stat-icon-orange">
                ♥
            </div>

            <div class="medical-stat-content">

                <span class="medical-stat-label">
                    {{ __('medical.active_medical_staff', [], null, 'Active Medical Staff') }}
                </span>

                <strong class="medical-stat-value">
                    {{ $activeStaff }}
                </strong>

                <span class="medical-stat-description">
                    {{ __('medical.active_medical_staff_description', [], null, 'Staff currently assigned') }}
                </span>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- Search --}}
    {{-- ========================================================= --}}

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
                    placeholder="{{ __('medical.search_clinics', [], null, 'Search by medical point name or code...') }}"
                >

                @if($q !== '')

                    <button
                        type="button"
                        wire:click="clearSearch"
                        class="medical-search-clear"
                    >
                        ×
                    </button>

                @endif

            </div>

            <div class="medical-results-count">

                <span>
                    {{ __('medical.results', [], null, 'Results') }}
                </span>

                <strong>
                    {{ $clinics->count() }}
                </strong>

            </div>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- Clinics --}}
    {{-- ========================================================= --}}

    <section class="medical-card medical-table-card">

        <div class="medical-card-header">

            <div>

                <h2 class="medical-card-title">
                    {{ __('medical.medical_points_list', [], null, 'Medical Points List') }}
                </h2>

                <p class="medical-card-description">
                    {{ __('medical.medical_points_list_description', [], null, 'Medical points and their assigned medical teams.') }}
                </p>

            </div>

            <div class="medical-card-badge">
                {{ $clinics->count() }}
            </div>

        </div>


        @if($clinics->count())

            <div class="medical-table-wrapper">

                <table class="medical-table">

                    <thead>

                        <tr>

                            <th>
                                {{ __('medical.medical_point', [], null, 'Medical Point') }}
                            </th>

                            <th>
                                {{ __('medical.code', [], null, 'Code') }}
                            </th>

                            <th>
                                {{ __('medical.status', [], null, 'Status') }}
                            </th>

                            <th>
                                {{ __('medical.medical_staff', [], null, 'Medical Staff') }}
                            </th>

                            <th>
                                {{ __('medical.active_staff', [], null, 'Active Staff') }}
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @foreach($clinics as $clinic)

                            @php

                                $clinicName = $isArabic
                                    ? $clinic->name_ar
                                    : ($clinic->name_en ?: $clinic->name_ar);

                                $staffCount = (int) (
                                    $staffStats[$clinic->id] ?? 0
                                );

                                $activeStaffCount = (int) (
                                    $activeStaffStats[$clinic->id] ?? 0
                                );

                            @endphp

                            <tr>

                                {{-- Medical Point --}}
                                <td>

                                    <div class="clinic-cell">

                                        <div class="clinic-icon">
                                            +
                                        </div>

                                        <div>

                                            <div class="medical-table-primary">
                                                {{ $clinicName }}
                                            </div>

                                            <div class="medical-table-secondary">
                                                {{ __('medical.medical_point', [], null, 'Medical Point') }}
                                            </div>

                                        </div>

                                    </div>

                                </td>


                                {{-- Code --}}
                                <td>

                                    <span class="medical-code">
                                        {{ $clinic->code }}
                                    </span>

                                </td>


                                {{-- Status --}}
                                <td>

                                    @if($clinic->is_active)

                                        <span class="medical-status medical-status-green">

                                            <span class="status-dot"></span>

                                            {{ __('medical.active', [], null, 'Active') }}

                                        </span>

                                    @else

                                        <span class="medical-status medical-status-gray">

                                            <span class="status-dot"></span>

                                            {{ __('medical.inactive', [], null, 'Inactive') }}

                                        </span>

                                    @endif

                                </td>


                                {{-- Total Staff --}}
                                <td>

                                    <div class="staff-count-cell">

                                        <strong>
                                            {{ $staffCount }}
                                        </strong>

                                        <span>
                                            {{ __('medical.members', [], null, 'members') }}
                                        </span>

                                    </div>

                                </td>


                                {{-- Active Staff --}}
                                <td>

                                    <div class="staff-count-cell">

                                        <strong class="active-number">
                                            {{ $activeStaffCount }}
                                        </strong>

                                        <span>
                                            {{ __('medical.active', [], null, 'active') }}
                                        </span>

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
                    +
                </div>

                <h3>
                    {{ __('medical.no_clinics', [], null, 'No medical points found') }}
                </h3>

                <p>

                    @if($q !== '')

                        {{ __('medical.no_clinics_search_description', [], null, 'No medical points match your search.') }}

                    @else

                        {{ __('medical.no_clinics_description', [], null, 'No medical points have been registered yet.') }}

                    @endif

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
        }

        .medical-page-description {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 14px;
            line-height: 1.8;
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
            font-size: 17px;
            font-weight: 900;
        }

        .medical-stat-icon-blue {
            background: #eff6ff;
            color: #2563eb;
        }

        .medical-stat-icon-green {
            background: #f0fdf4;
            color: #15803d;
        }

        .medical-stat-icon-orange {
            background: #fff7ed;
            color: #ea580c;
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
            gap: 15px;
            padding: 17px 20px;
        }

        .medical-search {
            position: relative;
            flex: 1;
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
        }

        .medical-results-count {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0 4px;
            white-space: nowrap;
            color: #94a3b8;
            font-size: 12px;
        }

        .medical-results-count strong {
            min-width: 30px;
            height: 30px;
            padding: 0 8px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f0fdf4;
            color: #15803d;
            font-size: 12px;
        }

        .medical-table-card {
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

        .medical-card-badge {
            min-width: 40px;
            height: 36px;
            padding: 0 10px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0fdf4;
            color: #15803d;
            font-size: 12px;
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
            padding: 17px 20px;
            border-top: 1px solid #eef2f7;
            color: #334155;
            font-size: 13px;
            vertical-align: middle;
        }

        .medical-table tbody tr:hover {
            background: #fafafa;
        }

        .clinic-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .clinic-icon {
            width: 40px;
            height: 40px;
            min-width: 40px;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            color: #2563eb;
            font-size: 18px;
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
            padding: 5px 8px;
            border-radius: 7px;
            background: #f8fafc;
            color: #475569;
            font-family: monospace;
            font-size: 11px;
            direction: ltr;
        }

        .medical-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
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

        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .staff-count-cell {
            display: flex;
            align-items: baseline;
            gap: 6px;
            white-space: nowrap;
        }

        .staff-count-cell strong {
            color: #0f172a;
            font-size: 16px;
            font-weight: 900;
        }

        .staff-count-cell .active-number {
            color: #15803d;
        }

        .staff-count-cell span {
            color: #94a3b8;
            font-size: 11px;
        }

        .medical-empty {
            padding: 65px 25px;
            text-align: center;
        }

        .medical-empty-icon {
            width: 55px;
            height: 55px;
            margin: 0 auto 15px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            color: #2563eb;
            font-size: 20px;
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

        @media (max-width: 1100px) {

            .medical-stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

        }

        @media (max-width: 700px) {

            .medical-page-header {
                flex-direction: column;
            }

            .medical-filter-inner {
                flex-direction: column;
                align-items: stretch;
            }

            .medical-results-count {
                justify-content: space-between;
            }

        }

        @media (max-width: 600px) {

            .medical-stats-grid {
                grid-template-columns: 1fr;
            }

            .medical-page-title {
                font-size: 25px;
            }

            .medical-card-header {
                padding: 18px;
            }

            .medical-table th,
            .medical-table td {
                padding: 13px 14px;
            }

        }

    </style>

</div>

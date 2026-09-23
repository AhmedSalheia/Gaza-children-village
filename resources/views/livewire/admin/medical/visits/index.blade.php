@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';
@endphp

<div class="medical-page">

    {{-- Medical Navigation --}}
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
                {{ __('medical.visits', [], null, 'Medical Visits') }}
            </h1>

            <p class="medical-page-description">
                {{ __('medical.visits_description', [], null, 'Manage and monitor patient medical visits and clinical records.') }}
            </p>

        </div>

        <a
            class="medical-btn medical-btn-primary"
            href="{{ route('admin.medical.visits.create') }}"
            wire:navigate
        >
            <span class="medical-btn-icon">+</span>
            {{ __('medical.new_visit', [], null, 'New Visit') }}
        </a>

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
                    {{ __('medical.total_visits', [], null, 'Total Visits') }}
                </span>

                <strong class="medical-stat-value">
                    {{ $visits->total() }}
                </strong>

                <span class="medical-stat-description">
                    {{ __('medical.registered_visits', [], null, 'Registered medical visits') }}
                </span>

            </div>

        </div>


        {{-- Current Page --}}
        <div class="medical-stat-card">

            <div class="medical-stat-icon medical-stat-icon-green">
                ✓
            </div>

            <div class="medical-stat-content">

                <span class="medical-stat-label">
                    {{ __('medical.displayed_visits', [], null, 'Displayed Visits') }}
                </span>

                <strong class="medical-stat-value">
                    {{ $visits->count() }}
                </strong>

                <span class="medical-stat-description">
                    {{ __('medical.current_page_results', [], null, 'Results on the current page') }}
                </span>

            </div>

        </div>


        {{-- Today --}}
        <div class="medical-stat-card">

            <div class="medical-stat-icon medical-stat-icon-orange">
                ⌕
            </div>

            <div class="medical-stat-content">

                <span class="medical-stat-label">
                    {{ __('medical.today_visits', [], null, 'Today') }}
                </span>

                <strong class="medical-stat-value">
                    {{ $todayVisits ?? 0 }}
                </strong>

                <span class="medical-stat-description">
                    {{ __('medical.visits_today', [], null, 'Visits recorded today') }}
                </span>

            </div>

        </div>


        {{-- Completed --}}
        <div class="medical-stat-card">

            <div class="medical-stat-icon medical-stat-icon-purple">
                ✓
            </div>

            <div class="medical-stat-content">

                <span class="medical-stat-label">
                    {{ __('medical.completed_visits', [], null, 'Completed') }}
                </span>

                <strong class="medical-stat-value">
                    {{ $completedVisits ?? 0 }}
                </strong>

                <span class="medical-stat-description">
                    {{ __('medical.completed_visits_description', [], null, 'Completed medical visits') }}
                </span>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- Filters --}}
    {{-- ========================================================= --}}

    <section class="medical-card medical-filter-card">

        <div class="medical-filter-header">

            <div class="medical-filter-heading">

                <div class="medical-filter-icon">
                    ≡
                </div>

                <div>

                    <h2>
                        {{ __('medical.search_and_filter', [], null, 'Search & Filter') }}
                    </h2>

                    <p>
                        {{ __('medical.visits_filter_description', [], null, 'Search medical visits and filter them by status.') }}
                    </p>

                </div>

            </div>

            @if(!empty($q) || ($status ?? 'all') !== 'all')

                <button
                    type="button"
                    wire:click="clearFilters"
                    class="medical-clear-button"
                >
                    ↻
                    {{ __('medical.clear_filters', [], null, 'Clear Filters') }}
                </button>

            @endif

        </div>


        <div class="medical-filter-body">

            {{-- Search --}}
            <div class="medical-field">

                <label>
                    {{ __('medical.search', [], null, 'Search') }}
                </label>

                <div class="medical-search-wrapper">

                    <span class="medical-search-icon">
                        ⌕
                    </span>

                    <input
                        type="text"
                        wire:model.live.debounce.350ms="q"
                        placeholder="{{ __('medical.search_visits_placeholder', [], null, 'Search by visit number, patient or student code...') }}"
                    >

                </div>

            </div>


            {{-- Status --}}
            <div class="medical-field medical-status-filter">

                <label>
                    {{ __('medical.status', [], null, 'Status') }}
                </label>

                <div class="medical-status-buttons">

                    <button
                        type="button"
                        wire:click="$set('status', 'all')"
                        class="medical-status-filter-btn {{ ($status ?? 'all') === 'all' ? 'active' : '' }}"
                    >
                        {{ __('medical.all', [], null, 'All') }}
                    </button>

                    <button
                        type="button"
                        wire:click="$set('status', 'completed')"
                        class="medical-status-filter-btn {{ ($status ?? '') === 'completed' ? 'active' : '' }}"
                    >
                        {{ __('medical.completed', [], null, 'Completed') }}
                    </button>

                    <button
                        type="button"
                        wire:click="$set('status', 'open')"
                        class="medical-status-filter-btn {{ ($status ?? '') === 'open' ? 'active' : '' }}"
                    >
                        {{ __('medical.open', [], null, 'Open') }}
                    </button>

                </div>

            </div>

        </div>

    </section>


    {{-- ========================================================= --}}
    {{-- Visits Table --}}
    {{-- ========================================================= --}}

    <section class="medical-card medical-table-card">

        <div class="medical-card-header">

            <div>

                <h2 class="medical-card-title">
                    {{ __('medical.visit_list', [], null, 'Medical Visits') }}
                </h2>

                <p class="medical-card-description">
                    {{ __('medical.visit_list_description', [], null, 'Latest patient visits and clinical records.') }}
                </p>

            </div>

            <div class="medical-results-count">
                {{ $visits->total() }}
                {{ __('medical.results', [], null, 'Results') }}
            </div>

        </div>


        @if($visits->count())

            <div class="medical-table-wrapper">

                <table class="medical-table">

                    <thead>

                        <tr>

                            <th>
                                {{ __('medical.number', [], null, 'Number') }}
                            </th>

                            <th>
                                {{ __('medical.patient', [], null, 'Patient') }}
                            </th>

                            <th>
                                {{ __('medical.clinic', [], null, 'Medical Point') }}
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

                            <th class="medical-actions-column">
                                {{ __('medical.actions', [], null, 'Actions') }}
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @foreach($visits as $visit)

                            @php

                                $patientName = $isArabic
                                    ? ($visit->full_name_ar ?: $visit->full_name_en)
                                    : ($visit->full_name_en ?: $visit->full_name_ar);

                                $clinicName = $isArabic
                                    ? ($visit->clinic_ar ?: $visit->clinic_en)
                                    : ($visit->clinic_en ?: $visit->clinic_ar);

                                $doctorName = $isArabic
                                    ? ($visit->doctor_ar ?: $visit->doctor_en)
                                    : ($visit->doctor_en ?: $visit->doctor_ar);

                                $status = strtolower((string) $visit->status);

                            @endphp

                            <tr>

                                {{-- Visit Number --}}
                                <td>

                                    <span class="medical-visit-number">
                                        {{ $visit->visit_number }}
                                    </span>

                                </td>


                                {{-- Patient --}}
                                <td>

                                    <div class="medical-patient-cell">

                                        <div class="medical-patient-avatar">
                                            {{ mb_substr($patientName ?: '?', 0, 1) }}
                                        </div>

                                        <div>

                                            <div class="medical-table-primary">
                                                {{ $patientName ?: '—' }}
                                            </div>

                                            @if(!empty($visit->student_code))

                                                <div class="medical-table-secondary">
                                                    {{ $visit->student_code }}
                                                </div>

                                            @endif

                                        </div>

                                    </div>

                                </td>


                                {{-- Clinic --}}
                                <td>

                                    <div class="medical-clinic-cell">

                                        <span class="medical-clinic-icon">
                                            +
                                        </span>

                                        <span>
                                            {{ $clinicName ?: '—' }}
                                        </span>

                                    </div>

                                </td>


                                {{-- Doctor --}}
                                <td>

                                    @if($doctorName)

                                        <div class="medical-doctor-cell">

                                            <span class="medical-doctor-icon">
                                                ✓
                                            </span>

                                            <span>
                                                {{ $doctorName }}
                                            </span>

                                        </div>

                                    @else

                                        <span class="medical-not-assigned">
                                            {{ __('medical.not_assigned', [], null, 'Not Assigned') }}
                                        </span>

                                    @endif

                                </td>


                                {{-- Date --}}
                                <td>

                                    @php
                                        $visitedAt = $visit->visited_at
                                            ? \Illuminate\Support\Carbon::parse($visit->visited_at)
                                            : null;
                                    @endphp

                                    @if($visitedAt)

                                        <div class="medical-date-cell">

                                            <strong>
                                                {{ $visitedAt->format('Y-m-d') }}
                                            </strong>

                                            <span>
                                                {{ $visitedAt->format('H:i') }}
                                            </span>

                                        </div>

                                    @else

                                        —

                                    @endif

                                </td>


                                {{-- Status --}}
                                <td>

                                    @if(in_array($status, ['completed', 'complete', 'closed', 'done'], true))

                                        <span class="medical-status medical-status-green">

                                            <span class="status-dot"></span>

                                            {{ __('medical.completed', [], null, 'Completed') }}

                                        </span>

                                    @elseif(in_array($status, ['open', 'pending', 'in_progress'], true))

                                        <span class="medical-status medical-status-orange">

                                            <span class="status-dot"></span>

                                            {{ __('medical.open', [], null, 'Open') }}

                                        </span>

                                    @elseif(in_array($status, ['cancelled', 'canceled'], true))

                                        <span class="medical-status medical-status-red">

                                            <span class="status-dot"></span>

                                            {{ __('medical.cancelled', [], null, 'Cancelled') }}

                                        </span>

                                    @else

                                        <span class="medical-status medical-status-gray">

                                            <span class="status-dot"></span>

                                            {{ __('medical.' . $status, [], null, ucfirst($status ?: 'Unknown')) }}

                                        </span>

                                    @endif

                                </td>


                                {{-- Actions --}}
                                <td>

                                    @if(isset($visit->id))

                                        <a
                                            href="{{ route('admin.medical.visits.index') }}"
                                            wire:navigate
                                            class="medical-view-button"
                                        >
                                            {{ __('medical.view', [], null, 'View') }}
                                        </a>

                                    @endif

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>


            {{-- Pagination --}}

            <div class="medical-pagination">
                {{ $visits->links() }}
            </div>

        @else

            <div class="medical-empty">

                <div class="medical-empty-icon">
                    +
                </div>

                <h3>
                    {{ __('medical.no_data', [], null, 'No visits found') }}
                </h3>

                <p>
                    {{ __('medical.no_visits_description', [], null, 'There are no medical visits matching the current filters.') }}
                </p>

                <a
                    href="{{ route('admin.medical.visits.create') }}"
                    wire:navigate
                    class="medical-btn medical-btn-primary"
                >
                    +
                    {{ __('medical.new_visit', [], null, 'New Visit') }}
                </a>

            </div>

        @endif

    </section>


    {{-- ========================================================= --}}
    {{-- Styles --}}
    {{-- ========================================================= --}}

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

        .medical-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 42px;
            padding: 0 16px;
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
            box-shadow: 0 5px 12px rgba(21, 128, 61, .16);
        }

        .medical-btn-primary:hover {
            background: #166534;
            color: #fff;
        }

        .medical-btn-icon {
            font-size: 18px;
            line-height: 1;
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

        .medical-stat-icon-purple {
            background: #faf5ff;
            color: #9333ea;
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

        .medical-card {
            margin-bottom: 20px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            box-shadow: 0 5px 18px rgba(15, 23, 42, .05);
            overflow: hidden;
        }

        .medical-filter-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 20px 22px;
            border-bottom: 1px solid #eef2f7;
        }

        .medical-filter-heading {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .medical-filter-icon {
            width: 40px;
            height: 40px;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0fdf4;
            color: #15803d;
            font-size: 17px;
            font-weight: 900;
        }

        .medical-filter-heading h2 {
            margin: 0;
            color: #0f172a;
            font-size: 14px;
            font-weight: 850;
        }

        .medical-filter-heading p {
            margin: 4px 0 0;
            color: #94a3b8;
            font-size: 11px;
        }

        .medical-clear-button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 0;
            background: #f8fafc;
            color: #64748b;
            border-radius: 9px;
            padding: 8px 12px;
            font-size: 11px;
            font-weight: 800;
            cursor: pointer;
        }

        .medical-clear-button:hover {
            background: #f1f5f9;
            color: #15803d;
        }

        .medical-filter-body {
            display: grid;
            grid-template-columns: minmax(280px, 1.5fr) minmax(280px, 1fr);
            gap: 22px;
            padding: 20px 22px;
        }

        .medical-field label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-size: 12px;
            font-weight: 800;
        }

        .medical-search-wrapper {
            position: relative;
        }

        .medical-search-icon {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 17px;
            pointer-events: none;
        }

        .medical-search-wrapper input {
            width: 100%;
            height: 43px;
            padding: 0 40px 0 14px;
            border: 1px solid #dbe3ec;
            border-radius: 10px;
            background: #fff;
            color: #0f172a;
            font-size: 12px;
            outline: none;
            transition: .15s ease;
        }

        .medical-search-wrapper input:focus {
            border-color: #86efac;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, .08);
        }

        .medical-status-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .medical-status-filter-btn {
            min-height: 42px;
            padding: 0 14px;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            background: #fff;
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            cursor: pointer;
            transition: .15s ease;
        }

        .medical-status-filter-btn:hover {
            border-color: #bbf7d0;
            color: #15803d;
        }

        .medical-status-filter-btn.active {
            border-color: #15803d;
            background: #15803d;
            color: #fff;
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

        .medical-results-count {
            min-width: 45px;
            height: 36px;
            padding: 0 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
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
            padding: 16px 20px;
            border-top: 1px solid #eef2f7;
            color: #334155;
            font-size: 13px;
            vertical-align: middle;
        }

        .medical-table tbody tr {
            transition: background .12s ease;
        }

        .medical-table tbody tr:hover {
            background: #fafafa;
        }

        .medical-visit-number {
            display: inline-flex;
            padding: 7px 10px;
            border-radius: 8px;
            background: #f8fafc;
            color: #475569;
            font-family: monospace;
            font-size: 11px;
            direction: ltr;
            white-space: nowrap;
        }

        .medical-patient-cell {
            display: flex;
            align-items: center;
            gap: 11px;
            min-width: 190px;
        }

        .medical-patient-avatar {
            width: 39px;
            height: 39px;
            min-width: 39px;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            color: #2563eb;
            font-size: 14px;
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

        .medical-clinic-cell,
        .medical-doctor-cell {
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
        }

        .medical-clinic-icon,
        .medical-doctor-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 900;
        }

        .medical-clinic-icon {
            background: #f0fdf4;
            color: #15803d;
        }

        .medical-doctor-icon {
            background: #eff6ff;
            color: #2563eb;
        }

        .medical-not-assigned {
            color: #94a3b8;
            font-size: 11px;
        }

        .medical-date-cell {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .medical-date-cell strong {
            color: #334155;
            font-size: 12px;
            direction: ltr;
            text-align: right;
        }

        .medical-date-cell span {
            color: #94a3b8;
            font-size: 10px;
            direction: ltr;
            text-align: right;
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

        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .medical-view-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 32px;
            padding: 0 10px;
            border: 1px solid #dbe3ec;
            border-radius: 8px;
            background: #fff;
            color: #475569;
            text-decoration: none;
            font-size: 10px;
            font-weight: 800;
        }

        .medical-view-button:hover {
            border-color: #86efac;
            background: #f0fdf4;
            color: #15803d;
        }

        .medical-pagination {
            padding: 18px 22px;
            border-top: 1px solid #eef2f7;
        }

        .medical-empty {
            padding: 65px 25px !important;
            text-align: center !important;
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
            font-size: 21px;
            font-weight: 900;
        }

        .medical-empty h3 {
            margin: 0;
            color: #0f172a;
            font-size: 16px;
            font-weight: 800;
        }

        .medical-empty p {
            margin: 7px 0 17px;
            color: #94a3b8;
            font-size: 13px;
        }

        @media (max-width: 1100px) {

            .medical-stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .medical-filter-body {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 700px) {

            .medical-page-header {
                flex-direction: column;
            }

            .medical-btn {
                width: 100%;
            }

            .medical-filter-header {
                align-items: flex-start;
                flex-direction: column;
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

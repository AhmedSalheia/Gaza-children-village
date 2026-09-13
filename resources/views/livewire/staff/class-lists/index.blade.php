@php
    /** @var \App\Livewire\Staff\ClassLists\ClassList $this */
@endphp

<div class="class-list-page">

    {{-- =========================================================
         REMOVE YELLOW FOCUS / OUTLINE / SHADOW
    ========================================================== --}}
    <style>
        .class-list-page,
        .class-list-page *,
        .class-list-page *:focus,
        .class-list-page *:focus-visible {
            outline: none !important;
            outline-color: transparent !important;
        }

        .class-list-page .card {
            border-color: #e5e7eb !important;
            outline: none !important;
            box-shadow: none !important;
        }

        .class-list-page table,
        .class-list-page thead,
        .class-list-page tbody,
        .class-list-page tr,
        .class-list-page th,
        .class-list-page td {
            outline: none !important;
            box-shadow: none !important;
        }

        .class-list-page table {
            border: none !important;
        }

        .class-list-page tr {
            border-color: #eef2f7;
        }

        .class-list-page button:focus,
        .class-list-page button:focus-visible,
        .class-list-page select:focus,
        .class-list-page select:focus-visible,
        .class-list-page a:focus,
        .class-list-page a:focus-visible {
            outline: none !important;
            box-shadow: none !important;
        }

        .class-list-page select {
            outline: none !important;
        }

        .class-list-page button {
            outline: none !important;
        }
    </style>


    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div
        style="
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:20px;
            margin-bottom:24px;
        "
    >
        <div>
            <h1
                style="
                    margin:0;
                    font-size:28px;
                    font-weight:800;
                    color:#172033;
                "
            >
                {{ __('ui.class_lists', [], null, 'Class Lists') }}
            </h1>

            <p
                style="
                    margin:7px 0 0;
                    color:#64748b;
                    font-size:14px;
                "
            >
                {{ __('ui.class_lists_description', [], null, 'View students by class and section.') }}
            </p>
        </div>
    </div>


    {{-- =========================================================
         FILTERS
    ========================================================== --}}
    <div
        class="card"
        style="
            margin-bottom:22px;
            padding:20px;
            border:1px solid #e5e7eb;
            box-shadow:none !important;
        "
    >

        <div
            style="
                display:flex;
                align-items:center;
                justify-content:space-between;
                gap:15px;
                margin-bottom:18px;
            "
        >
            <div>
                <h2
                    style="
                        margin:0;
                        font-size:17px;
                        font-weight:750;
                        color:#172033;
                    "
                >
                    {{ __('ui.filters', [], null, 'Filters') }}
                </h2>

                <p
                    style="
                        margin:5px 0 0;
                        font-size:13px;
                        color:#64748b;
                    "
                >
                    {{ __('ui.filter_by_class_section', [], null, 'Filter students by class and section.') }}
                </p>
            </div>

            @if ($academicLevelId > 0 || $classGroupId > 0)
                <span
                    style="
                        display:inline-flex;
                        align-items:center;
                        gap:6px;
                        padding:6px 10px;
                        border-radius:999px;
                        background:#eff6ff;
                        color:#2563eb;
                        font-size:12px;
                        font-weight:700;
                    "
                >
                    {{ __('ui.filters_active', [], null, 'Filters active') }}
                </span>
            @endif
        </div>


        <div
            style="
                display:grid;
                grid-template-columns:
                    minmax(220px, 1fr)
                    minmax(220px, 1fr)
                    auto;
                gap:14px;
                align-items:end;
            "
        >

            {{-- Class --}}
            <div>
                <label
                    for="academicLevelId"
                    style="
                        display:block;
                        margin-bottom:7px;
                        font-size:13px;
                        font-weight:700;
                        color:#334155;
                    "
                >
                    {{ __('ui.class', [], null, 'Class') }}
                </label>

                <select
                    id="academicLevelId"
                    wire:model.live="academicLevelId"
                    class="form-control"
                    style="
                        width:100%;
                        min-height:42px;
                        outline:none !important;
                    "
                >
                    <option value="0">
                        {{ __('ui.all_classes', [], null, 'All Classes') }}
                    </option>

                    @foreach ($academicLevels as $level)
                        <option value="{{ $level->id }}">
                            {{ $level->name_ar }}

                            @if ($level->name_en)
                                — {{ $level->name_en }}
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>


            {{-- Section --}}
            <div>
                <label
                    for="classGroupId"
                    style="
                        display:block;
                        margin-bottom:7px;
                        font-size:13px;
                        font-weight:700;
                        color:#334155;
                    "
                >
                    {{ __('ui.section', [], null, 'Section') }}
                </label>

                <select
                    id="classGroupId"
                    wire:model.live="classGroupId"
                    class="form-control"
                    style="
                        width:100%;
                        min-height:42px;
                        outline:none !important;
                    "
                    @disabled($academicLevelId === 0)
                >
                    @if ($academicLevelId === 0)

                        <option value="0">
                            {{ __('ui.select_class_first', [], null, 'Select a class first...') }}
                        </option>

                    @else

                        <option value="0">
                            {{ __('ui.select_section', [], null, 'Select a section') }}
                        </option>

                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}">
                                {{ $section->name_ar }}

                                @if ($section->code)
                                    — {{ $section->code }}
                                @endif
                            </option>
                        @endforeach

                    @endif
                </select>
            </div>


            {{-- Reset --}}
            <div>
                <button
                    type="button"
                    wire:click="resetFilters"
                    class="btn btn--outline btn--sm"
                    style="
                        min-height:42px;
                        display:inline-flex;
                        align-items:center;
                        justify-content:center;
                        gap:7px;
                        outline:none !important;
                        box-shadow:none !important;
                    "
                >
                    <svg
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="M3 12a9 9 0 1 0 3-6.7" />
                        <polyline points="3 3 3 9 9 9" />
                    </svg>

                    {{ __('ui.reset_filters', [], null, 'Reset Filters') }}
                </button>
            </div>

        </div>


        {{-- Filter information --}}
        <div
            style="
                display:flex;
                align-items:center;
                gap:8px;
                margin-top:18px;
                padding-top:14px;
                border-top:1px solid #e5e7eb;
                font-size:12px;
                color:#64748b;
            "
        >

            @if ($academicLevelId > 0)

                <span
                    style="
                        display:inline-flex;
                        align-items:center;
                        justify-content:center;
                        width:20px;
                        height:20px;
                        border-radius:50%;
                        background:#ecfdf5;
                        color:#16a34a;
                        font-weight:700;
                    "
                >
                    ✓
                </span>

                <span>
                    {{ __('ui.class_filter_active', [], null, 'Class filter is active.') }}

                    @if ($classGroupId > 0)
                        {{ __('ui.and', [], null, 'and') }}
                        {{ __('ui.section_filter_active', [], null, 'section filter is active.') }}
                    @endif
                </span>

            @else

                <span
                    style="
                        display:inline-flex;
                        align-items:center;
                        justify-content:center;
                        width:20px;
                        height:20px;
                        border-radius:50%;
                        background:#f1f5f9;
                        color:#64748b;
                        font-weight:700;
                    "
                >
                    i
                </span>

                <span>
                    {{ __('ui.select_class_to_filter_sections', [], null, 'Select a class to display its sections.') }}
                </span>

            @endif

        </div>

    </div>


    {{-- =========================================================
         MAIN CONTENT
    ========================================================== --}}
    <div
        style="
            display:grid;
            grid-template-columns:minmax(260px, 320px) minmax(0, 1fr);
            gap:22px;
            align-items:start;
        "
    >

        {{-- =====================================================
             CLASS GROUPS
        ====================================================== --}}
        <div
            class="card"
            style="
                border:1px solid #e5e7eb;
                box-shadow:none !important;
            "
        >

            <div
                style="
                    padding:18px 18px 14px;
                    border-bottom:1px solid #e5e7eb;
                "
            >
                <h2
                    style="
                        margin:0;
                        font-size:17px;
                        font-weight:750;
                        color:#172033;
                    "
                >
                    {{ __('ui.class_groups', [], null, 'Class Groups') }}
                </h2>

                <p
                    style="
                        margin:5px 0 0;
                        font-size:13px;
                        color:#64748b;
                    "
                >
                    {{ __('ui.available_sections', [], null, 'Available sections') }}
                </p>
            </div>


            <div style="padding:10px;">

                @forelse($classGroups as $cg)

                    <button
                        type="button"
                        wire:click="$set('classGroupId', {{ $cg->id }})"
                        style="
                            width:100%;
                            text-align:start;
                            border:0;
                            background:{{ $classGroupId == $cg->id ? '#eff6ff' : 'transparent' }};
                            border-radius:10px;
                            padding:13px;
                            margin-bottom:5px;
                            cursor:pointer;
                            transition:background .15s ease;
                            outline:none !important;
                            box-shadow:none !important;
                        "
                    >

                        <div
                            style="
                                display:flex;
                                align-items:center;
                                justify-content:space-between;
                                gap:10px;
                            "
                        >
                            <strong
                                style="
                                    color:#172033;
                                    font-size:14px;
                                "
                            >
                                {{ $cg->name_ar }}
                            </strong>

                            @if ($cg->code)
                                <span
                                    style="
                                        font-size:11px;
                                        color:#64748b;
                                        font-weight:700;
                                    "
                                >
                                    {{ $cg->code }}
                                </span>
                            @endif
                        </div>


                        <div
                            style="
                                margin-top:5px;
                                font-size:12px;
                                color:#64748b;
                            "
                        >
                            {{ $cg->level_name }}

                            @if ($cg->classroom_name)
                                · {{ $cg->classroom_name }}
                            @endif
                        </div>


                        @if ($cg->lifecycle_status)
                            <div style="margin-top:8px;">

                                <span
                                    style="
                                        display:inline-flex;
                                        padding:4px 8px;
                                        border-radius:999px;
                                        background:#f1f5f9;
                                        color:#475569;
                                        font-size:11px;
                                        font-weight:700;
                                    "
                                >
                                    {{ __('ui.' . $cg->lifecycle_status, [], null, $cg->lifecycle_status) }}
                                </span>

                            </div>
                        @endif

                    </button>

                @empty

                    <div
                        style="
                            padding:30px 15px;
                            text-align:center;
                            color:#64748b;
                            font-size:13px;
                        "
                    >

                        @if ($academicLevelId > 0)

                            {{ __('ui.no_sections_for_class', [], null, 'No sections found for this class.') }}

                        @else

                            {{ __('ui.no_class_groups', [], null, 'No class groups assigned.') }}

                        @endif

                    </div>

                @endforelse

            </div>

        </div>


        {{-- =====================================================
             STUDENTS
        ====================================================== --}}
        <div
            class="card"
            style="
                border:1px solid #e5e7eb;
                box-shadow:none !important;
            "
        >

            @if ($classGroupId === 0)

                {{-- Empty state --}}
                <div
                    style="
                        padding:70px 30px;
                        text-align:center;
                    "
                >

                    <div
                        style="
                            width:54px;
                            height:54px;
                            margin:0 auto 15px;
                            border-radius:14px;
                            background:#eff6ff;
                            color:#2563eb;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                        "
                    >
                        <svg
                            width="25"
                            height="25"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                    </div>

                    <h3
                        style="
                            margin:0;
                            font-size:18px;
                            font-weight:750;
                            color:#172033;
                        "
                    >
                        {{ __('ui.select_section', [], null, 'Select a section') }}
                    </h3>

                    <p
                        style="
                            margin:7px 0 0;
                            color:#64748b;
                            font-size:13px;
                        "
                    >
                        @if ($academicLevelId === 0)

                            {{ __('ui.select_class_first', [], null, 'Select a class first, then choose a section to view its students.') }}

                        @else

                            {{ __('ui.select_section_to_view_students', [], null, 'Choose a section to view the students enrolled in it.') }}

                        @endif
                    </p>

                </div>

            @else

                @php
                    $selectedSection = $sections->firstWhere('id', $classGroupId);
                @endphp


                {{-- Students Header --}}
                <div
                    style="
                        padding:20px;
                        border-bottom:1px solid #e5e7eb;
                    "
                >

                    <div
                        style="
                            display:flex;
                            align-items:flex-start;
                            justify-content:space-between;
                            gap:18px;
                            flex-wrap:wrap;
                        "
                    >

                        <div>

                            <div
                                style="
                                    display:flex;
                                    align-items:center;
                                    gap:10px;
                                    flex-wrap:wrap;
                                "
                            >
                                <h2
                                    style="
                                        margin:0;
                                        font-size:20px;
                                        font-weight:800;
                                        color:#172033;
                                    "
                                >
                                    {{ __('ui.students', [], null, 'Students') }}

                                    <span
                                        style="
                                            font-weight:500;
                                            color:#64748b;
                                        "
                                    >
                                        ({{ $classStudents->count() }})
                                    </span>
                                </h2>

                                @if ($selectedSection?->code)

                                    <span
                                        style="
                                            padding:5px 9px;
                                            border-radius:999px;
                                            background:#eff6ff;
                                            color:#2563eb;
                                            font-size:11px;
                                            font-weight:800;
                                        "
                                    >
                                        {{ $selectedSection->code }}
                                    </span>

                                @endif

                            </div>

                            <div
                                style="
                                    margin-top:6px;
                                    font-size:13px;
                                    color:#64748b;
                                "
                            >
                                {{ $selectedSection?->level_name ?? '' }}

                                @if ($selectedSection?->classroom_name)
                                    · {{ $selectedSection->classroom_name }}
                                @endif
                            </div>

                        </div>


                        {{-- Actions --}}
                        <div
                            style="
                                display:flex;
                                align-items:center;
                                gap:8px;
                                flex-wrap:wrap;
                            "
                        >

                            {{-- Excel --}}
                            <button
                                type="button"
                                wire:click="downloadExcel"
                                wire:loading.attr="disabled"
                                wire:target="downloadExcel"
                                class="btn btn--outline btn--sm"
                                style="
                                    display:inline-flex;
                                    align-items:center;
                                    justify-content:center;
                                    gap:7px;
                                    outline:none !important;
                                    box-shadow:none !important;
                                "
                            >

                                <span
                                    wire:loading.remove
                                    wire:target="downloadExcel"
                                >
                                    <svg
                                        width="16"
                                        height="16"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        aria-hidden="true"
                                    >
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                        <polyline points="14 2 14 8 20 8" />
                                        <line x1="16" y1="13" x2="8" y2="13" />
                                        <line x1="16" y1="17" x2="8" y2="17" />
                                        <line x1="10" y1="9" x2="8" y2="9" />
                                    </svg>
                                </span>

                                <span
                                    wire:loading
                                    wire:target="downloadExcel"
                                >
                                    {{ __('ui.preparing', [], null, 'Preparing...') }}
                                </span>

                                <span
                                    wire:loading.remove
                                    wire:target="downloadExcel"
                                >
                                    {{ __('ui.download_excel', [], null, 'Download Excel') }}
                                </span>

                            </button>


                            {{-- Manage Enrollments --}}
                            @if ($canManageEnrollments)

                                <a
                                    href="{{ route('staff.enrollments.index') }}"
                                    class="btn btn--secondary btn--sm"
                                    wire:navigate
                                    style="
                                        outline:none !important;
                                        box-shadow:none !important;
                                    "
                                >
                                    {{ __('ui.manage_enrollments', [], null, 'Manage Enrollments') }}
                                </a>

                            @endif

                        </div>

                    </div>

                </div>


                {{-- =================================================
                     STUDENTS TABLE
                ================================================== --}}
                <div
                    style="
                        width:100%;
                        overflow-x:auto;
                    "
                >

                    <table
                        style="
                            width:100%;
                            min-width:760px;
                            border-collapse:collapse;
                            table-layout:fixed;
                            direction:rtl;
                            border:none !important;
                            outline:none !important;
                            box-shadow:none !important;
                        "
                    >

                        <colgroup>
                            <col style="width:6%;">
                            <col style="width:18%;">
                            <col style="width:38%;">
                            <col style="width:16%;">
                            <col style="width:22%;">
                        </colgroup>


                        <thead>

                            <tr
                                style="
                                    background:#f8fafc;
                                    border-bottom:1px solid #e5e7eb;
                                    outline:none !important;
                                    box-shadow:none !important;
                                "
                            >

                                {{-- # --}}
                                <th
                                    style="
                                        padding:13px 16px;
                                        text-align:center;
                                        font-size:12px;
                                        font-weight:800;
                                        color:#475569;
                                        white-space:nowrap;
                                        outline:none !important;
                                    "
                                >
                                    #
                                </th>


                                {{-- National ID --}}
                                <th
                                    style="
                                        padding:13px 16px;
                                        text-align:right;
                                        font-size:12px;
                                        font-weight:800;
                                        color:#475569;
                                        white-space:nowrap;
                                        outline:none !important;
                                    "
                                >
                                    {{ __('ui.national_id', [], null, 'National ID') }}
                                </th>


                                {{-- Name --}}
                                <th
                                    style="
                                        padding:13px 16px;
                                        text-align:right;
                                        font-size:12px;
                                        font-weight:800;
                                        color:#475569;
                                        white-space:nowrap;
                                        outline:none !important;
                                    "
                                >
                                    {{ __('ui.name', [], null, 'Name') }}
                                </th>


                                {{-- Status --}}
                                <th
                                    style="
                                        padding:13px 16px;
                                        text-align:center;
                                        font-size:12px;
                                        font-weight:800;
                                        color:#475569;
                                        white-space:nowrap;
                                        outline:none !important;
                                    "
                                >
                                    {{ __('ui.status', [], null, 'Status') }}
                                </th>


                                {{-- Enrolled On --}}
                                <th
                                    style="
                                        padding:13px 16px;
                                        text-align:center;
                                        font-size:12px;
                                        font-weight:800;
                                        color:#475569;
                                        white-space:nowrap;
                                        outline:none !important;
                                    "
                                >
                                    {{ __('ui.enrolled_on', [], null, 'Enrolled On') }}
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($classStudents as $index => $student)

                                @php
                                    $status = match ($student->enrollment_status) {
                                        'active' => 'active',
                                        'draft' => 'draft',
                                        default => 'closed',
                                    };
                                @endphp


                                <tr
                                    style="
                                        border-bottom:1px solid #eef2f7;
                                        outline:none !important;
                                        box-shadow:none !important;
                                    "
                                >

                                    {{-- # --}}
                                    <td
                                        style="
                                            padding:14px 16px;
                                            font-size:13px;
                                            color:#64748b;
                                            text-align:center;
                                            vertical-align:middle;
                                            white-space:nowrap;
                                            outline:none !important;
                                        "
                                    >
                                        {{ $index + 1 }}
                                    </td>


                                    {{-- National ID --}}
                                    <td
                                        style="
                                            padding:14px 16px;
                                            font-size:13px;
                                            color:#334155;
                                            text-align:right;
                                            vertical-align:middle;
                                            white-space:nowrap;
                                            outline:none !important;
                                        "
                                    >
                                        {{ $student->national_id ?? '—' }}
                                    </td>


                                    {{-- Name --}}
                                    <td
                                        style="
                                            padding:14px 16px;
                                            text-align:right;
                                            vertical-align:middle;
                                            outline:none !important;
                                        "
                                    >

                                        <a
                                            href="{{ route('staff.students.detail', ['studentProfileId' => $student->student_id]) }}"
                                            wire:navigate
                                            style="
                                                display:block;
                                                text-decoration:none;
                                                color:#172033;
                                                outline:none !important;
                                                box-shadow:none !important;
                                            "
                                        >

                                            <div
                                                style="
                                                    font-size:14px;
                                                    font-weight:750;
                                                    line-height:1.5;
                                                "
                                            >
                                                {{ $student->name_ar ?? '—' }}
                                            </div>


                                            @if ($student->name_en)

                                                <div
                                                    style="
                                                        margin-top:3px;
                                                        font-size:12px;
                                                        color:#64748b;
                                                        line-height:1.4;
                                                        direction:ltr;
                                                        text-align:right;
                                                    "
                                                >
                                                    {{ $student->name_en }}
                                                </div>

                                            @endif


                                            @if ($student->student_code)

                                                <div
                                                    style="
                                                        margin-top:3px;
                                                        font-size:11px;
                                                        color:#94a3b8;
                                                        line-height:1.4;
                                                    "
                                                >
                                                    {{ $student->student_code }}
                                                </div>

                                            @endif

                                        </a>

                                    </td>


                                    {{-- Status --}}
                                    <td
                                        style="
                                            padding:14px 16px;
                                            text-align:center;
                                            vertical-align:middle;
                                            white-space:nowrap;
                                            outline:none !important;
                                        "
                                    >

                                        <span
                                            style="
                                                display:inline-flex;
                                                align-items:center;
                                                justify-content:center;
                                                padding:5px 10px;
                                                min-width:65px;
                                                border-radius:999px;
                                                background:{{ $status === 'active' ? '#ecfdf5' : '#fef3c7' }};
                                                color:{{ $status === 'active' ? '#047857' : '#92400e' }};
                                                font-size:11px;
                                                font-weight:800;
                                            "
                                        >
                                            {{ __('ui.' . $status, [], null, ucfirst($status)) }}
                                        </span>

                                    </td>


                                    {{-- Enrolled On --}}
                                    <td
                                        style="
                                            padding:14px 16px;
                                            font-size:13px;
                                            color:#64748b;
                                            text-align:center;
                                            vertical-align:middle;
                                            white-space:nowrap;
                                            direction:ltr;
                                            outline:none !important;
                                        "
                                    >
                                        {{ $student->enrolled_on ?? '—' }}
                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td
                                        colspan="5"
                                        style="
                                            padding:50px 20px;
                                            text-align:center;
                                            color:#64748b;
                                            font-size:13px;
                                            outline:none !important;
                                        "
                                    >
                                        {{ __('ui.no_students_in_class', [], null, 'No students in this class group.') }}
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            @endif

        </div>

    </div>

</div>`

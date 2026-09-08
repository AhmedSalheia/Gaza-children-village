@php
    /** @var \App\Livewire\Staff\ClassLists\ClassList $this */
@endphp

<div>

    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div
        style="
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:var(--space-4);
            margin-block-end:var(--space-6);
        "
    >
        <div>
            <h1
                style="
                    font-size:var(--text-2xl);
                    font-weight:700;
                    color:var(--text-primary);
                    margin:0;
                    line-height:1.3;
                "
            >
                {{ __('ui.class_lists', [], null, 'Class Lists') }}
            </h1>

            <p
                style="
                    margin:var(--space-1) 0 0;
                    font-size:var(--text-sm);
                    color:var(--text-secondary);
                "
            >
                {{ __('ui.class_lists_description', [], null, 'View students by class and section.') }}
            </p>
        </div>
    </div>


    {{-- =========================================================
         FILTER CARD
    ========================================================== --}}
    <div
        style="
            background:var(--surface-primary);
            border:1px solid var(--border-color);
            border-radius:var(--radius-lg);
            margin-block-end:var(--space-6);
            overflow:hidden;
            box-shadow:0 2px 8px rgba(0,0,0,0.04);
        "
    >

        {{-- Filter Header --}}
        <div
            style="
                display:flex;
                align-items:center;
                justify-content:space-between;
                gap:var(--space-4);
                padding:var(--space-4) var(--space-5);
                border-block-end:1px solid var(--border-color);
                background:var(--surface-secondary, var(--surface-primary));
            "
        >

            <div
                style="
                    display:flex;
                    align-items:center;
                    gap:var(--space-3);
                "
            >

                {{-- Filter Icon --}}
                <div
                    style="
                        width:38px;
                        height:38px;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        border-radius:var(--radius-sm);
                        background:var(--interactive-primary);
                        color:white;
                        flex-shrink:0;
                    "
                >
                    <svg
                        width="19"
                        height="19"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/>
                    </svg>
                </div>

                <div>
                    <h2
                        style="
                            margin:0;
                            font-size:var(--text-base);
                            font-weight:700;
                            color:var(--text-primary);
                        "
                    >
                        {{ __('ui.filters', [], null, 'Filters') }}
                    </h2>

                    <p
                        style="
                            margin:2px 0 0;
                            font-size:var(--text-xs);
                            color:var(--text-secondary);
                        "
                    >
                        {{ __('ui.filter_by_class_section', [], null, 'Filter students by class and section.') }}
                    </p>
                </div>
            </div>


            {{-- Active Filters Indicator --}}
            @if($academicLevelId > 0 || $classGroupId > 0)

                <div
                    style="
                        display:flex;
                        align-items:center;
                        gap:var(--space-2);
                        padding:5px 10px;
                        border-radius:999px;
                        background:rgba(34,197,94,0.10);
                        color:var(--text-primary);
                        font-size:var(--text-xs);
                        font-weight:600;
                        white-space:nowrap;
                    "
                >
                    <span
                        style="
                            width:7px;
                            height:7px;
                            border-radius:50%;
                            background:#22c55e;
                        "
                    ></span>

                    {{ __('ui.filters_active', [], null, 'Filters active') }}
                </div>

            @endif

        </div>


        {{-- Filter Body --}}
        <div style="padding:var(--space-5);">

            <div
                style="
                    display:grid;
                    grid-template-columns:repeat(2, minmax(0, 1fr));
                    gap:var(--space-5);
                "
            >

                {{-- =====================================================
                     CLASS FILTER
                ====================================================== --}}
            <div class="form-group" style="margin:0">

    <label
        for="academicLevelId"
        class="form-label"
    >
        {{ __('ui.class', [], null, 'Class') }}
    </label>

    <select
        id="academicLevelId"
        wire:model.live="academicLevelId"
        class="form-control"
    >
        <option value="0">
            {{ __('ui.all_classes', [], null, 'All Classes') }}
        </option>

        @foreach($academicLevels as $level)

            <option value="{{ $level->id }}">
                {{ $level->name_ar }}

                @if($level->name_en)
                    — {{ $level->name_en }}
                @endif
            </option>

        @endforeach

    </select>

</div>


                {{-- =====================================================
                     SECTION FILTER
                ====================================================== --}}
      <div class="form-group" style="margin:0">

    <label
        for="classGroupId"
        class="form-label"
    >
        {{ __('ui.section', [], null, 'Section') }}
    </label>

    <select
        id="classGroupId"
        wire:model.live="classGroupId"
        class="form-control"
        @disabled($academicLevelId === 0)
    >

        @if($academicLevelId === 0)

            <option value="0">
                {{ __('ui.select_class_first', [], null, 'Select a class first...') }}
            </option>

        @else

            <option value="0">
                {{ __('ui.all_sections', [], null, 'All Sections') }}
            </option>

            @foreach($sections as $section)

                <option value="{{ $section->id }}">
                    {{ $section->name_ar }}

                    @if($section->code)
                        — {{ $section->code }}
                    @endif
                </option>

            @endforeach

        @endif

    </select>

</div>


            {{-- =====================================================
                 FILTER FOOTER
            ====================================================== --}}
            <div
                style="
                    display:flex;
                    align-items:center;
                    justify-content:space-between;
                    gap:var(--space-4);
                    margin-block-start:var(--space-5);
                    padding-block-start:var(--space-4);
                    border-block-start:1px solid var(--border-color);
                "
            >

                <div
                    style="
                        display:flex;
                        align-items:center;
                        gap:8px;
                        font-size:var(--text-xs);
                        color:var(--text-secondary);
                    "
                >

                    @if($academicLevelId > 0)

                        <span
                            style="
                                display:inline-flex;
                                align-items:center;
                                justify-content:center;
                                width:20px;
                                height:20px;
                                border-radius:50%;
                                background:rgba(34,197,94,0.10);
                                color:#16a34a;
                                font-weight:700;
                            "
                        >
                            ✓
                        </span>

                        <span>
                            {{ __('ui.class_filter_active', [], null, 'Class filter is active.') }}

                            @if($classGroupId > 0)
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
                                background:var(--surface-secondary, #f3f4f6);
                                color:var(--text-secondary);
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


                {{-- Reset --}}
                @if($academicLevelId > 0 || $classGroupId > 0)

                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="btn btn--outline btn--sm"
                        style="
                            display:inline-flex;
                            align-items:center;
                            gap:7px;
                            white-space:nowrap;
                        "
                    >

                        <svg
                            width="15"
                            height="15"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <path d="M3 12a9 9 0 1 0 3-6.7"/>
                            <path d="M3 4v5h5"/>
                        </svg>

                        {{ __('ui.reset_filters', [], null, 'Reset Filters') }}

                    </button>

                @endif

            </div>

        </div>

    </div>


    {{-- =========================================================
         MAIN CONTENT
    ========================================================== --}}
    <div
        style="
            display:grid;
            grid-template-columns:280px minmax(0, 1fr);
            gap:var(--space-6);
            align-items:start;
        "
    >


        {{-- =====================================================
             LEFT: CLASS GROUPS
        ====================================================== --}}
        <div
            style="
                background:var(--surface-primary);
                border:1px solid var(--border-color);
                border-radius:var(--radius-md);
                overflow:hidden;
            "
        >

            <div
                style="
                    padding:var(--space-4);
                    border-block-end:1px solid var(--border-color);
                "
            >

                <h2
                    style="
                        margin:0;
                        font-size:var(--text-base);
                        font-weight:700;
                        color:var(--text-primary);
                    "
                >
                    {{ __('ui.class_groups', [], null, 'Class Groups') }}
                </h2>

                <p
                    style="
                        margin:4px 0 0;
                        font-size:var(--text-xs);
                        color:var(--text-secondary);
                    "
                >
                    {{ __('ui.available_sections', [], null, 'Available sections') }}
                </p>

            </div>


            <div style="padding:var(--space-3);">

                @if($classGroups->isEmpty())

                    <div
                        style="
                            padding:var(--space-5);
                            text-align:center;
                            color:var(--text-secondary);
                            font-size:var(--text-sm);
                        "
                    >

                        <div
                            style="
                                width:40px;
                                height:40px;
                                margin:0 auto var(--space-3);
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                border-radius:50%;
                                background:var(--surface-secondary, #f3f4f6);
                            "
                        >
                            <svg
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <circle cx="12" cy="12" r="9"/>
                                <path d="M12 8v4"/>
                                <path d="M12 16h.01"/>
                            </svg>
                        </div>

                        @if($academicLevelId > 0)

                            {{ __('ui.no_sections_for_class', [], null, 'No sections found for this class.') }}

                        @else

                            {{ __('ui.no_class_groups', [], null, 'No class groups assigned.') }}

                        @endif

                    </div>

                @else

                    <ul
                        style="
                            list-style:none;
                            margin:0;
                            padding:0;
                            display:flex;
                            flex-direction:column;
                            gap:4px;
                        "
                    >

                        @foreach($classGroups as $cg)

                            <li>

                                <button
                                    type="button"
                                    wire:click="$set('classGroupId', {{ $cg->id }})"
                                    style="
                                        width:100%;
                                        text-align:start;
                                        padding:12px;
                                        border-radius:var(--radius-sm);
                                        border:1px solid {{ $classGroupId === $cg->id ? 'var(--interactive-primary)' : 'transparent' }};
                                        cursor:pointer;
                                        background:{{ $classGroupId === $cg->id ? 'var(--interactive-primary)' : 'transparent' }};
                                        color:{{ $classGroupId === $cg->id ? 'white' : 'var(--text-primary)' }};
                                        transition:all 0.15s ease;
                                    "
                                >

                                    <div
                                        style="
                                            display:flex;
                                            align-items:center;
                                            justify-content:space-between;
                                            gap:8px;
                                            margin-block-end:4px;
                                        "
                                    >

                                        <span
                                            style="
                                                font-weight:600;
                                                font-size:var(--text-sm);
                                            "
                                        >
                                            {{ $cg->name_ar }}
                                        </span>

                                        @if($classGroupId === $cg->id)

                                            <span
                                                style="
                                                    font-size:11px;
                                                    opacity:0.9;
                                                "
                                            >
                                                ✓
                                            </span>

                                        @endif

                                    </div>


                                    <div
                                        style="
                                            font-size:var(--text-xs);
                                            opacity:0.75;
                                            margin-block-end:3px;
                                        "
                                    >
                                        {{ $cg->level_name }}
                                    </div>


                                    @if($cg->classroom_name)

                                        <div
                                            style="
                                                font-size:var(--text-xs);
                                                opacity:0.7;
                                                margin-block-end:3px;
                                            "
                                        >
                                            {{ $cg->classroom_name }}
                                        </div>

                                    @endif


                                    @if($cg->code)

                                        <div
                                            style="
                                                font-size:var(--text-xs);
                                                opacity:0.7;
                                                margin-block-end:6px;
                                            "
                                        >
                                            {{ $cg->code }}
                                        </div>

                                    @endif


                                    <span
                                        style="
                                            display:inline-flex;
                                            align-items:center;
                                            padding:2px 7px;
                                            border-radius:999px;
                                            background:rgba(0,0,0,0.10);
                                            font-size:10px;
                                        "
                                    >
                                        {{ __('ui.' . $cg->lifecycle_status, [], null, $cg->lifecycle_status) }}
                                    </span>

                                </button>

                            </li>

                        @endforeach

                    </ul>

                @endif

            </div>

        </div>


        {{-- =====================================================
             RIGHT: STUDENTS
        ====================================================== --}}
        <div
            style="
                min-width:0;
                background:var(--surface-primary);
                border:1px solid var(--border-color);
                border-radius:var(--radius-md);
                overflow:hidden;
            "
        >

            @if($classGroupId === 0)

                {{-- Empty State --}}
                <div
                    style="
                        min-height:360px;
                        display:flex;
                        flex-direction:column;
                        align-items:center;
                        justify-content:center;
                        padding:var(--space-8);
                        color:var(--text-secondary);
                        text-align:center;
                    "
                >

                    <div
                        style="
                            width:64px;
                            height:64px;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            border-radius:50%;
                            background:var(--surface-secondary, #f3f4f6);
                            margin-block-end:var(--space-4);
                        "
                    >

                        <svg
                            width="28"
                            height="28"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>

                    </div>


                    <div
                        style="
                            font-size:var(--text-lg);
                            font-weight:600;
                            color:var(--text-primary);
                            margin-block-end:var(--space-2);
                        "
                    >
                        {{ __('ui.select_section', [], null, 'Select a section') }}
                    </div>


                    <div
                        style="
                            max-width:420px;
                            font-size:var(--text-sm);
                            line-height:1.6;
                        "
                    >
                        @if($academicLevelId === 0)

                            {{ __('ui.select_class_first', [], null, 'Select a class first, then choose a section to view its students.') }}

                        @else

                            {{ __('ui.select_section_to_view_students', [], null, 'Choose a section to view the students enrolled in it.') }}

                        @endif
                    </div>

                </div>

            @else

                {{-- =================================================
                     STUDENT HEADER
                ================================================== --}}
                <div
                    style="
                        display:flex;
                        align-items:center;
                        justify-content:space-between;
                        gap:var(--space-4);
                        padding:var(--space-4) var(--space-5);
                        border-block-end:1px solid var(--border-color);
                    "
                >

                    @php
                        $selectedSection = $sections->firstWhere('id', $classGroupId);
                    @endphp


                    <div>

                        <h2
                            style="
                                margin:0;
                                font-size:var(--text-lg);
                                font-weight:700;
                                color:var(--text-primary);
                            "
                        >
                            {{ __('ui.students', [], null, 'Students') }}

                            <span
                                style="
                                    font-weight:500;
                                    color:var(--text-secondary);
                                "
                            >
                                ({{ $classStudents->count() }})
                            </span>
                        </h2>


                        @if($selectedSection)

                            <div
                                style="
                                    display:flex;
                                    align-items:center;
                                    flex-wrap:wrap;
                                    gap:6px;
                                    margin-block-start:5px;
                                    font-size:var(--text-xs);
                                    color:var(--text-secondary);
                                "
                            >

                                <span>
                                    {{ $selectedSection->level_name }}
                                </span>

                                <span>•</span>

                                <strong style="color:var(--text-primary);">
                                    {{ $selectedSection->name_ar }}
                                </strong>

                                @if($selectedSection->code)

                                    <span>•</span>

                                    <span>
                                        {{ $selectedSection->code }}
                                    </span>

                                @endif

                            </div>

                        @endif

                    </div>


                    {{-- Actions --}}
                    <div
                        style="
                            display:flex;
                            align-items:center;
                            gap:var(--space-2);
                            flex-wrap:wrap;
                        "
                    >

                        <button
                            type="button"
                            wire:click="downloadCsv"
                            class="btn btn--outline btn--sm"
                            style="
                                display:inline-flex;
                                align-items:center;
                                gap:7px;
                            "
                        >

                            <svg
                                width="15"
                                height="15"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M12 3v12"/>
                                <path d="m7 10 5 5 5-5"/>
                                <path d="M5 21h14"/>
                            </svg>

                            {{ __('ui.download_csv', [], null, 'Download CSV') }}

                        </button>


                        @if($canManageEnrollments)

                            <a
                                href="{{ route('staff.enrollments.index') }}"
                                class="btn btn--secondary btn--sm"
                                wire:navigate
                            >
                                {{ __('ui.manage_enrollments', [], null, 'Manage Enrollments') }}
                            </a>

                        @endif

                    </div>

                </div>


                {{-- =================================================
                     STUDENTS TABLE
                ================================================== --}}
                <div class="data-table-wrapper">

                    <table class="data-table">

                        <thead>

                            <tr>

                                <th>#</th>

                                <th>
                                    {{ __('ui.national_id', [], null, 'National ID') }}
                                </th>

                                <th>
                                    {{ __('ui.name', [], null, 'Name') }}
                                </th>

                                <th>
                                    {{ __('ui.status', [], null, 'Status') }}
                                </th>

                                <th>
                                    {{ __('ui.enrolled_on', [], null, 'Enrolled On') }}
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($classStudents as $i => $s)

                                @php
                                    $status = match ($s->enrollment_status) {
                                        'active' => 'active',
                                        'draft' => 'draft',
                                        default => 'closed',
                                    };
                                @endphp


                                <tr>

                                    <td style="color:var(--text-secondary);">
                                        {{ $i + 1 }}
                                    </td>


                                    <td>
                                        {{ $s->national_id }}
                                    </td>


                                    <td>

                                        <a
                                            href="{{ route('staff.students.detail', ['studentProfileId' => $s->student_id]) }}"
                                            class="link"
                                            wire:navigate
                                        >
                                            {{ $s->name_ar }}
                                        </a>


                                        @if($s->name_en)

                                            <div
                                                style="
                                                    margin-block-start:2px;
                                                    font-size:var(--text-xs);
                                                    color:var(--text-secondary);
                                                "
                                            >
                                                {{ $s->name_en }}
                                            </div>

                                        @endif

                                    </td>


                                    <td>

                                        <span class="badge badge--{{ $status }}">
                                            {{ __('ui.' . $status, [], null, $status) }}
                                        </span>

                                    </td>


                                    <td>
                                        {{ $s->enrolled_on }}
                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="5"
                                        style="
                                            text-align:center;
                                            color:var(--text-secondary);
                                            padding:var(--space-10);
                                            font-style:italic;
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

</div>

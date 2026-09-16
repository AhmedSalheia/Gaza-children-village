@php
    /** @var \App\Livewire\Staff\Students\StudentList $this */
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
            margin-block-end:var(--space-6);
            flex-wrap:wrap;
            gap:var(--space-3);
        "
    >
        <h1
            style="
                font-size:var(--text-2xl);
                font-weight:700;
                color:var(--text-primary);
                margin:0;
            "
        >
            {{ __('ui.students', [], null, 'Students') }}
        </h1>

        <div
            style="
                display:flex;
                align-items:center;
                gap:var(--space-2);
                flex-wrap:wrap;
            "
        >

            {{-- =================================================
                 Download Excel
            ================================================== --}}
            <button
                type="button"
                wire:click="downloadExcel"
                class="btn btn--secondary btn--sm"
            >
                <i class="bi bi-file-earmark-excel"></i>
                {{ __('ui.download_excel', [], null, 'Download Excel') }}
            </button>


            {{-- =================================================
                 Upload Excel
            ================================================== --}}
            @if($canCreateStudent)

                <button
                    type="button"
                    wire:click="openImportForm"
                    class="btn btn--secondary btn--sm"
                    style="
                        display:inline-flex;
                        align-items:center;
                        gap:7px;
                        border-color:#bfdbfe;
                        color:#1d4ed8;
                        background:#eff6ff;
                    "
                >
                    <i class="bi bi-cloud-arrow-up"></i>

                    {{ __('ui.upload_excel', [], null, 'Upload Excel') }}
                </button>

            @endif


            {{-- =================================================
                 Add Student
            ================================================== --}}
            @if($canCreateStudent)

                <a
                    href="{{ route('staff.students.add') }}"
                    class="btn btn--primary btn--sm"
                    wire:navigate
                >
                    <i class="bi bi-person-plus"></i>
                    {{ __('ui.add_student', [], null, 'Add Student') }}
                </a>

            @endif

        </div>
    </div>


    {{-- =========================================================
         UPLOAD EXCEL PANEL
    ========================================================== --}}
    @if($showImportForm)

        <div
            style="
                margin-block-end:var(--space-5);
                border:1px solid #dbeafe;
                border-radius:14px;
                background:#f8fbff;
                padding:20px;
            "
        >

            {{-- Panel Header --}}
            <div
                style="
                    display:flex;
                    align-items:flex-start;
                    justify-content:space-between;
                    gap:16px;
                    margin-block-end:18px;
                "
            >

                <div
                    style="
                        display:flex;
                        align-items:flex-start;
                        gap:12px;
                    "
                >

                    <div
                        style="
                            width:42px;
                            height:42px;
                            border-radius:10px;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            background:#dbeafe;
                            color:#2563eb;
                            flex-shrink:0;
                        "
                    >
                        <i
                            class="bi bi-file-earmark-arrow-up"
                            style="font-size:20px;"
                        ></i>
                    </div>

                    <div>

                        <div
                            style="
                                font-size:16px;
                                font-weight:700;
                                color:#1e293b;
                                margin-bottom:4px;
                            "
                        >
                            {{ __('ui.upload_excel_file', [], null, 'Upload Excel File') }}
                        </div>

                        <div
                            style="
                                font-size:13px;
                                color:#64748b;
                                line-height:1.6;
                            "
                        >
                            {{ __('ui.upload_excel_description', [], null, 'Upload an Excel file to save it securely in the system.') }}
                        </div>

                    </div>

                </div>


                {{-- Close --}}
                <button
                    type="button"
                    wire:click="closeImportForm"
                    style="
                        border:0;
                        background:transparent;
                        color:#64748b;
                        width:34px;
                        height:34px;
                        border-radius:8px;
                        cursor:pointer;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        font-size:18px;
                    "
                    title="{{ __('ui.close', [], null, 'Close') }}"
                >
                    <i class="bi bi-x-lg"></i>
                </button>

            </div>


            {{-- =================================================
                 File Input
            ================================================== --}}
            <div class="form-group" style="margin:0;">

                <label
                    class="form-label"
                    style="
                        display:block;
                        margin-bottom:8px;
                        font-weight:600;
                    "
                >
                    {{ __('ui.excel_file', [], null, 'Excel File') }}
                </label>


                <div
                    style="
                        border:1px dashed #93c5fd;
                        border-radius:12px;
                        background:#ffffff;
                        padding:18px;
                    "
                >

                    <input
                        type="file"
                        wire:model="studentFile"
                        accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                        class="form-control"
                    >

                    <div
                        style="
                            margin-top:8px;
                            font-size:12px;
                            color:#64748b;
                            display:flex;
                            align-items:center;
                            gap:6px;
                        "
                    >
                        <i class="bi bi-info-circle"></i>

                        {{ __('ui.excel_only_max_50mb', [], null, 'Excel files only (.xlsx) — Maximum size: 50 MB') }}
                    </div>

                    @error('studentFile')
                        <div
                            style="
                                margin-top:8px;
                                color:#dc2626;
                                font-size:13px;
                                display:flex;
                                align-items:center;
                                gap:6px;
                            "
                        >
                            <i class="bi bi-exclamation-circle"></i>
                            {{ $message }}
                        </div>
                    @enderror

                </div>

            </div>


            {{-- =================================================
                 Upload Actions
            ================================================== --}}
            <div
                style="
                    display:flex;
                    align-items:center;
                    justify-content:flex-end;
                    gap:10px;
                    margin-top:18px;
                "
            >

                <button
                    type="button"
                    wire:click="closeImportForm"
                    class="btn btn--outline btn--sm"
                    wire:loading.attr="disabled"
                    wire:target="uploadExcelFile"
                >
                    {{ __('ui.cancel', [], null, 'Cancel') }}
                </button>


                <button
                    type="submit"
                    class="btn btn--primary btn--sm"
                    wire:loading.attr="disabled"
                    wire:target="uploadExcelFile"
                >

                    <span wire:loading.remove wire:target="uploadExcelFile">
                        <i class="bi bi-cloud-arrow-up"></i>
                        {{ __('ui.upload_file', [], null, 'Upload File') }}
                    </span>

                    <span wire:loading wire:target="uploadExcelFile">
                        <i class="bi bi-arrow-repeat"></i>
                        {{ __('ui.uploading', [], null, 'Uploading...') }}
                    </span>

                </button>

            </div>

        </div>

    @endif


    {{-- =========================================================
         SUCCESS / ERROR MESSAGE
    ========================================================== --}}
    @if($importMessage)

        <div
            style="
                margin-block-end:var(--space-4);
                padding:12px 15px;
                border-radius:10px;
                display:flex;
                align-items:center;
                gap:9px;
                font-size:13px;

                @if($importMessageType === 'success')
                    background:#f0fdf4;
                    border:1px solid #bbf7d0;
                    color:#166534;
                @else
                    background:#fef2f2;
                    border:1px solid #fecaca;
                    color:#b91c1c;
                @endif
            "
        >

            @if($importMessageType === 'success')
                <i class="bi bi-check-circle-fill"></i>
            @else
                <i class="bi bi-exclamation-circle-fill"></i>
            @endif

            <span>{{ $importMessage }}</span>

        </div>

    @endif


    {{-- =========================================================
         FILTERS
    ========================================================== --}}
    <div
        style="
            display:grid;
            grid-template-columns:1fr 1fr 1fr auto;
            gap:var(--space-3);
            margin-block-end:var(--space-4);
            align-items:end;
        "
    >

        <div class="form-group" style="margin:0">

            <label class="form-label">
                {{ __('ui.search', [], null, 'Search') }}
            </label>

            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                class="form-control"
                placeholder="{{ __('ui.search_students_placeholder', [], null, 'Name or student code…') }}"
            >

        </div>


        <div class="form-group" style="margin:0">

            <label class="form-label">
                {{ __('ui.academic_level', [], null, 'Academic Level') }}
            </label>

            <select
                wire:model.live="levelFilter"
                class="form-control form-select"
            >

                <option value="0">
                    {{ __('ui.all', [], null, 'All') }}
                </option>

                @foreach($academicLevels as $level)

                    <option value="{{ $level->id }}">
                        {{ $level->name_ar }}
                    </option>

                @endforeach

            </select>

        </div>


        <div class="form-group" style="margin:0">

            <label class="form-label">
                {{ __('ui.class_group', [], null, 'Class Group') }}
            </label>

            <select
                wire:model.live="classGroupFilter"
                class="form-control form-select"
            >

                <option value="0">
                    {{ __('ui.all', [], null, 'All') }}
                </option>

                @foreach($classGroups as $cg)

                    <option value="{{ $cg->id }}">
                        {{ $cg->level_name }} — {{ $cg->name_ar }}
                    </option>

                @endforeach

            </select>

        </div>


        <div class="form-group" style="margin:0">

            <label class="form-label">
                {{ __('ui.status', [], null, 'Status') }}
            </label>

            <select
                wire:model.live="statusFilter"
                class="form-control form-select"
            >

                <option value="">
                    {{ __('ui.all', [], null, 'All') }}
                </option>

                <option value="draft">
                    {{ __('status.draft') }}
                </option>

                <option value="active">
                    {{ __('status.active') }}
                </option>

                <option value="suspended">
                    {{ __('status.suspended') }}
                </option>

            </select>

        </div>

    </div>


    {{-- =========================================================
         STUDENTS TABLE
    ========================================================== --}}
    <div class="data-table-wrapper">

        <table class="data-table">

            <thead>

                <tr>

                    <th>
                        {{ __('ui.student_code', [], null, 'Code') }}
                    </th>

                    <th>
                        {{ __('ui.name', [], null, 'Name') }}
                    </th>

                    <th>
                        {{ __('ui.class_group', [], null, 'Class Group') }}
                    </th>

                    <th>
                        {{ __('ui.level', [], null, 'Level') }}
                    </th>

                    <th>
                        {{ __('ui.enrollment_status', [], null, 'Enrollment') }}
                    </th>

                    <th></th>

                </tr>

            </thead>


            <tbody>

                @forelse($students as $student)

                    <tr>

                        {{-- Student Code --}}
                        <td>
                            {{ $student->national_id }}
                        </td>


                        {{-- Name --}}
                        <td>

                            <a
                                href="{{ route('staff.students.detail', ['studentNationalId' => $student->national_id]) }}"
                                class="link"
                                wire:navigate
                            >
                                {{ $student->name_ar }}
                            </a>

                            @if($student->name_en)

                                <div
                                    style="
                                        font-size:var(--text-xs);
                                        color:var(--text-secondary);
                                    "
                                >
                                    {{ $student->name_en }}
                                </div>

                            @endif

                        </td>


                        {{-- Class Group --}}
                        <td>
                            {{ $student->class_group_name }}
                        </td>


                        {{-- Level --}}
                        <td>
                            {{ $student->level_name }}
                        </td>


                        {{-- Enrollment --}}
                        <td>

                            <span
                                class="badge badge--{{
                                    match($student->enrollment_status) {
                                        'active' => 'active',
                                        'draft' => 'draft',
                                        'suspended' => 'pending',
                                        default => 'closed'
                                    }
                                }}"
                            >
                                {{ __('ui.'.$student->enrollment_status) }}
                            </span>

                        </td>


                        {{-- Actions --}}
                        <td style="white-space:nowrap">

                            <a
                                href="{{ route('staff.students.detail', ['studentNationalId' => $student->national_id]) }}"
                                class="btn btn--outline btn--sm"
                                wire:navigate
                            >
                                {{ __('ui.view', [], null, 'View') }}
                            </a>

                            @if($canManageEnrollments)

                                <a
                                    href="{{ route('staff.enrollments.transfer', ['studentNationalId' => $student->national_id]) }}"
                                    class="btn btn--outline--secondary btn--sm"
                                    wire:navigate
                                >
                                    {{ __('ui.transfer', [], null, 'Transfer') }}
                                </a>

                            @endif

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="6"
                            style="
                                text-align:center;
                                color:var(--text-secondary);
                                padding:var(--space-8);
                                font-style:italic;
                            "
                        >
                            {{ __('ui.no_students', [], null, 'No students found.') }}
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    {{-- Pagination --}}
    {{ $students->links() }}

</div>


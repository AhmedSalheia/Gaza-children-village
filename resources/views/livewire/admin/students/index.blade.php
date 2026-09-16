@php
    /** @var \App\Livewire\Admin\Students\StudentIndex $this */
@endphp

<div>

    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div
        class="page-header"
        style="
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:var(--space-4);
            flex-wrap:wrap;
        "
    >

        <h1 class="page-title">
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
                 DOWNLOAD EXCEL
            ================================================== --}}
            <button
                type="button"
                wire:click="downloadExcel"
                wire:loading.attr="disabled"
                wire:target="downloadExcel"
                class="btn btn--outline btn--sm"
                style="
                    display:inline-flex;
                    align-items:center;
                    gap:7px;
                "
            >
                <span
                    wire:loading.remove
                    wire:target="downloadExcel"
                >
                    <i class="bi bi-file-earmark-excel"></i>

                    {{ __('ui.download_excel', [], null, 'Download Excel') }}
                </span>

                <span
                    wire:loading
                    wire:target="downloadExcel"
                    style="
                        display:inline-flex;
                        align-items:center;
                        gap:7px;
                    "
                >
                    <i class="bi bi-arrow-repeat"></i>

                    {{-- {{ __('ui.loading', [], null, 'Loading…') }} --}}
                </span>
            </button>





        </div>

    </div>


    {{-- =========================================================
         FILTERS
    ========================================================== --}}
    <div class="filters-bar">

        {{-- Search --}}
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            class="form-control"
            placeholder="{{ __('ui.search_students', [], null, 'Search by name or code…') }}"
            style="max-inline-size:280px"
        >


        {{-- Status --}}
        <select
            wire:model.live="statusFilter"
            class="form-control form-select"
            style="max-inline-size:160px"
        >

            <option value="">
                {{ __('ui.all_statuses', [], null, 'All statuses') }}
            </option>

            @foreach($statusOptions as $opt)

                <option value="{{ $opt }}">
                    {{ __('ui.'.$opt) }}
                </option>

            @endforeach

        </select>


        {{-- Institution --}}
        <select
            wire:model.live="institutionFilter"
            class="form-control form-select"
            style="max-inline-size:240px"
        >

            <option value="0">
                {{ __('ui.all_institutions', [], null, 'All institutions') }}
            </option>

            @foreach($institutions as $inst)

                <option value="{{ $inst->id }}">
                    {{ $inst->name_ar }}
                </option>

            @endforeach

        </select>


        {{-- Clear --}}
        @if($search || $statusFilter || $institutionFilter)

            <button
                type="button"
                wire:click="$set('search', ''); $set('statusFilter', ''); $set('institutionFilter', 0)"
                class="btn btn--outline btn--sm"
            >
                <i class="bi bi-x-circle"></i>

                {{ __('ui.clear', [], null, 'Clear') }}
            </button>

        @endif

    </div>


    {{-- =========================================================
         LOADING
    ========================================================== --}}
    <div
        wire:loading
        wire:target="search,statusFilter,institutionFilter"
        class="alert alert--info"
    >
        {{ __('ui.loading', [], null, 'Loading…') }}
    </div>


    {{-- =========================================================
         TABLE
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
                        {{ __('ui.birth_date', [], null, 'Birth') }}
                    </th>

                    <th>
                        {{ __('ui.status', [], null, 'Status') }}
                    </th>

                    <th>
                        {{ __('ui.registered', [], null, 'Registered') }}
                    </th>

                    <th></th>

                </tr>

            </thead>


            <tbody>

                @forelse($students as $student)

                    <tr>

                        {{-- Student Code --}}
                        <td>
                            <code>
                                {{ $student->national_id }}
                            </code>
                        </td>


                        {{-- Name --}}
                        <td>

                            <div
                                style="font-weight:600"
                                dir="rtl"
                            >
                                {{ $student->full_name_ar }}
                            </div>

                            @if($student->full_name_en)

                                <div
                                    style="
                                        font-size:var(--text-sm);
                                        color:var(--text-secondary);
                                    "
                                >
                                    {{ $student->full_name_en }}
                                </div>

                            @endif

                        </td>


                        {{-- Birth Date --}}
                        <td
                            style="
                                font-size:var(--text-sm);
                            "
                        >
                            {{ $student->birth_date ?? '—' }}
                        </td>


                        {{-- Status --}}
                        <td>

                            <span
                                class="badge badge--{{
                                    match($student->lifecycle_status) {
                                        'active' => 'active',
                                        'draft' => 'draft',
                                        'withdrawn', 'inactive' => 'closed',
                                        'graduated' => 'archived',
                                        default => 'pending'
                                    }
                                }}"
                            >
                                {{ __('ui.'.$student->lifecycle_status) }}
                            </span>

                        </td>


                        {{-- Registered --}}
                        <td
                            style="
                                font-size:var(--text-sm);
                                color:var(--text-secondary);
                            "
                        >
                            {{ $student->registered_on ?? '—' }}
                        </td>


                        {{-- View --}}
                        <td>

                            <a
                                href="{{ route('admin.students.detail', ['studentId' => $student->id]) }}"
                                class="btn btn--outline btn--sm"
                                wire:navigate
                            >
                                <i class="bi bi-eye"></i>

                                {{ __('ui.view', [], null, 'View') }}
                            </a>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="6"
                            class="empty-state"
                        >
                            {{ __('ui.no_students', [], null, 'No students found.') }}
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    {{-- =========================================================
         PAGINATION
    ========================================================== --}}
    <div class="pagination">

        <div class="pagination__info">

            <span class="pagination__count">
                {{ $students->total() }}
            </span>

            <span>
                {{ __('ui.students', [], null, 'students') }}
            </span>

        </div>


        <div class="pagination__links">

            {{ $students->links() }}

        </div>

    </div>


    {{-- =========================================================
         PAGE STYLES
    ========================================================== --}}
    @include('livewire.admin._partials.page-styles')

</div>


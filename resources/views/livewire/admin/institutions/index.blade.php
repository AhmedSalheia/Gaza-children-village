@php
    /** @var \App\Livewire\Admin\Institutions\InstitutionIndex $this */
@endphp

<div>

    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div class="page-header">

        <div>
            <h1 class="page-title">
                {{ __('ui.institutions', [], null, 'Institutions') }}
            </h1>

            <div
                style="
                    margin-block-start:4px;
                    color:var(--text-secondary);
                    font-size:var(--text-sm);
                "
            >
                {{ __('ui.manage_institutions', [], null, 'Manage organization institutions') }}
            </div>
        </div>

        <div style="display:flex;gap:var(--space-2);align-items:center;">

            <button
                type="button"
                wire:click="openCreateForm"
                class="btn btn--outline btn--sm"
            >
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                <span>
                    {{ __('ui.add_institution', [], null, 'Add institution') }}
                </span>
            </button>

        </div>

    </div>


    {{-- =========================================================
         SUCCESS MESSAGE
    ========================================================== --}}
    @if($successMessage)

        <div
            style="
                display:flex;
                align-items:center;
                gap:var(--space-3);
                margin-block-end:var(--space-4);
                padding:12px 16px;
                border-radius:var(--radius-md);
                background:#ecfdf5;
                border:1px solid #a7f3d0;
                color:#065f46;
            "
        >

            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>

            <span style="flex:1">
                {{ $successMessage }}
            </span>

            <button
                type="button"
                wire:click="$set('successMessage', null)"
                style="
                    border:0;
                    background:transparent;
                    cursor:pointer;
                    color:inherit;
                    padding:4px;
                "
                aria-label="{{ __('ui.close', [], null, 'Close') }}"
            >
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>

        </div>

    @endif


    {{-- =========================================================
         CREATE FORM
    ========================================================== --}}
    @if($showCreateForm)

        <div
            style="
                margin-block-end:var(--space-5);
                padding:var(--space-5);
                border:1px solid var(--border-color);
                border-radius:var(--radius-md);
                background:var(--surface-primary);
            "
        >

            {{-- FORM HEADER --}}
            <div
                style="
                    display:flex;
                    align-items:center;
                    justify-content:space-between;
                    gap:var(--space-4);
                    flex-wrap:wrap;
                    margin-block-end:var(--space-5);
                "
            >

                <div>

                    <div
                        style="
                            display:flex;
                            align-items:center;
                            gap:var(--space-2);
                            font-weight:700;
                            font-size:var(--text-lg);
                        "
                    >
                        <i class="bi bi-building-add" aria-hidden="true"></i>

                        <span>
                            {{ __('ui.add_institution', [], null, 'Add institution') }}
                        </span>
                    </div>

                    <div
                        style="
                            margin-block-start:4px;
                            color:var(--text-secondary);
                            font-size:var(--text-sm);
                        "
                    >
                        {{ __('ui.enter_institution_data', [], null, 'Enter the institution information and save it.') }}
                    </div>

                </div>

                <button
                    type="button"
                    wire:click="closeCreateForm"
                    class="btn btn--outline btn--sm"
                >
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                    <span>
                        {{ __('ui.close', [], null, 'Close') }}
                    </span>
                </button>

            </div>


            {{-- FORM FIELDS --}}
            <div
                style="
                    display:grid;
                    grid-template-columns:repeat(2,minmax(0,1fr));
                    gap:var(--space-4);
                "
                class="institution-form-grid"
            >

                {{-- ORGANIZATION --}}
                <div>

                    <label
                        for="institution-organization"
                        style="
                            display:block;
                            margin-block-end:6px;
                            font-weight:600;
                            font-size:var(--text-sm);
                        "
                    >
                        {{ __('ui.organization', [], null, 'Organization') }}
                    </label>

                    <input
                        id="institution-organization"
                        type="text"
                        class="form-control"
                        value="GCV"
                        disabled
                    >

                    <input
                        type="hidden"
                        wire:model="organizationId"
                    >

                    <div
                        style="
                            margin-block-start:5px;
                            font-size:var(--text-xs);
                            color:var(--text-secondary);
                        "
                    >
                        {{ __('ui.institution_auto_organization', [], null, 'The institution will automatically be linked to the GCV organization.') }}
                    </div>

                    @error('organizationId')
                        <div class="form-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                {{-- INSTITUTION TYPE --}}
                <div>

                    <label
                        for="institution-type"
                        style="
                            display:block;
                            margin-block-end:6px;
                            font-weight:600;
                            font-size:var(--text-sm);
                        "
                    >
                        {{ __('ui.institution_type', [], null, 'Institution type') }}
                        <span style="color:#dc2626">*</span>
                    </label>

                  <select
    id="institution-type"
    wire:model="institutionTypeId"
    class="form-control form-select"
>
    <option value="">
        {{ __('ui.select_institution_type', [], null, 'Select institution type') }}
    </option>

    @foreach($institutionTypes as $type)
        <option value="{{ $type->id }}">
            {{ __('ui.institution_types.' . $type->name_en, [], $type->name_ar ?: $type->name_en) }}
        </option>
    @endforeach
</select>
                    @error('institutionTypeId')
                        <div class="form-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                {{-- ARABIC NAME --}}
                <div>

                    <label
                        for="institution-name-ar"
                        style="
                            display:block;
                            margin-block-end:6px;
                            font-weight:600;
                            font-size:var(--text-sm);
                        "
                    >
                        {{ __('ui.name_ar', [], null, 'Arabic name') }}
                        <span style="color:#dc2626">*</span>
                    </label>

                    <input
                        id="institution-name-ar"
                        type="text"
                        wire:model="nameAr"
                        class="form-control"
                        placeholder="{{ __('ui.institution_name_ar_placeholder', [], null, 'Example: Children Village') }}"
                        autocomplete="off"
                        dir="rtl"
                    >

                    @error('nameAr')
                        <div class="form-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                {{-- ENGLISH NAME --}}
                <div>

                    <label
                        for="institution-name-en"
                        style="
                            display:block;
                            margin-block-end:6px;
                            font-weight:600;
                            font-size:var(--text-sm);
                        "
                    >
                        {{ __('ui.name_en', [], null, 'English name') }}
                    </label>

                    <input
                        id="institution-name-en"
                        type="text"
                        wire:model="nameEn"
                        class="form-control"
                        placeholder="{{ __('ui.institution_name_en_placeholder', [], null, 'Example: GCV Institution') }}"
                        autocomplete="off"
                        dir="ltr"
                    >

                    @error('nameEn')
                        <div class="form-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                {{-- CODE --}}
  {{-- CODE --}}
<div>

    <label
        for="institution-code"
        style="
            display:block;
            margin-block-end:6px;
            font-weight:600;
            font-size:var(--text-sm);
        "
    >
        {{ __('ui.code', [], null, 'Code') }}
        <span style="color:#dc2626">*</span>
    </label>

    <input
        id="institution-code"
        type="text"
        wire:model="code"
        class="form-control"
        placeholder="{{ __('ui.institution_code_placeholder', [], null, 'Enter institution code') }}"
        autocomplete="off"
        dir="ltr"
    >

    <div
        style="
            margin-block-start:5px;
            font-size:var(--text-xs);
            color:var(--text-secondary);
        "
    >
    </div>

    @error('code')
        <div class="form-error">
            {{ $message }}
        </div>
    @enderror

</div>


                {{-- STATUS --}}
                <div>

                    <label
                        style="
                            display:block;
                            margin-block-end:6px;
                            font-weight:600;
                            font-size:var(--text-sm);
                        "
                    >
                        {{ __('ui.status', [], null, 'Status') }}
                    </label>

                    <label
                        style="
                            display:flex;
                            align-items:center;
                            gap:10px;
                            min-height:42px;
                            cursor:pointer;
                        "
                    >

                        <input
                            type="checkbox"
                            wire:model="isActive"
                            style="
                                width:18px;
                                height:18px;
                                cursor:pointer;
                            "
                        >

                        <span>
                            {{ __('ui.active_institution', [], null, 'Institution is active') }}
                        </span>

                    </label>

                    <div
                        style="
                            margin-block-start:5px;
                            font-size:var(--text-xs);
                            color:var(--text-secondary);
                        "
                    >
                        {{ __('ui.inactive_institution_help', [], null, 'Inactive institutions remain stored to preserve historical records.') }}
                    </div>

                    @error('isActive')
                        <div class="form-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

            </div>


            {{-- FORM ACTIONS --}}
            <div
                style="
                    display:flex;
                    align-items:center;
                    justify-content:flex-end;
                    gap:var(--space-2);
                    margin-block-start:var(--space-5);
                    padding-block-start:var(--space-4);
                    border-block-start:1px solid var(--border-color);
                "
            >

                <button
                    type="button"
                    wire:click="closeCreateForm"
                    class="btn btn--outline btn--sm"
                >
                    <i class="bi bi-x-lg" aria-hidden="true"></i>

                    <span>
                        {{ __('ui.cancel', [], null, 'Cancel') }}
                    </span>
                </button>

                <button
                    type="button"
                    wire:click="save"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="btn btn--primary btn--sm"
                >

                    <span wire:loading.remove wire:target="save">
                        <i class="bi bi-check-lg" aria-hidden="true"></i>
                        {{ __('ui.save', [], null, 'Save') }}
                    </span>

                    <span wire:loading wire:target="save">
                        <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                        {{ __('ui.saving', [], null, 'Saving...') }}
                    </span>

                </button>

            </div>

        </div>

    @endif


    {{-- =========================================================
         FILTERS
    ========================================================== --}}
    <div class="filters-bar">

        {{-- SEARCH --}}
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            class="form-control"
            placeholder="{{ __('ui.search_institutions', [], null, 'Search institution name or code...') }}"
            style="max-inline-size:280px"
        >


        {{-- TYPE --}}
        <select
            wire:model.live="typeFilter"
            class="form-control form-select"
            style="max-inline-size:220px"
        >

            <option value="">
                {{ __('ui.all_types', [], null, 'All types') }}
            </option>

           @foreach($institutionTypes as $type)

    <option value="{{ $type->id }}">
        {{ __('ui.institution_types.' . $type->name_en, [], $type->name_ar ?: $type->name_en) }}
    </option>

@endforeach

        </select>


        {{-- STATUS --}}
        <select
            wire:model.live="statusFilter"
            class="form-control form-select"
            style="max-inline-size:180px"
        >

            <option value="all">
                {{ __('ui.all_statuses', [], null, 'All statuses') }}
            </option>

            <option value="active">
                {{ __('ui.active', [], null, 'Active') }}
            </option>

            <option value="inactive">
                {{ __('ui.inactive', [], null, 'Inactive') }}
            </option>

        </select>

    </div>


    {{-- =========================================================
         TABLE
    ========================================================== --}}
    <div class="data-table-wrapper">

        <table class="data-table">

            <thead>

                <tr>

                    <th>
                        {{ __('ui.name', [], null, 'Institution') }}
                    </th>

                    <th>
                        {{ __('ui.code', [], null, 'Code') }}
                    </th>

                    <th>
                        {{ __('ui.type', [], null, 'Type') }}
                    </th>

                    <th>
                        {{ __('ui.status', [], null, 'Status') }}
                    </th>

                </tr>

            </thead>


            <tbody>

                @forelse($institutions as $institution)

                    <tr>

                        {{-- NAME --}}
                        <td>

                            <div
                                style="
                                    display:flex;
                                    align-items:center;
                                    gap:10px;
                                "
                            >

                                <div
                                    style="
                                        width:34px;
                                        height:34px;
                                        min-width:34px;
                                        display:flex;
                                        align-items:center;
                                        justify-content:center;
                                        border-radius:var(--radius-md);
                                        background:var(--surface-secondary);
                                    "
                                >
                                    <i
                                        class="bi bi-building"
                                        aria-hidden="true"
                                    ></i>
                                </div>

                                <div>

                                    <div
                                        style="
                                            font-weight:600;
                                        "
                                        dir="rtl"
                                    >
                                        {{ $institution->name_ar }}
                                    </div>

                                    @if($institution->name_en)

                                        <div
                                            style="
                                                margin-block-start:2px;
                                                font-size:var(--text-xs);
                                                color:var(--text-secondary);
                                            "
                                            dir="ltr"
                                        >
                                            {{ $institution->name_en }}
                                        </div>

                                    @endif

                                </div>

                            </div>

                        </td>


                        {{-- CODE --}}
                        <td>

                            <code
                                style="
                                    font-size:var(--text-xs);
                                    direction:ltr;
                                    unicode-bidi:embed;
                                "
                            >
                                {{ $institution->code }}
                            </code>

                        </td>


                        {{-- TYPE --}}
                        <td>

                            {{ $institution->institutionType?->name_ar
                                ?: $institution->institutionType?->name_en
                                ?: __('ui.not_specified', [], null, 'Not specified') }}

                        </td>


                        {{-- STATUS --}}
                        <td>

                            <span
                                class="badge badge--{{ $institution->is_active ? 'active' : 'archived' }}"
                            >

                                @if($institution->is_active)

                                    {{ __('ui.active', [], null, 'Active') }}

                                @else

                                    {{ __('ui.inactive', [], null, 'Inactive') }}

                                @endif

                            </span>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="4"
                            class="empty-state"
                        >
                            {{ __('ui.no_institutions', [], null, 'No institutions found.') }}
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
        <div class="pagination__info">{{ $institutions->total() }} {{ __('ui.total', [], null, 'total') }}</div>
        {{ $institutions->links() }}
    </div>

</div>


@include('livewire.admin._partials.page-styles')


<style>
    .institution-form-grid {
        width: 100%;
    }

    .form-error {
        margin-block-start: 5px;
        color: #dc2626;
        font-size: var(--text-xs);
    }

    .data-table tbody tr {
        transition: background-color .15s ease;
    }

    .data-table tbody tr:hover {
        background: var(--surface-secondary);
    }

    @media (max-width: 768px) {

        .institution-form-grid {
            grid-template-columns: 1fr !important;
        }

        .page-header {
            align-items: flex-start !important;
        }

        .filters-bar {
            align-items: stretch !important;
        }

        .filters-bar .form-control,
        .filters-bar .form-select {
            max-inline-size: none !important;
            width: 100%;
        }

        .data-table-wrapper {
            overflow-x: auto;
        }

    }
</style>

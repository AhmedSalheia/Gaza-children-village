@php
    /** @var \App\Livewire\Admin\Institutions\InstitutionIndex $this */
@endphp

<div class="institutions-page">

    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div
        style="
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:var(--space-5);
            margin-block-end:var(--space-6);
            flex-wrap:wrap;
        "
    >

        <div>

            <h1 class="page-title">
                <i
                    class="bi bi-buildings"
                    aria-hidden="true"
                    style="margin-inline-end:8px;"
                ></i>

                {{ __('ui.institutions', [], null, 'المؤسسات') }}
            </h1>

            <p
                style="
                    margin:6px 0 0;
                    color:var(--text-secondary);
                    font-size:var(--text-sm);
                "
            >
                إدارة المؤسسات التابعة لمنظمة GCV
            </p>

        </div>

        <div>

            <button
                type="button"
                wire:click="openCreateForm"
                class="btn btn--primary"
            >
                <i
                    class="bi bi-building-add"
                    aria-hidden="true"
                ></i>

                <span>
                    {{ __('ui.add_institution', [], null, 'إضافة مؤسسة') }}
                </span>
            </button>

        </div>

    </div>


    {{-- =========================================================
         SUCCESS MESSAGE
    ========================================================== --}}
    @if ($successMessage)

        <div
            style="
                margin-block-end:var(--space-5);
                padding:14px 16px;
                border:1px solid #bbf7d0;
                border-radius:var(--radius-md);
                background:#f0fdf4;
                color:#166534;
                display:flex;
                align-items:center;
                gap:10px;
            "
        >

            <i
                class="bi bi-check-circle-fill"
                aria-hidden="true"
            ></i>

            <span>
                {{ $successMessage }}
            </span>

        </div>

    @endif


    {{-- =========================================================
         CREATE FORM
    ========================================================== --}}
    @if ($showCreateForm)

        <div
            class="card"
            style="
                margin-block-end:var(--space-6);
            "
        >

            {{-- Form Header --}}
            <div
                style="
                    display:flex;
                    align-items:center;
                    justify-content:space-between;
                    gap:var(--space-4);
                    margin-block-end:var(--space-6);
                    padding-block-end:var(--space-4);
                    border-block-end:1px solid var(--border-color);
                "
            >

                <div>

                    <h2
                        style="
                            margin:0;
                            font-size:var(--text-lg);
                            font-weight:700;
                            color:var(--text-primary);
                        "
                    >
                        <i
                            class="bi bi-building-add"
                            aria-hidden="true"
                            style="margin-inline-end:8px;"
                        ></i>

                        إضافة مؤسسة جديدة
                    </h2>

                    <div
                        style="
                            margin-block-start:5px;
                            color:var(--text-secondary);
                            font-size:var(--text-sm);
                        "
                    >
                        أدخل بيانات المؤسسة وسيتم إنشاء الكود تلقائيًا.
                    </div>

                </div>

                <button
                    type="button"
                    wire:click="closeCreateForm"
                    class="btn btn--secondary"
                >
                    <i
                        class="bi bi-x-lg"
                        aria-hidden="true"
                    ></i>

                    إغلاق
                </button>

            </div>


            {{-- Form --}}
            <form wire:submit="save">

                <div class="institution-create-grid">

                    {{-- =================================================
                         ORGANIZATION
                    ================================================== --}}
                    <div>

                        <label
                            for="organization-display"
                            class="institution-field-label"
                        >
                            المنظمة
                        </label>

                        <input
                            id="organization-display"
                            type="text"
                            class="form-control"
                            value="GCV"
                            readonly
                            disabled
                        >

                        <input
                            type="hidden"
                            wire:model="organizationId"
                        >

                        <div class="institution-help">
                            جميع المؤسسات الحالية تابعة لمنظمة GCV.
                        </div>

                        @error('organizationId')
                            <div class="institution-error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- =================================================
                         INSTITUTION TYPE
                    ================================================== --}}
                    <div>

                        <label
                            for="institution-type"
                            class="institution-field-label"
                        >
                            نوع المؤسسة
                            <span class="required">*</span>
                        </label>

                        <select
                            id="institution-type"
                            wire:model.live="institutionTypeId"
                            class="form-control"
                            required
                        >

                            <option value="">
                                اختر نوع المؤسسة
                            </option>

                            @foreach ($institutionTypes as $type)

                                <option value="{{ $type->id }}">
                                    {{ $type->name_ar }}

                                    @if ($type->code)
                                        — {{ $type->code }}
                                    @endif
                                </option>

                            @endforeach

                        </select>

                        <div class="institution-help">
                            سيتم استخدام كود نوع المؤسسة في بداية الكود النهائي.
                        </div>

                        @error('institutionTypeId')
                            <div class="institution-error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- =================================================
                         ARABIC NAME
                    ================================================== --}}
                    <div>

                        <label
                            for="institution-name-ar"
                            class="institution-field-label"
                        >
                            اسم المؤسسة بالعربية
                            <span class="required">*</span>
                        </label>

                        <input
                            id="institution-name-ar"
                            type="text"
                            wire:model="nameAr"
                            class="form-control"
                            placeholder="مثال: أكاديمية غزة"
                            autocomplete="off"
                            required
                        >

                        @error('nameAr')
                            <div class="institution-error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- =================================================
                         ENGLISH NAME
                    ================================================== --}}
                    <div>

                        <label
                            for="institution-name-en"
                            class="institution-field-label"
                        >
                            اسم المؤسسة بالإنجليزية
                            <span class="required">*</span>
                        </label>

                        <input
                            id="institution-name-en"
                            type="text"
                            wire:model="nameEn"
                            class="form-control"
                            placeholder="Example: Gaza Academy"
                            autocomplete="off"
                            dir="ltr"
                            required
                        >

                        @error('nameEn')
                            <div class="institution-error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- =================================================
                         GENERATED CODE
                    ================================================== --}}
                    <div>

                        <label
                            for="institution-code"
                            class="institution-field-label"
                        >
                            كود المؤسسة
                        </label>

                        <input
                            id="institution-code"
                            type="text"
                            class="form-control"
                            value="سيتم إنشاؤه تلقائيًا بعد الحفظ"
                            readonly
                            disabled
                            dir="ltr"
                            style="
                                background:var(--surface-secondary);
                                font-family:monospace;
                                font-weight:700;
                                letter-spacing:.3px;
                            "
                        >

                        <div class="institution-help">
                            الكود غير قابل للإدخال اليدوي.
                            سيتم توليده بعد إنشاء السجل باستخدام
                            <strong>نوع المؤسسة</strong>
                            و<strong>ID الحقيقي للمؤسسة الجديدة</strong>.
                        </div>

                    </div>


                    {{-- =================================================
                         ACTIVE
                    ================================================== --}}
                    <div>

                        <label
                            class="institution-field-label"
                        >
                            حالة المؤسسة
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
                                "
                            >

                            <span>
                                المؤسسة نشطة
                            </span>

                        </label>

                        @error('isActive')
                            <div class="institution-error">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                </div>


                {{-- =================================================
                     CODE GENERATION INFORMATION
                ================================================== --}}
                <div
                    style="
                        margin-block-start:var(--space-5);
                        padding:var(--space-4);
                        border:1px dashed var(--border-color);
                        border-radius:var(--radius-md);
                        background:var(--surface-secondary);
                    "
                >

                    <div
                        style="
                            display:flex;
                            align-items:flex-start;
                            gap:12px;
                        "
                    >

                        <i
                            class="bi bi-info-circle"
                            aria-hidden="true"
                            style="
                                font-size:18px;
                                margin-block-start:2px;
                            "
                        ></i>

                        <div>

                            <div
                                style="
                                    font-weight:700;
                                    color:var(--text-primary);
                                "
                            >
                                التوليد التلقائي للكود
                            </div>

                            <div
                                style="
                                    margin-block-start:4px;
                                    font-size:var(--text-sm);
                                    color:var(--text-secondary);
                                    line-height:1.8;
                                "
                            >

                                يتم إنشاء الكود تلقائيًا بعد حفظ المؤسسة.

                                يعتمد الجزء الأول من الكود على
                                <strong>
                                    كود نوع المؤسسة
                                </strong>،

                                بينما يعتمد الرقم الأخير على
                                <strong>
                                    ID الخاص بالمؤسسة الجديدة
                                </strong>.

                                <br>

                                مثال:

                                إذا كان كود نوع المؤسسة:

                                <strong dir="ltr">
                                    academy
                                </strong>

                                وكان الـ ID الذي أنشأته قاعدة البيانات:

                                <strong dir="ltr">
                                    26
                                </strong>

                                فسيكون الكود النهائي:

                                <strong dir="ltr">
                                    GCV-academy-026
                                </strong>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- =================================================
                     FORM ACTIONS
                ================================================== --}}
                <div
                    style="
                        display:flex;
                        align-items:center;
                        justify-content:flex-start;
                        gap:var(--space-3);
                        margin-block-start:var(--space-6);
                        padding-block-start:var(--space-5);
                        border-block-start:1px solid var(--border-color);
                        flex-wrap:wrap;
                    "
                >

                    <button
                        type="submit"
                        class="btn btn--primary"
                        wire:loading.attr="disabled"
                        wire:target="save"
                    >

                        <span
                            wire:loading.remove
                            wire:target="save"
                        >
                            <i
                                class="bi bi-check-lg"
                                aria-hidden="true"
                            ></i>

                            حفظ المؤسسة
                        </span>

                        <span
                            wire:loading
                            wire:target="save"
                        >
                            <i
                                class="bi bi-arrow-repeat"
                                aria-hidden="true"
                            ></i>

                            جاري الحفظ...
                        </span>

                    </button>


                    <button
                        type="button"
                        wire:click="closeCreateForm"
                        class="btn btn--secondary"
                    >
                        <i
                            class="bi bi-x-lg"
                            aria-hidden="true"
                        ></i>

                        إلغاء
                    </button>

                </div>

            </form>

        </div>

    @endif


    {{-- =========================================================
         FILTERS
    ========================================================== --}}
    <div
        class="card"
        style="
            margin-block-end:var(--space-6);
        "
    >

        <div
            style="
                display:flex;
                align-items:center;
                gap:10px;
                margin-block-end:var(--space-5);
            "
        >

            <i
                class="bi bi-funnel"
                aria-hidden="true"
            ></i>

            <h2
                style="
                    margin:0;
                    font-size:var(--text-lg);
                    font-weight:700;
                    color:var(--text-primary);
                "
            >
                البحث والتصفية
            </h2>

        </div>


        <div class="institution-filter-grid">

            {{-- Search --}}
            <div>

                <label
                    for="institution-search"
                    class="institution-field-label"
                >
                    البحث
                </label>

                <div style="position:relative;">

                    <i
                        class="bi bi-search"
                        aria-hidden="true"
                        style="
                            position:absolute;
                            inset-inline-start:12px;
                            top:50%;
                            transform:translateY(-50%);
                            color:var(--text-secondary);
                            pointer-events:none;
                        "
                    ></i>

                    <input
                        id="institution-search"
                        type="search"
                        wire:model.live.debounce.400ms="search"
                        class="form-control"
                        style="padding-inline-start:38px;"
                        placeholder="اسم المؤسسة أو الكود..."
                    >

                </div>

            </div>


            {{-- Type --}}
            <div>

                <label
                    for="institution-type-filter"
                    class="institution-field-label"
                >
                    نوع المؤسسة
                </label>

                <select
                    id="institution-type-filter"
                    wire:model.live="typeFilter"
                    class="form-control"
                >

                    <option value="">
                        جميع الأنواع
                    </option>

                    @foreach ($institutionTypes as $type)

                        <option value="{{ $type->id }}">
                            {{ $type->name_ar }}
                        </option>

                    @endforeach

                </select>

            </div>


            {{-- Status --}}
            <div>

                <label
                    for="institution-status-filter"
                    class="institution-field-label"
                >
                    الحالة
                </label>

                <select
                    id="institution-status-filter"
                    wire:model.live="statusFilter"
                    class="form-control"
                >

                    <option value="all">
                        جميع الحالات
                    </option>

                    <option value="active">
                        نشطة
                    </option>

                    <option value="inactive">
                        غير نشطة
                    </option>

                </select>

            </div>

        </div>

    </div>


    {{-- =========================================================
         INSTITUTIONS TABLE
    ========================================================== --}}
    <div class="card">

        <div
            style="
                display:flex;
                align-items:center;
                justify-content:space-between;
                gap:var(--space-4);
                margin-block-end:var(--space-5);
                flex-wrap:wrap;
            "
        >

            <div>

                <h2
                    style="
                        margin:0;
                        font-size:var(--text-lg);
                        font-weight:700;
                        color:var(--text-primary);
                    "
                >
                    قائمة المؤسسات
                </h2>

                <div
                    style="
                        margin-block-start:4px;
                        color:var(--text-secondary);
                        font-size:var(--text-sm);
                    "
                >
                    عرض المؤسسات المسجلة في النظام
                </div>

            </div>

            <div
                style="
                    color:var(--text-secondary);
                    font-size:var(--text-sm);
                "
            >
                إجمالي النتائج:
                <strong>
                    {{ $institutions->total() }}
                </strong>
            </div>

        </div>


        {{-- Table --}}
        <div
            style="
                overflow-x:auto;
            "
        >

            <table class="data-table">

                <thead>

                    <tr>

                        <th>
                            #
                        </th>

                        <th>
                            المؤسسة
                        </th>

                        <th>
                            الكود
                        </th>

                        <th>
                            نوع المؤسسة
                        </th>

                        <th>
                            الحالة
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse ($institutions as $institution)

                        <tr>

                            {{-- ID --}}
                            <td>
                                <span
                                    dir="ltr"
                                    style="
                                        font-family:monospace;
                                        font-weight:600;
                                    "
                                >
                                    {{ $institution->id }}
                                </span>
                            </td>


                            {{-- Institution --}}
                            <td>

                                <div
                                    style="
                                        font-weight:700;
                                        color:var(--text-primary);
                                    "
                                >
                                    {{ $institution->name_ar }}
                                </div>

                                @if ($institution->name_en)

                                    <div
                                        dir="ltr"
                                        style="
                                            margin-block-start:3px;
                                            color:var(--text-secondary);
                                            font-size:var(--text-sm);
                                            text-align:right;
                                        "
                                    >
                                        {{ $institution->name_en }}
                                    </div>

                                @endif

                            </td>


                            {{-- Code --}}
                            <td>

                                <span
                                    dir="ltr"
                                    style="
                                        font-family:monospace;
                                        font-weight:700;
                                        letter-spacing:.3px;
                                    "
                                >
                                    {{ $institution->code ?: '—' }}
                                </span>

                            </td>


                            {{-- Type --}}
                            <td>

                                @if ($institution->institutionType)

                                    <div>
                                        {{ $institution->institutionType->name_ar }}
                                    </div>

                                    @if ($institution->institutionType->code)

                                        <div
                                            dir="ltr"
                                            style="
                                                margin-block-start:3px;
                                                color:var(--text-secondary);
                                                font-size:var(--text-sm);
                                            "
                                        >
                                            {{ $institution->institutionType->code }}
                                        </div>

                                    @endif

                                @else

                                    <span
                                        style="
                                            color:var(--text-secondary);
                                        "
                                    >
                                        —
                                    </span>

                                @endif

                            </td>


                            {{-- Status --}}
                            <td>

                                @if ($institution->is_active)

                                    <span
                                        style="
                                            display:inline-flex;
                                            align-items:center;
                                            gap:6px;
                                            padding:5px 10px;
                                            border-radius:999px;
                                            background:#dcfce7;
                                            color:#166534;
                                            font-size:var(--text-sm);
                                            font-weight:600;
                                        "
                                    >

                                        <i
                                            class="bi bi-check-circle-fill"
                                            aria-hidden="true"
                                        ></i>

                                        نشطة

                                    </span>

                                @else

                                    <span
                                        style="
                                            display:inline-flex;
                                            align-items:center;
                                            gap:6px;
                                            padding:5px 10px;
                                            border-radius:999px;
                                            background:#f3f4f6;
                                            color:#4b5563;
                                            font-size:var(--text-sm);
                                            font-weight:600;
                                        "
                                    >

                                        <i
                                            class="bi bi-pause-circle-fill"
                                            aria-hidden="true"
                                        ></i>

                                        غير نشطة

                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="5"
                                style="
                                    text-align:center;
                                    padding:var(--space-8);
                                    color:var(--text-secondary);
                                "
                            >

                                <div
                                    style="
                                        font-size:34px;
                                        margin-block-end:10px;
                                    "
                                >
                                    <i
                                        class="bi bi-buildings"
                                        aria-hidden="true"
                                    ></i>
                                </div>

                                <div
                                    style="
                                        font-weight:600;
                                        color:var(--text-primary);
                                    "
                                >
                                    لا توجد مؤسسات
                                </div>

                                <div
                                    style="
                                        margin-block-start:5px;
                                        font-size:var(--text-sm);
                                    "
                                >
                                    لم يتم العثور على مؤسسات مطابقة للبحث الحالي.
                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- Pagination --}}
        @if ($institutions->hasPages())

            <div
                style="
                    margin-block-start:var(--space-5);
                    padding-block-start:var(--space-5);
                    border-block-start:1px solid var(--border-color);
                "
            >
                {{ $institutions->links() }}
            </div>

        @endif

    </div>


    {{-- =========================================================
         PAGE STYLES
    ========================================================== --}}
    <style>

        .institution-create-grid {
            display:grid;
            grid-template-columns:repeat(2, minmax(0, 1fr));
            gap:var(--space-5);
        }

        .institution-filter-grid {
            display:grid;
            grid-template-columns:
                minmax(220px, 1.5fr)
                minmax(180px, 1fr)
                minmax(180px, 1fr);
            gap:var(--space-5);
        }

        .institution-field-label {
            display:block;
            margin-block-end:8px;
            font-size:var(--text-sm);
            font-weight:600;
            color:var(--text-primary);
        }

        .institution-help {
            margin-block-start:6px;
            font-size:var(--text-sm);
            color:var(--text-secondary);
            line-height:1.6;
        }

        .institution-error {
            margin-block-start:6px;
            color:#dc2626;
            font-size:var(--text-sm);
        }

        .required {
            color:#dc2626;
        }

        @media (max-width: 900px) {

            .institution-create-grid {
                grid-template-columns:1fr;
            }

            .institution-filter-grid {
                grid-template-columns:1fr;
            }

        }

        @media (max-width: 640px) {

            .institutions-page .btn {
                width:100%;
                justify-content:center;
            }

            .institutions-page
            > div:first-child
            > div:last-child {
                width:100%;
            }

            .institutions-page
            > div:first-child
            > div:last-child
            .btn {
                width:100%;
            }

        }

    </style>


    {{-- =========================================================
         SHARED PAGE STYLES
    ========================================================== --}}
    @include('livewire.admin._partials.page-styles')

</div>

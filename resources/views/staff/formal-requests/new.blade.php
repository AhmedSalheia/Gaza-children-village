@php
    /**
     * Staff portal — new formal request form.
     * Wire model: App\Livewire\Staff\FormalRequests\NewFormalRequest
     */
@endphp

<div class="formal-new-request-page">

    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div class="formal-page-header">

        <div class="formal-header-content">

            <div class="formal-header-icon">
                <i class="bi bi-file-earmark-plus"></i>
            </div>

            <div>
                <h1>
                    {{ __('requests.new_formal_request') }}
                </h1>

                <p>
                    {{ __('ui.formal_requests') }}
                </p>
            </div>

        </div>

        <a
            href="{{ route('staff.formal-requests.index') }}"
            class="formal-back-button"
        >
            <i class="bi bi-arrow-right"></i>
            <span>{{ __('ui.formal_requests') }}</span>
        </a>

    </div>


    {{-- =========================================================
         VALIDATION ERRORS
    ========================================================== --}}
    @if(count($errors) > 0)
        <div class="formal-alert formal-alert-danger">

            <div class="formal-alert-icon">
                <i class="bi bi-exclamation-triangle"></i>
            </div>

            <div class="formal-alert-content">

                <strong>
                    {{ __('ui.validation_error') }}
                </strong>

                <div class="formal-error-list">
                    @foreach($errors as $field => $error)

                        @if(is_array($error))

                            @foreach($error as $message)
                                <p>{{ $message }}</p>
                            @endforeach

                        @else

                            <p>{{ $error }}</p>

                        @endif

                    @endforeach
                </div>

            </div>

        </div>
    @endif


    {{-- =========================================================
         FORM CARD
    ========================================================== --}}
    <div class="formal-form-card">

        <div class="formal-card-header">

            <div class="formal-card-header-icon">
                <i class="bi bi-pencil-square"></i>
            </div>

            <div>
                <h2>
                    {{ __('requests.new_formal_request') }}
                </h2>

                <p>
                    {{ __('requests.request_details') }}
                </p>
            </div>

        </div>


        <form wire:submit="save">

            {{-- =====================================================
                 TYPE + PRIORITY
            ====================================================== --}}
            <div class="formal-grid-2">

                {{-- Request Type --}}
                <div class="formal-field">

                    <label for="requestType">
                        {{ __('requests.request_type') }}
                        <span class="formal-required">*</span>
                    </label>

                    <select
                        id="requestType"
                        wire:model="requestType"
                        class="formal-select"
                    >
                        @foreach($requestTypes as $type)
                            <option value="{{ $type }}">
                                {{ __('ui.' . $type) }}
                            </option>
                        @endforeach
                    </select>

                    @if(isset($errors['requestType']))
                        <p class="formal-field-error">
                            {{ is_array($errors['requestType'])
                                ? implode(', ', $errors['requestType'])
                                : $errors['requestType'] }}
                        </p>
                    @endif

                </div>


                {{-- Priority --}}
                <div class="formal-field">

                    <label for="priority">
                        {{ __('requests.priority') }}
                        <span class="formal-required">*</span>
                    </label>

                    <select
                        id="priority"
                        wire:model="priority"
                        class="formal-select"
                    >
                        @foreach($priorityOptions as $val => $label)
                            <option value="{{ $val }}">
                                {{ __('ui.priority_' . $label) }}
                                {{-- {{ $label }} --}}
                            </option>
                        @endforeach
                    </select>

                    @if(isset($errors['priority']))
                        <p class="formal-field-error">
                            {{ is_array($errors['priority'])
                                ? implode(', ', $errors['priority'])
                                : $errors['priority'] }}
                        </p>
                    @endif

                </div>

            </div>


            {{-- ============================================






            =========
                 ARABIC TITLE
            ====================================================== --}}
            <div class="formal-field">

                <label for="titleAr">
                    {{ __('requests.arabic_title') }}
                    <span class="formal-required">*</span>
                </label>

                <input
                    id="titleAr"
                    wire:model="titleAr"
                    type="text"
                    dir="rtl"
                    class="formal-input formal-input-rtl"
                    placeholder="{{ __('requests.title_ar_placeholder') }}"
                >

                @if(isset($errors['titleAr']))
                    <p class="formal-field-error">
                        {{ is_array($errors['titleAr'])
                            ? implode(', ', $errors['titleAr'])
                            : $errors['titleAr'] }}
                    </p>
                @endif

            </div>


            {{-- =====================================================
                 ENGLISH TITLE
            ====================================================== --}}
            <div class="formal-field">

                <label for="titleEn">
                    {{ __('requests.english_title') }}
                    <span class="formal-required">*</span>
                </label>

                <input
                    id="titleEn"
                    wire:model="titleEn"
                    type="text"
                    dir="ltr"
                    class="formal-input"
                    placeholder="{{ __('requests.title_en_placeholder') }}"
                >

                @if(isset($errors['titleEn']))
                    <p class="formal-field-error">
                        {{ is_array($errors['titleEn'])
                            ? implode(', ', $errors['titleEn'])
                            : $errors['titleEn'] }}
                    </p>
                @endif

            </div>


            {{-- =====================================================
                 REQUEST BODY
            ====================================================== --}}
            <div class="formal-field">

                <label for="bodyText">
                    {{ __('requests.request_body') }}
                    <span class="formal-required">*</span>
                </label>

                <textarea
                    id="bodyText"
                    wire:model="bodyText"
                    rows="8"
                    class="formal-textarea"
                    placeholder="{{ __('requests.body_placeholder') }}"
                ></textarea>

                @if(isset($errors['bodyText']))
                    <p class="formal-field-error">
                        {{ is_array($errors['bodyText'])
                            ? implode(', ', $errors['bodyText'])
                            : $errors['bodyText'] }}
                    </p>
                @endif

            </div>


            {{-- =====================================================
                 DUE DATE
            ====================================================== --}}
            <div class="formal-field formal-date-field">

                <label for="dueDate">
                    {{ __('requests.due_date_optional') }}
                </label>

                <input
                    id="dueDate"
                    wire:model="dueDate"
                    type="date"
                    class="formal-input"
                >

                @if(isset($errors['dueDate']))
                    <p class="formal-field-error">
                        {{ is_array($errors['dueDate'])
                            ? implode(', ', $errors['dueDate'])
                            : $errors['dueDate'] }}
                    </p>
                @endif

            </div>


            {{-- =====================================================
                 FORM FOOTER
            ====================================================== --}}
            <div class="formal-form-footer">

                <a
                    href="{{ route('staff.formal-requests.index') }}"
                    class="formal-secondary-button"
                >
                    <i class="bi bi-x-lg"></i>
                    <span>{{ __('ui.cancel') }}</span>
                </a>

                <button
                    type="submit"
                    class="formal-primary-button"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>
                        <i class="bi bi-save"></i>
                        {{ __('requests.save_as_draft') }}
                    </span>

                    <span wire:loading>
                        <i class="bi bi-arrow-repeat formal-spin"></i>
                        {{ __('ui.loading') }}
                    </span>
                </button>

            </div>

        </form>

    </div>

</div>


<style>
/* =========================================================
   PAGE
========================================================= */

.formal-new-request-page {
    width: 100%;
    max-width: 1180px;
    margin: 0 auto;
    padding: 0;
    background: transparent !important;
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
}


/* =========================================================
   PAGE HEADER
========================================================= */

.formal-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 24px;
    padding: 0;
}

.formal-header-content {
    display: flex;
    align-items: center;
    gap: 14px;
}

.formal-header-icon {
    width: 50px;
    height: 50px;
    min-width: 50px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 14px;

    background: linear-gradient(
        135deg,
        #e7f3fa,
        #d7eaf5
    );

    color: #4b86ae;

    font-size: 22px;

    border: none !important;
    box-shadow: none !important;
}

.formal-page-header h1 {
    margin: 0;
    color: #243746;
    font-size: 24px;
    font-weight: 700;
    line-height: 1.4;
}

.formal-page-header p {
    margin: 4px 0 0;
    color: #71808c;
    font-size: 13px;
}


/* =========================================================
   BACK BUTTON
========================================================= */

.formal-back-button {
    display: inline-flex;
    align-items: center;
    gap: 7px;

    padding: 9px 14px;

    border-radius: 9px;

    color: #4b86ae;
    background: #f4f9fc;

    border: 1px solid #dce8ef;

    text-decoration: none;

    font-size: 13px;
    font-weight: 600;

    transition: background .18s ease,
                color .18s ease,
                transform .18s ease;
}

.formal-back-button:hover {
    background: #eaf4f9;
    color: #3e769c;
    transform: translateY(-1px);
}

.formal-back-button i {
    font-size: 14px;
}


/* =========================================================
   ALERT
========================================================= */

.formal-alert {
    display: flex;
    align-items: flex-start;
    gap: 12px;

    margin-bottom: 20px;
    padding: 14px 16px;

    border-radius: 12px;
}

.formal-alert-danger {
    background: #fff6f6;
    border: 1px solid #f1d5d5;
    color: #a13d3d;
}

.formal-alert-icon {
    width: 34px;
    height: 34px;
    min-width: 34px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 9px;

    background: #fdeaea;
    color: #b94a48;
}

.formal-alert-content {
    flex: 1;
}

.formal-alert-content strong {
    display: block;
    margin-bottom: 5px;
    font-size: 13px;
}

.formal-error-list {
    font-size: 13px;
    line-height: 1.7;
}

.formal-error-list p {
    margin: 0;
}


/* =========================================================
   FORM CARD
========================================================= */

.formal-form-card {
    background: #ffffff;

    border: 1px solid #dfe8f0;

    border-radius: 15px;

    box-shadow: 0 6px 24px rgba(30, 70, 100, .04);

    overflow: hidden;
}


/* =========================================================
   CARD HEADER
========================================================= */

.formal-card-header {
    display: flex;
    align-items: center;
    gap: 13px;

    padding: 20px 22px;

    border-bottom: 1px solid #edf2f5;

    background: #fbfdfe;
}

.formal-card-header-icon {
    width: 40px;
    height: 40px;
    min-width: 40px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 11px;

    background: #e8f3f9;
    color: #4b86ae;

    font-size: 18px;
}

.formal-card-header h2 {
    margin: 0;

    color: #2e4351;

    font-size: 16px;
    font-weight: 700;
}

.formal-card-header p {
    margin: 3px 0 0;

    color: #82909a;

    font-size: 12px;
}


/* =========================================================
   FORM
========================================================= */

.formal-form-card form {
    padding: 24px;
}

.formal-grid-2 {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 18px;
}

.formal-field {
    margin-bottom: 18px;
}

.formal-grid-2 .formal-field {
    margin-bottom: 0;
}

.formal-field label {
    display: block;

    margin-bottom: 7px;

    color: #40515d;

    font-size: 13px;
    font-weight: 600;
}

.formal-required {
    color: #c35b5b;
    margin-inline-start: 2px;
}


/* =========================================================
   INPUTS
========================================================= */

.formal-input,
.formal-select,
.formal-textarea {
    width: 100%;

    display: block;

    padding: 10px 12px;

    border: 1px solid #d4dfe6;

    border-radius: 9px;

    background: #ffffff;

    color: #344854;

    font-size: 13px;

    transition:
        border-color .15s ease,
        background .15s ease;
}

.formal-input::placeholder,
.formal-textarea::placeholder {
    color: #a5b0b8;
}

.formal-textarea {
    min-height: 170px;
    resize: vertical;
    line-height: 1.7;
}

.formal-input-rtl {
    text-align: right;
}


/* =========================================================
   IMPORTANT:
   REMOVE YELLOW / BLUE FOCUS BORDER
========================================================= */

.formal-new-request-page,
.formal-new-request-page *,
.formal-new-request-page *::before,
.formal-new-request-page *::after {
    outline: none !important;
}

.formal-new-request-page :focus,
.formal-new-request-page :focus-visible,
.formal-new-request-page input:focus,
.formal-new-request-page input:focus-visible,
.formal-new-request-page select:focus,
.formal-new-request-page select:focus-visible,
.formal-new-request-page textarea:focus,
.formal-new-request-page textarea:focus-visible,
.formal-new-request-page button:focus,
.formal-new-request-page button:focus-visible,
.formal-new-request-page a:focus,
.formal-new-request-page a:focus-visible {
    outline: none !important;
    box-shadow: none !important;
}

.formal-input:focus,
.formal-select:focus,
.formal-textarea:focus {
    border-color: #d4dfe6 !important;
    outline: none !important;
    box-shadow: none !important;
}


/* =========================================================
   VALIDATION ERROR
========================================================= */

.formal-field-error {
    margin: 5px 0 0;

    color: #c04d4d;

    font-size: 11px;
    line-height: 1.5;
}


/* =========================================================
   DATE
========================================================= */

.formal-date-field {
    max-width: 300px;
}


/* =========================================================
   FOOTER
========================================================= */

.formal-form-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;

    margin-top: 8px;
    padding-top: 20px;

    border-top: 1px solid #edf2f5;
}


/* =========================================================
   BUTTONS
========================================================= */

.formal-primary-button,
.formal-secondary-button {
    min-height: 40px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    gap: 7px;

    padding: 9px 17px;

    border-radius: 9px;

    font-size: 13px;
    font-weight: 600;

    text-decoration: none;

    cursor: pointer;

    transition:
        background .18s ease,
        border-color .18s ease,
        color .18s ease,
        transform .18s ease;
}

.formal-primary-button {
    border: 1px solid #4b86ae;

    background: #4b86ae;

    color: #ffffff;
}

.formal-primary-button:hover {
    background: #3f789e;
    border-color: #3f789e;
    transform: translateY(-1px);
}

.formal-secondary-button {
    border: 1px solid #d4dfe6;

    background: #ffffff;

    color: #526571;
}

.formal-secondary-button:hover {
    background: #f6f9fb;
    border-color: #c8d6df;
    color: #3d5260;
}

.formal-primary-button:disabled {
    opacity: .65;
    cursor: not-allowed;
    transform: none;
}


/* =========================================================
   LOADING
========================================================= */

.formal-spin {
    display: inline-block;
    animation: formal-spin 1s linear infinite;
}

@keyframes formal-spin {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 768px) {

    .formal-page-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .formal-back-button {
        align-self: flex-start;
    }

    .formal-grid-2 {
        grid-template-columns: 1fr;
    }

    .formal-form-card form {
        padding: 18px;
    }

    .formal-date-field {
        max-width: none;
    }

}

@media (max-width: 520px) {

    .formal-page-header h1 {
        font-size: 20px;
    }

    .formal-header-icon {
        width: 44px;
        height: 44px;
        min-width: 44px;
        font-size: 19px;
    }

    .formal-card-header {
        padding: 16px;
    }

    .formal-form-card form {
        padding: 16px;
    }

    .formal-form-footer {
        flex-direction: column-reverse;
        align-items: stretch;
    }

    .formal-primary-button,
    .formal-secondary-button {
        width: 100%;
    }

    .formal-alert {
        padding: 12px;
    }
}


/* =========================================================
   REMOVE ANY OUTER PAGE BORDER / YELLOW LINE
========================================================= */

.formal-new-request-page,
.formal-new-request-page::before,
.formal-new-request-page::after {
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
}

.formal-new-request-page *:focus,
.formal-new-request-page *:focus-visible {
    outline: none !important;
}
</style>


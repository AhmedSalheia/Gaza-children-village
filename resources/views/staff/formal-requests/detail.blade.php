@php
    /**
     * Staff portal — formal request detail / action screen.
     * Wire model: App\Livewire\Staff\FormalRequests\FormalRequestDetail
     */
@endphp

<div class="formal-request-detail-page">

    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div class="formal-page-header">

        <div class="formal-header-content">

            <div class="formal-header-icon">
                <i class="bi bi-file-earmark-text"></i>
            </div>

            <div class="formal-header-text">

                <a
                    href="{{ route('staff.formal-requests.index') }}"
                    class="formal-back-link"
                >
                    <i class="bi bi-arrow-right-short"></i>
                    {{ __('ui.formal_requests') }}
                </a>

                <h1>
                    {{ $request->title_en }}
                </h1>

                <div class="formal-header-meta">
                    <span>
                        <i class="bi bi-hash"></i>
                        {{ $request->request_number }}
                    </span>

                    <span class="formal-meta-separator">•</span>

                    <span>
                        <i class="bi bi-layers"></i>
                        v{{ $request->version }}
                    </span>
                </div>

                @if($request->branched_from_id)
                    <div class="formal-branch-info">
                        <i class="bi bi-diagram-3"></i>
                        {{ __('requests.branched_from', ['id' => $request->branched_from_id]) }}
                    </div>
                @endif

            </div>

            <div class="formal-header-status">

                @php
                    $statusClasses = match($request->current_status) {
                        'closed',
                        'cancelled',
                        'superseded'
                            => 'status-neutral',

                        'signed',
                        'accepted'
                            => 'status-success',

                        'returned_to_preparer',
                        'clarification_requested'
                            => 'status-warning',

                        default
                            => 'status-primary',
                    };
                @endphp

                <span class="formal-status-badge {{ $statusClasses }}">
                    <span class="status-dot"></span>
                    {{ __('ui.' . $request->current_status) }}
                </span>

            </div>

        </div>
    </div>


    {{-- =========================================================
         FLASH MESSAGE
    ========================================================== --}}
    @if($flashMessage)
        <div class="formal-alert formal-alert-success">
            <div class="formal-alert-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>

            <div>
                {{ $flashMessage }}
            </div>
        </div>
    @endif


    {{-- =========================================================
         VALIDATION ERRORS
    ========================================================== --}}
    @if(count($errors) > 0)
        <div class="formal-alert formal-alert-danger">

            <div class="formal-alert-icon">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>

            <div class="formal-alert-content">
                @foreach($errors as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>

        </div>
    @endif


    {{-- =========================================================
         REQUEST DETAILS
    ========================================================== --}}
    <section class="formal-card">

        <div class="formal-card-header">

            <div class="formal-card-title-wrap">

                <div class="formal-card-icon">
                    <i class="bi bi-info-circle"></i>
                </div>

                <div>
                    <h2>
                        {{ __('requests.request_details') }}
                    </h2>

                    <p>
                        {{ __('ui.formal_requests') }}
                    </p>
                </div>

            </div>

            @if($canPrepare && $request->isEditable())
                <button
                    type="button"
                    wire:click="toggleEdit"
                    class="formal-outline-button"
                >
                    <i class="bi {{ $editMode ? 'bi-x-circle' : 'bi-pencil-square' }}"></i>

                    {{ $editMode ? __('requests.cancel_edit') : __('ui.edit') }}
                </button>
            @endif

        </div>


        {{-- =====================================================
             EDIT MODE
        ====================================================== --}}
        @if($editMode)

            <div class="formal-form">

                <div class="formal-field">

                    <label>
                        {{ __('requests.arabic_title') }}
                    </label>

                    <input
                        wire:model="titleAr"
                        type="text"
                        dir="rtl"
                        class="formal-input"
                    >

                </div>


                <div class="formal-field">

                    <label>
                        {{ __('requests.english_title') }}
                    </label>

                    <input
                        wire:model="titleEn"
                        type="text"
                        class="formal-input"
                    >

                </div>


                <div class="formal-field">

                    <label>
                        {{ __('requests.body') }}
                    </label>

                    <textarea
                        wire:model="bodyText"
                        rows="7"
                        class="formal-input formal-textarea"
                    ></textarea>

                </div>


                <div class="formal-form-grid">

                    <div class="formal-field">

                        <label>
                            {{ __('ui.priority') }}
                        </label>

                        <select
                            wire:model="priority"
                            class="formal-input"
                        >
                            <option value="1">
                                {{ __('ui.priority_low') }}
                            </option>

                            <option value="2">
                                {{ __('ui.priority_medium') }}
                            </option>

                            <option value="3">
                                {{ __('ui.priority_high') }}
                            </option>

                            <option value="4">
                                {{ __('ui.priority_urgent') }}
                            </option>
                        </select>

                    </div>


                    <div class="formal-field">

                        <label>
                            {{ __('requests.due_date') }}
                        </label>

                        <input
                            wire:model="dueDate"
                            type="date"
                            class="formal-input"
                        >

                    </div>

                </div>


                <div class="formal-form-actions">

                    <button
                        type="button"
                        wire:click="toggleEdit"
                        class="formal-secondary-button"
                    >
                        {{ __('ui.cancel') }}
                    </button>

                    <button
                        type="button"
                        wire:click="saveEdit"
                        class="formal-primary-button"
                    >
                        <i class="bi bi-check-lg"></i>
                        {{ __('ui.save') }}
                    </button>

                </div>

            </div>

        @else

            {{-- =================================================
                 READ ONLY DETAILS
            ================================================== --}}
            <div class="formal-details-list">

                <div class="formal-detail-row">

                    <div class="formal-detail-label">
                        <i class="bi bi-tag"></i>
                        {{ __('ui.type') }}
                    </div>

                    <div class="formal-detail-value">
                        {{ __('ui.' . $request->request_type) }}
                    </div>

                </div>


                <div class="formal-detail-row">

                    <div class="formal-detail-label">
                        <i class="bi bi-translate"></i>
                        {{ __('requests.arabic_title') }}
                    </div>

                    <div
                        class="formal-detail-value"
                        dir="rtl"
                    >
                        {{ $request->title_ar }}
                    </div>

                </div>


                <div class="formal-detail-row">

                    <div class="formal-detail-label">
                        <i class="bi bi-flag"></i>
                        {{ __('ui.priority') }}
                    </div>

                    <div class="formal-detail-value">

                        @php
                            $priorityKey = match((int) $request->priority) {
                                1 => 'priority_low',
                                2 => 'priority_medium',
                                3 => 'priority_high',
                                4 => 'priority_urgent',
                                default => 'priority',
                            };
                        @endphp

                        <span class="priority-badge priority-{{ $request->priority }}">
                            {{ __('ui.' . $priorityKey) }}
                        </span>

                    </div>

                </div>


                @if($request->due_date)

                    <div class="formal-detail-row">

                        <div class="formal-detail-label">
                            <i class="bi bi-calendar-event"></i>
                            {{ __('requests.due_date') }}
                        </div>

                        <div class="formal-detail-value">
                            {{ $request->due_date->format('d M Y') }}
                        </div>

                    </div>

                @endif


                <div class="formal-detail-block">

                    <div class="formal-detail-label">
                        <i class="bi bi-card-text"></i>
                        {{ __('requests.body') }}
                    </div>

                    <div class="formal-body-text">
                        {{ is_array($request->body)
                            ? ($request->body['text'] ?? '')
                            : $request->body
                        }}
                    </div>

                </div>


                @if($request->response_body)

                    <div class="formal-response-box">

                        <div class="formal-response-title">
                            <i class="bi bi-chat-left-text"></i>
                            {{ __('requests.management_response') }}
                        </div>

                        <div class="formal-body-text">
                            {{ is_array($request->response_body)
                                ? ($request->response_body['text'] ?? '')
                                : $request->response_body
                            }}
                        </div>

                        <div class="formal-response-date">
                            <i class="bi bi-clock"></i>
                            {{ __('requests.received') }}

                            {{ $request->response_at?->format('d M Y') }}
                        </div>

                    </div>

                @endif

            </div>

        @endif

    </section>


    {{-- =========================================================
         ATTACHMENTS
    ========================================================== --}}
    <section class="formal-card">

        <div class="formal-card-header">

            <div class="formal-card-title-wrap">

                <div class="formal-card-icon">
                    <i class="bi bi-paperclip"></i>
                </div>

                <div>
                    <h2>
                        {{ __('requests.attachments') }}
                    </h2>

                    <p>
                        {{ __('requests.no_attachments_yet') }}
                    </p>
                </div>

            </div>

        </div>


        <div class="formal-card-body">

            @forelse($attachments as $link)

                @php
                    $att = $link->attachment;
                @endphp

                @if($att && $att->status === 'available')

                    <div class="attachment-item">

                        <div class="attachment-info">

                            <div class="attachment-icon">
                                <i class="bi bi-file-earmark"></i>
                            </div>

                            <div class="attachment-name">

                                <span title="{{ $att->original_filename }}">
                                    {{ $att->original_filename }}
                                </span>

                                <small>
                                    {{ number_format($att->size_bytes / 1024, 1) }} KB
                                </small>

                            </div>

                        </div>

                        <a
                            href="{{ route('staff.attachments.download', $att->id) }}"
                            class="attachment-download"
                            target="_blank"
                        >
                            <i class="bi bi-download"></i>
                            {{ __('ui.download') }}
                        </a>

                    </div>

                @endif

            @empty

                <div class="formal-empty-state">

                    <div class="formal-empty-icon">
                        <i class="bi bi-paperclip"></i>
                    </div>

                    <p>
                        {{ __('requests.no_attachments_yet') }}
                    </p>

                </div>

            @endforelse


            @if($canPrepare && $request->isEditable())

                <div class="attachment-upload">

                    <div class="formal-field">

                        <label>
                            {{ __('requests.upload_attachment_label') }}
                        </label>

                        <input
                            wire:model="attachmentFile"
                            type="file"
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="formal-file-input"
                        >

                        @error('attachmentFile')
                            <p class="formal-field-error">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                    <button
                        type="button"
                        wire:click="uploadAttachment"
                        wire:loading.attr="disabled"
                        class="formal-secondary-button"
                    >
                        <i class="bi bi-cloud-arrow-up"></i>
                        {{ __('ui.upload') }}
                    </button>

                </div>

            @endif

        </div>

    </section>


    {{-- =========================================================
         ACTIONS
    ========================================================== --}}
    @if(! $editMode)

        <section class="formal-card">

            <div class="formal-card-header">

                <div class="formal-card-title-wrap">

                    <div class="formal-card-icon">
                        <i class="bi bi-lightning-charge"></i>
                    </div>

                    <div>
                        <h2>
                            {{ __('ui.actions') }}
                        </h2>

                        <p>
                            {{ __('ui.formal_requests') }}
                        </p>
                    </div>

                </div>

            </div>


            <div class="formal-actions-body">

                @if($canPrepare && $request->current_status === 'draft')

                    <button
                        type="button"
                        wire:click="submitForReview"
                        class="formal-action-button action-blue"
                    >
                        <i class="bi bi-send"></i>
                        {{ __('requests.submit_for_internal_review') }}
                    </button>

                @endif


                @if($canPrepare && $request->current_status === 'returned_to_preparer')

                    <button
                        type="button"
                        wire:click="resubmit"
                        class="formal-action-button action-blue"
                    >
                        <i class="bi bi-arrow-repeat"></i>
                        {{ __('requests.resubmit_for_review') }}
                    </button>

                @endif


                @if($canSubmit && $request->current_status === 'signed')

                    <button
                        type="button"
                        wire:click="submitToManagement"
                        class="formal-action-button action-green"
                    >
                        <i class="bi bi-send-check"></i>
                        {{ __('requests.submit_to_management') }}
                    </button>

                @endif


                @if($canReview && $request->current_status === 'internal_review')

                    <button
                        type="button"
                        wire:click="showReturn"
                        class="formal-action-button action-warning"
                    >
                        <i class="bi bi-arrow-return-left"></i>
                        {{ __('requests.return_to_preparer') }}
                    </button>

                @endif


                @if($canSign && $request->current_status === 'internal_review')

                    <button
                        type="button"
                        wire:click="showSign"
                        class="formal-action-button action-green"
                    >
                        <i class="bi bi-pen"></i>
                        {{ __('requests.sign_request') }}
                    </button>

                @endif


                @if($canSupersede && ! $showSupersedeForm)

                    <button
                        type="button"
                        wire:click="showSupersede"
                        class="formal-action-button action-violet"
                    >
                        <i class="bi bi-copy"></i>
                        {{ __('requests.create_followup') }}
                    </button>

                @endif

            </div>

        </section>

    @endif


    {{-- =========================================================
         RETURN TO PREPARER
    ========================================================== --}}
    @if($showReturnForm)

        <section class="formal-card action-card-warning">

            <div class="formal-card-header">

                <div class="formal-card-title-wrap">

                    <div class="formal-card-icon icon-warning">
                        <i class="bi bi-arrow-return-left"></i>
                    </div>

                    <div>
                        <h2>
                            {{ __('requests.return_to_preparer') }}
                        </h2>
                    </div>

                </div>

            </div>

            <div class="formal-card-body">

                <textarea
                    wire:model="returnReason"
                    rows="4"
                    class="formal-input"
                    placeholder="{{ __('requests.return_reason_placeholder') }}"
                ></textarea>

                <div class="formal-form-actions">

                    <button
                        type="button"
                        wire:click="$set('showReturnForm', false)"
                        class="formal-secondary-button"
                    >
                        {{ __('ui.cancel') }}
                    </button>

                    <button
                        type="button"
                        wire:click="returnToPreparer"
                        class="formal-action-button action-warning"
                    >
                        <i class="bi bi-arrow-return-left"></i>
                        {{ __('requests.confirm_return') }}
                    </button>

                </div>

            </div>

        </section>

    @endif


    {{-- =========================================================
         SIGNING
    ========================================================== --}}
    @if($showSignForm)

        <section class="formal-card action-card-success">

            <div class="formal-card-header">

                <div class="formal-card-title-wrap">

                    <div class="formal-card-icon icon-success">
                        <i class="bi bi-pen"></i>
                    </div>

                    <div>
                        <h2>
                            {{ __('requests.electronic_signature') }}
                        </h2>
                    </div>

                </div>

            </div>

            <div class="formal-card-body">

                <div class="formal-sign-intro">
                    <i class="bi bi-shield-check"></i>
                    <span>{{ __('requests.sign_intro') }}</span>
                </div>


                @if($pendingTokenId === null)

                    <div class="formal-field">

                        <label>
                            {{ __('requests.enter_password_confirm') }}
                        </label>

                        <input
                            wire:model="credential"
                            type="password"
                            class="formal-input"
                            placeholder="{{ __('requests.portal_password_label') }}"
                        >

                    </div>

                    <div class="formal-form-actions">

                        <button
                            type="button"
                            wire:click="$set('showSignForm', false)"
                            class="formal-secondary-button"
                        >
                            {{ __('ui.cancel') }}
                        </button>

                        <button
                            type="button"
                            wire:click="issueSigningToken"
                            class="formal-action-button action-green"
                        >
                            <i class="bi bi-shield-check"></i>
                            {{ __('requests.verify_proceed') }}
                        </button>

                    </div>

                @else

                    <div class="formal-verified-box">

                        <div class="formal-verified-title">
                            <i class="bi bi-check-circle-fill"></i>
                            {{ __('requests.identity_verified') }}
                        </div>

                        <p>
                            {{ __('requests.sign_permanent_notice') }}
                        </p>

                    </div>

                    <div class="formal-form-actions">

                        <button
                            type="button"
                            wire:click="$set('showSignForm', false); $set('pendingTokenId', null)"
                            class="formal-secondary-button"
                        >
                            {{ __('ui.cancel') }}
                        </button>

                        <button
                            type="button"
                            wire:click="confirmSign"
                            class="formal-action-button action-green"
                        >
                            <i class="bi bi-pen"></i>
                            {{ __('requests.confirm_signature') }}
                        </button>

                    </div>

                @endif

            </div>

        </section>

    @endif


    {{-- =========================================================
         SUPERSEDE / FOLLOW-UP
    ========================================================== --}}
    @if($showSupersedeForm)

        <section class="formal-card action-card-violet">

            <div class="formal-card-header">

                <div class="formal-card-title-wrap">

                    <div class="formal-card-icon icon-violet">
                        <i class="bi bi-copy"></i>
                    </div>

                    <div>
                        <h2>
                            {{ __('requests.create_followup_request') }}
                        </h2>

                        <p>
                            {{ __('requests.supersede_intro') }}
                        </p>
                    </div>

                </div>

            </div>

            <div class="formal-card-body">

                <div class="formal-form">

                    <div class="formal-field">

                        <label>
                            {{ __('requests.arabic_title') }}
                        </label>

                        <input
                            wire:model="supersedeTitleAr"
                            type="text"
                            dir="rtl"
                            class="formal-input"
                            placeholder="{{ __('requests.supersede_title_ar_placeholder') }}"
                        >

                    </div>


                    <div class="formal-field">

                        <label>
                            {{ __('requests.english_title') }}
                        </label>

                        <input
                            wire:model="supersedeTitleEn"
                            type="text"
                            class="formal-input"
                            placeholder="{{ __('requests.supersede_title_en_placeholder') }}"
                        >

                    </div>


                    <div class="formal-field">

                        <label>
                            {{ __('requests.body') }}
                        </label>

                        <textarea
                            wire:model="supersedeBodyText"
                            rows="6"
                            class="formal-input"
                            placeholder="{{ __('requests.supersede_body_placeholder') }}"
                        ></textarea>

                    </div>


                    <div class="formal-form-grid">

                        <div class="formal-field">

                            <label>
                                {{ __('ui.priority') }}
                            </label>

                            <select
                                wire:model="supersedePriority"
                                class="formal-input"
                            >
                                <option value="1">
                                    {{ __('ui.priority_low') }}
                                </option>

                                <option value="2">
                                    {{ __('ui.priority_medium') }}
                                </option>

                                <option value="3">
                                    {{ __('ui.priority_high') }}
                                </option>

                                <option value="4">
                                    {{ __('ui.priority_urgent') }}
                                </option>
                            </select>

                        </div>


                        <div class="formal-field">

                            <label>
                                {{ __('requests.due_date_optional') }}
                            </label>

                            <input
                                wire:model="supersedeDueDate"
                                type="date"
                                class="formal-input"
                            >

                        </div>

                    </div>


                    <div class="formal-form-actions">

                        <button
                            type="button"
                            wire:click="cancelSupersede"
                            class="formal-secondary-button"
                        >
                            {{ __('ui.cancel') }}
                        </button>

                        <button
                            type="button"
                            wire:click="supersede"
                            class="formal-action-button action-violet"
                        >
                            <i class="bi bi-copy"></i>
                            {{ __('requests.create_replacement_draft') }}
                        </button>

                    </div>

                </div>

            </div>

        </section>

    @endif


    {{-- =========================================================
         CLARIFICATION RESPONSE
    ========================================================== --}}
    @if($request->current_status === 'clarification_requested' && $canPrepare)

        <section class="formal-card action-card-info">

            <div class="formal-card-header">

                <div class="formal-card-title-wrap">

                    <div class="formal-card-icon">
                        <i class="bi bi-question-circle"></i>
                    </div>

                    <div>
                        <h2>
                            {{ __('requests.respond_clarification_title') }}
                        </h2>
                    </div>

                </div>

            </div>

            <div class="formal-card-body">

                <textarea
                    wire:model="newComment"
                    rows="5"
                    class="formal-input"
                    placeholder="{{ __('requests.clarification_response_placeholder') }}"
                ></textarea>

                <div class="formal-form-actions">

                    <button
                        type="button"
                        wire:click="respondToClarification"
                        class="formal-action-button action-blue"
                    >
                        <i class="bi bi-send"></i>
                        {{ __('requests.submit_clarification') }}
                    </button>

                </div>

            </div>

        </section>

    @endif


    {{-- =========================================================
         COMMENTS
    ========================================================== --}}
    <section class="formal-card">

        <div class="formal-card-header">

            <div class="formal-card-title-wrap">

                <div class="formal-card-icon">
                    <i class="bi bi-chat-left-text"></i>
                </div>

                <div>
                    <h2>
                        {{ __('requests.comments') }}
                    </h2>

                    <p>
                        {{ __('requests.add_comment_placeholder') }}
                    </p>
                </div>

            </div>

        </div>


        <div class="formal-card-body">

            @forelse($comments as $comment)

                <div class="comment-item">

                    <div class="comment-header">

                        <div class="comment-author">

                            <div class="comment-avatar">
                                <i class="bi bi-person"></i>
                            </div>

                            <span>
                                {{ __('ui.' . $comment->commenter_actor_type) }}
                            </span>

                        </div>

                        <span class="comment-date">
                            <i class="bi bi-clock"></i>
                            {{ $comment->created_at->format('d M Y H:i') }}
                        </span>

                    </div>

                    <div class="comment-text">
                        {{ $comment->comment_text }}
                    </div>

                </div>

            @empty

                <div class="formal-empty-state">

                    <div class="formal-empty-icon">
                        <i class="bi bi-chat-left"></i>
                    </div>

                    <p>
                        {{ __('requests.no_comments_yet') }}
                    </p>

                </div>

            @endforelse


            @if(! $request->isTerminal() && $request->current_status !== 'clarification_requested')

                <div class="comment-form">

                    <textarea
                        wire:model="newComment"
                        rows="4"
                        class="formal-input"
                        placeholder="{{ __('requests.add_comment_placeholder') }}"
                    ></textarea>

                    <div class="comment-form-footer">

                        @if($canReview)

                            <select
                                wire:model="commentAudience"
                                class="formal-select-small"
                            >
                                <option value="internal">
                                    {{ __('requests.internal_only') }}
                                </option>

                                <option value="all">
                                    {{ __('requests.all_parties') }}
                                </option>
                            </select>

                        @endif

                        <button
                            type="button"
                            wire:click="addComment"
                            class="formal-secondary-button"
                        >
                            <i class="bi bi-chat-left-text"></i>
                            {{ __('requests.add_comment') }}
                        </button>

                    </div>

                </div>

            @endif

        </div>

    </section>

</div>


{{-- =============================================================
     PAGE STYLES
============================================================== --}}
<style>

    .formal-request-detail-page {
        width: 100%;
        max-width: 1180px;
        margin: 0 auto;
        padding: 0 0 40px;
        color: var(--text-primary, #1f2937);
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
    }

    /* =========================================================
       REMOVE ALL UNWANTED PAGE / FOCUS LINES
    ========================================================== */

    .formal-request-detail-page,
    .formal-request-detail-page::before,
    .formal-request-detail-page::after,
    .formal-request-detail-page *::before,
    .formal-request-detail-page *::after {
        outline: none !important;
    }

    .formal-request-detail-page :focus,
    .formal-request-detail-page :focus-visible,
    .formal-request-detail-page button:focus,
    .formal-request-detail-page button:focus-visible,
    .formal-request-detail-page a:focus,
    .formal-request-detail-page a:focus-visible,
    .formal-request-detail-page input:focus,
    .formal-request-detail-page input:focus-visible,
    .formal-request-detail-page select:focus,
    .formal-request-detail-page select:focus-visible,
    .formal-request-detail-page textarea:focus,
    .formal-request-detail-page textarea:focus-visible,
    .formal-request-detail-page input[type="file"]:focus,
    .formal-request-detail-page input[type="file"]:focus-visible {
        outline: none !important;
        box-shadow: none !important;
    }


    /* =========================================================
       HEADER
    ========================================================== */

    .formal-page-header {
        margin-bottom: 24px;
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
    }

    .formal-header-content {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        width: 100%;
    }

    .formal-header-icon {
        width: 50px;
        height: 50px;
        min-width: 50px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(
            135deg,
            #e7f3fa,
            #d7eaf5
        );
        color: #4b86ae;
        font-size: 22px;
    }

    .formal-header-text {
        min-width: 0;
        flex: 1;
    }

    .formal-back-link {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-bottom: 3px;
        color: #4b86ae;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: color .18s ease;
    }

    .formal-back-link:hover {
        color: #2f6f97;
        text-decoration: none;
    }

    .formal-back-link i {
        font-size: 18px;
    }

    .formal-header-text h1 {
        margin: 0;
        color: var(--text-primary, #1f2937);
        font-size: 24px;
        line-height: 1.4;
        font-weight: 700;
    }

    .formal-header-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 7px;
        margin-top: 5px;
        color: #7a8b99;
        font-size: 12px;
    }

    .formal-header-meta span {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .formal-meta-separator {
        color: #b5c1ca;
    }

    .formal-branch-info {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 7px;
        padding: 5px 9px;
        border-radius: 7px;
        background: #fff7e6;
        color: #9a6a18;
        font-size: 11px;
    }

    .formal-header-status {
        padding-top: 5px;
    }

    .formal-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }

    .status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: currentColor;
    }

    .status-primary {
        background: #eaf4fb;
        color: #4b86ae;
    }

    .status-success {
        background: #eaf8f0;
        color: #2f8a5b;
    }

    .status-warning {
        background: #fff6e5;
        color: #a06d17;
    }

    .status-neutral {
        background: #f1f4f6;
        color: #687782;
    }


    /* =========================================================
       ALERTS
    ========================================================== */

    .formal-alert {
        display: flex;
        align-items: flex-start;
        gap: 11px;
        margin-bottom: 18px;
        padding: 12px 15px;
        border-radius: 10px;
        font-size: 13px;
    }

    .formal-alert-icon {
        flex: 0 0 auto;
        font-size: 16px;
    }

    .formal-alert-success {
        border: 1px solid #cdebd9;
        background: #f0faf4;
        color: #277348;
    }

    .formal-alert-danger {
        border: 1px solid #f1d0d0;
        background: #fff5f5;
        color: #a33d3d;
    }

    .formal-alert-content p {
        margin: 0 0 3px;
    }

    .formal-alert-content p:last-child {
        margin-bottom: 0;
    }


    /* =========================================================
       CARD
    ========================================================== */

    .formal-card {
        width: 100%;
        margin-bottom: 20px;
        overflow: hidden;
        background: #ffffff;
        border: 1px solid #dfe8f0;
        border-radius: 15px;
        box-shadow: 0 6px 24px rgba(30, 70, 100, .04);
    }

    .formal-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 20px;
        border-bottom: 1px solid #edf2f5;
    }

    .formal-card-title-wrap {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .formal-card-icon {
        width: 38px;
        height: 38px;
        min-width: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: var(--interactive-primary, #4b86ae);
        color: #ffffff;
        font-size: 16px;
    }

    .formal-card-icon.icon-warning {
        background: #fff0d2;
        color: #a16c18;
    }

    .formal-card-icon.icon-success {
        background: #e5f6ed;
        color: #32845a;
    }

    .formal-card-icon.icon-violet {
        background: #f0eafd;
        color: #7255a6;
    }

    .formal-card-title-wrap h2 {
        margin: 0;
        color: #253746;
        font-size: 15px;
        font-weight: 700;
    }

    .formal-card-title-wrap p {
        margin: 3px 0 0;
        color: #8a9aa6;
        font-size: 11px;
    }

    .formal-card-body {
        padding: 20px;
    }


    /* =========================================================
       BUTTONS
    ========================================================== */

    .formal-primary-button,
    .formal-secondary-button,
    .formal-outline-button,
    .formal-action-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 38px;
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition:
            transform .15s ease,
            background .15s ease,
            border-color .15s ease;
    }

    .formal-primary-button:hover,
    .formal-secondary-button:hover,
    .formal-outline-button:hover,
    .formal-action-button:hover {
        transform: translateY(-1px);
    }

    .formal-primary-button {
        border: 1px solid #4b86ae;
        background: #4b86ae;
        color: #ffffff;
    }

    .formal-primary-button:hover {
        background: #3f789d;
    }

    .formal-secondary-button {
        border: 1px solid #d8e1e7;
        background: #ffffff;
        color: #526572;
    }

    .formal-secondary-button:hover {
        border-color: #b9cad6;
        background: #f7fafc;
    }

    .formal-outline-button {
        border: 1px solid #cbdde8;
        background: #f8fbfd;
        color: #4b86ae;
    }

    .formal-outline-button:hover {
        background: #edf6fb;
        border-color: #aac7d8;
    }


    /* =========================================================
       FORM
    ========================================================== */

    .formal-form {
        padding: 20px;
    }

    .formal-field {
        margin-bottom: 17px;
    }

    .formal-field:last-child {
        margin-bottom: 0;
    }

    .formal-field label {
        display: block;
        margin-bottom: 7px;
        color: #4e606d;
        font-size: 12px;
        font-weight: 600;
    }

    .formal-input,
    .formal-select-small,
    .formal-file-input {
        width: 100%;
        box-sizing: border-box;
        border: 1px solid #d4dfe6 !important;
        border-radius: 8px;
        background: #ffffff;
        color: #263845;
        font-size: 13px;
        outline: none !important;
        box-shadow: none !important;
        transition: border-color .15s ease;
    }

    .formal-input {
        padding: 10px 12px;
    }

    /*
     * مهم:
     * لا يوجد هنا لون أصفر عند الضغط على الحقل.
     * نحافظ فقط على نفس الحد الهادئ.
     */
    .formal-input:hover,
    .formal-select-small:hover,
    .formal-file-input:hover {
        border-color: #c7d5de !important;
        outline: none !important;
        box-shadow: none !important;
    }

    .formal-input:focus,
    .formal-input:focus-visible,
    .formal-select-small:focus,
    .formal-select-small:focus-visible,
    .formal-file-input:focus,
    .formal-file-input:focus-visible {
        border-color: #d4dfe6 !important;
        outline: none !important;
        box-shadow: none !important;
    }

    .formal-textarea {
        min-height: 130px;
        resize: vertical;
        line-height: 1.7;
    }

    .formal-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .formal-form-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 9px;
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #edf2f5;
    }

    .formal-field-error {
        margin-top: 5px;
        color: #b34b4b;
        font-size: 11px;
    }


    /* =========================================================
       DETAILS
    ========================================================== */

    .formal-details-list {
        padding: 0 20px;
    }

    .formal-detail-row {
        display: grid;
        grid-template-columns: 180px minmax(0, 1fr);
        gap: 20px;
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px solid #edf2f5;
    }

    .formal-detail-row:last-child {
        border-bottom: 0;
    }

    .formal-detail-label {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #81919c;
        font-size: 12px;
        font-weight: 600;
    }

    .formal-detail-label i {
        color: #7ea5bd;
    }

    .formal-detail-value {
        color: #344955;
        font-size: 13px;
        line-height: 1.6;
    }

    .formal-detail-block {
        padding: 17px 0;
        border-bottom: 1px solid #edf2f5;
    }

    .formal-body-text {
        margin-top: 8px;
        color: #3d4f5b;
        font-size: 13px;
        line-height: 1.9;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
    }

    .formal-response-box {
        margin: 17px 0;
        padding: 15px;
        border: 1px solid #d8eaf5;
        border-radius: 10px;
        background: #f4f9fc;
    }

    .formal-response-title {
        display: flex;
        align-items: center;
        gap: 7px;
        color: #4b86ae;
        font-size: 13px;
        font-weight: 700;
    }

    .formal-response-date {
        display: flex;
        align-items: center;
        gap: 5px;
        margin-top: 10px;
        color: #8b9ba5;
        font-size: 11px;
    }


    /* =========================================================
       PRIORITY
    ========================================================== */

    .priority-badge {
        display: inline-flex;
        align-items: center;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
    }

    .priority-1 {
        background: #eef3f6;
        color: #687983;
    }

    .priority-2 {
        background: #eaf4fb;
        color: #4b86ae;
    }

    .priority-3 {
        background: #fff2df;
        color: #a36b20;
    }

    .priority-4 {
        background: #fdeaea;
        color: #ad4c4c;
    }


    /* =========================================================
       ATTACHMENTS
    ========================================================== */

    .attachment-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        padding: 11px 12px;
        margin-bottom: 8px;
        border: 1px solid #e6edf2;
        border-radius: 9px;
        background: #fafcfd;
    }

    .attachment-info {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .attachment-icon {
        width: 34px;
        height: 34px;
        min-width: 34px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: #e9f3f9;
        color: #5687a5;
    }

    .attachment-name {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .attachment-name > span {
        max-width: 600px;
        overflow: hidden;
        color: #455964;
        font-size: 12px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .attachment-name small {
        margin-top: 2px;
        color: #9aa8b0;
        font-size: 10px;
    }

    .attachment-download {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        flex-shrink: 0;
        color: #4b86ae;
        font-size: 11px;
        font-weight: 600;
        text-decoration: none;
        outline: none !important;
        box-shadow: none !important;
    }

    .attachment-download:hover {
        color: #315f7d;
    }

    .attachment-download:focus,
    .attachment-download:focus-visible {
        outline: none !important;
        box-shadow: none !important;
    }

    .attachment-upload {
        margin-top: 18px;
        padding-top: 18px;
        border-top: 1px solid #edf2f5;
    }

    .formal-file-input {
        padding: 8px;
        background: #fafcfd;
    }


    /* =========================================================
       EMPTY
    ========================================================== */

    .formal-empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 130px;
        text-align: center;
    }

    .formal-empty-icon {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        border-radius: 12px;
        background: linear-gradient(
            135deg,
            #e7f3fa,
            #d7eaf5
        );
        color: #709bb6;
        font-size: 20px;
    }

    .formal-empty-state p {
        margin: 0;
        color: #98a7b0;
        font-size: 12px;
    }


    /* =========================================================
       ACTIONS
    ========================================================== */

    .formal-actions-body {
        display: flex;
        flex-wrap: wrap;
        gap: 9px;
        padding: 20px;
    }

    .formal-action-button {
        border: 1px solid transparent;
    }

    .action-blue {
        background: #4b86ae;
        color: #ffffff;
        border-color: #4b86ae;
    }

    .action-blue:hover {
        background: #3f789d;
    }

    .action-green {
        background: #3d9566;
        color: #ffffff;
        border-color: #3d9566;
    }

    .action-green:hover {
        background: #347f57;
    }

    .action-warning {
        background: #fff4df;
        color: #98671b;
        border-color: #edd09b;
    }

    .action-warning:hover {
        background: #ffedce;
    }

    .action-violet {
        background: #7659a9;
        color: #ffffff;
        border-color: #7659a9;
    }

    .action-violet:hover {
        background: #684d96;
    }


    /* =========================================================
       ACTION CARDS
    ========================================================== */

    .action-card-warning {
        border-color: #eadbbd;
    }

    .action-card-success {
        border-color: #d0e8da;
    }

    .action-card-violet {
        border-color: #ddd3ef;
    }

    .action-card-info {
        border-color: #d2e6f2;
    }

    .formal-sign-intro {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 18px;
        padding: 12px 14px;
        border-radius: 9px;
        background: #eef9f3;
        color: #397956;
        font-size: 12px;
        line-height: 1.7;
    }

    .formal-sign-intro i {
        margin-top: 2px;
        font-size: 17px;
    }

    .formal-verified-box {
        padding: 14px;
        border: 1px solid #cce6d7;
        border-radius: 9px;
        background: #f5fbf7;
    }

    .formal-verified-title {
        display: flex;
        align-items: center;
        gap: 7px;
        color: #32845a;
        font-size: 13px;
        font-weight: 700;
    }

    .formal-verified-box p {
        margin: 6px 0 0;
        color: #64776c;
        font-size: 12px;
        line-height: 1.7;
    }


    /* =========================================================
       COMMENTS
    ========================================================== */

    .comment-item {
        margin-bottom: 11px;
        padding: 13px;
        border: 1px solid #e6edf2;
        border-radius: 10px;
        background: #fafcfd;
    }

    .comment-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 9px;
    }

    .comment-author {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #526572;
        font-size: 12px;
        font-weight: 600;
    }

    .comment-avatar {
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #e8f2f8;
        color: #5b89a5;
    }

    .comment-date {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        color: #9aa7ae;
        font-size: 10px;
    }

    .comment-text {
        padding-inline-start: 36px;
        color: #42535e;
        font-size: 12px;
        line-height: 1.8;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
    }

    .comment-form {
        margin-top: 18px;
        padding-top: 18px;
        border-top: 1px solid #edf2f5;
    }

    .comment-form-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 10px;
    }

    .formal-select-small {
        width: auto;
        min-width: 150px;
        padding: 8px 10px;
        font-size: 11px;
    }


    /* =========================================================
       REMOVE LIVEWIRE / BROWSER FOCUS LINES
    ========================================================== */

    [wire\:loading],
    [wire\:loading\.class],
    [wire\:loading\.class\.remove] {
        outline: none !important;
        box-shadow: none !important;
    }

    .formal-request-detail-page button,
    .formal-request-detail-page a,
    .formal-request-detail-page input,
    .formal-request-detail-page select,
    .formal-request-detail-page textarea {
        -webkit-tap-highlight-color: transparent;
    }

    .formal-request-detail-page button:focus,
    .formal-request-detail-page button:focus-visible,
    .formal-request-detail-page a:focus,
    .formal-request-detail-page a:focus-visible {
        outline: none !important;
        box-shadow: none !important;
    }


    /* =========================================================
       RESPONSIVE
    ========================================================== */

    @media (max-width: 768px) {

        .formal-request-detail-page {
            padding-bottom: 25px;
        }

        .formal-header-content {
            flex-wrap: wrap;
        }

        .formal-header-status {
            width: 100%;
            padding-top: 0;
            padding-inline-start: 66px;
        }

        .formal-header-text h1 {
            font-size: 20px;
        }

        .formal-card-header {
            align-items: flex-start;
        }

        .formal-detail-row {
            grid-template-columns: 1fr;
            gap: 5px;
        }

        .formal-form-grid {
            grid-template-columns: 1fr;
        }

        .attachment-item {
            align-items: flex-start;
        }

        .attachment-download {
            margin-top: 3px;
        }

        .comment-header {
            align-items: flex-start;
        }

        .comment-date {
            white-space: nowrap;
        }

    }


    @media (max-width: 520px) {

        .formal-card-header,
        .formal-card-body,
        .formal-form,
        .formal-actions-body {
            padding: 15px;
        }

        .formal-header-status {
            padding-inline-start: 0;
        }

        .formal-form-actions,
        .comment-form-footer {
            justify-content: stretch;
        }

        .formal-form-actions > *,
        .comment-form-footer > * {
            flex: 1 1 auto;
        }

        .formal-action-button,
        .formal-secondary-button,
        .formal-primary-button {
            width: 100%;
        }

        .attachment-item {
            flex-direction: column;
        }

        .attachment-download {
            align-self: flex-start;
        }

        .comment-text {
            padding-inline-start: 0;
        }

    }

</style>

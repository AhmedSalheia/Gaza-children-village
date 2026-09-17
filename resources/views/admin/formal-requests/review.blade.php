@php
    /**
     * Admin portal — formal request management review screen.
     * Wire model: App\Livewire\Admin\FormalRequests\ManagementReview
     */

    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';

    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */
    $statusLabels = [
        'submitted_to_management' => [
            'label' => __('ui.status_submitted_to_management', [], null, 'Submitted to Management'),
            'class' => 'status-submitted',
            'icon'  => 'bi-send',
        ],

        'under_management_review' => [
            'label' => __('ui.status_under_management_review', [], null, 'Under Management Review'),
            'class' => 'status-review',
            'icon'  => 'bi-search',
        ],

        'clarification_requested' => [
            'label' => __('ui.status_clarification_requested', [], null, 'Clarification Requested'),
            'class' => 'status-clarification',
            'icon'  => 'bi-question-circle',
        ],

        'accepted' => [
            'label' => __('ui.status_accepted', [], null, 'Accepted'),
            'class' => 'status-accepted',
            'icon'  => 'bi-check-circle',
        ],

        'rejected' => [
            'label' => __('ui.status_rejected', [], null, 'Rejected'),
            'class' => 'status-rejected',
            'icon'  => 'bi-x-circle',
        ],

        'responded' => [
            'label' => __('ui.status_responded', [], null, 'Responded'),
            'class' => 'status-responded',
            'icon'  => 'bi-reply',
        ],

        'closed' => [
            'label' => __('ui.status_closed', [], null, 'Closed'),
            'class' => 'status-closed',
            'icon'  => 'bi-lock',
        ],
    ];

    $currentStatus = $statusLabels[$request->current_status] ?? [
        'label' => ucwords(str_replace('_', ' ', $request->current_status)),
        'class' => 'status-default',
        'icon'  => 'bi-circle',
    ];

    /*
    |--------------------------------------------------------------------------
    | Priority
    |--------------------------------------------------------------------------
    */
    $priorityLabels = [
        1 => [
            'label' => __('ui.priority_low', [], null, 'Low'),
            'class' => 'priority-low',
        ],
        2 => [
            'label' => __('ui.priority_medium', [], null, 'Medium'),
            'class' => 'priority-medium',
        ],
        3 => [
            'label' => __('ui.priority_high', [], null, 'High'),
            'class' => 'priority-high',
        ],
        4 => [
            'label' => __('ui.priority_urgent', [], null, 'Urgent'),
            'class' => 'priority-urgent',
        ],
    ];

    $currentPriority = $priorityLabels[$request->priority] ?? [
        'label' => '',
        'class' => 'priority-default',
    ];

    /*
    |--------------------------------------------------------------------------
    | Request type
    |--------------------------------------------------------------------------
    */
    $requestTypeLabel = __('ui.' . $request->request_type, [], null, ucwords(
        str_replace('_', ' ', $request->request_type)
    ));
@endphp

<div class="formal-request-review-page">

    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div class="review-page-header">

        <div class="review-header-main">

            <a
                href="{{ route('admin.formal-requests.index') }}"
                class="review-back-link"
            >
                <i class="bi {{ $isArabic ? 'bi-arrow-right' : 'bi-arrow-left' }}"></i>
                <span>{{ __('ui.inbox', [], null, 'Formal Requests Management') }}</span>
            </a>

            <div class="review-title-row">

                <div class="review-title-icon">
                    <i class="bi bi-file-earmark-text"></i>
                </div>

                <div class="review-title-content">

                    <h1 class="review-page-title">
                        {{ $request->title_en }}
                    </h1>

                    <div class="review-meta">

                        <span>
                            <i class="bi bi-hash"></i>
                            {{ $request->request_number }}
                        </span>

                        <span class="review-meta-separator">•</span>

                        <span>
                            <i class="bi bi-building"></i>
                            {{ __('ui.institution') }}
                            #{{ $request->institution_id }}
                        </span>

                    </div>

                </div>

            </div>

        </div>

        <div class="review-header-status">

            <span class="status-badge {{ $currentStatus['class'] }}">
                <i class="bi {{ $currentStatus['icon'] }}"></i>
                {{ $currentStatus['label'] }}
            </span>

        </div>

    </div>


    {{-- =========================================================
         FLASH MESSAGE
    ========================================================== --}}
    @if($flashMessage)

        <div class="review-alert review-alert-success">

            <div class="review-alert-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>

            <div class="review-alert-content">
                {{ $flashMessage }}
            </div>

        </div>

    @endif


    {{-- =========================================================
         ERRORS
    ========================================================== --}}
    @if(count($errors) > 0)

        <div class="review-alert review-alert-danger">

            <div class="review-alert-icon">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>

            <div class="review-alert-content">

                @foreach($errors as $error)
                    <div>{{ $error }}</div>
                @endforeach

            </div>

        </div>

    @endif


    {{-- =========================================================
         REQUEST INFORMATION
    ========================================================== --}}
    <section class="review-card">

        <div class="review-card-header">

            <div class="review-card-heading">

                <div class="review-card-icon">
                    <i class="bi bi-info-circle"></i>
                </div>

                <div>
                    <h2>
                        {{ __('ui.request_details', [], null, 'Request Details') }}
                    </h2>

                    <p>
                        {{ __('ui.request_details_description', [], null, 'Complete information about this formal request.') }}
                    </p>
                </div>

            </div>

        </div>


        <div class="review-card-body">

            <div class="details-grid">

                {{-- Request Type --}}
                <div class="detail-item">

                    <div class="detail-label">
                        <i class="bi bi-tag"></i>
                        {{ __('ui.type') }}
                    </div>

                    <div class="detail-value">
                        {{ $requestTypeLabel }}
                    </div>

                </div>


                {{-- Request Number --}}
                <div class="detail-item">

                    <div class="detail-label">
                        <i class="bi bi-hash"></i>
                        {{ __('ui.document_number', [], null, 'Document Number') }}
                    </div>

                    <div class="detail-value font-mono">
                        {{ $request->request_number }}
                    </div>

                </div>


                {{-- Priority --}}
                <div class="detail-item">

                    <div class="detail-label">
                        <i class="bi bi-flag"></i>
                        {{ __('ui.priority') }}
                    </div>

                    <div class="detail-value">

                        <span class="priority-badge {{ $currentPriority['class'] }}">
                            {{ $currentPriority['label'] }}
                        </span>

                    </div>

                </div>


                {{-- Version --}}
                <div class="detail-item">

                    <div class="detail-label">
                        <i class="bi bi-layers"></i>
                        {{ __('ui.version_label', [], null, 'Version') }}
                    </div>

                    <div class="detail-value">
                        {{ $request->version }}
                    </div>

                </div>


                {{-- Institution --}}
                <div class="detail-item">

                    <div class="detail-label">
                        <i class="bi bi-building"></i>
                        {{ __('ui.institution') }}
                    </div>

                    <div class="detail-value">
                        #{{ $request->institution_id }}
                    </div>

                </div>


                {{-- Due Date --}}
                @if($request->due_date)

                    <div class="detail-item">

                        <div class="detail-label">
                            <i class="bi bi-calendar-event"></i>
                            {{ __('ui.due_date') }}
                        </div>

                        <div class="detail-value">
                            {{ $request->due_date->format('d M Y') }}
                        </div>

                    </div>

                @endif


                {{-- Arabic Title --}}
                <div class="detail-item detail-item-wide">

                    <div class="detail-label">
                        <i class="bi bi-translate"></i>
                        {{ __('ui.arabic_title') }}
                    </div>

                    <div
                        class="detail-value detail-arabic"
                        dir="rtl"
                    >
                        {{ $request->title_ar }}
                    </div>

                </div>


                {{-- Content Hash --}}
                @if($request->content_hash)

                    <div class="detail-item detail-item-wide">

                        <div class="detail-label">
                            <i class="bi bi-shield-check"></i>
                            {{ __('ui.signed') }}
                        </div>

                        <div class="detail-value">

                            <span class="hash-value">
                                {{ __('ui.hash') }}:
                                {{ substr($request->content_hash, 0, 16) }}…
                            </span>

                        </div>

                    </div>

                @endif

            </div>


            {{-- Request Body --}}
            <div class="request-body-section">

                <div class="detail-label">
                    <i class="bi bi-file-text"></i>
                    {{ __('ui.body') }}
                </div>

                <div class="request-body-content">
                    {{ is_array($request->body) ? ($request->body['text'] ?? '') : $request->body }}
                </div>

            </div>

        </div>

    </section>


    {{-- =========================================================
         SUPPORTING ATTACHMENTS
    ========================================================== --}}
    <section class="review-card">

        <div class="review-card-header">

            <div class="review-card-heading">

                <div class="review-card-icon">
                    <i class="bi bi-paperclip"></i>
                </div>

                <div>

                    <h2>
                        {{ __('ui.supporting_attachments', [], null, 'Supporting Attachments') }}
                    </h2>

                    <p>
                        {{ __('ui.attachments_description', [], null, 'Documents and files attached to this request.') }}
                    </p>

                </div>

            </div>

            <div class="review-card-count">
                {{ $attachments->count() }}
            </div>

        </div>


        <div class="review-card-body">

            @if($attachments->isEmpty())

                <div class="empty-state compact">

                    <div class="empty-state-icon">
                        <i class="bi bi-paperclip"></i>
                    </div>

                    <div>
                        <h3>
                            {{ __('ui.no_attachments') }}
                        </h3>

                        <p>
                            {{ __('ui.no_attachments_description', [], null, 'No supporting documents have been attached to this request.') }}
                        </p>
                    </div>

                </div>

            @else

                <div class="attachments-list">

                    @foreach($attachments as $link)

                        <div class="attachment-item">

                            <div class="attachment-main">

                                <div class="attachment-icon">
                                    <i class="bi bi-file-earmark"></i>
                                </div>

                                <div class="attachment-info">

                                    <div class="attachment-name">
                                        {{ $link->attachment?->original_filename ?? $link->attachment_id }}
                                    </div>

                                    <div class="attachment-id">
                                        {{ __('ui.attachment_id', [], null, 'Attachment') }}
                                        #{{ $link->attachment_id }}
                                    </div>

                                </div>

                            </div>

                            <a
                                href="{{ route('admin.attachments.download', $link->attachment_id) }}"
                                class="attachment-download"
                            >
                                <i class="bi bi-download"></i>
                                <span>{{ __('ui.download') }}</span>
                            </a>

                        </div>

                    @endforeach

                </div>

            @endif

        </div>

    </section>


    {{-- =========================================================
         MANAGEMENT ACTIONS
    ========================================================== --}}
    @if(! $request->isTerminal())

        <section class="review-card action-card">

            <div class="review-card-header">

                <div class="review-card-heading">

                    <div class="review-card-icon action-icon">
                        <i class="bi bi-lightning-charge"></i>
                    </div>

                    <div>

                        <h2>
                            {{ __('ui.actions') }}
                        </h2>

                        <p>
                            {{ __('ui.management_actions_description', [], null, 'Available management actions for this request.') }}
                        </p>

                    </div>

                </div>

            </div>


            <div class="review-card-body">

                {{-- Main Action Buttons --}}
                <div class="action-buttons">

                    @if($request->current_status === 'submitted_to_management')

                        <button
                            type="button"
                            wire:click="startReview"
                            class="action-button action-primary"
                        >
                            <i class="bi bi-play-circle"></i>
                            <span>
                                {{ __('ui.start_review_btn') }}
                            </span>
                        </button>

                    @endif


                    @if($request->current_status === 'under_management_review')

                        <button
                            type="button"
                            wire:click="$set('action', 'accept')"
                            class="action-button action-success"
                        >
                            <i class="bi bi-check-circle"></i>
                            <span>
                                {{ __('ui.approve_btn') }}
                            </span>
                        </button>


                        <button
                            type="button"
                            wire:click="$set('action', 'reject')"
                            class="action-button action-danger"
                        >
                            <i class="bi bi-x-circle"></i>
                            <span>
                                {{ __('ui.reject_btn') }}
                            </span>
                        </button>


                        <button
                            type="button"
                            wire:click="$set('action', 'clarify')"
                            class="action-button action-warning"
                        >
                            <i class="bi bi-question-circle"></i>
                            <span>
                                {{ __('ui.clarify_btn') }}
                            </span>
                        </button>

                    @endif


                    @if(in_array($request->current_status, ['accepted', 'rejected']))

                        <button
                            type="button"
                            wire:click="$set('action', 'respond')"
                            class="action-button action-primary"
                        >
                            <i class="bi bi-reply"></i>
                            <span>
                                {{ __('ui.add_response') }}
                            </span>
                        </button>

                    @endif


                    @if($request->current_status === 'responded')

                        <button
                            type="button"
                            wire:click="close"
                            wire:confirm="{{ __('ui.close_confirm') }}"
                            class="action-button action-secondary"
                        >
                            <i class="bi bi-lock"></i>
                            <span>
                                {{ __('ui.close_request') }}
                            </span>
                        </button>

                    @endif

                </div>


                {{-- =================================================
                     ACCEPT
                ================================================== --}}
                @if($action === 'accept')

                    <div class="action-form action-form-success">

                        <div class="action-form-title">
                            <i class="bi bi-check-circle"></i>
                            {{ __('ui.accept_request') }}
                        </div>

                        <textarea
                            wire:model="comment"
                            rows="4"
                            class="action-textarea"
                            placeholder="{{ __('ui.acceptance_note_placeholder') }}"
                        ></textarea>

                        <div class="action-form-footer">

                            <button
                                type="button"
                                wire:click="accept"
                                class="action-button action-success"
                            >
                                <i class="bi bi-check-lg"></i>
                                {{ __('ui.confirm_accept') }}
                            </button>

                            <button
                                type="button"
                                wire:click="$set('action', '')"
                                class="action-button action-cancel"
                            >
                                {{ __('ui.cancel') }}
                            </button>

                        </div>

                    </div>

                @elseif($action === 'reject')

                    {{-- =================================================
                         REJECT
                    ================================================== --}}
                    <div class="action-form action-form-danger">

                        <div class="action-form-title">
                            <i class="bi bi-x-circle"></i>
                            {{ __('ui.reject_request') }}
                        </div>

                        <textarea
                            wire:model="comment"
                            rows="4"
                            class="action-textarea"
                            placeholder="{{ __('ui.rejection_reason_placeholder') }}"
                            required
                        ></textarea>

                        <div class="action-form-footer">

                            <button
                                type="button"
                                wire:click="reject"
                                class="action-button action-danger"
                            >
                                <i class="bi bi-x-lg"></i>
                                {{ __('ui.confirm_reject') }}
                            </button>

                            <button
                                type="button"
                                wire:click="$set('action', '')"
                                class="action-button action-cancel"
                            >
                                {{ __('ui.cancel') }}
                            </button>

                        </div>

                    </div>

                @elseif($action === 'clarify')

                    {{-- =================================================
                         CLARIFICATION
                    ================================================== --}}
                    <div class="action-form action-form-warning">

                        <div class="action-form-title">
                            <i class="bi bi-question-circle"></i>
                            {{ __('ui.clarify_btn') }}
                        </div>

                        <textarea
                            wire:model="comment"
                            rows="4"
                            class="action-textarea"
                            placeholder="{{ __('ui.clarify_placeholder') }}"
                        ></textarea>

                        <div class="action-form-footer">

                            <button
                                type="button"
                                wire:click="requestClarification"
                                class="action-button action-warning"
                            >
                                <i class="bi bi-send"></i>
                                {{ __('ui.send') }}
                            </button>

                            <button
                                type="button"
                                wire:click="$set('action', '')"
                                class="action-button action-cancel"
                            >
                                {{ __('ui.cancel') }}
                            </button>

                        </div>

                    </div>

                @elseif($action === 'respond')

                    {{-- =================================================
                         RESPONSE
                    ================================================== --}}
                    <div class="action-form action-form-primary">

                        <div class="action-form-title">
                            <i class="bi bi-reply"></i>
                            {{ __('ui.management_response') }}
                        </div>

                        <textarea
                            wire:model="responseText"
                            rows="6"
                            class="action-textarea"
                            placeholder="{{ __('ui.response_placeholder') }}"
                            required
                        ></textarea>

                        <div class="action-form-footer">

                            <button
                                type="button"
                                wire:click="respond"
                                class="action-button action-primary"
                            >
                                <i class="bi bi-send"></i>
                                {{ __('ui.submit_response') }}
                            </button>

                            <button
                                type="button"
                                wire:click="$set('action', '')"
                                class="action-button action-cancel"
                            >
                                {{ __('ui.cancel') }}
                            </button>

                        </div>

                    </div>

                @endif

            </div>

        </section>

    @endif


    {{-- =========================================================
         COMMENTS
    ========================================================== --}}
    <section class="review-card">

        <div class="review-card-header">

            <div class="review-card-heading">

                <div class="review-card-icon">
                    <i class="bi bi-chat-left-text"></i>
                </div>

                <div>

                    <h2>
                        {{ __('ui.comments') }}
                    </h2>

                    <p>
                        {{ __('ui.comments_description', [], null, 'Comments and internal management notes related to this request.') }}
                    </p>

                </div>

            </div>

            <div class="review-card-count">
                {{ $comments->count() }}
            </div>

        </div>


        <div class="review-card-body">

            @forelse($comments as $comment)

                <div class="comment-item">

                    <div class="comment-avatar">
                        <i class="bi bi-person"></i>
                    </div>

                    <div class="comment-content">

                        <div class="comment-header">

                            <div class="comment-author">
                                {{ ucwords($comment->commenter_actor_type) }}
                            </div>

                            <div class="comment-date">
                                <i class="bi bi-clock"></i>
                                {{ $comment->created_at->format('d M Y H:i') }}
                            </div>

                        </div>

                        <div class="comment-text">
                            {{ $comment->comment_text }}
                        </div>

                    </div>

                </div>

            @empty

                <div class="empty-state compact">

                    <div class="empty-state-icon">
                        <i class="bi bi-chat-left"></i>
                    </div>

                    <div>
                        <h3>
                            {{ __('ui.no_comments') }}
                        </h3>

                        <p>
                            {{ __('ui.no_comments_description', [], null, 'No comments have been added to this request yet.') }}
                        </p>
                    </div>

                </div>

            @endforelse


            {{-- Add Internal Note --}}
            @if(! $request->isTerminal())

                <div class="comment-form">

                    <div class="comment-form-header">

                        <div class="comment-form-title">
                            <i class="bi bi-pencil-square"></i>
                            {{ __('ui.add_note') }}
                        </div>

                        <span class="comment-form-label">
                            {{ __('ui.internal_note', [], null, 'Internal Note') }}
                        </span>

                    </div>

                    <textarea
                        wire:model="comment"
                        rows="3"
                        class="comment-textarea"
                        placeholder="{{ __('ui.add_internal_note_placeholder') }}"
                    ></textarea>

                    <div class="comment-form-footer">

                        <button
                            type="button"
                            wire:click="addComment"
                            class="action-button action-secondary"
                        >
                            <i class="bi bi-plus-circle"></i>
                            {{ __('ui.add_note') }}
                        </button>

                    </div>

                </div>

            @endif

        </div>

    </section>

</div>


{{-- =============================================================
     PAGE STYLES
============================================================= --}}
<style>
    .formal-request-review-page {
        width: 100%;
        max-width: 1180px;
        margin: 0 auto;
        padding: 0 0 var(--space-8, 32px);
    }

    /* =========================================================
       HEADER
    ========================================================== */

    .review-page-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 24px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .review-header-main {
        min-width: 0;
        flex: 1;
    }

    .review-back-link {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 16px;
        color: var(--interactive-primary, #2563eb);
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        transition: 0.2s ease;
    }

    .review-back-link:hover {
        opacity: 0.8;
        transform: translateX({{ $isArabic ? '3px' : '-3px' }});
    }

    .review-title-row {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .review-title-icon {
        width: 52px;
        height: 52px;
        flex: 0 0 52px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        background: rgba(37, 99, 235, 0.1);
        color: var(--interactive-primary, #2563eb);
        font-size: 23px;
    }

    .review-page-title {
        margin: 0;
        color: var(--text-primary, #111827);
        font-size: clamp(21px, 2vw, 28px);
        font-weight: 750;
        line-height: 1.25;
    }

    .review-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 7px;
        color: var(--text-secondary, #6b7280);
        font-size: 12px;
    }

    .review-meta span {
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .review-meta i {
        font-size: 12px;
    }

    .review-meta-separator {
        opacity: 0.45;
    }

    .review-header-status {
        padding-top: 48px;
    }

    /* =========================================================
       STATUS
    ========================================================== */

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .status-submitted {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .status-review {
        background: #f3f4f6;
        color: #374151;
    }

    .status-clarification {
        background: #fffbeb;
        color: #b45309;
    }

    .status-accepted {
        background: #ecfdf5;
        color: #047857;
    }

    .status-rejected {
        background: #fef2f2;
        color: #b91c1c;
    }

    .status-responded {
        background: #eef2ff;
        color: #4338ca;
    }

    .status-closed {
        background: #f3f4f6;
        color: #4b5563;
    }

    .status-default {
        background: #f3f4f6;
        color: #374151;
    }

    /* =========================================================
       ALERTS
    ========================================================== */

    .review-alert {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 13px 16px;
        margin-bottom: 18px;
        border-radius: 12px;
        font-size: 13px;
    }

    .review-alert-icon {
        flex: 0 0 auto;
        font-size: 17px;
    }

    .review-alert-content {
        line-height: 1.7;
    }

    .review-alert-success {
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #065f46;
    }

    .review-alert-danger {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
    }

    /* =========================================================
       CARDS
    ========================================================== */

    .review-card {
        background: var(--surface-card, #ffffff);
        border: 1px solid var(--border-color, #e5e7eb);
        border-radius: 16px;
        overflow: hidden;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.035);
    }

    .review-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 20px;
        border-bottom: 1px solid var(--border-color, #e5e7eb);
        background: rgba(248, 250, 252, 0.72);
    }

    .review-card-heading {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .review-card-icon {
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 11px;
        background: rgba(37, 99, 235, 0.09);
        color: var(--interactive-primary, #2563eb);
        font-size: 17px;
    }

    .review-card-heading h2 {
        margin: 0;
        color: var(--text-primary, #111827);
        font-size: 15px;
        font-weight: 750;
    }

    .review-card-heading p {
        margin: 4px 0 0;
        color: var(--text-secondary, #6b7280);
        font-size: 12px;
        line-height: 1.5;
    }

    .review-card-count {
        min-width: 30px;
        height: 30px;
        padding: 0 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #eff6ff;
        color: #2563eb;
        font-size: 12px;
        font-weight: 750;
    }

    .review-card-body {
        padding: 20px;
    }

    /* =========================================================
       DETAILS
    ========================================================== */

    .details-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0;
        border: 1px solid #eef0f3;
        border-radius: 12px;
        overflow: hidden;
    }

    .detail-item {
        min-width: 0;
        padding: 15px 16px;
        border-bottom: 1px solid #eef0f3;
    }

    .detail-item:nth-child(odd) {
        border-inline-end: 1px solid #eef0f3;
    }

    .detail-item:nth-last-child(-n + 2) {
        border-bottom: 0;
    }

    .detail-item-wide {
        grid-column: 1 / -1;
        border-inline-end: 0 !important;
    }

    .detail-label {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 7px;
        color: var(--text-secondary, #6b7280);
        font-size: 11px;
        font-weight: 650;
    }

    .detail-label i {
        color: #94a3b8;
    }

    .detail-value {
        color: var(--text-primary, #1f2937);
        font-size: 13px;
        font-weight: 600;
        line-height: 1.6;
        overflow-wrap: anywhere;
    }

    .detail-arabic {
        font-size: 14px;
    }

    .hash-value {
        display: inline-block;
        padding: 5px 8px;
        border-radius: 7px;
        background: #f8fafc;
        color: #64748b;
        font-family: monospace;
        font-size: 11px;
    }

    .request-body-section {
        margin-top: 18px;
    }

    .request-body-content {
        min-height: 90px;
        padding: 14px 16px;
        border: 1px solid #eef0f3;
        border-radius: 11px;
        background: #fafafa;
        color: #374151;
        font-size: 13px;
        line-height: 1.85;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
    }

    /* =========================================================
       PRIORITY
    ========================================================== */

    .priority-badge {
        display: inline-flex;
        align-items: center;
        padding: 5px 10px;
        border-radius: 7px;
        font-size: 11px;
        font-weight: 750;
    }

    .priority-low {
        background: #f0fdf4;
        color: #15803d;
    }

    .priority-medium {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .priority-high {
        background: #fff7ed;
        color: #c2410c;
    }

    .priority-urgent {
        background: #fef2f2;
        color: #b91c1c;
    }

    .priority-default {
        background: #f3f4f6;
        color: #4b5563;
    }

    /* =========================================================
       ATTACHMENTS
    ========================================================== */

    .attachments-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .attachment-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 12px 14px;
        border: 1px solid #eef0f3;
        border-radius: 11px;
        transition: 0.18s ease;
    }

    .attachment-item:hover {
        border-color: #dbeafe;
        background: #f8fbff;
    }

    .attachment-main {
        display: flex;
        align-items: center;
        gap: 11px;
        min-width: 0;
    }

    .attachment-icon {
        width: 36px;
        height: 36px;
        flex: 0 0 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 9px;
        background: #eff6ff;
        color: #2563eb;
    }

    .attachment-info {
        min-width: 0;
    }

    .attachment-name {
        color: #374151;
        font-size: 13px;
        font-weight: 650;
        overflow-wrap: anywhere;
    }

    .attachment-id {
        margin-top: 2px;
        color: #9ca3af;
        font-size: 10px;
    }

    .attachment-download {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        flex: 0 0 auto;
        padding: 7px 10px;
        border-radius: 8px;
        color: #2563eb;
        background: #eff6ff;
        text-decoration: none;
        font-size: 11px;
        font-weight: 700;
        transition: 0.18s ease;
    }

    .attachment-download:hover {
        background: #dbeafe;
    }

    /* =========================================================
       ACTIONS
    ========================================================== */

    .action-icon {
        background: #fff7ed;
        color: #ea580c;
    }

    .action-buttons {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 9px;
    }

    .action-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 38px;
        padding: 8px 14px;
        border: 1px solid transparent;
        border-radius: 9px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: 0.18s ease;
    }

    .action-button:hover {
        transform: translateY(-1px);
    }

    .action-primary {
        background: #2563eb;
        color: #fff;
    }

    .action-primary:hover {
        background: #1d4ed8;
    }

    .action-success {
        background: #059669;
        color: #fff;
    }

    .action-success:hover {
        background: #047857;
    }

    .action-danger {
        background: #dc2626;
        color: #fff;
    }

    .action-danger:hover {
        background: #b91c1c;
    }

    .action-warning {
        background: #f59e0b;
        color: #fff;
    }

    .action-warning:hover {
        background: #d97706;
    }

    .action-secondary {
        background: #374151;
        color: #fff;
    }

    .action-secondary:hover {
        background: #1f2937;
    }

    .action-cancel {
        background: #fff;
        border-color: #d1d5db;
        color: #4b5563;
    }

    .action-cancel:hover {
        background: #f9fafb;
    }

    /* =========================================================
       ACTION FORMS
    ========================================================== */

    .action-form {
        margin-top: 18px;
        padding: 16px;
        border-radius: 12px;
        border: 1px solid;
    }

    .action-form-success {
        background: #f0fdf4;
        border-color: #bbf7d0;
    }

    .action-form-danger {
        background: #fef2f2;
        border-color: #fecaca;
    }

    .action-form-warning {
        background: #fffbeb;
        border-color: #fde68a;
    }

    .action-form-primary {
        background: #eff6ff;
        border-color: #bfdbfe;
    }

    .action-form-title {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
        font-size: 13px;
        font-weight: 750;
    }

    .action-form-success .action-form-title {
        color: #047857;
    }

    .action-form-danger .action-form-title {
        color: #b91c1c;
    }

    .action-form-warning .action-form-title {
        color: #b45309;
    }

    .action-form-primary .action-form-title {
        color: #1d4ed8;
    }

    .action-textarea,
    .comment-textarea {
        display: block;
        width: 100%;
        resize: vertical;
        padding: 11px 12px;
        border: 1px solid #d1d5db;
        border-radius: 9px;
        background: #fff;
        color: #1f2937;
        font-size: 13px;
        line-height: 1.7;
        outline: none;
        transition: 0.18s ease;
        box-sizing: border-box;
    }

    .action-textarea:focus,
    .comment-textarea:focus {
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .action-form-footer {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 10px;
    }

    /* =========================================================
       COMMENTS
    ========================================================== */

    .comment-item {
        display: flex;
        gap: 12px;
        padding: 15px 0;
        border-bottom: 1px solid #eef0f3;
    }

    .comment-item:first-child {
        padding-top: 0;
    }

    .comment-item:last-of-type {
        border-bottom: 0;
    }

    .comment-avatar {
        width: 36px;
        height: 36px;
        flex: 0 0 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #f1f5f9;
        color: #64748b;
        font-size: 14px;
    }

    .comment-content {
        flex: 1;
        min-width: 0;
    }

    .comment-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 6px;
    }

    .comment-author {
        color: #374151;
        font-size: 12px;
        font-weight: 750;
    }

    .comment-date {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        color: #9ca3af;
        font-size: 10px;
        white-space: nowrap;
    }

    .comment-text {
        padding: 10px 12px;
        border-radius: 9px;
        background: #f8fafc;
        color: #374151;
        font-size: 12px;
        line-height: 1.75;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
    }

    .comment-form {
        margin-top: 18px;
        padding-top: 18px;
        border-top: 1px solid #eef0f3;
    }

    .comment-form-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 9px;
    }

    .comment-form-title {
        display: flex;
        align-items: center;
        gap: 7px;
        color: #374151;
        font-size: 12px;
        font-weight: 750;
    }

    .comment-form-label {
        padding: 4px 8px;
        border-radius: 6px;
        background: #f3f4f6;
        color: #6b7280;
        font-size: 10px;
        font-weight: 650;
    }

    .comment-form-footer {
        display: flex;
        justify-content: flex-end;
        margin-top: 9px;
    }

    /* =========================================================
       EMPTY STATE
    ========================================================== */

    .empty-state {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 14px;
        padding: 38px 20px;
        text-align: start;
    }

    .empty-state.compact {
        justify-content: flex-start;
        padding: 22px 4px;
    }

    .empty-state-icon {
        width: 44px;
        height: 44px;
        flex: 0 0 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: #f3f4f6;
        color: #9ca3af;
        font-size: 18px;
    }

    .empty-state h3 {
        margin: 0;
        color: #4b5563;
        font-size: 13px;
        font-weight: 700;
    }

    .empty-state p {
        margin: 4px 0 0;
        color: #9ca3af;
        font-size: 11px;
        line-height: 1.6;
    }

    /* =========================================================
       RTL
    ========================================================== */

    [dir="rtl"] .review-title-row,
    [dir="rtl"] .review-card-heading,
    [dir="rtl"] .attachment-main,
    [dir="rtl"] .comment-item {
        direction: rtl;
    }

    /* =========================================================
       RESPONSIVE
    ========================================================== */

    @media (max-width: 768px) {

        .formal-request-review-page {
            padding-inline: 10px;
        }

        .review-header-status {
            padding-top: 0;
            width: 100%;
        }

        .review-title-row {
            align-items: flex-start;
        }

        .review-title-icon {
            width: 44px;
            height: 44px;
            flex-basis: 44px;
            font-size: 19px;
        }

        .details-grid {
            grid-template-columns: 1fr;
        }

        .detail-item:nth-child(odd) {
            border-inline-end: 0;
        }

        .detail-item,
        .detail-item:nth-last-child(-n + 2) {
            border-bottom: 1px solid #eef0f3;
        }

        .detail-item:last-child {
            border-bottom: 0;
        }

        .detail-item-wide {
            grid-column: auto;
        }

        .attachment-item {
            align-items: flex-start;
            flex-direction: column;
        }

        .attachment-download {
            width: 100%;
            justify-content: center;
        }

        .comment-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .action-button {
            flex: 1 1 auto;
        }

        .action-form-footer {
            flex-direction: column;
        }

        .action-form-footer .action-button {
            width: 100%;
        }
    }

    @media (max-width: 480px) {

        .review-card-header,
        .review-card-body {
            padding: 15px;
        }

        .review-title-icon {
            display: none;
        }

        .review-page-title {
            font-size: 20px;
        }

        .review-meta {
            align-items: flex-start;
            flex-direction: column;
        }

        .review-meta-separator {
            display: none !important;
        }
    }
</style>

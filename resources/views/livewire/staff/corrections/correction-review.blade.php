@title(__('requests.review_title', [], null, 'Review Correction Request'))

<div class="page-container">
    <div class="page-header">
        <a href="{{ route('staff.corrections.index') }}" class="btn btn--ghost btn--sm">
            ← {{ __('requests.back_to_inbox', [], null, 'Back to inbox') }}
        </a>
        <h1 class="page-title">{{ __('requests.review_title', [], null, 'Review Correction Request') }} #{{ $request->id }}</h1>
    </div>

    {{-- Flash message --}}
    @if($flashMessage)
        <div class="alert alert--success" role="status">{{ $flashMessage }}</div>
    @endif

    {{-- Errors --}}
    @if(!empty($errors))
        <div class="alert alert--danger" role="alert">
            @foreach($errors as $err)<p>{{ $err }}</p>@endforeach
        </div>
    @endif

    {{-- Conflict warning --}}
    @if($conflictDetected)
        <div class="alert alert--warning" role="alert">
            {{ __('requests.conflict_warning', [], null, 'The official data changed since this request was submitted. Manual review required before applying.') }}
        </div>
    @endif

    {{-- Summary --}}
    <div class="detail-card">
        @php $field = \Modules\Requests\Enums\CorrectionFieldCatalogue::tryFrom($request->field_catalogue_code); @endphp

        <div class="detail-card__header">
            <span class="status-badge status-badge--{{ $instance->current_state }}">
                {{ __('workflow.state.' . $instance->current_state, [], null, $instance->current_state) }}
            </span>
            @if($request->classification === 'sensitive')
                <span class="badge badge--warning">{{ __('requests.sensitive', [], null, 'Sensitive — Principal Approval Required') }}</span>
            @endif
            @if($request->conflict_flag)
                <span class="badge badge--danger">{{ __('requests.conflict', [], null, 'Conflict Detected') }}</span>
            @endif
        </div>

        <dl class="comparison-grid">
            <dt>{{ __('requests.col_field', [], null, 'Field') }}</dt>
            <dd>{{ $field?->labelAr() ?? $request->field_catalogue_code }}</dd>

            @if($proposal)
            <dt>{{ __('requests.current_value', [], null, 'Current official value') }}</dt>
            <dd class="current-value">{{ $currentValueDisplay ?? '—' }}</dd>

            <dt>{{ __('requests.proposed_value_label', [], null, 'Proposed new value') }}</dt>
            <dd class="proposed-value {{ $valuesAreSensitive && !$canApprove ? 'sensitive-hidden' : '' }}">
                {{ $proposedValueDisplay }}
            </dd>

            @if($proposal->explanation)
            <dt>{{ __('requests.explanation_label', [], null, 'Guardian explanation') }}</dt>
            <dd>{{ $proposal->explanation }}</dd>
            @endif
            @endif
        </dl>
    </div>

    {{-- ------------------------------------------------------------------ --}}
    {{-- Credential confirmation form (shown for sensitive approvals only)   --}}
    {{-- ------------------------------------------------------------------ --}}
    @if($showCredentialForm)
    <div class="reconfirmation-panel" role="region" aria-labelledby="reconfirm-title">
        <h2 id="reconfirm-title" class="reconfirmation-panel__title">
            {{ __('requests.confirm_identity_title', [], null, 'Confirm Your Identity') }}
        </h2>
        <p class="text-muted text-sm">
            {{ __('requests.confirm_identity_hint', [], null, 'Sensitive corrections require principal-level reconfirmation. Enter your portal password to proceed.') }}
        </p>

        <div class="form-group">
            <label for="credentialInput" class="form-label">
                {{ __('requests.portal_password_label', [], null, 'Portal password') }}
            </label>
            <input
                type="password"
                id="credentialInput"
                wire:model="credentialInput"
                class="form-control"
                autocomplete="current-password"
                required
            >
        </div>

        <div class="form-actions">
            <button
                type="button"
                wire:click="cancelCredentialForm"
                class="btn btn--ghost"
            >
                {{ __('ui.cancel') }}
            </button>
            <button
                type="button"
                wire:click="confirmAndApprove"
                class="btn btn--success"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove>{{ __('requests.confirm_approve_btn', [], null, 'Confirm Approval') }}</span>
                <span wire:loading>{{ __('ui.saving') }}</span>
            </button>
        </div>
    </div>
    @endif

    {{-- Action comment field --}}
    @if(!$showCredentialForm)
    <div class="form-group">
        <label for="comment" class="form-label">
            {{ __('requests.comment_label', [], null, 'Comment (required for reject; required for sensitive approve)') }}
        </label>
        <textarea id="comment" wire:model="comment" class="form-control" rows="3" maxlength="1000"></textarea>
    </div>
    @endif

    {{-- Actions --}}
    @if(!$showCredentialForm)
    <div class="form-actions">
        @if(in_array($instance->current_state, ['submitted', 'resubmitted']))
            <button type="button" wire:click="startReview" class="btn btn--secondary">
                {{ __('requests.start_review_btn', [], null, 'Start Review') }}
            </button>
        @endif

        @if(in_array($instance->current_state, ['submitted', 'resubmitted']))
            <button type="button" wire:click="requestClarification" class="btn btn--ghost">
                {{ __('requests.clarify_btn', [], null, 'Request Clarification') }}
            </button>
        @endif

        @if($instance->current_state === 'under_review')
            @if($canApprove)
                {{-- For sensitive: clicking Approve transitions to the credential form above --}}
                <button type="button" wire:click="initiateApprove" class="btn btn--success">
                    {{ __('requests.approve_btn', [], null, 'Approve') }}
                </button>
            @endif

            <button type="button" wire:click="reject" class="btn btn--danger">
                {{ __('requests.reject_btn', [], null, 'Reject') }}
            </button>
        @endif

        @if($instance->current_state === 'approved' && $canApply)
            <button type="button" wire:click="apply" class="btn btn--primary" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ __('requests.apply_btn', [], null, 'Apply Correction') }}</span>
                <span wire:loading>{{ __('ui.saving') }}</span>
            </button>
        @endif
    </div>
    @endif

    {{-- Timeline --}}
    <div class="timeline">
        <h2 class="timeline__title">{{ __('requests.history_title', [], null, 'Request History') }}</h2>
        <ol class="timeline__list">
            @foreach($timeline as $action)
            <li class="timeline__item">
                <div class="timeline__action">{{ $action->action_code }}</div>
                <div class="timeline__states">{{ $action->previous_state }} → {{ $action->new_state }}</div>
                <div class="timeline__actor">{{ $action->actor_type }}</div>
                @if($action->comment)
                    <div class="timeline__comment">{{ $action->comment }}</div>
                @endif
                <time class="timeline__time" datetime="{{ \Carbon\Carbon::parse($action->created_at)->toIso8601String() }}">
                    {{ \Carbon\Carbon::parse($action->created_at)->format('Y-m-d H:i') }}
                </time>
            </li>
            @endforeach
        </ol>
    </div>
</div>

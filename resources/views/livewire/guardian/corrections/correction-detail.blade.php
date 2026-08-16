@title(__('requests.request_detail_title', [], null, 'Correction Request'))

<div class="page-container">
    <div class="page-header">
        <a href="{{ route('guardian.corrections.index') }}" class="btn btn--ghost btn--sm">
            ← {{ __('requests.back_to_list', [], null, 'Back to my corrections') }}
        </a>
        <h1 class="page-title">{{ __('requests.request_detail_title', [], null, 'Correction Request') }} #{{ $request->id }}</h1>
    </div>

    {{-- Errors --}}
    @if(!empty($errors))
        <div class="alert alert--danger" role="alert">
            @foreach($errors as $err)<p>{{ $err }}</p>@endforeach
        </div>
    @endif

    @if($resubmitted)
        <div class="alert alert--success">{{ __('requests.resubmitted_success', [], null, 'Your clarification has been submitted.') }}</div>
    @endif

    @if($cancelled)
        <div class="alert alert--info">{{ __('requests.cancelled', [], null, 'This request has been cancelled.') }}</div>
    @endif

    {{-- Summary card --}}
    <div class="detail-card">
        <dl class="detail-list">
            @php $field = \Modules\Requests\Enums\CorrectionFieldCatalogue::tryFrom($request->field_catalogue_code); @endphp

            <dt>{{ __('requests.col_field', [], null, 'Field') }}</dt>
            <dd>{{ $field?->labelAr() ?? $request->field_catalogue_code }}
                @if($request->classification === 'sensitive')
                    <span class="badge badge--warning">{{ __('requests.sensitive', [], null, 'Sensitive') }}</span>
                @endif
            </dd>

            <dt>{{ __('requests.col_status', [], null, 'Status') }}</dt>
            <dd>
                <span class="status-badge status-badge--{{ $instance->current_state }}">
                    {{ __('workflow.state.' . $instance->current_state, [], null, $instance->current_state) }}
                </span>
                @if($request->conflict_flag)
                    <span class="badge badge--danger">{{ __('requests.conflict', [], null, 'Conflict') }}</span>
                @endif
            </dd>

            @if($proposal)
            <dt>{{ __('requests.proposed_value_label', [], null, 'Proposed value') }}</dt>
            <dd>
                @php
                    $displayValue = $field?->requiresEncryption()
                        ? __('requests.sensitive_value_hidden', [], null, '(sensitive — hidden)')
                        : $proposal->proposed_value;
                @endphp
                {{ $displayValue }}
            </dd>

            @if($proposal->explanation)
            <dt>{{ __('requests.explanation_label', [], null, 'Explanation') }}</dt>
            <dd>{{ $proposal->explanation }}</dd>
            @endif
            @endif

            <dt>{{ __('requests.col_date', [], null, 'Submitted') }}</dt>
            <dd>{{ \Carbon\Carbon::parse($request->created_at)->format('Y-m-d H:i') }}</dd>
        </dl>
    </div>

    {{-- Clarification response form --}}
    @if($instance->current_state === 'clarification_requested' && !$resubmitted && !$cancelled)
    <div class="form-section form-section--highlight">
        <h2 class="form-section__title">{{ __('requests.clarification_needed', [], null, 'Clarification Needed') }}</h2>

        @if($timeline->isNotEmpty())
        @php $lastAction = $timeline->last(); @endphp
        @if($lastAction->comment)
            <blockquote class="clarification-comment">
                <p>{{ $lastAction->comment }}</p>
            </blockquote>
        @endif
        @endif

        <div class="form-group">
            <label for="revisedValue" class="form-label">{{ __('requests.revised_value', [], null, 'Revised proposed value') }}</label>
            <input type="text" id="revisedValue" wire:model="revisedValue" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="clarificationResponse" class="form-label">{{ __('requests.your_response', [], null, 'Your response') }}</label>
            <textarea id="clarificationResponse" wire:model="clarificationResponse" class="form-control" rows="3"></textarea>
        </div>

        <div class="form-actions">
            <button type="button" wire:click="resubmit" class="btn btn--primary">
                {{ __('requests.resubmit_btn', [], null, 'Resubmit with Clarification') }}
            </button>
        </div>
    </div>
    @endif

    {{-- Cancel button --}}
    @if(in_array($instance->current_state, ['submitted', 'clarification_requested', 'resubmitted', 'under_review', 'approved']) && !$cancelled)
    <div class="form-actions form-actions--danger">
        <button type="button" wire:click="cancel" wire:confirm="{{ __('requests.cancel_confirm', [], null, 'Are you sure you want to cancel this request?') }}" class="btn btn--danger btn--sm">
            {{ __('requests.cancel_btn', [], null, 'Cancel Request') }}
        </button>
    </div>
    @endif

    {{-- Timeline --}}
    <div class="timeline">
        <h2 class="timeline__title">{{ __('requests.history_title', [], null, 'Request History') }}</h2>
        <ol class="timeline__list" reversed>
            @foreach($timeline->reverse() as $action)
            <li class="timeline__item">
                <div class="timeline__action">{{ $action->action_code }}</div>
                <div class="timeline__states">{{ $action->previous_state }} → {{ $action->new_state }}</div>
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

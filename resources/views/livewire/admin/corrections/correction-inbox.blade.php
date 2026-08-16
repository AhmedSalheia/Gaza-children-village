@title(__('requests.admin_inbox_title', [], null, 'Correction Requests — Central Inbox'))

<div class="page-container">
    <div class="page-header">
        <h1 class="page-title">{{ __('requests.admin_inbox_title', [], null, 'Correction Requests') }}</h1>
    </div>

    {{-- Flash / errors --}}
    @if($flashMessage)
        <div class="alert alert--success" role="status">{{ $flashMessage }}</div>
    @endif
    @if(!empty($errors))
        <div class="alert alert--danger">
            @foreach($errors as $err)<p>{{ $err }}</p>@endforeach
        </div>
    @endif

    {{-- Inline approve comment form (shown when $confirmingRequestId is set) --}}
    @if($confirmingRequestId)
    <div class="modal-overlay">
        <div class="modal" role="dialog" aria-labelledby="approve-modal-title">
            <h2 id="approve-modal-title" class="modal__title">
                {{ __('requests.approve_confirm_title', [], null, 'Confirm Approval') }}
            </h2>
            <div class="form-group">
                <label for="approveComment" class="form-label">
                    {{ __('requests.comment_label', [], null, 'Comment') }}
                    <span class="text-muted text-sm">({{ __('requests.required_for_sensitive', [], null, 'required for sensitive corrections') }})</span>
                </label>
                <textarea
                    id="approveComment"
                    wire:model="comment"
                    class="form-control"
                    rows="3"
                    maxlength="1000"
                ></textarea>
            </div>
            <div class="form-actions">
                <button type="button" wire:click="cancelApprove" class="btn btn--ghost">
                    {{ __('ui.cancel') }}
                </button>
                <button type="button" wire:click="confirmApprove" class="btn btn--primary">
                    {{ __('requests.approve_btn', [], null, 'Approve') }}
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Filters --}}
    <div class="filter-bar">
        <div class="filter-bar__item">
            <label class="form-label">{{ __('requests.col_status', [], null, 'Status') }}</label>
            <select wire:model.live="stateFilter" class="form-control form-control--sm">
                <option value="">{{ __('ui.all') }}</option>
                @foreach(['submitted','resubmitted','clarification_requested','under_review','approved','applied','rejected','cancelled'] as $state)
                    <option value="{{ $state }}">{{ __('workflow.state.' . $state, [], null, $state) }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-bar__item">
            <label class="form-label">{{ __('requests.classification', [], null, 'Classification') }}</label>
            <select wire:model.live="classFilter" class="form-control form-control--sm">
                <option value="">{{ __('ui.all') }}</option>
                <option value="standard">{{ __('requests.standard', [], null, 'Standard') }}</option>
                <option value="sensitive">{{ __('requests.sensitive', [], null, 'Sensitive') }}</option>
            </select>
        </div>

        <div class="filter-bar__item filter-bar__item--checkbox">
            <label>
                <input type="checkbox" wire:model.live="conflictOnly"> {{ __('requests.conflict_only', [], null, 'Conflicts only') }}
            </label>
        </div>
    </div>

    @if($requests->isEmpty())
        <div class="empty-state">
            <p>{{ __('requests.no_requests_found', [], null, 'No correction requests found matching the filters.') }}</p>
        </div>
    @else
    <table class="data-table" role="grid">
        <thead>
            <tr>
                <th scope="col">{{ __('requests.col_student', [], null, 'Student') }}</th>
                <th scope="col">{{ __('requests.institution', [], null, 'Institution') }}</th>
                <th scope="col">{{ __('requests.col_field', [], null, 'Field') }}</th>
                <th scope="col">{{ __('requests.col_status', [], null, 'Status') }}</th>
                <th scope="col">{{ __('requests.col_date', [], null, 'Submitted') }}</th>
                <th scope="col"><span class="sr-only">{{ __('ui.actions') }}</span></th>
            </tr>
        </thead>
        <tbody>
            @foreach($requests as $req)
            <tr>
                <td>{{ $req->student_name }}</td>
                <td>{{ $req->institution_name ?? '—' }}</td>
                <td>
                    @php $field = \Modules\Requests\Enums\CorrectionFieldCatalogue::tryFrom($req->field_catalogue_code); @endphp
                    {{ $field?->labelAr() ?? $req->field_catalogue_code }}
                    @if($req->classification === 'sensitive')
                        <span class="badge badge--warning">{{ __('requests.sensitive', [], null, 'Sensitive') }}</span>
                    @endif
                    @if($req->conflict_flag)
                        <span class="badge badge--danger">{{ __('requests.conflict', [], null, 'Conflict') }}</span>
                    @endif
                </td>
                <td>
                    <span class="status-badge status-badge--{{ $req->current_state }}">
                        {{ __('workflow.state.' . $req->current_state, [], null, $req->current_state) }}
                    </span>
                </td>
                <td>{{ \Carbon\Carbon::parse($req->created_at)->format('Y-m-d') }}</td>
                <td class="action-cell">
                    @if($req->current_state === 'under_review')
                        @if($req->classification === 'sensitive')
                            {{-- Sensitive: principal approval must go through staff portal with credential reconfirmation --}}
                            <span class="text-muted text-sm">
                                {{ __('requests.principal_approval_required', [], null, 'Requires principal approval via staff portal') }}
                            </span>
                        @else
                            <button
                                type="button"
                                wire:click="initiateApprove({{ $req->id }})"
                                class="btn btn--sm btn--success"
                            >
                                {{ __('requests.approve_btn', [], null, 'Approve') }}
                            </button>
                        @endif
                    @elseif($req->current_state === 'approved')
                        <button
                            type="button"
                            wire:click="apply({{ $req->id }})"
                            class="btn btn--sm btn--primary"
                            wire:confirm="{{ __('requests.apply_confirm', [], null, 'Apply this correction?') }}"
                        >
                            {{ __('requests.apply_btn', [], null, 'Apply') }}
                        </button>
                    @else
                        <span class="text-muted text-sm">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@php /** @var \App\Livewire\Staff\Enrollments\PromotionReview $this */ @endphp

<div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-block-end:var(--space-6);flex-wrap:wrap;gap:var(--space-3)">
        <h1 style="font-size:var(--text-2xl);font-weight:700;margin:0">{{ __('ui.promotion_proposals', [], null, 'Promotion Proposals') }}</h1>
        @if($canCreate)
        <button wire:click="$set('showCreateForm', true)" class="btn btn--primary btn--sm">
            + {{ __('ui.create_proposal', [], null, 'Create Proposal') }}
        </button>
        @endif
    </div>

    @error('createProposal') <div class="alert alert--danger">{{ $message }}</div> @enderror
    @error('review') <div class="alert alert--danger">{{ $message }}</div> @enderror

    {{-- Create form --}}
    @if($showCreateForm)
    <div class="card" style="margin-block-end:var(--space-6)">
        <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">{{ __('ui.new_proposal', [], null, 'New Promotion Proposal') }}</h2>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-4)">
            <div class="form-group">
                <label class="form-label form-label--required">{{ __('ui.enrollment', [], null, 'Enrollment') }}</label>
                <select wire:model="createEnrollmentId" class="form-control form-select @error('createEnrollmentId') form-control--error @enderror">
                    <option value="0">— {{ __('ui.select', [], null, 'Select student') }} —</option>
                    @foreach($activeEnrollments as $e)
                    <option value="{{ $e->id }}">{{ $e->student_name }} — {{ $e->class_group_name }}</option>
                    @endforeach
                </select>
                @error('createEnrollmentId') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label form-label--required">{{ __('ui.proposed_outcome', [], null, 'Proposed Outcome') }}</label>
                <select wire:model="proposalStatus" class="form-control form-select">
                    @foreach($proposalStatuses as $s)
                    <option value="{{ $s->value }}">{{ $s->value }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        @if($proposalStatus === 'promoted' || $proposalStatus === 'transferred')
        <div class="form-group">
            <label class="form-label">{{ __('ui.proposed_class_group', [], null, 'Proposed Class Group (optional)') }}</label>
            <select wire:model="proposedClassGroupId" class="form-control form-select">
                <option value="">— {{ __('ui.auto_assign', [], null, 'Auto-assign') }} —</option>
                @foreach($nextLevelGroups as $g)
                <option value="{{ $g->id }}">{{ $g->level_name }} — {{ $g->name_ar }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="form-group">
            <label class="form-label">{{ __('ui.reason', [], null, 'Reason') }}</label>
            <textarea wire:model="proposalReason" class="form-control" rows="2"></textarea>
        </div>
        <div style="display:flex;gap:var(--space-3)">
            <button wire:click="createProposal" class="btn btn--primary">{{ __('ui.submit', [], null, 'Submit') }}</button>
            <button wire:click="$set('showCreateForm', false)" class="btn btn--outline">{{ __('ui.cancel', [], null, 'Cancel') }}</button>
        </div>
    </div>
    @endif

    {{-- Review form --}}
    @if($reviewingProposalId)
    <div class="card" style="margin-block-end:var(--space-6);border-inline-start:4px solid var(--brand-gold)">
        <h2 style="font-size:var(--text-lg);font-weight:600;margin-block-end:var(--space-4)">{{ __('ui.review_proposal', [], null, 'Review Proposal') }}</h2>
        <div class="form-group">
            <label class="form-label form-label--required">{{ __('ui.decision', [], null, 'Decision') }}</label>
            <div style="display:flex;gap:var(--space-3)">
                <label style="display:flex;align-items:center;gap:var(--space-2)">
                    <input type="radio" wire:model="reviewDecision" value="approved"> {{ __('ui.approve', [], null, 'Approve') }}
                </label>
                <label style="display:flex;align-items:center;gap:var(--space-2)">
                    <input type="radio" wire:model="reviewDecision" value="rejected"> {{ __('ui.reject', [], null, 'Reject') }}
                </label>
            </div>
            @error('reviewDecision') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label class="form-label">{{ __('ui.reason', [], null, 'Reason') }}</label>
            <input type="text" wire:model="reviewReason" class="form-control">
        </div>
        <div style="display:flex;gap:var(--space-3)">
            <button wire:click="submitReview" class="btn btn--primary">{{ __('ui.submit_review', [], null, 'Submit Review') }}</button>
            <button wire:click="cancelReview" class="btn btn--outline">{{ __('ui.cancel', [], null, 'Cancel') }}</button>
        </div>
    </div>
    @endif

    {{-- Filter + table --}}
    <div style="display:flex;gap:var(--space-3);margin-block-end:var(--space-4)">
        <select wire:model.live="reviewFilter" class="form-control form-select" style="max-inline-size:180px">
            <option value="">{{ __('ui.all', [], null, 'All') }}</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
        </select>
    </div>

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead><tr>
                <th>{{ __('ui.student', [], null, 'Student') }}</th>
                <th>{{ __('ui.class_group', [], null, 'Class') }}</th>
                <th>{{ __('ui.proposed_outcome', [], null, 'Proposed') }}</th>
                <th>{{ __('ui.review_status', [], null, 'Status') }}</th>
                <th>{{ __('ui.reason', [], null, 'Reason') }}</th>
                <th>{{ __('ui.reviewed_by', [], null, 'Reviewed By') }}</th>
                <th></th>
            </tr></thead>
            <tbody>
                @forelse($proposals as $p)
                <tr>
                    <td>
                        <a href="{{ route('staff.students.detail', ['studentProfileId' => $p->student_id]) }}" class="link" wire:navigate>{{ $p->student_name }}</a>
                    </td>
                    <td>{{ $p->class_group_name }} / {{ $p->level_name }}</td>
                    <td><span class="badge badge--draft">{{ $p->proposed_status }}</span></td>
                    <td>
                        <span class="badge badge--{{ match($p->review_status) {'pending'=>'pending','approved'=>'active','rejected'=>'closed',default=>'draft'} }}">
                            {{ $p->review_status }}
                        </span>
                    </td>
                    <td>{{ $p->reason ?? '—' }}</td>
                    <td>{{ $p->reviewed_by ?? '—' }}</td>
                    <td>
                        @if($canApprove && $p->review_status === 'pending')
                        <button wire:click="startReview({{ $p->id }})" class="btn btn--outline btn--sm">{{ __('ui.review', [], null, 'Review') }}</button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;color:var(--text-secondary);padding:var(--space-8);font-style:italic">{{ __('ui.no_proposals', [], null, 'No proposals found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $proposals->links() }}
</div>

<style>.card{background:var(--surface-card);border:1px solid var(--card-border);border-radius:var(--card-radius);padding:var(--card-padding);box-shadow:var(--card-shadow)}</style>

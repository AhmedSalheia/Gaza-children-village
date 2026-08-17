@php /** @var \App\Livewire\Admin\Enrollments\PromotionIndex $this */ @endphp

<div>
    <div class="page-header">
        <h1 class="page-title">{{ __('ui.promotion_proposals', [], null, 'Promotion Proposals') }}</h1>
    </div>

    @include('livewire.admin._partials.flash-message')

    <div class="filters-bar">
        <select wire:model.live="reviewStatusFilter" class="form-control form-select" style="max-inline-size:160px">
            <option value="">{{ __('ui.all', [], null, 'All') }}</option>
            <option value="pending">{{ __('status.pending') }}</option>
            <option value="approved">{{ __('status.approved') }}</option>
            <option value="rejected">{{ __('status.rejected') }}</option>
        </select>
    </div>

    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('ui.student', [], null, 'Student') }}</th>
                    <th>{{ __('ui.current_class', [], null, 'Current Class') }}</th>
                    <th>{{ __('ui.proposed', [], null, 'Proposed') }}</th>
                    <th>{{ __('ui.proposed_level', [], null, 'To Level') }}</th>
                    <th>{{ __('ui.review_status', [], null, 'Review') }}</th>
                    <th>{{ __('ui.reviewed_by', [], null, 'By') }}</th>
                    <th>{{ __('ui.actions', [], null, 'Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($proposals as $proposal)
                    <tr>
                        <td>
                            <div style="font-weight:600" dir="rtl">{{ $proposal->student_name }}</div>
                            <code style="font-size:var(--text-xs)">{{ $proposal->student_code }}</code>
                        </td>
                        <td dir="rtl">{{ $proposal->current_class_group }}</td>
                        <td>
                            <span class="badge badge--{{ match($proposal->proposed_status) { 'promoted' => 'open', 'repeating' => 'pending', default => 'draft' } }}">
                                {{ $proposal->proposed_status }}
                            </span>
                        </td>
                        <td>{{ $proposal->proposed_level ?? '—' }}</td>
                        <td>
                            <span class="badge badge--{{ match($proposal->review_status) { 'approved' => 'active', 'rejected' => 'closed', default => 'pending' } }}">
                                {{ $proposal->review_status }}
                            </span>
                        </td>
                        <td style="font-size:var(--text-sm);color:var(--text-secondary)">
                            {{ $proposal->reviewed_by ?? '—' }}
                        </td>
                        <td style="display:flex;gap:var(--space-1)">
                            @if($proposal->review_status === 'pending')
                                <button wire:click="approve({{ $proposal->id }})" class="btn btn--primary btn--sm">
                                    {{ __('ui.approve', [], null, 'Approve') }}
                                </button>
                                <button wire:click="reject({{ $proposal->id }})" class="btn btn--danger btn--sm">
                                    {{ __('ui.reject', [], null, 'Reject') }}
                                </button>
                            @else
                                <span style="color:var(--text-secondary);font-size:var(--text-sm)">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty-state">{{ __('ui.no_proposals', [], null, 'No promotion proposals found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $proposals->links() }}
</div>

@include('livewire.admin._partials.page-styles')

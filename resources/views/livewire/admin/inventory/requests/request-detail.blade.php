<div class="inventory-request-detail-page">

    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div class="request-detail-header">

        <div class="request-detail-header-main">

            <div class="request-detail-icon">

                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M7 3h10l4 4v14H3V3z"/>
                    <path d="M7 3v5h10V3"/>
                    <path d="M8 13h8"/>
                    <path d="M8 17h5"/>
                </svg>

            </div>

            <div>

                <div class="request-detail-eyebrow">
                    GCV INVENTORY / REQUEST
                </div>

                <h1>
                    {{ $request->request_number }}
                </h1>

                <p>
                    {{ __('ui.inventory.new_issue_request') }}
                </p>

            </div>

        </div>

        <div class="request-detail-header-actions">

            <span class="request-detail-top-status status-{{ $request->status }}">

                @if($request->status === 'approved_for_issue' || $request->status === 'issued')

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m5 12 4 4L19 6"/>
                    </svg>

                @elseif($request->status === 'pending_approval')

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 7v5l3 2"/>
                    </svg>

                @elseif($request->status === 'rejected')

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m7 7 10 10"/>
                        <path d="m17 7-10 10"/>
                    </svg>

                @else

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="12" cy="12" r="9"/>
                    </svg>

                @endif

                {{ $request->status }}

            </span>

            <a
                class="request-detail-back-btn"
                href="{{ route('admin.inventory.requests.index') }}"
                wire:navigate
            >

                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M19 12H5"/>
                    <path d="m12 19-7-7 7-7"/>
                </svg>

                {{ __('ui.inventory.back') }}

            </a>

        </div>

    </div>


    {{-- =========================================================
         NAVIGATION
    ========================================================== --}}
    @include('livewire.admin.inventory._nav')


    {{-- =========================================================
         SUMMARY
    ========================================================== --}}
    @php

        $totalRequested = $request->lines->sum(
            fn ($line) => (float) $line->requested_quantity
        );

        $totalApproved = $request->lines->sum(
            fn ($line) => (float) (
                $line->approved_quantity
                ?? $line->requested_quantity
            )
        );

        $pendingApprovals = $request->approvals
            ->where('status', 'pending')
            ->count();

        $completedApprovals = $request->approvals
            ->whereIn('status', ['approved', 'rejected'])
            ->count();

    @endphp


    <div class="request-detail-summary-grid">

        {{-- Institution --}}
        <div class="request-detail-summary-card">

            <div class="summary-card-icon institution">

                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="m3 10 9-6 9 6"/>
                    <path d="M5 10v10"/>
                    <path d="M9 10v10"/>
                    <path d="M15 10v10"/>
                    <path d="M19 10v10"/>
                    <path d="M3 20h18"/>
                </svg>

            </div>

            <div class="summary-card-content">

                <span>
                    {{ __('ui.inventory.institution') }}
                </span>

                <strong>
                    {{ $request->institution?->name_ar ?? '—' }}
                </strong>

            </div>

        </div>


        {{-- Warehouse --}}
        <div class="request-detail-summary-card">

            <div class="summary-card-icon warehouse">

                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M3 10 12 4l9 6"/>
                    <path d="M5 10v10h14V10"/>
                    <path d="M9 20v-6h6v6"/>
                </svg>

            </div>

            <div class="summary-card-content">

                <span>
                    {{ __('ui.inventory.warehouse') }}
                </span>

                <strong>
                    {{ $request->warehouse?->name_ar ?? '—' }}
                </strong>

            </div>

        </div>


        {{-- Requested --}}
        <div class="request-detail-summary-card">

            <div class="summary-card-icon requested">

                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M4 6h16v14H4z"/>
                    <path d="M8 3v6"/>
                    <path d="M16 3v6"/>
                    <path d="M8 13h8"/>
                    <path d="M8 17h5"/>
                </svg>

            </div>

            <div class="summary-card-content">

                <span>
                    {{ __('ui.inventory.requested') }}
                </span>

                <strong>
                    {{ number_format($totalRequested, 3) }}
                </strong>

            </div>

        </div>


        {{-- Approved --}}
        <div class="request-detail-summary-card">

            <div class="summary-card-icon approved">

                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="m8 12 3 3 5-6"/>
                </svg>

            </div>

            <div class="summary-card-content">

                <span>
                    {{ __('ui.inventory.approved') }}
                </span>

                <strong>
                    {{ number_format($totalApproved, 3) }}
                </strong>

            </div>

        </div>

    </div>


    {{-- =========================================================
         REQUEST INFORMATION
    ========================================================== --}}
    <div class="request-detail-info-card">

        <div class="detail-card-header">

            <div class="detail-card-heading">

                <div class="detail-card-icon">

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 11v5"/>
                        <path d="M12 8h.01"/>
                    </svg>

                </div>

                <div>

                    <div class="detail-card-kicker">
                        REQUEST INFORMATION
                    </div>

                    <h2>
                        {{ __('ui.inventory.requests') }}
                    </h2>

                </div>

            </div>

            <span class="detail-card-number">
                #{{ $request->id }}
            </span>

        </div>


        <div class="request-info-grid">

            <div class="request-info-item">

                <span>
                    {{ __('ui.inventory.request_number') }}
                </span>

                <strong class="ltr-value">
                    {{ $request->request_number }}
                </strong>

            </div>


            <div class="request-info-item">

                <span>
                    {{ __('ui.inventory.status') }}
                </span>

                <strong>

                    <span class="request-status-pill status-{{ $request->status }}">
                        {{ $request->status }}
                    </span>

                </strong>

            </div>


            @if(isset($request->priority))

                <div class="request-info-item">

                    <span>
                        {{ __('ui.inventory.priority') }}
                    </span>

                    <strong>
                        {{ $request->priority }}
                    </strong>

                </div>

            @endif


            @if(isset($request->estimated_value))

                <div class="request-info-item">

                    <span>
                        {{ __('ui.inventory.value') }}
                    </span>

                    <strong class="ltr-value">
                        {{ number_format((float) $request->estimated_value, 2) }}
                    </strong>

                </div>

            @endif


            <div class="request-info-item full">

                <span>
                    {{ __('ui.inventory.reason') }}
                </span>

                <div class="request-reason">

                    @if($request->reason_ar)

                        {{ $request->reason_ar }}

                    @else

                        <span class="muted">
                            —
                        </span>

                    @endif

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         REQUEST LINES
    ========================================================== --}}
    <div class="request-detail-table-card">

        <div class="detail-card-header">

            <div class="detail-card-heading">

                <div class="detail-card-icon">

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M4 5h16v14H4z"/>
                        <path d="M8 9h8"/>
                        <path d="M8 13h8"/>
                        <path d="M8 17h5"/>
                    </svg>

                </div>

                <div>

                    <div class="detail-card-kicker">
                        GCV DATA
                    </div>

                    <h2>
                        {{ __('ui.inventory.item') }}
                    </h2>

                </div>

            </div>

            <div class="detail-card-count">

                {{ number_format($request->lines->count()) }}

                <span>
                    {{ __('ui.inventory.count') }}
                </span>

            </div>

        </div>


        <div class="request-lines-scroll">

            <table class="request-lines-table">

                <thead>

                    <tr>

                        <th>#</th>

                        <th>
                            {{ __('ui.inventory.item') }}
                        </th>

                        <th>
                            {{ __('ui.inventory.requested') }}
                        </th>

                        <th>
                            {{ __('ui.inventory.approved') }}
                        </th>

                        <th>
                            {{ __('ui.inventory.status') }}
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($request->lines as $index => $l)

                        @php

                            $requested = (float) $l->requested_quantity;

                            $approved = (float) (
                                $l->approved_quantity
                                ?? $l->requested_quantity
                            );

                            $difference = $approved - $requested;

                        @endphp

                        <tr>

                            <td>

                                <span class="line-number">
                                    {{ $index + 1 }}
                                </span>

                            </td>


                            <td>

                                <div class="line-item">

                                    <div class="line-item-icon">

                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path d="M4 7h16v13H4z"/>
                                            <path d="M7 7V5h10v2"/>
                                            <path d="M8 12h8"/>
                                            <path d="M8 16h5"/>
                                        </svg>

                                    </div>

                                    <div>

                                        <strong>
                                            {{ $l->item?->name_ar ?? '—' }}
                                        </strong>

                                        @if($l->item?->name_en)

                                            <small>
                                                {{ $l->item->name_en }}
                                            </small>

                                        @endif

                                        @if($l->item?->sku)

                                            <small class="sku">
                                                {{ $l->item->sku }}
                                            </small>

                                        @endif

                                    </div>

                                </div>

                            </td>


                            <td>

                                <span class="quantity-badge requested">
                                    {{ number_format($requested, 3) }}
                                </span>

                            </td>


                            <td>

                                <span class="quantity-badge approved">
                                    {{ number_format($approved, 3) }}
                                </span>

                            </td>


                            <td>

                                @if($difference > 0)

                                    <span class="line-status increase">
                                        +{{ number_format($difference, 3) }}
                                    </span>

                                @elseif($difference < 0)

                                    <span class="line-status decrease">
                                        {{ number_format($difference, 3) }}
                                    </span>

                                @else

                                    <span class="line-status equal">
                                        {{ __('ui.inventory.approved') }}
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="5">

                                <div class="detail-empty">

                                    <div class="detail-empty-icon">

                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path d="M4 5h16v14H4z"/>
                                            <path d="M8 9h8"/>
                                            <path d="M8 13h8"/>
                                        </svg>

                                    </div>

                                    <strong>
                                        {{ __('ui.inventory.no_data') }}
                                    </strong>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>


    {{-- =========================================================
         APPROVAL CHAIN
    ========================================================== --}}
    <div class="approval-chain-card">

        <div class="detail-card-header">

            <div class="detail-card-heading">

                <div class="detail-card-icon approval-icon">

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M8 7h12"/>
                        <path d="M8 12h12"/>
                        <path d="M8 17h12"/>
                        <circle cx="4" cy="7" r="1"/>
                        <circle cx="4" cy="12" r="1"/>
                        <circle cx="4" cy="17" r="1"/>
                    </svg>

                </div>

                <div>

                    <div class="detail-card-kicker">
                        APPROVAL WORKFLOW
                    </div>

                    <h2>
                        {{ __('ui.inventory.approval_chain') }}
                    </h2>

                </div>

            </div>


            <div class="approval-summary">

                <span>
                    {{ $completedApprovals }}
                    / {{ $request->approvals->count() }}
                </span>

                <small>
                    {{ __('ui.inventory.count') }}
                </small>

            </div>

        </div>


        @if($request->approvals->count())

            <div class="approval-timeline">

                @foreach($request->approvals as $s)

                    @php

                        $approvalStatus = match ($s->status) {

                            'approved'
                                => 'approved',

                            'rejected'
                                => 'rejected',

                            'pending'
                                => 'pending',

                            default
                                => 'default',

                        };

                    @endphp

                    <div class="approval-step {{ $approvalStatus }}">

                        <div class="approval-step-marker">

                            @if($approvalStatus === 'approved')

                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="m5 12 4 4L19 6"/>
                                </svg>

                            @elseif($approvalStatus === 'rejected')

                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="m7 7 10 10"/>
                                    <path d="m17 7-10 10"/>
                                </svg>

                            @else

                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <circle cx="12" cy="12" r="8"/>
                                </svg>

                            @endif

                        </div>


                        <div class="approval-step-content">

                            <div class="approval-step-top">

                                <div>

                                    <span class="approval-step-number">
                                        {{ $s->step_order }}
                                    </span>

                                    <strong>
                                        {{ $s->approver_role }}
                                    </strong>

                                </div>


                                <span class="approval-status-badge {{ $approvalStatus }}">

                                    @if($approvalStatus === 'approved')

                                        {{ __('ui.inventory.approve') }}

                                    @elseif($approvalStatus === 'rejected')

                                        {{ __('ui.inventory.reject') }}

                                    @elseif($approvalStatus === 'pending')

                                        {{ __('ui.inventory.pending_approval') }}

                                    @else

                                        {{ $s->status }}

                                    @endif

                                </span>

                            </div>


                            @if($s->status === 'pending')

                                <div class="approval-actions">

                                    <button
                                        type="button"
                                        wire:click="decide({{ $s->id }}, 'approved')"
                                        wire:loading.attr="disabled"
                                        wire:target="decide({{ $s->id }}, 'approved')"
                                        class="approval-action approve"
                                    >

                                        <span
                                            wire:loading.remove
                                            wire:target="decide({{ $s->id }}, 'approved')"
                                        >

                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="m5 12 4 4L19 6"/>
                                            </svg>

                                            {{ __('ui.inventory.approve') }}

                                        </span>

                                        <span
                                            wire:loading
                                            wire:target="decide({{ $s->id }}, 'approved')"
                                        >
                                            ...
                                        </span>

                                    </button>


                                    <button
                                        type="button"
                                        wire:click="decide({{ $s->id }}, 'rejected')"
                                        wire:loading.attr="disabled"
                                        wire:target="decide({{ $s->id }}, 'rejected')"
                                        class="approval-action reject"
                                    >

                                        <span
                                            wire:loading.remove
                                            wire:target="decide({{ $s->id }}, 'rejected')"
                                        >

                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="m7 7 10 10"/>
                                                <path d="m17 7-10 10"/>
                                            </svg>

                                            {{ __('ui.inventory.reject') }}

                                        </span>

                                        <span
                                            wire:loading
                                            wire:target="decide({{ $s->id }}, 'rejected')"
                                        >
                                            ...
                                        </span>

                                    </button>

                                </div>

                            @endif

                        </div>

                    </div>

                @endforeach

            </div>

        @else

            <div class="detail-empty approval-empty">

                <div class="detail-empty-icon">

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M8 7h12"/>
                        <path d="M8 12h12"/>
                        <path d="M8 17h12"/>
                    </svg>

                </div>

                <strong>
                    {{ __('ui.inventory.no_pending_approvals') }}
                </strong>

            </div>

        @endif


        {{-- =====================================================
             EXECUTE ISSUE
        ====================================================== --}}
        @if($request->status === 'approved_for_issue')

            <div class="execute-issue-box">

                <div class="execute-issue-info">

                    <div class="execute-issue-icon">

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M12 3 4 7v5c0 4.5 3.4 7.8 8 9 4.6-1.2 8-4.5 8-9V7z"/>
                            <path d="m8 12 3 3 5-6"/>
                        </svg>

                    </div>

                    <div>

                        <strong>
                            {{ __('ui.inventory.approved') }}
                        </strong>

                        <span>
                            {{ __('ui.inventory.execute_issue') }}
                        </span>

                    </div>

                </div>


                <button
                    type="button"
                    wire:click="execute"
                    wire:loading.attr="disabled"
                    wire:target="execute"
                    class="execute-issue-btn"
                >

                    <span wire:loading.remove wire:target="execute">

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14"/>
                            <path d="m13 6 6 6-6 6"/>
                        </svg>

                        {{ __('ui.inventory.execute_issue') }}

                    </span>

                    <span wire:loading wire:target="execute">
                        ...
                    </span>

                </button>

            </div>

        @endif

    </div>


    {{-- =========================================================
         STYLES
    ========================================================== --}}
    <style>

        .inventory-request-detail-page {
            direction: rtl;
            width: 100%;
            max-width: 1600px;
            margin: 0 auto;
            padding: 8px 0 45px;
        }


        /* =====================================================
           HEADER
        ====================================================== */

        .request-detail-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding: 26px 28px;
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 22px;
            background: linear-gradient(
                135deg,
                #ffffff,
                #f8fafc
            );
            box-shadow: 0 10px 35px rgba(15,23,42,.06);
        }

        .request-detail-header-main {
            display: flex;
            align-items: center;
            gap: 15px;
            min-width: 0;
        }

        .request-detail-icon {
            width: 58px;
            height: 58px;
            flex: 0 0 58px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 17px;
            background: #111827;
            color: #fff;
            box-shadow: 0 8px 20px rgba(17,24,39,.18);
        }

        .request-detail-icon svg {
            width: 28px;
            height: 28px;
        }

        .request-detail-eyebrow {
            margin-bottom: 4px;
            color: #9ca3af;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .14em;
            direction: ltr;
        }

        .request-detail-header h1 {
            margin: 0;
            color: #111827;
            font-size: 23px;
            font-weight: 900;
            direction: ltr;
            text-align: right;
            word-break: break-all;
        }

        .request-detail-header p {
            margin: 5px 0 0;
            color: #6b7280;
            font-size: 12px;
        }

        .request-detail-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .request-detail-top-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 38px;
            padding: 0 12px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 850;
            white-space: nowrap;
        }

        .request-detail-top-status svg {
            width: 16px;
            height: 16px;
        }

        .request-detail-top-status.status-approved_for_issue,
        .request-detail-top-status.status-issued {
            background: #ecfdf5;
            color: #047857;
        }

        .request-detail-top-status.status-pending_approval {
            background: #fffbeb;
            color: #b45309;
        }

        .request-detail-top-status.status-rejected,
        .request-detail-top-status.status-cancelled {
            background: #fff1f2;
            color: #be123c;
        }

        .request-detail-top-status.status-draft {
            background: #f3f4f6;
            color: #4b5563;
        }

        .request-detail-back-btn {
            min-height: 38px;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 0 13px;
            border: 1px solid #dfe3e8;
            border-radius: 10px;
            background: #fff;
            color: #374151 !important;
            text-decoration: none !important;
            font-size: 11px;
            font-weight: 800;
            transition: .2s ease;
        }

        .request-detail-back-btn:hover {
            background: #f9fafb;
            border-color: #cbd5e1;
        }

        .request-detail-back-btn svg {
            width: 16px;
            height: 16px;
        }


        /* =====================================================
           SUMMARY
        ====================================================== */

        .request-detail-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .request-detail-summary-card {
            min-height: 100px;
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 17px;
            border: 1px solid #e5e7eb;
            border-radius: 17px;
            background: #fff;
            box-shadow: 0 7px 24px rgba(15,23,42,.045);
        }

        .summary-card-icon {
            width: 45px;
            height: 45px;
            flex: 0 0 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 13px;
        }

        .summary-card-icon svg {
            width: 21px;
            height: 21px;
        }

        .summary-card-icon.institution {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .summary-card-icon.warehouse {
            background: #f5f3ff;
            color: #6d28d9;
        }

        .summary-card-icon.requested {
            background: #fff7ed;
            color: #c2410c;
        }

        .summary-card-icon.approved {
            background: #ecfdf5;
            color: #047857;
        }

        .summary-card-content {
            min-width: 0;
        }

        .summary-card-content span {
            display: block;
            margin-bottom: 5px;
            color: #6b7280;
            font-size: 11px;
            font-weight: 750;
        }

        .summary-card-content strong {
            display: block;
            color: #111827;
            font-size: 15px;
            font-weight: 900;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }


        /* =====================================================
           COMMON CARD
        ====================================================== */

        .request-detail-info-card,
        .request-detail-table-card,
        .approval-chain-card {
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 8px 28px rgba(15,23,42,.045);
            overflow: hidden;
        }

        .detail-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 19px 22px;
            border-bottom: 1px solid #eef0f2;
        }

        .detail-card-heading {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .detail-card-icon {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #f3f4f6;
            color: #374151;
        }

        .detail-card-icon svg {
            width: 20px;
            height: 20px;
        }

        .detail-card-kicker {
            margin-bottom: 3px;
            color: #9ca3af;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: .14em;
            direction: ltr;
        }

        .detail-card-heading h2 {
            margin: 0;
            color: #111827;
            font-size: 15px;
            font-weight: 900;
        }

        .detail-card-number,
        .detail-card-count,
        .approval-summary {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 7px 10px;
            border: 1px solid #e5e7eb;
            border-radius: 9px;
            background: #f9fafb;
            color: #374151;
            font-size: 11px;
            font-weight: 850;
        }

        .detail-card-count span,
        .approval-summary small {
            color: #9ca3af;
            font-size: 9px;
            font-weight: 700;
        }


        /* =====================================================
           INFORMATION
        ====================================================== */

        .request-info-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0;
        }

        .request-info-item {
            min-width: 0;
            padding: 17px 20px;
            border-left: 1px solid #eef0f2;
            border-bottom: 1px solid #eef0f2;
        }

        .request-info-item:nth-child(4n) {
            border-left: 0;
        }

        .request-info-item.full {
            grid-column: 1 / -1;
            border-left: 0;
        }

        .request-info-item > span {
            display: block;
            margin-bottom: 6px;
            color: #9ca3af;
            font-size: 10px;
            font-weight: 800;
        }

        .request-info-item > strong {
            display: block;
            color: #374151;
            font-size: 12px;
            font-weight: 850;
        }

        .ltr-value {
            direction: ltr;
            text-align: right;
        }

        .request-reason {
            min-height: 50px;
            padding: 11px 12px;
            border: 1px solid #eef0f2;
            border-radius: 10px;
            background: #f9fafb;
            color: #4b5563;
            font-size: 12px;
            line-height: 1.8;
        }

        .muted {
            color: #9ca3af;
        }

        .request-status-pill {
            display: inline-flex;
            align-items: center;
            min-height: 27px;
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 850;
        }

        .request-status-pill.status-approved_for_issue,
        .request-status-pill.status-issued {
            background: #ecfdf5;
            color: #047857;
        }

        .request-status-pill.status-pending_approval {
            background: #fffbeb;
            color: #b45309;
        }

        .request-status-pill.status-rejected,
        .request-status-pill.status-cancelled {
            background: #fff1f2;
            color: #be123c;
        }

        .request-status-pill.status-draft {
            background: #f3f4f6;
            color: #4b5563;
        }


        /* =====================================================
           LINES TABLE
        ====================================================== */

        .request-lines-scroll {
            overflow-x: auto;
        }

        .request-lines-table {
            width: 100%;
            min-width: 780px;
            border-collapse: separate;
            border-spacing: 0;
        }

        .request-lines-table th {
            padding: 13px 18px;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
            color: #6b7280;
            font-size: 10px;
            font-weight: 850;
            text-align: right;
            white-space: nowrap;
        }

        .request-lines-table td {
            padding: 14px 18px;
            border-bottom: 1px solid #f1f3f5;
            vertical-align: middle;
            color: #374151;
            font-size: 12px;
        }

        .request-lines-table tbody tr:hover {
            background: #fafafa;
        }

        .request-lines-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .line-number {
            width: 27px;
            height: 27px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #f3f4f6;
            color: #6b7280;
            font-size: 10px;
            font-weight: 850;
        }

        .line-item {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 220px;
        }

        .line-item-icon {
            width: 37px;
            height: 37px;
            flex: 0 0 37px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .line-item-icon svg {
            width: 18px;
            height: 18px;
        }

        .line-item strong {
            display: block;
            color: #111827;
            font-size: 12px;
            font-weight: 850;
        }

        .line-item small {
            display: block;
            margin-top: 2px;
            color: #9ca3af;
            font-size: 9px;
        }

        .line-item small.sku {
            color: #6b7280;
            font-weight: 800;
            direction: ltr;
            text-align: right;
        }

        .quantity-badge {
            display: inline-flex;
            min-width: 75px;
            justify-content: center;
            padding: 7px 9px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 900;
            direction: ltr;
        }

        .quantity-badge.requested {
            background: #fff7ed;
            color: #c2410c;
        }

        .quantity-badge.approved {
            background: #ecfdf5;
            color: #047857;
        }

        .line-status {
            display: inline-flex;
            min-width: 65px;
            justify-content: center;
            padding: 6px 8px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 850;
            direction: ltr;
        }

        .line-status.increase {
            background: #ecfdf5;
            color: #047857;
        }

        .line-status.decrease {
            background: #fff1f2;
            color: #be123c;
        }

        .line-status.equal {
            background: #f3f4f6;
            color: #4b5563;
        }


        /* =====================================================
           APPROVAL TIMELINE
        ====================================================== */

        .approval-timeline {
            padding: 22px;
        }

        .approval-step {
            position: relative;
            display: flex;
            gap: 14px;
            padding-bottom: 18px;
        }

        .approval-step:last-child {
            padding-bottom: 0;
        }

        .approval-step:not(:last-child)::after {
            content: "";
            position: absolute;
            top: 35px;
            right: 17px;
            width: 1px;
            height: calc(100% - 17px);
            background: #e5e7eb;
        }

        .approval-step-marker {
            position: relative;
            z-index: 2;
            width: 35px;
            height: 35px;
            flex: 0 0 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            border: 2px solid #e5e7eb;
            background: #fff;
            color: #9ca3af;
        }

        .approval-step-marker svg {
            width: 16px;
            height: 16px;
        }

        .approval-step.approved .approval-step-marker {
            border-color: #a7f3d0;
            background: #ecfdf5;
            color: #047857;
        }

        .approval-step.rejected .approval-step-marker {
            border-color: #fecdd3;
            background: #fff1f2;
            color: #be123c;
        }

        .approval-step.pending .approval-step-marker {
            border-color: #fde68a;
            background: #fffbeb;
            color: #b45309;
        }

        .approval-step-content {
            flex: 1;
            min-width: 0;
            padding: 11px 13px;
            border: 1px solid #eef0f2;
            border-radius: 13px;
            background: #fafafa;
        }

        .approval-step-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .approval-step-top > div {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .approval-step-number {
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 7px;
            background: #e5e7eb;
            color: #4b5563;
            font-size: 9px;
            font-weight: 900;
        }

        .approval-step-top strong {
            color: #111827;
            font-size: 12px;
            font-weight: 900;
        }

        .approval-status-badge {
            display: inline-flex;
            align-items: center;
            min-height: 25px;
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 850;
        }

        .approval-status-badge.approved {
            background: #ecfdf5;
            color: #047857;
        }

        .approval-status-badge.rejected {
            background: #fff1f2;
            color: #be123c;
        }

        .approval-status-badge.pending {
            background: #fffbeb;
            color: #b45309;
        }

        .approval-status-badge.default {
            background: #f3f4f6;
            color: #4b5563;
        }

        .approval-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 13px;
            padding-top: 12px;
            border-top: 1px solid #eef0f2;
        }

        .approval-action {
            min-height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 0 11px;
            border: 0;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 850;
            cursor: pointer;
            transition: .2s ease;
        }

        .approval-action svg {
            width: 14px;
            height: 14px;
        }

        .approval-action.approve {
            background: #ecfdf5;
            color: #047857;
        }

        .approval-action.approve:hover {
            background: #d1fae5;
        }

        .approval-action.reject {
            background: #fff1f2;
            color: #be123c;
        }

        .approval-action.reject:hover {
            background: #ffe4e6;
        }

        .approval-action:disabled {
            opacity: .55;
            cursor: wait;
        }


        /* =====================================================
           EXECUTE
        ====================================================== */

        .execute-issue-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin: 0 22px 22px;
            padding: 17px;
            border: 1px solid #bbf7d0;
            border-radius: 15px;
            background: #f0fdf4;
        }

        .execute-issue-info {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .execute-issue-icon {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #dcfce7;
            color: #047857;
        }

        .execute-issue-icon svg {
            width: 21px;
            height: 21px;
        }

        .execute-issue-info strong {
            display: block;
            color: #166534;
            font-size: 12px;
            font-weight: 900;
        }

        .execute-issue-info span {
            display: block;
            margin-top: 3px;
            color: #15803d;
            font-size: 10px;
        }

        .execute-issue-btn {
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 0 16px;
            border: 0;
            border-radius: 10px;
            background: #047857;
            color: #fff;
            font-size: 11px;
            font-weight: 850;
            cursor: pointer;
        }

        .execute-issue-btn:hover {
            background: #065f46;
        }

        .execute-issue-btn:disabled {
            opacity: .6;
            cursor: wait;
        }

        .execute-issue-btn svg {
            width: 16px;
            height: 16px;
        }


        /* =====================================================
           EMPTY
        ====================================================== */

        .detail-empty {
            min-height: 180px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 25px;
            text-align: center;
        }

        .detail-empty-icon {
            width: 54px;
            height: 54px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            border-radius: 15px;
            background: #f3f4f6;
            color: #9ca3af;
        }

        .detail-empty-icon svg {
            width: 26px;
            height: 26px;
        }

        .detail-empty strong {
            color: #6b7280;
            font-size: 12px;
            font-weight: 850;
        }

        .approval-empty {
            border-bottom: 1px solid #eef0f2;
        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 1150px) {

            .request-detail-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .request-info-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .request-info-item:nth-child(4n) {
                border-left: 1px solid #eef0f2;
            }

            .request-info-item:nth-child(2n) {
                border-left: 0;
            }

        }


        @media (max-width: 750px) {

            .request-detail-header {
                flex-direction: column;
                align-items: stretch;
                padding: 20px;
            }

            .request-detail-header-actions {
                justify-content: space-between;
            }

            .request-detail-summary-grid {
                grid-template-columns: 1fr;
            }

            .request-info-grid {
                grid-template-columns: 1fr;
            }

            .request-info-item,
            .request-info-item:nth-child(2n),
            .request-info-item:nth-child(4n) {
                border-left: 0;
            }

            .request-info-item.full {
                grid-column: auto;
            }

            .detail-card-header {
                padding: 16px;
            }

            .approval-timeline {
                padding: 16px;
            }

            .approval-step-top {
                align-items: flex-start;
                flex-direction: column;
            }

            .execute-issue-box {
                align-items: stretch;
                flex-direction: column;
                margin: 0 16px 16px;
            }

            .execute-issue-btn {
                width: 100%;
            }

        }


        @media (max-width: 480px) {

            .request-detail-header-main {
                align-items: flex-start;
            }

            .request-detail-icon {
                width: 48px;
                height: 48px;
                flex-basis: 48px;
            }

            .request-detail-header h1 {
                font-size: 17px;
            }

            .request-detail-header-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .request-detail-top-status,
            .request-detail-back-btn {
                justify-content: center;
                width: 100%;
            }

            .approval-actions {
                flex-direction: column;
            }

            .approval-action {
                width: 100%;
            }

        }

    </style>

</div>

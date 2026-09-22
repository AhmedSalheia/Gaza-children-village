<div class="inventory-requests-page">

    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div class="inventory-page-header">

        <div class="inventory-page-header-main">

            <div class="inventory-page-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M7 3h10l4 4v14H3V3z"/>
                    <path d="M7 3v5h10V3"/>
                    <path d="M8 13h8"/>
                    <path d="M8 17h5"/>
                </svg>
            </div>

            <div>
                <div class="inventory-eyebrow">
                    GCV INVENTORY
                </div>

                <h1 class="inventory-page-title">
                    {{ __('ui.inventory.requests') }}
                </h1>

                <p class="inventory-page-description">
                    {{ __('ui.inventory.description') }}
                </p>
            </div>

        </div>

        <div class="inventory-header-badge">

            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M12 6v12"/>
                <path d="M6 12h12"/>
            </svg>

            <span>
                {{ __('ui.inventory.new_issue_request') }}
            </span>

        </div>

    </div>


    {{-- =========================================================
         NAVIGATION
    ========================================================== --}}
    @include('livewire.admin.inventory._nav')


    {{-- =========================================================
         SUCCESS MESSAGE
    ========================================================== --}}
    @if (session('success'))

        <div class="inventory-success-alert">

            <div class="inventory-success-icon">

                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m5 12 4 4L19 6"/>
                </svg>

            </div>

            <div>
                <strong>
                    {{ __('ui.inventory.saved') }}
                </strong>

                <span>
                    {{ session('success') }}
                </span>
            </div>

        </div>

    @endif


    {{-- =========================================================
         SUMMARY CARDS
    ========================================================== --}}
    <div class="request-summary-grid">

        {{-- Total --}}
        <div class="request-summary-card">

            <div class="request-summary-icon total">

                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M4 5h16v14H4z"/>
                    <path d="M8 9h8"/>
                    <path d="M8 13h6"/>
                    <path d="M8 17h4"/>
                </svg>

            </div>

            <div class="request-summary-content">

                <span>
                    {{ __('ui.inventory.requests') }}
                </span>

                <strong>
                    {{ number_format($requests->total()) }}
                </strong>

            </div>

        </div>


        {{-- Pending --}}
        <div class="request-summary-card">

            <div class="request-summary-icon pending">

                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M12 7v5l3 2"/>
                </svg>

            </div>

            <div class="request-summary-content">

                <span>
                    {{ __('ui.inventory.pending_approvals') }}
                </span>

                <strong>
                    {{ number_format(
                        $requests->getCollection()
                            ->whereIn('status', [
                                'pending_approval',
                                'approved_for_issue'
                            ])
                            ->count()
                    ) }}
                </strong>

            </div>

        </div>


        {{-- Approved --}}
        <div class="request-summary-card">

            <div class="request-summary-icon approved">

                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="m8 12 3 3 5-6"/>
                </svg>

            </div>

            <div class="request-summary-content">

                <span>
                    {{ __('ui.inventory.approved') }}
                </span>

                <strong>
                    {{ number_format(
                        $requests->getCollection()
                            ->where('status', 'approved_for_issue')
                            ->count()
                    ) }}
                </strong>

            </div>

        </div>


        {{-- Current page value --}}
        <div class="request-summary-card">

            <div class="request-summary-icon value">

                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M12 7v10"/>
                    <path d="M15 9.5c0-1.1-1.3-2-3-2s-3 .9-3 2 1.3 2 3 2 3 .9 3 2-1.3 2-3 2-3-.9-3-2"/>
                </svg>

            </div>

            <div class="request-summary-content">

                <span>
                    {{ __('ui.inventory.value') }}
                </span>

                <strong>
                    {{ number_format(
                        (float) $requests->getCollection()->sum('estimated_value'),
                        2
                    ) }}
                </strong>

            </div>

        </div>

    </div>


    {{-- =========================================================
         CREATE REQUEST CARD
    ========================================================== --}}
    <div class="request-form-card">

        <div class="request-form-header">

            <div class="request-form-title">

                <div class="request-form-icon">

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 5v14"/>
                        <path d="M5 12h14"/>
                    </svg>

                </div>

                <div>

                    <div class="request-form-kicker">
                        GCV REQUEST
                    </div>

                    <h2>
                        {{ __('ui.inventory.new_issue_request') }}
                    </h2>

                    <p>
                        {{ __('ui.inventory.submit_request') }}
                    </p>

                </div>

            </div>

        </div>


        <form wire:submit="create">

            <div class="request-form-grid">

                {{-- Institution --}}
                <div class="request-field">

                    <label for="institutionId">
                        {{ __('ui.inventory.institution') }}

                        <span class="required">*</span>
                    </label>

                    <div class="request-select-wrapper">

                        <select
                            id="institutionId"
                            wire:model.live="institutionId"
                            class="request-select @error('institutionId') is-invalid @enderror"
                        >

                            <option value="">—</option>

                            @foreach ($institutions as $i)

                                <option value="{{ $i->id }}">
                                    {{ $i->name_ar }}
                                </option>

                            @endforeach

                        </select>

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>

                    </div>

                    @error('institutionId')
                        <div class="request-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                {{-- Warehouse --}}
                <div class="request-field">

                    <label for="warehouseId">
                        {{ __('ui.inventory.warehouse') }}

                        <span class="required">*</span>
                    </label>

                    <div class="request-select-wrapper">

                        <select
                            id="warehouseId"
                            wire:model.live="warehouseId"
                            class="request-select @error('warehouseId') is-invalid @enderror"
                        >

                            <option value="">—</option>

                            @foreach ($warehouses as $w)

                                <option value="{{ $w->id }}">
                                    {{ $w->name_ar }}
                                </option>

                            @endforeach

                        </select>

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>

                    </div>

                    @error('warehouseId')
                        <div class="request-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                {{-- Item --}}
                <div class="request-field">

                    <label for="itemId">
                        {{ __('ui.inventory.item') }}

                        <span class="required">*</span>
                    </label>

                    <div class="request-select-wrapper">

                        <select
                            id="itemId"
                            wire:model.live="itemId"
                            class="request-select @error('itemId') is-invalid @enderror"
                        >

                            <option value="">—</option>

                            @foreach ($items as $i)

                                <option value="{{ $i->id }}">
                                    {{ $i->name_ar }} — {{ $i->sku }}
                                </option>

                            @endforeach

                        </select>

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>

                    </div>

                    @error('itemId')
                        <div class="request-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                {{-- Quantity --}}
                <div class="request-field">

                    <label for="quantity">
                        {{ __('ui.inventory.quantity') }}

                        <span class="required">*</span>
                    </label>

                    <div class="request-number-wrapper">

                        <input
                            id="quantity"
                            type="number"
                            min="0"
                            step=".001"
                            wire:model.live="quantity"
                            class="request-input request-number-input @error('quantity') is-invalid @enderror"
                            placeholder="0.000"
                        >

                        <span class="request-input-suffix">
                            {{ __('ui.inventory.quantity') }}
                        </span>

                    </div>

                    @error('quantity')
                        <div class="request-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                {{-- Unit Cost --}}
                <div class="request-field">

                    <label for="unitCost">
                        {{ __('ui.inventory.estimated_unit_cost') }}
                    </label>

                    <div class="request-number-wrapper">

                        <input
                            id="unitCost"
                            type="number"
                            min="0"
                            step=".01"
                            wire:model.live="unitCost"
                            class="request-input request-number-input @error('unitCost') is-invalid @enderror"
                            placeholder="0.00"
                        >

                        <span class="request-input-suffix">
                            {{ __('ui.inventory.value') }}
                        </span>

                    </div>

                    @error('unitCost')
                        <div class="request-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                {{-- Priority --}}
                <div class="request-field">

                    <label for="priority">
                        {{ __('ui.inventory.priority') }}

                        <span class="required">*</span>
                    </label>

                    <div class="request-select-wrapper">

                        <select
                            id="priority"
                            wire:model.live="priority"
                            class="request-select @error('priority') is-invalid @enderror"
                        >

                            <option value="normal">
                                {{ __('ui.inventory.priority_medium') }}
                            </option>

                            <option value="high">
                                {{ __('ui.inventory.priority_high') }}
                            </option>

                            <option value="urgent">
                                {{ __('ui.inventory.priority_urgent') }}
                            </option>

                        </select>

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>

                    </div>

                    @error('priority')
                        <div class="request-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                {{-- Reason --}}
                <div class="request-field full">

                    <label for="reason_ar">
                        {{ __('ui.inventory.reason') }}
                    </label>

                    <textarea
                        id="reason_ar"
                        wire:model.live="reason_ar"
                        class="request-textarea @error('reason_ar') is-invalid @enderror"
                        rows="5"
                        placeholder="{{ __('ui.inventory.reason') }}"
                    ></textarea>

                    @error('reason_ar')
                        <div class="request-error">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

            </div>


            {{-- Form Actions --}}
            <div class="request-form-actions">

                <button
                    type="submit"
                    class="request-submit-btn"
                    wire:loading.attr="disabled"
                    wire:target="create"
                >

                    <span wire:loading.remove wire:target="create">

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14"/>
                            <path d="m13 6 6 6-6 6"/>
                        </svg>

                        {{ __('ui.inventory.submit_request') }}

                    </span>

                    <span
                        wire:loading
                        wire:target="create"
                        class="request-saving"
                    >

                        <span class="request-spinner"></span>

                        {{ __('ui.inventory.saving') }}

                    </span>

                </button>

            </div>

        </form>

    </div>


    {{-- =========================================================
         REQUESTS TABLE
    ========================================================== --}}
    <div class="request-table-card">

        <div class="request-table-header">

            <div>

                <div class="request-table-kicker">
                    GCV DATA
                </div>

                <h2>
                    {{ __('ui.inventory.requests') }}
                </h2>

            </div>

            <div class="request-result-count">

                <span>
                    {{ __('ui.inventory.count') }}
                </span>

                <strong>
                    {{ number_format($requests->total()) }}
                </strong>

            </div>

        </div>


        <div class="request-table-scroll">

            <table class="request-data-table">

                <thead>

                    <tr>

                        <th>
                            {{ __('ui.inventory.request_number') }}
                        </th>

                        <th>
                            {{ __('ui.inventory.institution') }}
                        </th>

                        <th>
                            {{ __('ui.inventory.status') }}
                        </th>

                        <th>
                            {{ __('ui.inventory.value') }}
                        </th>

                        <th>
                            {{ __('ui.inventory.view') }}
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse ($requests as $r)

                        <tr>

                            {{-- Request number --}}
                            <td>

                                <div class="request-number-cell">

                                    <div class="request-number-icon">

                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path d="M7 3h10l4 4v14H3V3z"/>
                                            <path d="M7 3v5h10V3"/>
                                            <path d="M8 13h8"/>
                                            <path d="M8 17h5"/>
                                        </svg>

                                    </div>

                                    <div>

                                        <strong>
                                            {{ $r->request_number }}
                                        </strong>

                                        <small>
                                            GCV REQUEST
                                        </small>

                                    </div>

                                </div>

                            </td>


                            {{-- Institution --}}
                            <td>

                                <div class="request-institution-cell">

                                    <div class="request-institution-icon">

                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path d="m3 10 9-6 9 6"/>
                                            <path d="M5 10v10"/>
                                            <path d="M9 10v10"/>
                                            <path d="M15 10v10"/>
                                            <path d="M19 10v10"/>
                                            <path d="M3 20h18"/>
                                        </svg>

                                    </div>

                                    <span>
                                        {{ $r->institution?->name_ar ?? '—' }}
                                    </span>

                                </div>

                            </td>


                            {{-- Status --}}
                            <td>

                                @php

                                    $statusClass = match ($r->status) {

                                        'draft'
                                            => 'draft',

                                        'pending_approval',
                                        'under_review'
                                            => 'pending',

                                        'approved_for_issue'
                                            => 'approved',

                                        'issued'
                                            => 'issued',

                                        'rejected',
                                        'cancelled'
                                            => 'rejected',

                                        default
                                            => 'default',

                                    };

                                    $statusLabel = match ($r->status) {

                                        'draft'
                                            => __('ui.inventory.draft'),

                                        'pending_approval'
                                            => __('ui.inventory.pending_approval'),

                                        'under_review'
                                            => __('ui.inventory.under_review'),

                                        'approved_for_issue'
                                            => __('ui.inventory.approved'),

                                        'issued'
                                            => __('ui.inventory.issue'),

                                        'rejected'
                                            => __('ui.inventory.reject'),

                                        'cancelled'
                                            => __('ui.inventory.cancel'),

                                        default
                                            => $r->status,

                                    };

                                @endphp

                                <span class="request-status-badge {{ $statusClass }}">

                                    @if($statusClass === 'approved' || $statusClass === 'issued')

                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="m5 12 4 4L19 6"/>
                                        </svg>

                                    @elseif($statusClass === 'pending')

                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <circle cx="12" cy="12" r="9"/>
                                            <path d="M12 7v5l3 2"/>
                                        </svg>

                                    @elseif($statusClass === 'rejected')

                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="m7 7 10 10"/>
                                            <path d="m17 7-10 10"/>
                                        </svg>

                                    @else

                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <circle cx="12" cy="12" r="9"/>
                                        </svg>

                                    @endif

                                    {{ $statusLabel }}

                                </span>

                            </td>


                            {{-- Value --}}
                            <td>

                                <span class="request-value">
                                    {{ number_format((float) $r->estimated_value, 2) }}
                                </span>

                            </td>


                            {{-- View --}}
                            <td>

                                <a
                                    class="request-view-btn"
                                    href="{{ route('admin.inventory.requests.show', $r) }}"
                                    wire:navigate
                                >

                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"/>
                                        <circle cx="12" cy="12" r="2.5"/>
                                    </svg>

                                    {{ __('ui.inventory.view') }}

                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="5">

                                <div class="request-empty-state">

                                    <div class="request-empty-icon">

                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path d="M7 3h10l4 4v14H3V3z"/>
                                            <path d="M7 3v5h10V3"/>
                                            <path d="M8 13h8"/>
                                            <path d="M8 17h5"/>
                                        </svg>

                                    </div>

                                    <h3>
                                        {{ __('ui.inventory.no_data') }}
                                    </h3>

                                    <p>
                                        {{ __('ui.inventory.requests') }}
                                    </p>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        @if($requests->hasPages())

            <div class="request-pagination">
                {{ $requests->links() }}
            </div>

        @endif

    </div>


    {{-- =========================================================
         LOADING
    ========================================================== --}}
    <div
        wire:loading
        wire:target="institutionId,warehouseId,itemId,quantity,unitCost,priority,reason_ar"
        class="request-loading"
    >
        <span class="request-loading-spinner"></span>
    </div>


    {{-- =========================================================
         STYLES
    ========================================================== --}}
    <style>

        .inventory-requests-page {
            direction: rtl;
            width: 100%;
            max-width: 1600px;
            margin: 0 auto;
            padding: 8px 0 45px;
        }


        /* =====================================================
           HEADER
        ====================================================== */

        .inventory-page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding: 28px;
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 22px;
            background:
                linear-gradient(
                    135deg,
                    rgba(255,255,255,.98),
                    rgba(248,250,252,.96)
                );
            box-shadow: 0 10px 35px rgba(15,23,42,.06);
        }

        .inventory-page-header-main {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .inventory-page-icon {
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

        .inventory-page-icon svg {
            width: 28px;
            height: 28px;
        }

        .inventory-eyebrow {
            margin-bottom: 4px;
            color: #6b7280;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .16em;
            direction: ltr;
        }

        .inventory-page-title {
            margin: 0;
            color: #111827;
            font-size: 25px;
            font-weight: 900;
        }

        .inventory-page-description {
            margin: 6px 0 0;
            color: #6b7280;
            font-size: 13px;
            line-height: 1.7;
        }

        .inventory-header-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 42px;
            padding: 0 14px;
            border: 1px solid #dbeafe;
            border-radius: 11px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 850;
            white-space: nowrap;
        }

        .inventory-header-badge svg {
            width: 17px;
            height: 17px;
        }


        /* =====================================================
           SUCCESS
        ====================================================== */

        .inventory-success-alert {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding: 14px 16px;
            border: 1px solid #bbf7d0;
            border-radius: 15px;
            background: #f0fdf4;
            color: #166534;
        }

        .inventory-success-icon {
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            background: #dcfce7;
        }

        .inventory-success-icon svg {
            width: 20px;
            height: 20px;
        }

        .inventory-success-alert strong {
            display: block;
            font-size: 12px;
            font-weight: 900;
        }

        .inventory-success-alert span {
            display: block;
            margin-top: 2px;
            font-size: 12px;
        }


        /* =====================================================
           SUMMARY
        ====================================================== */

        .request-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .request-summary-card {
            min-height: 105px;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 18px;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 7px 24px rgba(15,23,42,.045);
        }

        .request-summary-icon {
            width: 46px;
            height: 46px;
            flex: 0 0 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
        }

        .request-summary-icon svg {
            width: 22px;
            height: 22px;
        }

        .request-summary-icon.total {
            background: #f3f4f6;
            color: #374151;
        }

        .request-summary-icon.pending {
            background: #fffbeb;
            color: #b45309;
        }

        .request-summary-icon.approved {
            background: #ecfdf5;
            color: #047857;
        }

        .request-summary-icon.value {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .request-summary-content span {
            display: block;
            margin-bottom: 5px;
            color: #6b7280;
            font-size: 12px;
            font-weight: 700;
        }

        .request-summary-content strong {
            display: block;
            color: #111827;
            font-size: 21px;
            font-weight: 900;
        }


        /* =====================================================
           FORM CARD
        ====================================================== */

        .request-form-card {
            margin-bottom: 20px;
            padding: 22px;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 8px 28px rgba(15,23,42,.045);
        }

        .request-form-header {
            padding-bottom: 18px;
            margin-bottom: 20px;
            border-bottom: 1px solid #eef0f2;
        }

        .request-form-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .request-form-icon {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #111827;
            color: #fff;
        }

        .request-form-icon svg {
            width: 21px;
            height: 21px;
        }

        .request-form-kicker {
            margin-bottom: 3px;
            color: #9ca3af;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: .14em;
            direction: ltr;
        }

        .request-form-title h2 {
            margin: 0;
            color: #111827;
            font-size: 16px;
            font-weight: 900;
        }

        .request-form-title p {
            margin: 3px 0 0;
            color: #9ca3af;
            font-size: 11px;
        }

        .request-form-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .request-field {
            min-width: 0;
        }

        .request-field.full {
            grid-column: 1 / -1;
        }

        .request-field label {
            display: block;
            margin-bottom: 7px;
            color: #374151;
            font-size: 12px;
            font-weight: 850;
        }

        .request-field .required {
            color: #dc2626;
        }

        .request-select-wrapper,
        .request-number-wrapper {
            position: relative;
        }

        .request-select-wrapper > svg {
            position: absolute;
            top: 50%;
            left: 13px;
            width: 17px;
            height: 17px;
            color: #9ca3af;
            pointer-events: none;
            transform: translateY(-50%);
        }

        .request-select,
        .request-input,
        .request-textarea {
            width: 100%;
            border: 1px solid #dfe3e8;
            border-radius: 11px;
            background: #fff;
            color: #111827;
            outline: none;
            font-size: 13px;
            transition: .2s ease;
            box-sizing: border-box;
        }

        .request-select {
            height: 46px;
            padding: 0 13px 0 40px;
            appearance: none;
        }

        .request-input {
            height: 46px;
            padding: 0 13px;
        }

        .request-number-input {
            padding-left: 105px;
        }

        .request-input-suffix {
            position: absolute;
            left: 10px;
            top: 50%;
            padding: 5px 7px;
            border-radius: 7px;
            background: #f3f4f6;
            color: #6b7280;
            font-size: 9px;
            font-weight: 800;
            pointer-events: none;
            transform: translateY(-50%);
        }

        .request-textarea {
            min-height: 120px;
            padding: 13px;
            resize: vertical;
            line-height: 1.8;
        }

        .request-select:focus,
        .request-input:focus,
        .request-textarea:focus {
            border-color: #9ca3af;
            box-shadow: 0 0 0 3px rgba(156,163,175,.12);
        }

        .request-select.is-invalid,
        .request-input.is-invalid,
        .request-textarea.is-invalid {
            border-color: #ef4444;
        }

        .request-error {
            margin-top: 6px;
            color: #dc2626;
            font-size: 11px;
            font-weight: 700;
        }

        .request-form-actions {
            display: flex;
            justify-content: flex-end;
            padding-top: 20px;
            margin-top: 20px;
            border-top: 1px solid #eef0f2;
        }

        .request-submit-btn {
            min-height: 45px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 20px;
            border: 0;
            border-radius: 11px;
            background: #111827;
            color: #fff;
            font-size: 12px;
            font-weight: 850;
            cursor: pointer;
            transition: .2s ease;
        }

        .request-submit-btn:hover {
            background: #1f2937;
            transform: translateY(-1px);
        }

        .request-submit-btn:disabled {
            opacity: .65;
            cursor: wait;
        }

        .request-submit-btn svg {
            width: 17px;
            height: 17px;
        }

        .request-saving {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .request-spinner {
            width: 15px;
            height: 15px;
            border: 2px solid rgba(255,255,255,.35);
            border-top-color: #fff;
            border-radius: 50%;
            animation: request-spin .7s linear infinite;
        }

        @keyframes request-spin {
            to {
                transform: rotate(360deg);
            }
        }


        /* =====================================================
           TABLE
        ====================================================== */

        .request-table-card {
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 8px 28px rgba(15,23,42,.045);
        }

        .request-table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 20px 22px;
            border-bottom: 1px solid #eef0f2;
        }

        .request-table-kicker {
            margin-bottom: 3px;
            color: #9ca3af;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .16em;
            direction: ltr;
        }

        .request-table-header h2 {
            margin: 0;
            color: #111827;
            font-size: 16px;
            font-weight: 900;
        }

        .request-result-count {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 11px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #f9fafb;
        }

        .request-result-count span {
            color: #6b7280;
            font-size: 11px;
            font-weight: 700;
        }

        .request-result-count strong {
            color: #111827;
            font-size: 13px;
            font-weight: 900;
        }

        .request-table-scroll {
            width: 100%;
            overflow-x: auto;
        }

        .request-data-table {
            width: 100%;
            min-width: 850px;
            border-collapse: separate;
            border-spacing: 0;
        }

        .request-data-table th {
            padding: 13px 18px;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
            color: #6b7280;
            font-size: 11px;
            font-weight: 850;
            text-align: right;
            white-space: nowrap;
        }

        .request-data-table td {
            padding: 15px 18px;
            border-bottom: 1px solid #f1f3f5;
            color: #374151;
            font-size: 13px;
            vertical-align: middle;
        }

        .request-data-table tbody tr {
            transition: background .15s ease;
        }

        .request-data-table tbody tr:hover {
            background: #fafafa;
        }

        .request-data-table tbody tr:last-child td {
            border-bottom: 0;
        }


        /* =====================================================
           REQUEST NUMBER
        ====================================================== */

        .request-number-cell {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 190px;
        }

        .request-number-icon {
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #f3f4f6;
            color: #374151;
        }

        .request-number-icon svg {
            width: 18px;
            height: 18px;
        }

        .request-number-cell strong {
            display: block;
            color: #111827;
            font-size: 11px;
            font-weight: 850;
            direction: ltr;
            text-align: right;
        }

        .request-number-cell small {
            display: block;
            margin-top: 3px;
            color: #9ca3af;
            font-size: 8px;
            font-weight: 800;
            letter-spacing: .1em;
            direction: ltr;
        }


        /* =====================================================
           INSTITUTION
        ====================================================== */

        .request-institution-cell {
            display: flex;
            align-items: center;
            gap: 9px;
            min-width: 190px;
        }

        .request-institution-icon {
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .request-institution-icon svg {
            width: 18px;
            height: 18px;
        }

        .request-institution-cell span {
            color: #374151;
            font-size: 12px;
            font-weight: 750;
        }


        /* =====================================================
           STATUS
        ====================================================== */

        .request-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 30px;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 850;
            white-space: nowrap;
        }

        .request-status-badge svg {
            width: 14px;
            height: 14px;
        }

        .request-status-badge.draft {
            background: #f3f4f6;
            color: #4b5563;
        }

        .request-status-badge.pending {
            background: #fffbeb;
            color: #b45309;
        }

        .request-status-badge.approved {
            background: #ecfdf5;
            color: #047857;
        }

        .request-status-badge.issued {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .request-status-badge.rejected {
            background: #fff1f2;
            color: #be123c;
        }

        .request-status-badge.default {
            background: #f3f4f6;
            color: #4b5563;
        }


        /* =====================================================
           VALUE
        ====================================================== */

        .request-value {
            display: inline-flex;
            min-width: 90px;
            justify-content: center;
            padding: 7px 10px;
            border: 1px solid #e5e7eb;
            border-radius: 9px;
            background: #f9fafb;
            color: #111827;
            font-size: 12px;
            font-weight: 900;
            direction: ltr;
        }


        /* =====================================================
           VIEW BUTTON
        ====================================================== */

        .request-view-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 34px;
            padding: 0 11px;
            border: 1px solid #dbeafe;
            border-radius: 9px;
            background: #eff6ff;
            color: #1d4ed8 !important;
            text-decoration: none !important;
            font-size: 10px;
            font-weight: 850;
            white-space: nowrap;
            transition: .2s ease;
        }

        .request-view-btn:hover {
            background: #dbeafe;
        }

        .request-view-btn svg {
            width: 15px;
            height: 15px;
        }


        /* =====================================================
           EMPTY
        ====================================================== */

        .request-empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 250px;
            padding: 30px;
            text-align: center;
        }

        .request-empty-icon {
            width: 62px;
            height: 62px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            border-radius: 17px;
            background: #f3f4f6;
            color: #9ca3af;
        }

        .request-empty-icon svg {
            width: 29px;
            height: 29px;
        }

        .request-empty-state h3 {
            margin: 0;
            color: #374151;
            font-size: 15px;
            font-weight: 850;
        }

        .request-empty-state p {
            margin: 6px 0 0;
            color: #9ca3af;
            font-size: 12px;
        }


        /* =====================================================
           PAGINATION
        ====================================================== */

        .request-pagination {
            padding: 16px 20px;
            border-top: 1px solid #eef0f2;
        }


        /* =====================================================
           LOADING
        ====================================================== */

        .request-loading {
            position: fixed;
            z-index: 1000;
            left: 24px;
            bottom: 24px;
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: rgba(255,255,255,.96);
            box-shadow: 0 8px 25px rgba(15,23,42,.12);
        }

        .request-loading-spinner {
            width: 16px;
            height: 16px;
            border: 2px solid #d1d5db;
            border-top-color: #111827;
            border-radius: 50%;
            animation: request-spin .7s linear infinite;
        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 1100px) {

            .request-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .request-form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

        }

        @media (max-width: 700px) {

            .inventory-requests-page {
                padding-bottom: 25px;
            }

            .inventory-page-header {
                flex-direction: column;
                align-items: stretch;
                padding: 20px;
            }

            .inventory-page-header-main {
                align-items: flex-start;
            }

            .inventory-header-badge {
                justify-content: center;
            }

            .inventory-page-title {
                font-size: 21px;
            }

            .inventory-page-description {
                font-size: 12px;
            }

            .request-summary-grid {
                grid-template-columns: 1fr;
            }

            .request-form-card {
                padding: 16px;
            }

            .request-form-grid {
                grid-template-columns: 1fr;
            }

            .request-field.full {
                grid-column: auto;
            }

            .request-table-header {
                padding: 16px;
            }

            .request-loading {
                left: 16px;
                bottom: 16px;
            }

        }

    </style>

</div>

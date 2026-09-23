{{-- ============================================================
     INVENTORY PORTAL NAVIGATION
============================================================ --}}

<div class="inventory-nav">

    {{-- ========================================================
         HEADER
    ========================================================= --}}

    <div class="inventory-nav-header">

        <div class="inventory-nav-brand">

            <div class="inventory-nav-brand-icon">
                ▦
            </div>

            <div>
                <strong>
                    {{ __('ui.inventory.title') }}
                </strong>

                <span>
                    {{ __('ui.inventory.description') }}
                </span>
            </div>

        </div>

        <div class="inventory-nav-status">
            <span class="inventory-status-dot"></span>

            {{ __('ui.inventory.active') }}
        </div>

    </div>


    {{-- ========================================================
         MENU
    ========================================================= --}}

    <div class="inventory-nav-menu">

        {{-- Dashboard --}}
        <a
            href="{{ route('admin.inventory.index') }}"
            wire:navigate
            class="inventory-nav-item {{ request()->routeIs('admin.inventory.index') ? 'is-active' : '' }}"
        >
            <span class="inventory-nav-icon">
                ⌂
            </span>

            <span class="inventory-nav-label">
                {{ __('ui.inventory.title') }}
            </span>
        </a>


        {{-- Items --}}
        <a
            href="{{ route('admin.inventory.items.index') }}"
            wire:navigate
            class="inventory-nav-item {{ request()->routeIs('admin.inventory.items.*') ? 'is-active' : '' }}"
        >
            <span class="inventory-nav-icon">
                ▣
            </span>

            <span class="inventory-nav-label">
                {{ __('ui.inventory.items') }}
            </span>
        </a>


        {{-- Warehouses --}}
        <a
            href="{{ route('admin.inventory.warehouses.index') }}"
            wire:navigate
            class="inventory-nav-item {{ request()->routeIs('admin.inventory.warehouses.*') ? 'is-active' : '' }}"
        >
            <span class="inventory-nav-icon">
                ▤
            </span>

            <span class="inventory-nav-label">
                {{ __('ui.inventory.warehouses') }}
            </span>
        </a>


        {{-- Stock --}}
        <a
            href="{{ route('admin.inventory.stock.index') }}"
            wire:navigate
            class="inventory-nav-item {{ request()->routeIs('admin.inventory.stock.*') ? 'is-active' : '' }}"
        >
            <span class="inventory-nav-icon">
                ◫
            </span>

            <span class="inventory-nav-label">
                {{ __('ui.inventory.stock') }}
            </span>
        </a>


        {{-- Movements --}}
        <a
            href="{{ route('admin.inventory.movements.index') }}"
            wire:navigate
            class="inventory-nav-item {{ request()->routeIs('admin.inventory.movements.*') ? 'is-active' : '' }}"
        >
            <span class="inventory-nav-icon">
                ⇄
            </span>

            <span class="inventory-nav-label">
                {{ __('ui.inventory.movements') }}
            </span>
        </a>


        {{-- Requests --}}
        <a
            href="{{ route('admin.inventory.requests.index') }}"
            wire:navigate
            class="inventory-nav-item {{ request()->routeIs('admin.inventory.requests.*') ? 'is-active' : '' }}"
        >
            <span class="inventory-nav-icon">
                ▱
            </span>

            <span class="inventory-nav-label">
                {{ __('ui.inventory.requests') }}
            </span>
        </a>


        {{-- Approvals --}}
        <a
            href="{{ route('admin.inventory.approvals.index') }}"
            wire:navigate
            class="inventory-nav-item {{ request()->routeIs('admin.inventory.approvals.*') ? 'is-active' : '' }}"
        >
            <span class="inventory-nav-icon">
                ✓
            </span>

            <span class="inventory-nav-label">
                {{ __('ui.inventory.approvals') }}
            </span>
        </a>


        {{-- Reallocation --}}
        <a
            href="{{ route('admin.inventory.reallocation.index') }}"
            wire:navigate
            class="inventory-nav-item {{ request()->routeIs('admin.inventory.reallocation.*') ? 'is-active' : '' }}"
        >
            <span class="inventory-nav-icon">
                ⇆
            </span>

            <span class="inventory-nav-label">
                {{ __('ui.inventory.reallocation') }}
            </span>
        </a>


        {{-- Counts --}}
        <a
            href="{{ route('admin.inventory.counts.index') }}"
            wire:navigate
            class="inventory-nav-item {{ request()->routeIs('admin.inventory.counts.*') ? 'is-active' : '' }}"
        >
            <span class="inventory-nav-icon">
                ⊞
            </span>

            <span class="inventory-nav-label">
                {{ __('ui.inventory.counts') }}
            </span>
        </a>


        {{-- Reports --}}
        <a
            href="{{ route('admin.inventory.reports.index') }}"
            wire:navigate
            class="inventory-nav-item {{ request()->routeIs('admin.inventory.reports.*') ? 'is-active' : '' }}"
        >
            <span class="inventory-nav-icon">
                ◫
            </span>

            <span class="inventory-nav-label">
                {{ __('ui.inventory.reports') }}
            </span>
        </a>

    </div>


    {{-- ========================================================
         FOOTER ACTIONS
    ========================================================= --}}

    <div class="inventory-nav-footer">

        <a
            href="{{ route('admin.inventory.requests.index') }}"
            wire:navigate
            class="inventory-nav-action inventory-nav-action-primary"
        >
            <span>＋</span>

            {{ __('ui.inventory.requests') }}
        </a>

        <a
            href="{{ route('admin.inventory.counts.index') }}"
            wire:navigate
            class="inventory-nav-action inventory-nav-action-secondary"
        >
            <span>⊞</span>

            {{ __('ui.inventory.counts') }}
        </a>

    </div>

</div>


<style>

    /* ============================================================
       INVENTORY NAVIGATION
    ============================================================ */

    .inventory-nav {
        direction: rtl;
        width: 100%;
        margin-bottom: 28px;

        background: #ffffff;

        border: 1px solid #e5e9f0;
        border-radius: 16px;

        box-shadow:
            0 5px 20px rgba(15, 23, 42, .05);

        overflow: hidden;
    }


    /* ============================================================
       HEADER
    ============================================================ */

    .inventory-nav-header {
        display: flex;
        align-items: center;
        justify-content: space-between;

        gap: 20px;

        padding: 17px 20px;

        border-bottom: 1px solid #edf0f4;

        background:
            linear-gradient(
                135deg,
                #f8fbff 0%,
                #ffffff 100%
            );
    }


    .inventory-nav-brand {
        display: flex;
        align-items: center;

        gap: 12px;
    }


    .inventory-nav-brand-icon {
        width: 42px;
        height: 42px;

        display: flex;
        align-items: center;
        justify-content: center;

        border-radius: 11px;

        background: #eef6ff;
        color: #2563eb;

        font-size: 22px;
        font-weight: 800;
    }


    .inventory-nav-brand div:last-child {
        display: flex;
        flex-direction: column;
    }


    .inventory-nav-brand strong {
        color: #172033;

        font-size: 15px;
        font-weight: 800;
    }


    .inventory-nav-brand span {
        margin-top: 3px;

        color: #7b8494;

        font-size: 11px;
    }


    /* ============================================================
       STATUS
    ============================================================ */

    .inventory-nav-status {
        display: inline-flex;
        align-items: center;

        gap: 7px;

        padding: 6px 10px;

        border-radius: 20px;

        background: #ecfdf3;
        color: #15803d;

        font-size: 11px;
        font-weight: 700;
    }


    .inventory-status-dot {
        width: 7px;
        height: 7px;

        border-radius: 50%;

        background: #22c55e;

        box-shadow:
            0 0 0 3px rgba(34, 197, 94, .12);
    }


    /* ============================================================
       MENU
    ============================================================ */

    .inventory-nav-menu {
        display: flex;
        align-items: stretch;

        gap: 4px;

        padding: 9px;

        overflow-x: auto;

        scrollbar-width: thin;

        background: #ffffff;
    }


    .inventory-nav-item {
        position: relative;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        gap: 8px;

        min-height: 44px;

        padding: 0 13px;

        border-radius: 9px;

        color: #626d7e;

        text-decoration: none;

        white-space: nowrap;

        font-size: 12px;
        font-weight: 700;

        transition:
            all .18s ease;
    }


    .inventory-nav-item:hover {
        color: #2563eb;

        background: #f4f7fb;
    }


    .inventory-nav-item.is-active {
        color: #2563eb;

        background: #eaf2ff;
    }


    .inventory-nav-item.is-active::after {
        content: "";

        position: absolute;

        right: 12px;
        left: 12px;

        bottom: 3px;

        height: 2px;

        border-radius: 10px;

        background: #2563eb;
    }


    /* ============================================================
       ICON
    ============================================================ */

    .inventory-nav-icon {
        width: 25px;
        height: 25px;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        border-radius: 7px;

        background: #f3f5f8;

        color: #697386;

        font-size: 14px;

        transition:
            all .18s ease;
    }


    .inventory-nav-item:hover .inventory-nav-icon,
    .inventory-nav-item.is-active .inventory-nav-icon {
        background: #ffffff;
        color: #2563eb;
    }


    /* ============================================================
       FOOTER ACTIONS
    ============================================================ */

    .inventory-nav-footer {
        display: flex;

        justify-content: flex-start;

        gap: 8px;

        padding: 10px 14px;

        border-top: 1px solid #edf0f4;

        background: #fafbfc;
    }


    .inventory-nav-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        gap: 7px;

        min-height: 36px;

        padding: 0 13px;

        border-radius: 8px;

        text-decoration: none;

        font-size: 11px;
        font-weight: 700;

        transition: .18s ease;
    }


    .inventory-nav-action-primary {
        background: #2563eb;
        color: #ffffff;
    }


    .inventory-nav-action-primary:hover {
        background: #1d4ed8;
        color: #ffffff;
    }


    .inventory-nav-action-secondary {
        background: #ffffff;

        color: #596579;

        border: 1px solid #dfe4eb;
    }


    .inventory-nav-action-secondary:hover {
        color: #2563eb;

        border-color: #b9c9e6;

        background: #f8fbff;
    }


    /* ============================================================
       RESPONSIVE
    ============================================================ */

    @media (max-width: 900px) {

        .inventory-nav-header {
            align-items: flex-start;
        }

        .inventory-nav-menu {
            justify-content: flex-start;
        }

    }


    @media (max-width: 600px) {

        .inventory-nav-header {
            padding: 14px;
        }

        .inventory-nav-status {
            display: none;
        }

        .inventory-nav-brand-icon {
            width: 38px;
            height: 38px;
        }

        .inventory-nav-footer {
            flex-direction: column;
        }

        .inventory-nav-action {
            width: 100%;
        }

    }

</style>

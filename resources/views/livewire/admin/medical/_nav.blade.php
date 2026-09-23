{{-- ============================================================
     MEDICAL PORTAL NAVIGATION
============================================================ --}}

<div class="medical-nav">

    <div class="medical-nav-header">

        <div class="medical-nav-brand">
            <div class="medical-nav-brand-icon">
                +
            </div>

            <div>
                <strong>{{ __('medical.title') }}</strong>

                <span>
                    {{ __('medical.description') }}
                </span>
            </div>
        </div>

        <div class="medical-nav-status">
            <span class="medical-status-dot"></span>
            {{ __('medical.active') }}
        </div>

    </div>


    <div class="medical-nav-menu">

        {{-- Dashboard --}}
        <a
            href="{{ route('admin.medical.index') }}"
            wire:navigate
            class="medical-nav-item {{ request()->routeIs('admin.medical.index') ? 'is-active' : '' }}"
        >
            <span class="medical-nav-icon">
                ⌂
            </span>

            <span class="medical-nav-label">
                {{ __('medical.dashboard') }}
            </span>
        </a>


        {{-- Clinics --}}
        <a
            href="{{ route('admin.medical.clinics.index') }}"
            wire:navigate
            class="medical-nav-item {{ request()->routeIs('admin.medical.clinics.*') ? 'is-active' : '' }}"
        >
            <span class="medical-nav-icon">
                ⊞
            </span>

            <span class="medical-nav-label">
                {{ __('medical.clinics') }}
            </span>
        </a>


        {{-- Medical Staff --}}
        <a
            href="{{ route('admin.medical.staff.index') }}"
            wire:navigate
            class="medical-nav-item {{ request()->routeIs('admin.medical.staff.*') ? 'is-active' : '' }}"
        >
            <span class="medical-nav-icon">
                ◉
            </span>

            <span class="medical-nav-label">
                {{ __('medical.staff') }}
            </span>
        </a>


        {{-- Patients --}}
        <a
            href="{{ route('admin.medical.patients.index') }}"
            wire:navigate
            class="medical-nav-item {{ request()->routeIs('admin.medical.patients.*') ? 'is-active' : '' }}"
        >
            <span class="medical-nav-icon">
                ♙
            </span>

            <span class="medical-nav-label">
                {{ __('medical.patients') }}
            </span>
        </a>


        {{-- Visits --}}
        <a
            href="{{ route('admin.medical.visits.index') }}"
            wire:navigate
            class="medical-nav-item {{ request()->routeIs('admin.medical.visits.*') ? 'is-active' : '' }}"
        >
            <span class="medical-nav-icon">
                ◷
            </span>

            <span class="medical-nav-label">
                {{ __('medical.visits') }}
            </span>
        </a>


        {{-- Medicines --}}
        <a
            href="{{ route('admin.medical.medicines.index') }}"
            wire:navigate
            class="medical-nav-item {{ request()->routeIs('admin.medical.medicines.*') ? 'is-active' : '' }}"
        >
            <span class="medical-nav-icon">
                ▣
            </span>

            <span class="medical-nav-label">
                {{ __('medical.medicines') }}
            </span>
        </a>


        {{-- Medicine Batches --}}
        <a
            href="{{ route('admin.medical.batches.index') }}"
            wire:navigate
            class="medical-nav-item {{ request()->routeIs('admin.medical.batches.*') ? 'is-active' : '' }}"
        >
            <span class="medical-nav-icon">
                ▤
            </span>

            <span class="medical-nav-label">
                {{ __('medical.batches') }}
            </span>
        </a>


        {{-- Movements --}}
        <a
            href="{{ route('admin.medical.movements.index') }}"
            wire:navigate
            class="medical-nav-item {{ request()->routeIs('admin.medical.movements.*') ? 'is-active' : '' }}"
        >
            <span class="medical-nav-icon">
                ⇄
            </span>

            <span class="medical-nav-label">
                {{ __('medical.movements') }}
            </span>
        </a>


        {{-- Prescriptions --}}
        <a
            href="{{ route('admin.medical.prescriptions.index') }}"
            wire:navigate
            class="medical-nav-item {{ request()->routeIs('admin.medical.prescriptions.*') ? 'is-active' : '' }}"
        >
            <span class="medical-nav-icon">
                ▤
            </span>

            <span class="medical-nav-label">
                {{ __('medical.prescriptions') }}
            </span>
        </a>


        {{-- Reports --}}
        <a
            href="{{ route('admin.medical.reports.index') }}"
            wire:navigate
            class="medical-nav-item {{ request()->routeIs('admin.medical.reports.*') ? 'is-active' : '' }}"
        >
            <span class="medical-nav-icon">
                ◫
            </span>

            <span class="medical-nav-label">
                {{ __('medical.reports') }}
            </span>
        </a>

    </div>


    <div class="medical-nav-footer">

        <a
            href="{{ route('admin.medical.receipts.create') }}"
            wire:navigate
            class="medical-nav-action medical-nav-action-primary"
        >
            <span>＋</span>
            {{ __('medical.new_receipt') }}
        </a>

        <a
            href="{{ route('admin.medical.issues.create') }}"
            wire:navigate
            class="medical-nav-action medical-nav-action-secondary"
        >
            <span>↓</span>
            {{ __('medical.new_issue') }}
        </a>

    </div>

</div>


<style>
    .medical-nav {
        direction: rtl;
        width: 100%;
        margin-bottom: 28px;
        background: #ffffff;
        border: 1px solid #e5e9f0;
        border-radius: 16px;
        box-shadow: 0 5px 20px rgba(15, 23, 42, .05);
        overflow: hidden;
    }

    /* ============================================================
       HEADER
    ============================================================ */

    .medical-nav-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 17px 20px;
        border-bottom: 1px solid #edf0f4;
        background: linear-gradient(
            135deg,
            #f8fbff 0%,
            #ffffff 100%
        );
    }

    .medical-nav-brand {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .medical-nav-brand-icon {
        width: 42px;
        height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 11px;
        background: #eaf2ff;
        color: #2563eb;
        font-size: 24px;
        font-weight: 800;
    }

    .medical-nav-brand div:last-child {
        display: flex;
        flex-direction: column;
    }

    .medical-nav-brand strong {
        color: #172033;
        font-size: 15px;
        font-weight: 800;
    }

    .medical-nav-brand span {
        margin-top: 3px;
        color: #7b8494;
        font-size: 11px;
    }

    .medical-nav-status {
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

    .medical-status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #22c55e;
        box-shadow: 0 0 0 3px rgba(34, 197, 94, .12);
    }

    /* ============================================================
       MENU
    ============================================================ */

    .medical-nav-menu {
        display: flex;
        align-items: stretch;
        gap: 4px;
        padding: 9px;
        overflow-x: auto;
        scrollbar-width: thin;
        background: #fff;
    }

    .medical-nav-item {
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
        transition: all .18s ease;
    }

    .medical-nav-item:hover {
        color: #2563eb;
        background: #f4f7fb;
    }

    .medical-nav-item.is-active {
        color: #2563eb;
        background: #eaf2ff;
    }

    .medical-nav-item.is-active::after {
        content: "";
        position: absolute;
        right: 12px;
        left: 12px;
        bottom: 3px;
        height: 2px;
        border-radius: 10px;
        background: #2563eb;
    }

    .medical-nav-icon {
        width: 25px;
        height: 25px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 7px;
        background: #f3f5f8;
        color: #697386;
        font-size: 14px;
        transition: all .18s ease;
    }

    .medical-nav-item:hover .medical-nav-icon,
    .medical-nav-item.is-active .medical-nav-icon {
        background: #ffffff;
        color: #2563eb;
    }

    /* ============================================================
       FOOTER ACTIONS
    ============================================================ */

    .medical-nav-footer {
        display: flex;
        justify-content: flex-start;
        gap: 8px;
        padding: 10px 14px;
        border-top: 1px solid #edf0f4;
        background: #fafbfc;
    }

    .medical-nav-action {
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

    .medical-nav-action-primary {
        background: #2563eb;
        color: #fff;
    }

    .medical-nav-action-primary:hover {
        background: #1d4ed8;
        color: #fff;
    }

    .medical-nav-action-secondary {
        background: #fff;
        color: #596579;
        border: 1px solid #dfe4eb;
    }

    .medical-nav-action-secondary:hover {
        color: #2563eb;
        border-color: #b9c9e6;
        background: #f8fbff;
    }

    /* ============================================================
       RESPONSIVE
    ============================================================ */

    @media (max-width: 900px) {
        .medical-nav-header {
            align-items: flex-start;
        }

        .medical-nav-menu {
            justify-content: flex-start;
        }
    }

    @media (max-width: 600px) {
        .medical-nav-header {
            padding: 14px;
        }

        .medical-nav-status {
            display: none;
        }

        .medical-nav-brand-icon {
            width: 38px;
            height: 38px;
        }

        .medical-nav-footer {
            flex-direction: column;
        }

        .medical-nav-action {
            width: 100%;
        }
    }
</style>

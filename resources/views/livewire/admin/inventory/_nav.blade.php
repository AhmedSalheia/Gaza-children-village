<div class="inventory-nav">

    <a
        href="{{ route('admin.inventory.index') }}"
        wire:navigate
    >
        {{ __('ui.inventory.title') }}
    </a>

    <a
        href="{{ route('admin.inventory.items.index') }}"
        wire:navigate
    >
        {{ __('ui.inventory.items') }}
    </a>

    <a
        href="{{ route('admin.inventory.warehouses.index') }}"
        wire:navigate
    >
        {{ __('ui.inventory.warehouses') }}
    </a>

    <a
        href="{{ route('admin.inventory.stock.index') }}"
        wire:navigate
    >
        {{ __('ui.inventory.stock') }}
    </a>

    <a
        href="{{ route('admin.inventory.movements.index') }}"
        wire:navigate
    >
        {{ __('ui.inventory.movements') }}
    </a>

    <a
        href="{{ route('admin.inventory.requests.index') }}"
        wire:navigate
    >
        {{ __('ui.inventory.requests') }}
    </a>

    <a
        href="{{ route('admin.inventory.approvals.index') }}"
        wire:navigate
    >
        {{ __('ui.inventory.approvals') }}
    </a>

    <a
        href="{{ route('admin.inventory.reallocation.index') }}"
        wire:navigate
    >
        {{ __('ui.inventory.reallocation') }}
    </a>

    <a
        href="{{ route('admin.inventory.counts.index') }}"
        wire:navigate
    >
        {{ __('ui.inventory.counts') }}
    </a>

    <a
        href="{{ route('admin.inventory.reports.index') }}"
        wire:navigate
    >
        {{ __('ui.inventory.reports') }}
    </a>

</div>

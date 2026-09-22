<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Inventory\Movements;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use App\Models\Inventory\InventoryMovement;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class MovementIndex extends Component
{
    use HasAdminAuth;
    use WithPagination;

    #[Url]
    public string $q = '';

    #[Url]
    public string $type = 'all';

    public function mount(): void
    {
        $this->requirePermission('inventory.view');
    }

    public function render(): View
    {
        $movements = InventoryMovement::with([
            'item',
            'warehouse',
            'counterparty',
        ])
            ->when(
                $this->q !== '',
                fn ($query) => $query->where(
                    'movement_number',
                    'like',
                    '%' . $this->q . '%'
                )
            )
            ->when(
                $this->type !== 'all',
                fn ($query) => $query->where(
                    'type',
                    $this->type
                )
            )
            ->latest()
            ->paginate(30);

        return view(
            'livewire.admin.inventory.movements.movement-index',
            [
                'movements' => $movements,
            ]
        )->layout('layouts.admin');
    }
}

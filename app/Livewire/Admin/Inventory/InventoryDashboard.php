<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Inventory;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use App\Models\Inventory\{
    InventoryItem,
    InventoryStock,
    InventoryWarehouse,
    InventoryRequest
};
use Illuminate\View\View;
use Livewire\Component;

final class InventoryDashboard extends Component
{
    use HasAdminAuth;

    public function mount(): void
    {
        $this->requirePermission('inventory.view');
    }

    public function render(): View
    {
        return view('livewire.admin.inventory.inventory-dashboard', [
            'items' => InventoryItem::where('is_active', 1)->count(),

            'warehouses' => InventoryWarehouse::where(
                'status',
                'active'
            )->count(),

            'lowStock' => InventoryStock::with('item')
                ->get()
                ->filter(
                    fn ($stock): bool =>
                        (float) $stock->quantity
                        - (float) $stock->reserved_quantity
                        <= (float) $stock->item->reorder_level
                )
                ->count(),

            'pending' => InventoryRequest::whereIn(
                'status',
                [
                    'pending_approval',
                    'approved_for_issue',
                ]
            )->count(),
        ])->layout('layouts.admin');
    }
}

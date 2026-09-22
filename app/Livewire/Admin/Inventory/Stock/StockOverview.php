<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Inventory\Stock;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use App\Models\Inventory\InventoryStock;
use App\Models\Inventory\InventoryWarehouse;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class StockOverview extends Component
{
    use HasAdminAuth;
    use WithPagination;

    #[Url]
    public string $q = '';

    public $warehouseId = '';

    public function mount(): void
    {
        $this->requirePermission('inventory.view');
    }

    public function render(): View
    {
        $stocks = InventoryStock::with([
            'item',
            'warehouse',
        ])
            ->when(
                $this->warehouseId,
                fn ($query) => $query->where(
                    'warehouse_id',
                    $this->warehouseId
                )
            )
            ->whereHas(
                'item',
                fn ($query) => $query
                    ->where(
                        'sku',
                        'like',
                        '%' . $this->q . '%'
                    )
                    ->orWhere(
                        'name_ar',
                        'like',
                        '%' . $this->q . '%'
                    )
            )
            ->paginate(30);

        $warehouses = InventoryWarehouse::where(
            'status',
            'active'
        )
            ->orderBy('name_ar')
            ->get();

        return view(
            'livewire.admin.inventory.stock.stock-overview',
            [
                'stocks' => $stocks,
                'warehouses' => $warehouses,
            ]
        )->layout('layouts.admin');
    }
}

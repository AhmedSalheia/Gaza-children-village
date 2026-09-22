<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Inventory\Movements;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryWarehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\View\View;
use Livewire\Component;

final class MovementEntry extends Component
{
    use HasAdminAuth;

    public string $mode = 'receipt';

    public $warehouseId;
    public $toWarehouseId;
    public $itemId;
    public $quantity;
    public $unitCost = 0;
    public $reason_ar;

    public function mount(): void
    {
        $this->requirePermission('inventory.receipt.create');
    }

    public function save(): void
    {
        $data = $this->validate([
            'warehouseId' => [
                'required',
                'exists:inventory_warehouses,id',
            ],

            'toWarehouseId' => [
                'nullable',
                'exists:inventory_warehouses,id',
            ],

            'itemId' => [
                'required',
                'exists:inventory_items,id',
            ],

            'quantity' => [
                'required',
                'numeric',
                'min:0.001',
            ],

            'unitCost' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'reason_ar' => [
                'nullable',
            ],
        ]);

        if ($this->mode === 'receipt') {
            app(InventoryService::class)->receive(
                $data['warehouseId'],
                $data['itemId'],
                $data['quantity'],
                $data['unitCost'],
                $data['reason_ar']
            );
        } else {
            abort_unless(
                $this->adminCan('inventory.transfer.create'),
                403
            );

            app(InventoryService::class)->transfer(
                $data['warehouseId'],
                $data['toWarehouseId'],
                $data['itemId'],
                $data['quantity'],
                $data['reason_ar']
            );
        }

        session()->flash(
            'success',
            __('ui.inventory.saved')
        );

        $this->reset([
            'itemId',
            'quantity',
            'unitCost',
            'reason_ar',
        ]);
    }

    public function render(): View
    {
        return view(
            'livewire.admin.inventory.movements.movement-entry',
            [
                'items' => InventoryItem::where(
                    'is_active',
                    1
                )
                    ->orderBy('name_ar')
                    ->get(),

                'warehouses' => InventoryWarehouse::where(
                    'status',
                    'active'
                )
                    ->orderBy('name_ar')
                    ->get(),
            ]
        )->layout('layouts.admin');
    }
}

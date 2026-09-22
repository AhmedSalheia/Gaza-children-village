<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Inventory\Reallocation;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use App\Models\Inventory\InventoryReallocationSuggestion;
use App\Models\Inventory\InventoryWarehouse;
use App\Services\Inventory\InventoryService;
use App\Services\Inventory\ReallocationService;
use Illuminate\View\View;
use Livewire\Component;

final class ReallocationMap extends Component
{
    use HasAdminAuth;

    public function mount(): void
    {
        $this->requirePermission(
            'inventory.reallocation.view'
        );
    }

    public function generate(): void
    {
        $this->requirePermission(
            'inventory.reallocation.approve'
        );

        app(ReallocationService::class)->generate();
    }

    public function execute(int $id): void
    {
        abort_unless(
            $this->adminCan(
                'inventory.reallocation.approve'
            ),
            403
        );

        $suggestion =
            InventoryReallocationSuggestion::findOrFail($id);

        app(InventoryService::class)->transfer(
            $suggestion->from_warehouse_id,
            $suggestion->to_warehouse_id,
            $suggestion->item_id,
            $suggestion->suggested_quantity,
            $suggestion->reason_ar
        );

        $suggestion->update([
            'status' => 'executed',
        ]);
    }

    public function render(): View
    {
        return view(
            'livewire.admin.inventory.reallocation.reallocation-map',
            [
                'suggestions' =>
                    InventoryReallocationSuggestion::with([
                        'item',
                        'fromWarehouse',
                        'toWarehouse',
                    ])
                        ->where('status', 'proposed')
                        ->orderByDesc('priority_score')
                        ->get(),

                'warehouses' =>
                    InventoryWarehouse::where(
                        'status',
                        'active'
                    )
                        ->orderBy('name_ar')
                        ->get(),
            ]
        )->layout('layouts.admin');
    }
}

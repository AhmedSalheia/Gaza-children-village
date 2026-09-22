<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Inventory\Requests;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryRequest;
use App\Models\Inventory\InventoryWarehouse;
use App\Services\Inventory\InventoryApprovalService;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Organization\Models\Institution;

final class RequestIndex extends Component
{
    use HasAdminAuth;
    use WithPagination;

    public $institutionId;
    public $warehouseId;
    public $itemId;
    public $quantity;
    public $unitCost = 0;
    public $priority = 'normal';
    public $reason_ar = '';

    public function mount(): void
    {
        $this->requirePermission('inventory.issue.create');
    }

    public function create(): void
    {
        $data = $this->validate([
            'institutionId' => [
                'required',
                'exists:institutions,id',
            ],

            'warehouseId' => [
                'required',
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

            'priority' => [
                'required',
            ],

            'reason_ar' => [
                'nullable',
            ],
        ]);

        $request = InventoryRequest::create([
            'request_number' => (string) Str::uuid(),

            'requesting_institution_id' => $data['institutionId'],

            'source_warehouse_id' => $data['warehouseId'],

            'type' => 'issue',

            'status' => 'draft',

            'estimated_value' =>
                (float) $data['quantity']
                * (float) ($data['unitCost'] ?? 0),

            'priority' => $data['priority'],

            'reason_ar' => $data['reason_ar'],

            'created_by_account_id' =>
                session('administrative_account_id')
                ?? auth()->id(),
        ]);

        $request->lines()->create([
            'item_id' => $data['itemId'],
            'requested_quantity' => $data['quantity'],
            'unit_cost' => $data['unitCost'] ?? 0,
        ]);

        app(InventoryApprovalService::class)
            ->createApprovalChain($request);

        session()->flash(
            'success',
            __('ui.inventory.request_created')
        );

        $this->redirect(
            route('admin.inventory.requests.show', $request),
            navigate: true
        );
    }

    public function render(): View
    {
        return view(
            'livewire.admin.inventory.requests.request-index',
            [
                'requests' => InventoryRequest::with('institution')
                    ->latest()
                    ->paginate(20),

                'institutions' => Institution::where('is_active', 1)
                    ->orderBy('name_ar')
                    ->get(),

                'warehouses' => InventoryWarehouse::where(
                    'status',
                    'active'
                )
                    ->orderBy('name_ar')
                    ->get(),

                'items' => InventoryItem::where('is_active', 1)
                    ->orderBy('name_ar')
                    ->get(),
            ]
        )->layout('layouts.admin');
    }
}

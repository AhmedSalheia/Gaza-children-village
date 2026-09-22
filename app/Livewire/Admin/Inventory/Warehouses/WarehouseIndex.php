<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Inventory\Warehouses;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use App\Models\Inventory\InventoryWarehouse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Organization\Models\Institution;

final class WarehouseIndex extends Component
{
    use HasAdminAuth;

    public bool $show = false;

    public ?int $id = null;

    public $institution_id;
    public $code;
    public $name_ar;
    public $name_en;
    public $warehouse_type = 'central';
    public $status = 'active';
    public $address_ar;
    public $address_en;
    public $latitude;
    public $longitude;

    public function mount(): void
    {
        $this->requirePermission('inventory.warehouse.view');
    }

    public function open(?int $id = null): void
    {
        $this->requirePermission('inventory.warehouse.manage');

        $this->resetErrorBag();

        $this->reset([
            'id',
            'institution_id',
            'code',
            'name_ar',
            'name_en',
            'address_ar',
            'address_en',
            'latitude',
            'longitude',
        ]);

        $this->warehouse_type = 'central';
        $this->status = 'active';
        $this->show = true;

        if ($id !== null) {
            $warehouse = InventoryWarehouse::findOrFail($id);

            $this->id = $warehouse->id;

            $this->fill(
                $warehouse->only([
                    'institution_id',
                    'code',
                    'name_ar',
                    'name_en',
                    'warehouse_type',
                    'status',
                    'address_ar',
                    'address_en',
                    'latitude',
                    'longitude',
                ])
            );
        }
    }

    public function save(): void
    {
        abort_unless(
            $this->adminCan('inventory.warehouse.manage'),
            403
        );

        $data = $this->validate([
            'institution_id' => [
                'required',
                'exists:institutions,id',
            ],

            'code' => [
                'required',
                'max:80',
                Rule::unique(
                    'inventory_warehouses',
                    'code'
                )->ignore($this->id),
            ],

            'name_ar' => [
                'required',
                'max:255',
            ],

            'name_en' => [
                'required',
                'max:255',
            ],

            'warehouse_type' => [
                'required',
            ],

            'status' => [
                'required',
            ],

            'address_ar' => [
                'nullable',
            ],

            'address_en' => [
                'nullable',
            ],

            'latitude' => [
                'nullable',
                'numeric',
            ],

            'longitude' => [
                'nullable',
                'numeric',
            ],
        ]);

        if ($this->id !== null) {
            $warehouse = InventoryWarehouse::findOrFail($this->id);

            $warehouse->update($data);
        } else {
            InventoryWarehouse::create($data);
        }

        $this->show = false;

        session()->flash(
            'success',
            __('ui.inventory.saved')
        );
    }

    public function render(): View
    {
        return view(
            'livewire.admin.inventory.warehouses.warehouse-index',
            [
                'warehouses' => InventoryWarehouse::with('institution')
                    ->latest()
                    ->get(),

                'institutions' => Institution::where(
                    'is_active',
                    1
                )
                    ->orderBy('name_ar')
                    ->get(),
            ]
        )->layout('layouts.admin');
    }
}

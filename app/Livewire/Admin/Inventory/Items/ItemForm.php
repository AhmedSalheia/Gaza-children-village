<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Inventory\Items;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use App\Models\Inventory\InventoryCategory;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryUnit;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

final class ItemForm extends Component
{
    use HasAdminAuth;

    public ?int $itemId = null;

    public $category_id;
    public $base_unit_id;
    public $sku;
    public $name_ar;
    public $name_en;
    public $description_ar;
    public $description_en;
    public $barcode;

    public $reorder_level = 0;
    public $minimum_stock = 0;
    public $maximum_stock;
    public $safety_stock = 0;

    public $is_transferable = true;
    public $is_active = true;

    public function mount(?InventoryItem $item = null): void
    {
        $this->requirePermission('inventory.item.manage');

        if ($item?->exists) {
            $this->itemId = $item->id;

            $this->fill(
                $item->only([
                    'category_id',
                    'base_unit_id',
                    'sku',
                    'name_ar',
                    'name_en',
                    'description_ar',
                    'description_en',
                    'barcode',
                    'reorder_level',
                    'minimum_stock',
                    'maximum_stock',
                    'safety_stock',
                    'is_transferable',
                    'is_active',
                ])
            );
        }
    }

    protected function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'exists:inventory_categories,id',
            ],

            'base_unit_id' => [
                'required',
                'exists:inventory_units,id',
            ],

            'sku' => [
                'required',
                'max:80',
                Rule::unique(
                    'inventory_items',
                    'sku'
                )->ignore($this->itemId),
            ],

            'name_ar' => [
                'required',
                'max:255',
            ],

            'name_en' => [
                'required',
                'max:255',
            ],

            'description_ar' => [
                'nullable',
            ],

            'description_en' => [
                'nullable',
            ],

            'barcode' => [
                'nullable',
                'max:120',
                Rule::unique(
                    'inventory_items',
                    'barcode'
                )->ignore($this->itemId),
            ],

            'reorder_level' => [
                'numeric',
                'min:0',
            ],

            'minimum_stock' => [
                'numeric',
                'min:0',
            ],

            'maximum_stock' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'safety_stock' => [
                'numeric',
                'min:0',
            ],

            'is_transferable' => [
                'boolean',
            ],

            'is_active' => [
                'boolean',
            ],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        if (! $this->itemId) {
            $data['qr_token'] = Str::uuid()->toString();

            InventoryItem::create($data);
        } else {
            $item = InventoryItem::findOrFail($this->itemId);

            $item->update($data);
        }

        session()->flash(
            'success',
            __('ui.inventory.saved')
        );

        $this->redirect(
            route('admin.inventory.items.index'),
            navigate: true
        );
    }

    public function render(): View
    {
        return view(
            'livewire.admin.inventory.items.item-form',
            [
                'categories' => InventoryCategory::where(
                    'is_active',
                    1
                )
                    ->orderBy('name_ar')
                    ->get(),

                'units' => InventoryUnit::where(
                    'is_active',
                    1
                )
                    ->orderBy('name_ar')
                    ->get(),
            ]
        )->layout('layouts.admin');
    }
}

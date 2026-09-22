<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Inventory\Items;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use App\Models\Inventory\InventoryItem;
use Illuminate\View\View;
use Livewire\Component;

final class ItemLabels extends Component
{
    use HasAdminAuth;

    public function mount(): void
    {
        $this->requirePermission('inventory.item.view');
    }

    public function render(): View
    {
        return view(
            'livewire.admin.inventory.items.item-labels',
            [
                'items' => InventoryItem::where(
                    'is_active',
                    1
                )
                    ->orderBy('name_ar')
                    ->get(),
            ]
        )->layout('layouts.admin');
    }
}

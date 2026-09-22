<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Inventory\Items;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use App\Models\Inventory\InventoryItem;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class ItemIndex extends Component
{
    use HasAdminAuth;
    use WithPagination;

    #[Url(as: 'q')]
    public string $q = '';

    public string $status = 'all';

    public function mount(): void
    {
        $this->requirePermission('inventory.item.view');
    }

    public function toggle(int $id): void
    {
        abort_unless(
            $this->adminCan('inventory.item.manage'),
            403
        );

        $item = InventoryItem::findOrFail($id);

        $item->update([
            'is_active' => ! $item->is_active,
        ]);
    }

    public function render(): View
    {
        $items = InventoryItem::with([
            'category',
            'unit',
        ])
            ->when(
                $this->q !== '',
                fn ($query) => $query->where(
                    fn ($where) => $where
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
                        ->orWhere(
                            'name_en',
                            'like',
                            '%' . $this->q . '%'
                        )
                )
            )
            ->when(
                $this->status !== 'all',
                fn ($query) => $query->where(
                    'is_active',
                    $this->status === 'active'
                )
            )
            ->latest()
            ->paginate(20);

        return view(
            'livewire.admin.inventory.items.item-index',
            compact('items')
        )->layout('layouts.admin');
    }
}

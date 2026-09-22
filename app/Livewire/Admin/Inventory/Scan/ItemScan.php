<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Inventory\Scan;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use App\Models\Inventory\InventoryItem;
use Illuminate\View\View;
use Livewire\Component;

final class ItemScan extends Component
{
    use HasAdminAuth;

    public string $token = '';

    public ?InventoryItem $item = null;

    public function mount(): void
    {
        $this->requirePermission('inventory.item.view');
    }

    public function lookup(): void
    {
        $this->item = InventoryItem::query()
            ->where('qr_token', $this->token)
            ->orWhere('barcode', $this->token)
            ->orWhere('sku', $this->token)
            ->first();
    }

    public function render(): View
    {
        return view(
            'livewire.admin.inventory.scan.item-scan'
        )->layout('layouts.admin');
    }
}

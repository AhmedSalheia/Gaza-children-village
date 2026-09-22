<?php
declare(strict_types=1);
namespace App\Support\Inventory;
trait InventoryGate
{
    protected function inventoryGate(string $permission): void
    {
        abort_unless($this->can($permission), 403);
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Inventory\Counts;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use App\Models\Inventory\{
    InventoryCount as CountModel,
    InventoryWarehouse,
    InventoryStock
};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class InventoryCount extends Component
{
    use HasAdminAuth;
    use WithPagination;

    public $warehouseId;
    public $countId;

    public array $lines = [];

    public function mount(): void
    {
        $this->requirePermission('inventory.count.view');
    }

    public function start(): void
    {
        $this->requirePermission('inventory.count.perform');

        $this->validate([
            'warehouseId' => 'required|exists:inventory_warehouses,id',
        ]);

        $c = CountModel::create([
            'count_number' => (string) Str::uuid(),
            'warehouse_id' => $this->warehouseId,
            'status' => 'open',
            'started_by_account_id' =>
                session('administrative_account_id') ?? auth()->id(),
            'started_at' => now(),
        ]);

        foreach (
            InventoryStock::where('warehouse_id', $this->warehouseId)->get()
            as $stock
        ) {
            $c->lines()->create([
                'item_id' => $stock->item_id,
                'system_quantity' => $stock->quantity,
            ]);
        }

        $this->countId = $c->id;

        $this->load();
    }

    public function load(): void
    {
        $c = CountModel::with('lines.item')->findOrFail($this->countId);

        $this->lines = $c->lines
            ->map(fn ($line) => [
                'id' => $line->id,
                'item' => $line->item?->name_ar ?? '',
                'system' => (float) $line->system_quantity,
                'counted' => (float) (
                    $line->counted_quantity
                    ?? $line->system_quantity
                ),
            ])
            ->toArray();
    }

    public function save(): void
    {
        abort_unless(
            $this->adminCan('inventory.count.perform'),
            403
        );

        foreach ($this->lines as $line) {
            DB::table('inventory_count_lines')
                ->where('id', $line['id'])
                ->update([
                    'counted_quantity' => $line['counted'],
                    'counted_by_account_id' =>
                        session('administrative_account_id') ?? auth()->id(),
                    'counted_at' => now(),
                ]);
        }

        $this->load();
    }

    public function close(): void
    {
        abort_unless(
            $this->adminCan('inventory.count.perform'),
            403
        );

        $c = CountModel::with('lines')->findOrFail($this->countId);

        DB::transaction(function () use ($c): void {
            foreach ($c->lines as $line) {
                $counted = (float) (
                    $line->counted_quantity
                    ?? $line->system_quantity
                );

                $delta = $counted - (float) $line->system_quantity;

                if ($delta != 0) {
                    $stock = InventoryStock::firstOrCreate(
                        [
                            'warehouse_id' => $c->warehouse_id,
                            'item_id' => $line->item_id,
                        ],
                        [
                            'quantity' => 0,
                            'reserved_quantity' => 0,
                            'average_unit_cost' => 0,
                        ]
                    );

                    $stock->quantity =
                        (float) $stock->quantity + $delta;

                    $stock->save();
                }
            }

            $c->update([
                'status' => 'closed',
                'closed_by_account_id' =>
                    session('administrative_account_id') ?? auth()->id(),
                'closed_at' => now(),
            ]);
        });

        $this->load();
    }

    public function render(): View
    {
        return view(
            'livewire.admin.inventory.counts.inventory-count',
            [
                'warehouses' => InventoryWarehouse::where(
                    'status',
                    'active'
                )->get(),

                'counts' => CountModel::with('warehouse')
                    ->latest()
                    ->paginate(10),
            ]
        )->layout('layouts.admin');
    }
}

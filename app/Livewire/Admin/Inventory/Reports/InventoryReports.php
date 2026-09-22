<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Inventory\Reports;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

final class InventoryReports extends Component
{
    use HasAdminAuth;

    public function mount(): void
    {
        $this->requirePermission('inventory.report.view');
    }

    public function export()
    {
        abort_unless(
            $this->adminCan('inventory.export'),
            403
        );

        $rows = DB::table('inventory_stock as s')
            ->join(
                'inventory_items as i',
                'i.id',
                '=',
                's.item_id'
            )
            ->join(
                'inventory_warehouses as w',
                'w.id',
                '=',
                's.warehouse_id'
            )
            ->select(
                'i.sku',
                'i.name_ar',
                'i.name_en',
                'w.code',
                'w.name_ar as warehouse_ar',
                's.quantity',
                's.reserved_quantity',
                's.average_unit_cost'
            )
            ->get();

        return response()->streamDownload(
            function () use ($rows): void {
                echo "SKU\tArabic Name\tEnglish Name\tWarehouse\tWarehouse Arabic\tQuantity\tReserved\tAverage Cost\n";

                foreach ($rows as $row) {
                    echo implode("\t", [
                        $row->sku,
                        $row->name_ar,
                        $row->name_en,
                        $row->code,
                        $row->warehouse_ar,
                        $row->quantity,
                        $row->reserved_quantity,
                        $row->average_unit_cost,
                    ]) . "\n";
                }
            },
            'gcv-inventory-report.xls',
            [
                'Content-Type' => 'application/vnd.ms-excel',
            ]
        );
    }

 public function render(): View
{
    $summary = DB::table('inventory_stock')
        ->selectRaw('
            COUNT(*) AS `total_lines`,
            COALESCE(SUM(quantity), 0) AS `total_quantity`,
            COALESCE(
                SUM(
                    (quantity - reserved_quantity)
                    * average_unit_cost
                ),
                0
            ) AS `total_value`
        ')
        ->first();

    $movementSummary = DB::table('inventory_movements')
        ->select('type')
        ->selectRaw('
            COUNT(*) AS `movement_count`,
            COALESCE(SUM(quantity), 0) AS `movement_quantity`
        ')
        ->groupBy('type')
        ->get();

    $low = DB::table('inventory_stock as s')
        ->join(
            'inventory_items as i',
            'i.id',
            '=',
            's.item_id'
        )
        ->whereRaw(
            '(s.quantity - s.reserved_quantity) <= i.reorder_level'
        )
        ->orderBy('s.quantity')
        ->limit(50)
        ->get();

    return view(
        'livewire.admin.inventory.reports.inventory-reports',
        [
            'summary' => $summary,
            'movementSummary' => $movementSummary,
            'low' => $low,
        ]
    )->layout('layouts.admin');
}
}

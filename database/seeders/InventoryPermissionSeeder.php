<?php
declare(strict_types=1);
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventoryPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $keys=[
            'inventory.view','inventory.item.view','inventory.item.manage',
            'inventory.warehouse.view','inventory.warehouse.manage',
            'inventory.receipt.create','inventory.issue.create','inventory.issue.approve',
            'inventory.transfer.create','inventory.transfer.approve',
            'inventory.count.view','inventory.count.perform',
            'inventory.reallocation.view','inventory.reallocation.approve',
            'inventory.report.view','inventory.export',
        ];
        foreach($keys as $key){
            DB::table('permissions')->updateOrInsert(
                ['key'=>$key],
                ['description'=>$key,'group'=>'inventory','is_system'=>1,'updated_at'=>now(),'created_at'=>now()]
            );
        }
    }
}

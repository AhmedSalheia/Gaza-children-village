<?php
declare(strict_types=1);
namespace App\Services\Inventory;

use App\Models\Inventory\{InventoryItem,InventoryStock,InventoryMovement,InventoryRequest};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryService
{
    public function receive(int $warehouseId, int $itemId, float $qty, float $unitCost = 0, ?string $reasonAr = null, ?string $reasonEn = null): InventoryMovement
    {
        return DB::transaction(function () use ($warehouseId,$itemId,$qty,$unitCost,$reasonAr,$reasonEn) {
            abort_if($qty <= 0, 422, __('ui.inventory.invalid_quantity'));
            $stock = InventoryStock::query()->firstOrCreate(
                ['warehouse_id'=>$warehouseId,'item_id'=>$itemId],
                ['quantity'=>0,'reserved_quantity'=>0,'average_unit_cost'=>0]
            );
            $oldQty = (float)$stock->quantity;
            $newQty = $oldQty + $qty;
            $oldCost = (float)$stock->average_unit_cost;
            $avg = $newQty > 0 ? (($oldQty*$oldCost)+($qty*$unitCost))/$newQty : $unitCost;
            $stock->update(['quantity'=>$newQty,'average_unit_cost'=>$avg]);
            return InventoryMovement::create([
                'movement_number'=>(string)Str::uuid(),
                'item_id'=>$itemId,'warehouse_id'=>$warehouseId,'type'=>'receipt',
                'status'=>'posted','quantity'=>$qty,'unit_cost'=>$unitCost,
                'reason_ar'=>$reasonAr,'reason_en'=>$reasonEn,
                'actor_type'=>'administrative','actor_account_id'=>auth()->id(),
                'posted_at'=>now(),
            ]);
        });
    }

    public function issue(int $warehouseId, int $itemId, float $qty, ?string $reasonAr=null, ?string $reasonEn=null, ?int $referenceId=null): InventoryMovement
    {
        return DB::transaction(function () use ($warehouseId,$itemId,$qty,$reasonAr,$reasonEn) {
            abort_if($qty <= 0, 422, __('ui.inventory.invalid_quantity'));
            $stock = InventoryStock::query()->where('warehouse_id',$warehouseId)->where('item_id',$itemId)->lockForUpdate()->first();
            abort_if(!$stock || $stock->available() < $qty, 422, __('ui.inventory.insufficient_stock'));
            $stock->quantity = (float)$stock->quantity - $qty;
            $stock->save();
            return InventoryMovement::create([
                'movement_number'=>(string)Str::uuid(),
                'item_id'=>$itemId,'warehouse_id'=>$warehouseId,'type'=>'issue',
                'status'=>'posted','quantity'=>$qty,'unit_cost'=>$stock->average_unit_cost,
                'reason_ar'=>$reasonAr,'reason_en'=>$reasonEn,
                'actor_type'=>'administrative','actor_account_id'=>session('administrative_account_id')??auth()->id(),
                'reference_type'=>$referenceId?InventoryRequest::class:null,'reference_id'=>$referenceId,
                'posted_at'=>now(),
            ]);
        });
    }

    public function transfer(int $from, int $to, int $itemId, float $qty, ?string $reasonAr=null): void
    {
        DB::transaction(function () use ($from,$to,$itemId,$qty) {
            $source = InventoryStock::query()->where('warehouse_id',$from)->where('item_id',$itemId)->lockForUpdate()->first();
            abort_if(!$source || $source->available() < $qty, 422, __('ui.inventory.insufficient_stock'));
            $target = InventoryStock::query()->firstOrCreate(
                ['warehouse_id'=>$to,'item_id'=>$itemId],
                ['quantity'=>0,'reserved_quantity'=>0,'average_unit_cost'=>$source->average_unit_cost]
            );
            $source->quantity = (float)$source->quantity - $qty; $source->save();
            $target->quantity = (float)$target->quantity + $qty; $target->save();
            foreach ([[$from,$to,'transfer_out'],[$to,$from,'transfer_in']] as [$warehouse,$counterparty,$type]) {
                InventoryMovement::create([
                    'movement_number'=>(string)Str::uuid(),'item_id'=>$itemId,'warehouse_id'=>$warehouse,
                    'counterparty_warehouse_id'=>$counterparty,'type'=>$type,'status'=>'posted',
                    'quantity'=>$qty,'unit_cost'=>$source->average_unit_cost,
                    'actor_type'=>'administrative','actor_account_id'=>session('administrative_account_id')??auth()->id(),'reason_ar'=>$reasonAr,'posted_at'=>now(),
                ]);
            }
        });
    }
}

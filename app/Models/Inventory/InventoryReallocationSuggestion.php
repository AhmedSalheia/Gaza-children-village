<?php
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
class InventoryReallocationSuggestion extends Model { protected $table='inventory_reallocation_suggestions'; protected $guarded=[]; protected $casts=['suggested_quantity'=>'decimal:3','from_surplus'=>'decimal:3','to_shortage'=>'decimal:3','distance_km'=>'decimal:2','priority_score'=>'decimal:4']; public function item(){return $this->belongsTo(InventoryItem::class,'item_id');} public function fromWarehouse(){return $this->belongsTo(InventoryWarehouse::class,'from_warehouse_id');} public function toWarehouse(){return $this->belongsTo(InventoryWarehouse::class,'to_warehouse_id');} }

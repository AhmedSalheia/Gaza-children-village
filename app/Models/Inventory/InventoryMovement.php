<?php
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
class InventoryMovement extends Model { protected $table='inventory_movements'; protected $guarded=[]; protected $casts=['quantity'=>'decimal:3','unit_cost'=>'decimal:4','posted_at'=>'datetime']; public function item(){return $this->belongsTo(InventoryItem::class,'item_id');} public function warehouse(){return $this->belongsTo(InventoryWarehouse::class,'warehouse_id');} public function counterparty(){return $this->belongsTo(InventoryWarehouse::class,'counterparty_warehouse_id');} }

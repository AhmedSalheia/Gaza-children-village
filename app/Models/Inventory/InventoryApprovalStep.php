<?php
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
class InventoryApprovalStep extends Model { protected $table='inventory_approval_steps'; protected $guarded=[]; protected $casts=['acted_at'=>'datetime']; public function request(){return $this->belongsTo(InventoryRequest::class,'inventory_request_id');} }

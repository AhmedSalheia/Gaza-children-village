<?php
declare(strict_types=1);
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
class InventoryRequestLine extends Model
{
    protected $table = 'inventory_request_lines';
    protected $guarded = [];
    protected $casts = ['requested_quantity'=>'decimal:3','approved_quantity'=>'decimal:3','unit_cost'=>'decimal:4'];
    public function item() { return $this->belongsTo(InventoryItem::class); }
}

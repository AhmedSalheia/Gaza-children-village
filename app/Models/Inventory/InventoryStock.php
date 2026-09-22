<?php
declare(strict_types=1);
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStock extends Model
{
    protected $table = 'inventory_stock';
    protected $guarded = [];
    protected $casts = ['quantity'=>'decimal:3','reserved_quantity'=>'decimal:3','average_unit_cost'=>'decimal:4'];
    public function item(): BelongsTo { return $this->belongsTo(InventoryItem::class, 'item_id'); }
    public function warehouse(): BelongsTo { return $this->belongsTo(InventoryWarehouse::class, 'warehouse_id'); }
    public function available(): float { return max(0, (float)$this->quantity - (float)$this->reserved_quantity); }
}

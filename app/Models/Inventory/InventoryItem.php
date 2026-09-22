<?php
declare(strict_types=1);
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    protected $table = 'inventory_items';
    protected $guarded = [];
    public function category(): BelongsTo { return $this->belongsTo(InventoryCategory::class, 'category_id'); }
    public function unit(): BelongsTo { return $this->belongsTo(InventoryUnit::class, 'base_unit_id'); }
    public function stocks(): HasMany { return $this->hasMany(InventoryStock::class, 'item_id'); }
}

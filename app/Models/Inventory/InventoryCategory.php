<?php
declare(strict_types=1);
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class InventoryCategory extends Model
{
    protected $table = 'inventory_categories';
    protected $guarded = [];
    public function items(): HasMany { return $this->hasMany(InventoryItem::class, 'category_id'); }
}

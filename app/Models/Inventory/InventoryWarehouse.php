<?php
declare(strict_types=1);
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Organization\Models\Institution;

class InventoryWarehouse extends Model
{
    protected $table = 'inventory_warehouses';
    protected $guarded = [];
    protected $casts = ['latitude'=>'float','longitude'=>'float'];
    public function institution() { return $this->belongsTo(Institution::class); }
    public function stocks(): HasMany { return $this->hasMany(InventoryStock::class, 'warehouse_id'); }
}

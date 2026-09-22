<?php
declare(strict_types=1);
namespace App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class InventoryRequest extends Model
{
    protected $table = 'inventory_requests';
    protected $guarded = [];
    protected $casts = ['estimated_value'=>'decimal:2'];
    public function lines(): HasMany { return $this->hasMany(InventoryRequestLine::class); }
    public function approvals(): HasMany { return $this->hasMany(InventoryApprovalStep::class, 'inventory_request_id'); }
    public function institution() { return $this->belongsTo(\Modules\Organization\Models\Institution::class, 'requesting_institution_id'); }
    public function warehouse() { return $this->belongsTo(InventoryWarehouse::class, 'source_warehouse_id'); }
}

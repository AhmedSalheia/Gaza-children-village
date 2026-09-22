<?php

declare(strict_types=1);

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryCount extends Model
{
    use HasFactory;

    protected $table = 'inventory_counts';

    protected $fillable = [
        'count_number',
        'warehouse_id',
        'status',
        'started_by_account_id',
        'started_at',
        'closed_by_account_id',
        'closed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(
            InventoryWarehouse::class,
            'warehouse_id'
        );
    }

    public function lines(): HasMany
    {
        return $this->hasMany(
            InventoryCountLine::class,
            'inventory_count_id'
        );
    }
}

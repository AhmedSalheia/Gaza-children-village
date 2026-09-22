<?php

declare(strict_types=1);

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCountLine extends Model
{
    use HasFactory;

    protected $table = 'inventory_count_lines';

    protected $fillable = [
        'inventory_count_id',
        'item_id',
        'system_quantity',
        'counted_quantity',
        'counted_by_account_id',
        'counted_at',
    ];

    protected $casts = [
        'system_quantity' => 'decimal:3',
        'counted_quantity' => 'decimal:3',
        'counted_at' => 'datetime',
    ];

    public function count(): BelongsTo
    {
        return $this->belongsTo(
            InventoryCount::class,
            'inventory_count_id'
        );
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(
            InventoryItem::class,
            'item_id'
        );
    }
}

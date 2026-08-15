<?php

declare(strict_types=1);

namespace Modules\Imports\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Imports\Database\Factories\ImportRowFactory;

final class ImportRow extends Model
{
    protected $fillable = [];

    protected $casts = [
        'raw_data' => 'array',
        'mapped_data' => 'array',
    ];

    /** @return BelongsTo<ImportBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'batch_id');
    }

    /** @return HasOne<ImportRowResult, $this> */
    public function result(): HasOne
    {
        return $this->hasOne(ImportRowResult::class, 'row_id');
    }

    /**
     * @return static
     */
    public static function newFactory(): Factory
    {
        return ImportRowFactory::new();
    }
}

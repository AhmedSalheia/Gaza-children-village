<?php

declare(strict_types=1);

namespace Modules\Imports\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ImportColumnMapping extends Model
{
    protected $fillable = [];

    protected $casts = [
        'is_ignored' => 'boolean',
    ];

    /** @return BelongsTo<ImportBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'batch_id');
    }
}

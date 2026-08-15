<?php

declare(strict_types=1);

namespace Modules\Imports\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Imports\Enums\RowResultStatus;

final class ImportRowResult extends Model
{
    protected $fillable = [];

    protected $casts = [
        'status' => RowResultStatus::class,
        'error_detail' => 'array',
    ];

    /** @return BelongsTo<ImportBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'batch_id');
    }

    /** @return BelongsTo<ImportRow, $this> */
    public function row(): BelongsTo
    {
        return $this->belongsTo(ImportRow::class, 'row_id');
    }

    /** @return HasMany<ImportAppliedRecord, $this> */
    public function appliedRecords(): HasMany
    {
        return $this->hasMany(ImportAppliedRecord::class, 'result_id');
    }
}

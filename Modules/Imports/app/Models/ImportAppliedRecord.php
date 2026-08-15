<?php

declare(strict_types=1);

namespace Modules\Imports\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ImportAppliedRecord extends Model
{
    protected $fillable = [];

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

    /** @return BelongsTo<ImportRowResult, $this> */
    public function result(): BelongsTo
    {
        return $this->belongsTo(ImportRowResult::class, 'result_id');
    }
}

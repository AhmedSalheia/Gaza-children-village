<?php

declare(strict_types=1);

namespace Modules\Imports\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Imports\Database\Factories\ImportBatchFactory;
use Modules\Imports\Enums\BatchStatus;
use Modules\Imports\Exceptions\BatchMutationDeniedException;

final class ImportBatch extends Model
{
    /** @use HasFactory<ImportBatchFactory> */
    use HasFactory;

    protected $fillable = [];

    protected $casts = [
        'status' => BatchStatus::class,
        'applied_at' => 'datetime',
    ];

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    /** @return HasOne<ImportFile, $this> */
    public function file(): HasOne
    {
        return $this->hasOne(ImportFile::class, 'batch_id');
    }

    /** @return HasMany<ImportRow, $this> */
    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class, 'batch_id');
    }

    /** @return HasMany<ImportColumnMapping, $this> */
    public function columnMappings(): HasMany
    {
        return $this->hasMany(ImportColumnMapping::class, 'batch_id');
    }

    /** @return HasMany<ImportRowResult, $this> */
    public function rowResults(): HasMany
    {
        return $this->hasMany(ImportRowResult::class, 'batch_id');
    }

    /** @return HasMany<ImportAppliedRecord, $this> */
    public function appliedRecords(): HasMany
    {
        return $this->hasMany(ImportAppliedRecord::class, 'batch_id');
    }

    // ------------------------------------------------------------------
    // Status transitions
    // ------------------------------------------------------------------

    /**
     * Transition to a new status.
     *
     * @throws BatchMutationDeniedException if the transition is not allowed.
     */
    public function transitionTo(BatchStatus $next): void
    {
        if (! $this->status->canTransitionTo($next)) {
            throw new BatchMutationDeniedException(
                "Cannot transition batch from [{$this->status->value}] to [{$next->value}]."
            );
        }

        $this->status = $next;
        $this->save();
    }

    protected static function newFactory(): ImportBatchFactory
    {
        return ImportBatchFactory::new();
    }
}

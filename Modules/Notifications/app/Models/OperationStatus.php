<?php

declare(strict_types=1);

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Background job / operation status record.
 *
 * Tracks long-running operations (PDF exports, bulk imports, large reports)
 * dispatched to the database queue. Each actor can only see their own records.
 *
 * Status lifecycle: queued → running → completed | failed | cancelled | expired
 *
 * Table: operation_statuses
 */
final class OperationStatus extends Model
{
    protected $table = 'operation_statuses';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'scope' => 'array',
            'progress_percent' => 'integer',
            'attempts' => 'integer',
            'queued_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    /**
     * Only operations belonging to a specific actor (isolation check).
     *
     * @param  Builder<OperationStatus>  $query
     * @return Builder<OperationStatus>
     */
    public function scopeForActor(Builder $query, string $actorType, int $actorId, string $portal): Builder
    {
        return $query
            ->where('actor_type', $actorType)
            ->where('actor_account_id', $actorId)
            ->where('portal', $portal);
    }

    /**
     * Only active (not yet terminal) operations.
     *
     * @param  Builder<OperationStatus>  $query
     * @return Builder<OperationStatus>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['queued', 'running']);
    }
}

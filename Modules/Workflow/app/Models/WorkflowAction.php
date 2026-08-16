<?php

declare(strict_types=1);

namespace Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only record of a single state transition performed on a WorkflowInstance.
 *
 * Once written, a WorkflowAction is never updated or deleted.
 * Together the action rows form the complete audit trail of a workflow instance.
 *
 * metadata must contain only non-sensitive supplemental data (no national IDs,
 * no marks, no medical information, no full file paths).
 *
 * Cross-module column references (plain unsigned integers — no DB FK):
 *   actor_account_id — Accounts module (type disambiguated by actor_type)
 */
final class WorkflowAction extends Model
{
    /**
     * Disable automatic updated_at; this row is write-once.
     */
    public const UPDATED_AT = null;

    protected $table = 'workflow_actions';

    /** @var list<string> */
    protected $fillable = [
        'workflow_instance_id',
        'previous_state',
        'new_state',
        'action_code',
        'actor_type',
        'actor_portal',
        'actor_account_id',
        'decision',
        'comment',
        'metadata',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'metadata' => 'array',
    ];

    /** @return BelongsTo<WorkflowInstance, $this> */
    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }
}

<?php

declare(strict_types=1);

namespace Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Immutable blueprint for a workflow type.
 *
 * Code-governed: rows are seeded by WorkflowDefinitionSeeder and must not be
 * created or mutated through the UI. Only the `is_active` flag changes in
 * production (to deactivate an obsolete version).
 *
 * The `transitions` JSON field defines the valid state machine as an array of:
 *   { "from": "<state>", "action": "<action_code>", "to": "<state>" }
 *
 * The `terminal_states` JSON field lists state names that are irreversible;
 * no further transitions are accepted once current_state is terminal.
 */
final class WorkflowDefinition extends Model
{
    protected $table = 'workflow_definitions';

    /** @var list<string> */
    protected $fillable = [
        'type',
        'version',
        'description',
        'is_active',
        'transitions',
        'terminal_states',
        'initial_state',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
        'transitions' => 'array',
        'terminal_states' => 'array',
    ];

    /** @return HasMany<WorkflowInstance, $this> */
    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class, 'workflow_definition_id');
    }

    /**
     * Find the next state for a given (from_state, action_code) pair.
     *
     * Returns null when no valid transition exists.
     */
    public function resolveNextState(string $fromState, string $actionCode): ?string
    {
        foreach ($this->transitions ?? [] as $transition) {
            if ($transition['from'] === $fromState && $transition['action'] === $actionCode) {
                return $transition['to'];
            }
        }

        return null;
    }

    /**
     * Whether the given state is terminal (no further transitions allowed).
     */
    public function isTerminalState(string $state): bool
    {
        return in_array($state, $this->terminal_states ?? [], true);
    }

    /**
     * @param  Builder<WorkflowDefinition>  $query
     * @return Builder<WorkflowDefinition>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<WorkflowDefinition>  $query
     * @return Builder<WorkflowDefinition>
     */
    public function scopeForType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }
}

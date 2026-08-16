<?php

declare(strict_types=1);

namespace Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A running instance of a workflow definition attached to a domain subject.
 *
 * Cross-module column references (plain unsigned integers — no DB FK):
 *   institution_id          — Organization module
 *   institution_semester_id — AcademicCalendar module
 *   actor_account_id        — Accounts module (type disambiguated by initiating_actor_type)
 *   subject_id              — Any domain model (type disambiguated by subject_type)
 *   assigned_account_id     — Accounts module (type disambiguated by assigned_actor_type)
 *
 * correlation_id: a caller-supplied UUID that uniquely identifies the request
 * so idempotent callers can detect a duplicate before creating a second instance.
 */
final class WorkflowInstance extends Model
{
    protected $table = 'workflow_instances';

    /** @var list<string> */
    protected $fillable = [
        'workflow_definition_id',
        'subject_type',
        'subject_id',
        'current_state',
        'initiating_actor_type',
        'initiating_actor_portal',
        'initiating_account_id',
        'institution_id',
        'institution_semester_id',
        'assigned_actor_type',
        'assigned_account_id',
        'due_date',
        'correlation_id',
        'completed_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    /** @return BelongsTo<WorkflowDefinition, $this> */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }

    /** @return HasMany<WorkflowAction, $this> */
    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowAction::class, 'workflow_instance_id')->orderBy('id');
    }

    /**
     * @param  Builder<WorkflowInstance>  $query
     * @return Builder<WorkflowInstance>
     */
    public function scopeForInstitution(Builder $query, int $institutionId): Builder
    {
        return $query->where('institution_id', $institutionId);
    }

    /**
     * @param  Builder<WorkflowInstance>  $query
     * @return Builder<WorkflowInstance>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }

    /**
     * @param  Builder<WorkflowInstance>  $query
     * @return Builder<WorkflowInstance>
     */
    public function scopeForSubject(Builder $query, string $subjectType, int $subjectId): Builder
    {
        return $query->where('subject_type', $subjectType)->where('subject_id', $subjectId);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Requests\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Requests\Enums\CorrectionClassification;
use Modules\Requests\Enums\CorrectionFieldCatalogue;

/**
 * A guardian-initiated proposal to correct one student data field.
 *
 * State is owned by the linked WorkflowInstance (accessed via workflow_instance_id).
 * This model holds domain fields; WorkflowTransitionService drives state changes.
 *
 * Cross-module column references (plain integers, no DB FK):
 *   workflow_instance_id  → workflow_instances (Workflow module)
 *   student_profile_id    → student_profiles (Students module)
 *   guardian_account_id   → guardian_accounts (Accounts module)
 *   guardian_profile_id   → guardian_profiles (Students module)
 *   institution_id        → institutions (Organization module)
 *   applied_by_account_id → staff_accounts or administrative_accounts
 */
final class StudentCorrectionRequest extends Model
{
    protected $table = 'student_correction_requests';

    /** All columns excluded from mass assignment — direct property assignment required. */
    protected $guarded = ['*'];

    /** @var array<string, string> */
    protected $casts = [
        'classification' => CorrectionClassification::class,
        'conflict_flag' => 'boolean',
        'applied_at' => 'datetime',
    ];

    /** @return HasMany<CorrectionFieldProposal, $this> */
    public function proposals(): HasMany
    {
        return $this->hasMany(CorrectionFieldProposal::class, 'correction_request_id')
            ->orderBy('submission_sequence');
    }

    /**
     * Most recent proposal for this request.
     *
     * @return HasMany<CorrectionFieldProposal, $this>
     */
    public function latestProposal(): HasMany
    {
        return $this->hasMany(CorrectionFieldProposal::class, 'correction_request_id')
            ->orderByDesc('submission_sequence')
            ->limit(1);
    }

    // -----------------------------------------------------------------
    // Typed accessors
    // -----------------------------------------------------------------

    public function fieldCatalogue(): CorrectionFieldCatalogue
    {
        return CorrectionFieldCatalogue::from($this->field_catalogue_code);
    }

    public function isSensitive(): bool
    {
        return $this->classification === CorrectionClassification::Sensitive;
    }

    public function isApplied(): bool
    {
        return $this->applied_at !== null;
    }

    // -----------------------------------------------------------------
    // Query scopes
    // -----------------------------------------------------------------

    /**
     * @param  Builder<StudentCorrectionRequest>  $query
     * @return Builder<StudentCorrectionRequest>
     */
    public function scopeForGuardian(Builder $query, int $guardianAccountId): Builder
    {
        return $query->where('guardian_account_id', $guardianAccountId);
    }

    /**
     * @param  Builder<StudentCorrectionRequest>  $query
     * @return Builder<StudentCorrectionRequest>
     */
    public function scopeForStudent(Builder $query, int $studentProfileId): Builder
    {
        return $query->where('student_profile_id', $studentProfileId);
    }

    /**
     * @param  Builder<StudentCorrectionRequest>  $query
     * @return Builder<StudentCorrectionRequest>
     */
    public function scopeForInstitution(Builder $query, int $institutionId): Builder
    {
        return $query->where('institution_id', $institutionId);
    }

    /**
     * @param  Builder<StudentCorrectionRequest>  $query
     * @return Builder<StudentCorrectionRequest>
     */
    public function scopeSensitive(Builder $query): Builder
    {
        return $query->where('classification', CorrectionClassification::Sensitive->value);
    }

    /**
     * @param  Builder<StudentCorrectionRequest>  $query
     * @return Builder<StudentCorrectionRequest>
     */
    public function scopeConflicted(Builder $query): Builder
    {
        return $query->where('conflict_flag', true);
    }
}

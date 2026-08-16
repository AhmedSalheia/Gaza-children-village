<?php

declare(strict_types=1);

namespace Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Immutable electronic approval record.
 *
 * Created when a principal, deputy, or authorised admin provides their
 * identity-reconfirmed decision on a workflow step requiring a formal signature.
 *
 * Immutability: rows are never updated after creation. Revocation is expressed
 * by setting is_revoked = true and superseded_by_id in a NEW row that supersedes
 * this one — the original row remains intact for the audit trail.
 *
 * content_hash: SHA-256 of the exact document/request body the approver saw on
 * their screen. The service recomputes the hash at decision time and rejects
 * the approval if the hash no longer matches (content changed since load).
 *
 * device_fingerprint: low-entropy safe summary (e.g. browser/OS family, nothing
 * that can uniquely identify a physical device). Never stored as raw UA string.
 *
 * Cross-module column references (plain unsigned integers — no DB FK):
 *   approver_account_id — Accounts module (type disambiguated by approver_actor_type)
 *   subject_id          — Any domain model (type disambiguated by subject_type)
 *   superseded_by_id    — self-reference
 */
final class ElectronicApproval extends Model
{
    /**
     * Disable automatic updated_at; this row is write-once.
     */
    public const UPDATED_AT = null;

    protected $table = 'electronic_approvals';

    /** @var list<string> */
    protected $fillable = [
        'approver_actor_type',
        'approver_actor_portal',
        'approver_account_id',
        'approval_type',
        'decision',
        'subject_type',
        'subject_id',
        'subject_version',
        'content_hash',
        'comment',
        'reconfirmation_method',
        'is_revoked',
        'superseded_by_id',
        'device_fingerprint',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_revoked' => 'boolean',
    ];

    /**
     * Returns records that are live (not themselves revoked).
     *
     * Note: a revocation-record row has is_revoked = false (it is not itself revoked),
     * so it correctly appears in the active scope. Only the original row that was
     * superseded has is_revoked = true and is excluded.
     *
     * @param  Builder<ElectronicApproval>  $query
     * @return Builder<ElectronicApproval>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_revoked', false);
    }

    /**
     * @param  Builder<ElectronicApproval>  $query
     * @return Builder<ElectronicApproval>
     */
    public function scopeForSubject(Builder $query, string $subjectType, int $subjectId): Builder
    {
        return $query->where('subject_type', $subjectType)->where('subject_id', $subjectId);
    }
}

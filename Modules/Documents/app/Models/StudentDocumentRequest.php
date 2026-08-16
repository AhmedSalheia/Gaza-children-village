<?php

declare(strict_types=1);

namespace Modules\Documents\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A guardian or secretary request for an issued document.
 *
 * Cross-module IDs (enrollment_id, student_profile_id, institution_id,
 * requested_by_account_id) are plain integers without DB foreign keys,
 * respecting module boundary rules.
 *
 * The 13 workflow states are defined in STATUS_* constants.
 * Only terminal states (issued, generation_failed, cancelled) may not
 * transition further. rejected is also terminal.
 */
final class StudentDocumentRequest extends Model
{
    // ── Workflow states ───────────────────────────────────────────────────

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_PENDING_COMPLETENESS = 'pending_completeness';

    public const STATUS_COMPLETENESS_FAILED = 'completeness_failed';

    public const STATUS_COMPLETENESS_PASSED = 'completeness_passed';

    public const STATUS_AWAITING_APPROVAL = 'awaiting_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_GENERATING = 'generating';

    public const STATUS_ISSUED = 'issued';

    public const STATUS_GENERATION_FAILED = 'generation_failed';

    public const STATUS_PENDING_CLARIFICATION = 'pending_clarification';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Terminal statuses: no further transitions allowed.
     *
     * NOTE: generation_failed is intentionally NOT terminal — the generation
     * job can be re-dispatched after fixing template/data issues. The request
     * can also be cancelled while in generation_failed state.
     *
     * @var list<string>
     */
    public const TERMINAL_STATUSES = [
        self::STATUS_ISSUED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
    ];

    /** Actor types */
    public const ACTOR_GUARDIAN = 'guardian';

    public const ACTOR_STAFF = 'staff';

    public const ACTOR_ADMIN = 'admin';

    /** @var list<string> */
    protected $guarded = ['id'];

    /** @var array<string, string> */
    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    /** @return HasOne<IssuedDocument, $this> */
    public function issuedDocument(): HasOne
    {
        return $this->hasOne(IssuedDocument::class, 'request_id');
    }

    // ── Status helpers ────────────────────────────────────────────────────

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isIssued(): bool
    {
        return $this->status === self::STATUS_ISSUED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isAwaitingApproval(): bool
    {
        return $this->status === self::STATUS_AWAITING_APPROVAL
            || $this->status === self::STATUS_COMPLETENESS_PASSED;
    }

    public function isPendingClarification(): bool
    {
        return $this->status === self::STATUS_PENDING_CLARIFICATION;
    }
}

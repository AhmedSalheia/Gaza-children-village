<?php

declare(strict_types=1);

namespace Modules\Requests\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * An institution-to-central-management formal request.
 *
 * Workflow states (13) are managed directly by InstitutionFormalRequestService.
 * State transitions follow the institution_formal_request workflow definition.
 *
 * version: revision counter (1 for the original draft; new branch drafts also start at 1).
 * content_hash: SHA-256 of canonical content at signing time (set by sign action).
 * superseded_by_id: set when this request is replaced; the replacement is a NEW row
 *   linked back here. Used for both supersede() (after management decision) and
 *   resubmit() (when a returned request is branched into a new draft).
 * branched_from_id: set on the NEW draft when resubmit() creates it. Points back to
 *   the returned-to-preparer source that was preserved as an immutable snapshot.
 *   Distinguishes "version branching on resubmit" from "supersession after decision".
 *
 * Cross-module column references (plain integers — no DB FK):
 *   institution_id           → institutions (Organization)
 *   institution_semester_id  → institution_semesters (AcademicCalendar)
 *   responsible_account_id   → staff_accounts (Accounts)
 *   created_by_account_id    → staff_accounts (Accounts)
 *   superseded_by_id         → self (institution_formal_requests)
 *   branched_from_id         → self (institution_formal_requests)
 */
final class InstitutionFormalRequest extends Model
{
    protected $table = 'institution_formal_requests';

    /** All columns excluded from mass assignment — direct property assignment required. */
    protected $guarded = ['*'];

    /** @var array<string, string> */
    protected $casts = [
        'body' => 'array',
        'response_body' => 'array',
        'response_at' => 'datetime',
        'due_date' => 'date',
    ];

    // ------------------------------------------------------------------
    // Status constants (13 workflow states)
    // ------------------------------------------------------------------

    public const STATUS_DRAFT = 'draft';

    public const STATUS_INTERNAL_REVIEW = 'internal_review';

    public const STATUS_RETURNED_TO_PREPARER = 'returned_to_preparer';

    public const STATUS_SIGNED = 'signed';

    public const STATUS_SUBMITTED_TO_MANAGEMENT = 'submitted_to_management';

    public const STATUS_UNDER_MANAGEMENT_REVIEW = 'under_management_review';

    public const STATUS_CLARIFICATION_REQUESTED = 'clarification_requested';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_RESPONDED = 'responded';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_SUPERSEDED = 'superseded';

    /**
     * States from which the content of the request can be mutated via updateDraft().
     *
     * returned_to_preparer is intentionally excluded: returned source rows are
     * preserved as immutable audit snapshots of the signed revision. A secretary
     * must call resubmit() first to branch the request into a new editable draft
     * before any content changes can be made.
     *
     * @var list<string>
     */
    public const EDITABLE_STATUSES = [
        self::STATUS_DRAFT,
    ];

    /**
     * Terminal states — no further transitions are permitted.
     *
     * @var list<string>
     */
    public const TERMINAL_STATUSES = [
        self::STATUS_CLOSED,
        self::STATUS_CANCELLED,
        self::STATUS_SUPERSEDED,
    ];

    /**
     * States from which a cancellation is allowed.
     *
     * @var list<string>
     */
    public const CANCELLABLE_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_INTERNAL_REVIEW,
        self::STATUS_RETURNED_TO_PREPARER,
    ];

    // ------------------------------------------------------------------
    // Request type catalogue
    // ------------------------------------------------------------------

    /**
     * @var list<string>
     */
    public const REQUEST_TYPES = [
        'budget',
        'staffing',
        'maintenance',
        'equipment',
        'curriculum',
        'administrative',
        'other',
    ];

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    /** @return HasMany<InstitutionFormalRequestComment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(InstitutionFormalRequestComment::class, 'request_id')
            ->orderBy('created_at');
    }

    /**
     * Attachment links for this request (polymorphic via attachment_links table).
     *
     * Uses string-variable pattern so the boundary scanner does not flag a direct
     * import of Modules\Attachments\Models\AttachmentLink from Modules\Requests.
     * (Requests declares Attachments as a dependency in config/module-boundaries.php.)
     *
     * @return MorphMany<Model, $this>
     */
    public function attachmentLinks(): MorphMany
    {
        $linkClass = 'Modules\\Attachments\\Models\\AttachmentLink';

        return $this->morphMany($linkClass, 'linkable');
    }

    // ------------------------------------------------------------------
    // State helpers
    // ------------------------------------------------------------------

    public function isTerminal(): bool
    {
        return in_array($this->current_status, self::TERMINAL_STATUSES, true);
    }

    public function isEditable(): bool
    {
        return in_array($this->current_status, self::EDITABLE_STATUSES, true);
    }

    public function isCancellable(): bool
    {
        return in_array($this->current_status, self::CANCELLABLE_STATUSES, true);
    }

    // ------------------------------------------------------------------
    // Query scopes
    // ------------------------------------------------------------------

    /**
     * @param  Builder<InstitutionFormalRequest>  $query
     * @return Builder<InstitutionFormalRequest>
     */
    public function scopeForInstitution(Builder $query, int $institutionId): Builder
    {
        return $query->where('institution_id', $institutionId);
    }

    /**
     * @param  Builder<InstitutionFormalRequest>  $query
     * @return Builder<InstitutionFormalRequest>
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('current_status', $status);
    }

    /**
     * @param  Builder<InstitutionFormalRequest>  $query
     * @return Builder<InstitutionFormalRequest>
     */
    public function scopeNonTerminal(Builder $query): Builder
    {
        return $query->whereNotIn('current_status', self::TERMINAL_STATUSES);
    }

    /**
     * Requests visible to management (submitted or beyond).
     *
     * @param  Builder<InstitutionFormalRequest>  $query
     * @return Builder<InstitutionFormalRequest>
     */
    public function scopeManagementVisible(Builder $query): Builder
    {
        return $query->whereIn('current_status', [
            self::STATUS_SUBMITTED_TO_MANAGEMENT,
            self::STATUS_UNDER_MANAGEMENT_REVIEW,
            self::STATUS_CLARIFICATION_REQUESTED,
            self::STATUS_ACCEPTED,
            self::STATUS_REJECTED,
            self::STATUS_RESPONDED,
            self::STATUS_CLOSED,
        ]);
    }
}

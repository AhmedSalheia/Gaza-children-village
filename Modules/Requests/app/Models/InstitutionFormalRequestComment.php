<?php

declare(strict_types=1);

namespace Modules\Requests\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

/**
 * A comment on an institution formal request.
 *
 * comment_text is stored ENCRYPTED using Laravel Crypt. The accessor/mutator
 * pattern here handles transparent encrypt/decrypt. Never expose raw ciphertext
 * to the UI — always call $comment->comment_text (decrypted via accessor).
 *
 * Audience values:
 *   'internal'   — visible to institution side only (staff portal)
 *   'management' — visible to management side only (admin portal)
 *   'all'        — visible to both sides
 *
 * Cross-module column references (plain integers — no DB FK):
 *   commenter_account_id → staff_accounts or administrative_accounts (Accounts module)
 */
final class InstitutionFormalRequestComment extends Model
{
    protected $table = 'institution_formal_request_comments';

    /** All columns excluded from mass assignment — direct property assignment required. */
    protected $guarded = ['*'];

    /** @var array<string, string> */
    protected $casts = [];

    public const AUDIENCE_INTERNAL = 'internal';

    public const AUDIENCE_MANAGEMENT = 'management';

    public const AUDIENCE_ALL = 'all';

    // ------------------------------------------------------------------
    // Encrypted comment_text accessor / mutator
    // ------------------------------------------------------------------

    /**
     * Get the decrypted comment text.
     */
    public function getCommentTextAttribute(string $value): string
    {
        return Crypt::decryptString($value);
    }

    /**
     * Store the comment text encrypted.
     */
    public function setCommentTextAttribute(string $value): void
    {
        $this->attributes['comment_text'] = Crypt::encryptString($value);
    }

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    /** @return BelongsTo<InstitutionFormalRequest, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(InstitutionFormalRequest::class, 'request_id');
    }

    // ------------------------------------------------------------------
    // Query scopes
    // ------------------------------------------------------------------

    /**
     * Filter comments visible to the institution side (portal = 'staff').
     *
     * Internal and all-audience comments are visible; management-only are hidden.
     *
     * @param  Builder<InstitutionFormalRequestComment>  $query
     * @return Builder<InstitutionFormalRequestComment>
     */
    public function scopeVisibleToInstitution(Builder $query): Builder
    {
        return $query->whereIn('audience', [self::AUDIENCE_INTERNAL, self::AUDIENCE_ALL]);
    }

    /**
     * Filter comments visible to the management side (portal = 'admin').
     *
     * Management and all-audience comments are visible; internal-only are hidden.
     *
     * @param  Builder<InstitutionFormalRequestComment>  $query
     * @return Builder<InstitutionFormalRequestComment>
     */
    public function scopeVisibleToManagement(Builder $query): Builder
    {
        return $query->whereIn('audience', [self::AUDIENCE_MANAGEMENT, self::AUDIENCE_ALL]);
    }
}

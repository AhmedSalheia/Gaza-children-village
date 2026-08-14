<?php

declare(strict_types=1);

namespace Modules\Accounts\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Accounts\Enums\ChallengePurpose;

/**
 * A short-lived, purpose-bound, account-bound, attempt-limited verification
 * challenge used to authorize password setup or reset.
 *
 * Security contracts:
 * - The plaintext token is NEVER stored; only its SHA-256 hash.
 * - Expired, exhausted, revoked, or consumed challenges are permanently closed.
 * - isActive() is the single authoritative check before any attempt.
 */
final class AccountVerificationChallenge extends Model
{
    protected $fillable = [
        'portal',
        'account_id',
        'account_type',
        'purpose',
        'token_hash',
        'attempts',
        'expires_at',
        'consumed_at',
        'revoked_at',
    ];

    protected $casts = [
        'purpose' => ChallengePurpose::class,
        'attempts' => 'integer',
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected $hidden = [
        'token_hash', // never serialize the hash into API responses
    ];

    /**
     * Whether this challenge may still be attempted.
     */
    public function isActive(): bool
    {
        $maxAttempts = (int) config('account-challenges.challenge.max_attempts', 5);

        return $this->consumed_at === null
            && $this->revoked_at === null
            && $this->expires_at->isFuture()
            && $this->attempts < $maxAttempts;
    }
}

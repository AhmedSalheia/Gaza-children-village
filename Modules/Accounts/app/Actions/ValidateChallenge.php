<?php

declare(strict_types=1);

namespace Modules\Accounts\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Accounts\Data\PortalAuthConfig;
use Modules\Accounts\Enums\ChallengePurpose;
use Modules\Accounts\Enums\ChallengeValidationResult;
use Modules\Accounts\Models\AccountVerificationChallenge;
use SensitiveParameter;

/**
 * Validate a submitted challenge token for an account.
 *
 * Lookup strategy:
 * 1. Hash the submitted token and look for a challenge row with that exact hash.
 *    If found, inspect its status — this gives a precise result (Revoked,
 *    AlreadyConsumed, Expired, Exhausted, or Valid) for that specific token.
 * 2. If no row matches the hash (wrong token submitted), find the current active
 *    challenge and increment its attempt counter. This limits brute-force guessing
 *    without allowing unlimited attempts on revoked/superseded challenges.
 *
 * On a successful match the challenge is marked consumed and may not be used again.
 * Callers should map all non-Valid results to a single generic public error message.
 */
final class ValidateChallenge
{
    public function __invoke(
        Authenticatable $account,
        PortalAuthConfig $config,
        ChallengePurpose $purpose,
        #[SensitiveParameter] string $plaintextToken,
    ): ChallengeValidationResult {
        $submittedHash = hash('sha256', $plaintextToken);
        $maxAttempts = (int) config('account-challenges.challenge.max_attempts', 5);

        // ------------------------------------------------------------------
        // Phase 1: find the challenge that matches this specific token hash.
        // ------------------------------------------------------------------
        $matched = AccountVerificationChallenge::where('portal', $config->portal)
            ->where('account_id', $account->getAuthIdentifier())
            ->where('account_type', $account::class)
            ->where('purpose', $purpose->value)
            ->where('token_hash', $submittedHash)
            ->first();

        if ($matched !== null) {
            // We know which challenge this token belongs to — return its status precisely.
            if ($matched->revoked_at !== null) {
                return ChallengeValidationResult::Revoked;
            }

            if ($matched->consumed_at !== null) {
                return ChallengeValidationResult::AlreadyConsumed;
            }

            if ($matched->expires_at->isPast()) {
                return ChallengeValidationResult::Expired;
            }

            if ($matched->attempts >= $maxAttempts) {
                return ChallengeValidationResult::Exhausted;
            }

            // Valid — mark consumed so it cannot be reused.
            $matched->update(['consumed_at' => now()]);

            return ChallengeValidationResult::Valid;
        }

        // ------------------------------------------------------------------
        // Phase 2: wrong token submitted — penalise the active challenge.
        // ------------------------------------------------------------------
        $active = AccountVerificationChallenge::where('portal', $config->portal)
            ->where('account_id', $account->getAuthIdentifier())
            ->where('account_type', $account::class)
            ->where('purpose', $purpose->value)
            ->whereNull('revoked_at')
            ->whereNull('consumed_at')
            ->orderByDesc('id')
            ->first();

        if ($active === null) {
            return ChallengeValidationResult::NotFound;
        }

        if ($active->expires_at->isPast()) {
            return ChallengeValidationResult::Expired;
        }

        if ($active->attempts >= $maxAttempts) {
            return ChallengeValidationResult::Exhausted;
        }

        // Count the failed attempt.
        $active->increment('attempts');
        $active->refresh();

        if ($active->attempts >= $maxAttempts) {
            return ChallengeValidationResult::Exhausted;
        }

        return ChallengeValidationResult::InvalidToken;
    }
}

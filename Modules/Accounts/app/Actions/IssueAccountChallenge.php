<?php

declare(strict_types=1);

namespace Modules\Accounts\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Accounts\Contracts\ChallengeDelivery;
use Modules\Accounts\Data\PortalAuthConfig;
use Modules\Accounts\Enums\ChallengePurpose;
use Modules\Accounts\Models\AccountVerificationChallenge;

/**
 * Issue a new verification challenge for an account.
 *
 * Steps:
 * 1. Revoke any existing open challenges for the same account+portal+purpose.
 * 2. Generate a cryptographically random plaintext token.
 * 3. Store only the SHA-256 hash of the token.
 * 4. Deliver the plaintext token via the configured ChallengeDelivery provider.
 * 5. Discard the plaintext — it never appears in a response or log after delivery.
 *
 * The plaintext token is passed to the delivery provider and immediately falls
 * out of scope. If delivery throws, the challenge record is still created
 * (the delivery exception is not re-thrown — callers always see a generic outcome).
 */
final class IssueAccountChallenge
{
    public function __construct(
        private readonly RevokeAccountChallenges $revokeExisting,
        private readonly ChallengeDelivery $delivery,
    ) {}

    public function __invoke(
        Authenticatable $account,
        PortalAuthConfig $config,
        ChallengePurpose $purpose,
    ): void {
        // Revoke any still-open challenges for the same purpose so only the
        // newest token is valid.
        ($this->revokeExisting)(
            $account,
            $config->portal,
            $purpose->value,
        );

        // Generate the plaintext token (never stored).
        $bytes = (int) config('account-challenges.challenge.token_bytes', 32);
        $plaintext = bin2hex(random_bytes($bytes));

        // Store only the SHA-256 hash.
        $tokenHash = hash('sha256', $plaintext);

        $lifetimeMinutes = (int) config('account-challenges.challenge.lifetime_minutes', 30);

        AccountVerificationChallenge::create([
            'portal' => $config->portal,
            'account_id' => $account->getAuthIdentifier(),
            'account_type' => $account::class,
            'purpose' => $purpose->value,
            'token_hash' => $tokenHash,
            'attempts' => 0,
            'expires_at' => now()->addMinutes($lifetimeMinutes),
        ]);

        // Deliver the plaintext — after this call the plaintext goes out of scope.
        try {
            $this->delivery->deliver($account, $config, $purpose, $plaintext);
        } catch (\Throwable) {
            // Delivery failure is silent: the challenge is already persisted.
            // The caller always receives a generic public outcome.
        }
    }
}

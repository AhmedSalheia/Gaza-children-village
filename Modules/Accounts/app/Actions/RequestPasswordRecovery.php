<?php

declare(strict_types=1);

namespace Modules\Accounts\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Modules\Accounts\Data\PortalAuthConfig;
use Modules\Accounts\Enums\ChallengePurpose;
use Modules\Accounts\Services\LoginIdentifierNormalizer;

/**
 * Request a password-reset challenge for an existing account.
 *
 * Anti-enumeration contract: this action ALWAYS returns void regardless of
 * whether the identifier is known, valid, eligible, or has a recovery contact.
 * Callers must show a generic "if your account exists you will receive
 * instructions" message.
 *
 * Guardian self-service recovery is explicitly disabled until guardian legal
 * eligibility and person linkage are approved.
 *
 * A challenge is issued only for Active accounts; accounts in any other state
 * silently receive no challenge.
 */
final class RequestPasswordRecovery
{
    public function __construct(
        private readonly LoginIdentifierNormalizer $normalizer,
        private readonly IssueAccountChallenge $issue,
    ) {}

    public function __invoke(PortalAuthConfig $config, string $rawIdentifier): void
    {
        // Guardian self-service recovery is disabled.
        if ($config->portal === 'guardian') {
            return;
        }

        try {
            $normalized = $this->normalizer->normalize($rawIdentifier);

            /** @var Authenticatable|null $account */
            $account = Auth::guard($config->portal)
                ->getProvider()
                ->retrieveByCredentials([$config->identifierField => $normalized]);

            if ($account === null) {
                return;
            }

            // Only Active accounts receive password-reset challenges.
            $status = $account->status ?? null;
            if ($status !== null) {
                $statusValue = $status->value ?? $status;
                if ($statusValue !== 'active') {
                    return;
                }
            }

            ($this->issue)($account, $config, ChallengePurpose::PasswordReset);
        } catch (\Throwable) {
            // Silently absorb all errors — the public response is always generic.
        }
    }
}

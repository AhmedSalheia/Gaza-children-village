<?php

declare(strict_types=1);

namespace Modules\Accounts\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Modules\Accounts\Data\PortalAuthConfig;
use Modules\Accounts\Enums\ChallengePurpose;
use Modules\Accounts\Services\LoginIdentifierNormalizer;

/**
 * Request an initial password-setup challenge for a provisioned account.
 *
 * Anti-enumeration contract: this action ALWAYS returns void regardless of
 * whether the identifier is known, valid, or eligible. Callers must show
 * a generic "if your account exists you will receive instructions" message.
 *
 * Guardian self-service is explicitly not implemented; this action silently
 * no-ops for guardian portal requests.
 *
 * A challenge is issued only for Pending or Active accounts; Suspended,
 * Locked, and Revoked accounts silently receive no challenge.
 */
final class RequestAccountSetup
{
    public function __construct(
        private readonly LoginIdentifierNormalizer $normalizer,
        private readonly IssueAccountChallenge $issue,
    ) {}

    public function __invoke(PortalAuthConfig $config, string $rawIdentifier): void
    {
        // Guardian self-service password setup is disabled until guardian
        // legal eligibility and person linkage are approved.
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

            // Only Pending and Active accounts receive setup challenges.
            // Suspended/Locked/Revoked silently receive nothing.
            $status = $account->status ?? null;
            if ($status !== null && ! in_array($status->value ?? $status, ['pending', 'active'], true)) {
                return;
            }

            ($this->issue)($account, $config, ChallengePurpose::InitialPasswordSetup);
        } catch (\Throwable) {
            // Silently absorb all errors — the public response is always generic.
        }
    }
}

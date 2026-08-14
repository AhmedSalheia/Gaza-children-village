<?php

declare(strict_types=1);

namespace Modules\Accounts\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Accounts\Contracts\ChallengeDelivery;
use Modules\Accounts\Data\PortalAuthConfig;
use Modules\Accounts\Enums\ChallengePurpose;
use SensitiveParameter;

/**
 * Production-safe no-op delivery implementation.
 *
 * Used when no real SMS/email provider is configured. The challenge is issued
 * and stored but the token is not transmitted. Administrators who need to
 * activate accounts must use an out-of-band channel or swap this binding.
 *
 * This is the default binding. A real delivery provider replaces this binding
 * in the application service provider once a delivery channel is approved.
 */
final class NullChallengeDelivery implements ChallengeDelivery
{
    public function deliver(
        Authenticatable $account,
        PortalAuthConfig $config,
        ChallengePurpose $purpose,
        #[SensitiveParameter] string $plaintextToken,
    ): void {
        // No delivery provider is configured. The plaintext token is discarded.
        // Log at debug level so developers know the challenge was issued but not sent.
        logger()->debug('Challenge issued but no delivery provider is configured.', [
            'portal' => $config->portal,
            'purpose' => $purpose->value,
            // account_id is logged for traceability; no plaintext token, identifier, or contact
            'account_id' => $account->getAuthIdentifier(),
        ]);
    }
}

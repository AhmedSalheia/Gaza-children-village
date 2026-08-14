<?php

declare(strict_types=1);

namespace Modules\Accounts\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Accounts\Data\PortalAuthConfig;
use Modules\Accounts\Enums\ChallengePurpose;
use SensitiveParameter;

/**
 * Delivery channel for verification challenge tokens.
 *
 * Implementations are responsible for resolving a verified, recovery-eligible
 * delivery destination for the given account and transmitting the plaintext
 * token. The token is the only call site where the plaintext is available;
 * implementations MUST NOT store or log it.
 *
 * The production null implementation logs a warning and discards the token.
 * A test fake captures the token so tests can assert the challenge flow.
 * A real SMS/email implementation wires to the configured provider.
 */
interface ChallengeDelivery
{
    public function deliver(
        Authenticatable $account,
        PortalAuthConfig $config,
        ChallengePurpose $purpose,
        #[SensitiveParameter] string $plaintextToken,
    ): void;
}

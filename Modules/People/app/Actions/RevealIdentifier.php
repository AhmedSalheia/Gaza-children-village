<?php

declare(strict_types=1);

namespace Modules\People\Actions;

use Modules\People\Models\PersonIdentifier;
use Modules\People\Services\IdentifierCrypto;

/**
 * Explicitly reveal the raw decrypted identifier value for an authorized actor.
 *
 * This action must only be called after the caller has confirmed authorization.
 * The caller is responsible for recording an audit entry for the reveal.
 *
 * The raw value must never appear in logs or any response payload other than
 * the explicit authorized channel (e.g. a secured API endpoint with audit).
 */
final class RevealIdentifier
{
    public function __construct(
        private readonly IdentifierCrypto $crypto,
    ) {}

    /**
     * Decrypt and return the raw identifier value.
     *
     * @param  bool  $authorized  Pass true only after confirming caller authorization.
     *
     * @throws \RuntimeException if authorization has not been confirmed.
     */
    public function __invoke(PersonIdentifier $identifier, bool $authorized): string
    {
        if (! $authorized) {
            throw new \RuntimeException('Identifier reveal requires explicit authorization.');
        }

        return $identifier->revealRaw($this->crypto);
    }
}

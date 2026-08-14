<?php

declare(strict_types=1);

namespace Modules\People\Actions;

use Modules\People\Models\ContactPoint;
use Modules\People\Services\IdentifierCrypto;

/**
 * Explicitly reveal the raw decrypted contact value for an authorized actor.
 *
 * Callers must confirm authorization before invoking this action.
 * Raw values must never appear in logs or general response payloads.
 */
final class RevealContact
{
    public function __construct(
        private readonly IdentifierCrypto $crypto,
    ) {}

    /**
     * @throws \RuntimeException if authorization has not been confirmed.
     */
    public function __invoke(ContactPoint $contact, bool $authorized): string
    {
        if (! $authorized) {
            throw new \RuntimeException('Contact reveal requires explicit authorization.');
        }

        return $contact->revealRaw($this->crypto);
    }
}

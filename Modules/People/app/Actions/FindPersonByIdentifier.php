<?php

declare(strict_types=1);

namespace Modules\People\Actions;

use Modules\People\Enums\IdentifierType;
use Modules\People\Models\Person;
use Modules\People\Models\PersonIdentifier;
use Modules\People\Services\IdentifierCrypto;
use Modules\People\Services\PalestinianIdNormalizer;
use SensitiveParameter;

/**
 * Find a Person by their normalized identifier using fingerprint lookup.
 *
 * Only current (non-superseded) identifiers are searched. Fingerprint lookup
 * never exposes the raw value in the query or in any exception.
 *
 * Returns null if no match is found — callers must not distinguish "not found"
 * from "access denied" in public-facing responses.
 */
final class FindPersonByIdentifier
{
    public function __construct(
        private readonly IdentifierCrypto $crypto,
        private readonly PalestinianIdNormalizer $psNormalizer,
    ) {}

    public function __invoke(
        IdentifierType $type,
        #[SensitiveParameter] string $rawValue,
    ): ?Person {
        $normalized = $this->normalize($type, $rawValue);
        $fingerprint = $this->crypto->fingerprint($normalized);

        $identifier = PersonIdentifier::where('lookup_fingerprint', $fingerprint)
            ->where('is_current', true)
            ->with('person')
            ->first();

        return $identifier?->person;
    }

    private function normalize(IdentifierType $type, #[SensitiveParameter] string $raw): string
    {
        return match ($type) {
            IdentifierType::PsNationalId => $this->psNormalizer->normalize($raw),
            default => trim($raw),
        };
    }
}

<?php

declare(strict_types=1);

namespace Modules\People\Actions;

use Modules\People\Enums\IdentifierType;
use Modules\People\Exceptions\IdentifierCollisionException;
use Modules\People\Models\Person;
use Modules\People\Models\PersonIdentifier;
use Modules\People\Services\IdentifierCrypto;
use Modules\People\Services\PalestinianIdNormalizer;
use SensitiveParameter;

/**
 * Add a new PersonIdentifier to an existing Person.
 *
 * For Palestinian national IDs, the raw value is normalized before storage.
 * The lookup fingerprint is computed from the normalized value so that
 * equivalent representations produce the same fingerprint.
 *
 * If the fingerprint collides with an existing row (current or superseded),
 * an IdentifierCollisionException is thrown. The caller must route to
 * human review — automatic merging is never permitted.
 *
 * The identifier is stored encrypted. Only the fingerprint and masked value
 * are visible by default; the raw value requires an explicit reveal.
 */
final class AddPersonIdentifier
{
    public function __construct(
        private readonly IdentifierCrypto $crypto,
        private readonly PalestinianIdNormalizer $psNormalizer,
    ) {}

    public function __invoke(
        Person $person,
        IdentifierType $type,
        #[SensitiveParameter] string $rawValue,
        ?string $countryCode = null,
        ?string $issuerName = null,
        ?\DateTimeInterface $effectiveFrom = null,
    ): PersonIdentifier {
        $normalized = $this->normalize($type, $rawValue);
        $fingerprint = $this->crypto->fingerprint($normalized);

        // Collision check — catches concurrent writes via the DB unique index.
        // Also surface a clear domain error before hitting the constraint.
        if (PersonIdentifier::where('lookup_fingerprint', $fingerprint)->exists()) {
            throw new IdentifierCollisionException(
                'A person identifier with this value already exists and requires human review before proceeding.'
            );
        }

        $record = new PersonIdentifier;
        $record->person_id = $person->id;
        $record->type = $type->value;
        $record->country_code = $countryCode;
        $record->issuer_name = $issuerName;
        $record->identifier_encrypted = $this->crypto->encrypt($normalized);
        $record->lookup_fingerprint = $fingerprint;
        $record->is_current = true;
        $record->effective_from = $effectiveFrom?->format('Y-m-d');
        $record->save();

        return $record;
    }

    private function normalize(IdentifierType $type, #[SensitiveParameter] string $raw): string
    {
        return match ($type) {
            IdentifierType::PsNationalId => $this->psNormalizer->normalize($raw),
            default => trim($raw),
        };
    }
}

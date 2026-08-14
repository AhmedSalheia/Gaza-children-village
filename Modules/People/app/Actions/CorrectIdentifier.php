<?php

declare(strict_types=1);

namespace Modules\People\Actions;

use Illuminate\Support\Facades\DB;
use Modules\People\Enums\IdentifierType;
use Modules\People\Exceptions\IdentifierCollisionException;
use Modules\People\Models\PersonIdentifier;
use Modules\People\Services\IdentifierCrypto;
use Modules\People\Services\PalestinianIdNormalizer;
use SensitiveParameter;

/**
 * Correct (supersede) an existing PersonIdentifier with a new value.
 *
 * Correction is append-only:
 *  1. A new identifier record is created for the corrected value.
 *  2. The old record is marked superseded (is_current = false, superseded_by_id = new id).
 *  3. The whole operation is atomic within a single DB transaction.
 *  4. The old fingerprint remains in the table so historical collisions surface for review.
 *
 * Actor, source, and reason are required. History stores only references and
 * safe metadata — never the plaintext of old or new identifiers.
 *
 * If the new fingerprint collides with any existing row, IdentifierCollisionException
 * is thrown and the correction is rejected.
 */
final class CorrectIdentifier
{
    public function __construct(
        private readonly IdentifierCrypto $crypto,
        private readonly PalestinianIdNormalizer $psNormalizer,
    ) {}

    public function __invoke(
        PersonIdentifier $existing,
        #[SensitiveParameter] string $newRawValue,
        string $actor,
        string $source,
        string $reason,
    ): PersonIdentifier {
        if (! $existing->is_current) {
            throw new \InvalidArgumentException(
                'Cannot correct a superseded identifier. Correct the current version instead.'
            );
        }

        $type = $existing->type;
        $normalized = $this->normalize($type, $newRawValue);
        $fingerprint = $this->crypto->fingerprint($normalized);

        // Collision check — also enforced by unique DB index on commit.
        if (PersonIdentifier::where('lookup_fingerprint', $fingerprint)->exists()) {
            throw new IdentifierCollisionException(
                'The corrected identifier value conflicts with an existing record and requires human review.'
            );
        }

        return DB::transaction(function () use ($existing, $normalized, $fingerprint, $actor, $source, $reason, $type): PersonIdentifier {
            // Create the corrected record.
            $newRecord = new PersonIdentifier;
            $newRecord->person_id = $existing->person_id;
            $newRecord->type = $type->value;
            $newRecord->country_code = $existing->country_code;
            $newRecord->issuer_name = $existing->issuer_name;
            $newRecord->identifier_encrypted = $this->crypto->encrypt($normalized);
            $newRecord->lookup_fingerprint = $fingerprint;
            $newRecord->is_current = true;
            $newRecord->corrects_id = $existing->id;
            $newRecord->correction_actor = $actor;
            $newRecord->correction_source = $source;
            $newRecord->correction_reason = $reason;
            $newRecord->save();

            // Supersede the old record.
            $existing->is_current = false;
            $existing->superseded_by_id = $newRecord->id;
            $existing->superseded_at = now();
            $existing->save();

            return $newRecord;
        });
    }

    private function normalize(IdentifierType $type, #[SensitiveParameter] string $raw): string
    {
        return match ($type) {
            IdentifierType::PsNationalId => $this->psNormalizer->normalize($raw),
            default => trim($raw),
        };
    }
}

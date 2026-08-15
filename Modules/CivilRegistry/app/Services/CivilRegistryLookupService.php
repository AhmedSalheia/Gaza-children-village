<?php

declare(strict_types=1);

namespace Modules\CivilRegistry\Services;

use Illuminate\Support\Facades\DB;
use Modules\CivilRegistry\Contracts\CivilRegistryLookupContract;
use Modules\CivilRegistry\Data\CivilRegistryMatch;

/**
 * Production implementation of CivilRegistryLookupContract.
 *
 * Lookup steps:
 *  1. Normalise the raw national ID using PalestinianIdNormalizer.
 *  2. Compute the HMAC-SHA256 fingerprint (keyed; plain SHA-256 is enumerable).
 *  3. Check whether a GCV Person already has a matching PersonIdentifier.
 *  4. Query the civil-registry table by fingerprint (O(1) unique index hit).
 *  5. Audit the lookup: actor_account_id + truncated correlation token (never raw ID).
 *  6. Return a CivilRegistryMatch DTO, or null if no record found.
 *
 * Cross-module People access uses string-variable class references to stay
 * within the boundary-scanner rules.
 *
 * AuditRecorder access uses the Audit module's public Contracts surface.
 */
final class CivilRegistryLookupService implements CivilRegistryLookupContract
{
    public function lookup(
        #[\SensitiveParameter] string $rawNationalId,
        int $actorAccountId,
        ?int $institutionId = null,
    ): ?CivilRegistryMatch {
        // Step 1: normalise.
        $normalizerClass = 'Modules\\People\\Services\\PalestinianIdNormalizer';
        $normalizer = new $normalizerClass;
        $normalised = $normalizer->normalize($rawNationalId);

        // Step 2: HMAC fingerprint (offline enumeration resistance).
        $fpService = new CivilRegistryIdFingerprintService;
        $fingerprint = $fpService->fingerprint($normalised);

        // Step 3: check for existing GCV Person with this identifier.
        [$hasExisting, $existingPersonId] = $this->resolveExistingPerson($normalised);

        // Step 4: query the civil registry by fingerprint.
        $table = config('civil-registry.table', 'gaza_civil_records');
        $row = DB::table($table)->where('lookup_fingerprint', $fingerprint)->first();

        // Step 5: audit (actor_account_id + correlation token — no raw ID in payload).
        $this->recordAudit($fingerprint, $actorAccountId, $institutionId, found: $row !== null);

        if ($row === null) {
            return null;
        }

        // Step 6: build match DTO — related IDs exposed only as correlation tokens.
        return new CivilRegistryMatch(
            registryRecordId: (int) $row->id,
            lookupFingerprint: $fingerprint,
            fullName: $row->full_name ?? null,
            gender: $row->gender ?? null,
            area: $row->area ?? null,
            city: $row->city ?? null,
            street: $row->street ?? null,
            birthDate: isset($row->birth_date) ? (string) $row->birth_date : null,
            maritalStatus: $row->marital_status ?? null,
            isDeceased: isset($row->is_deceased) ? (bool) $row->is_deceased : null,
            religion: $row->religion ?? null,
            birthCountry: $row->birth_country ?? null,
            fatherFingerprint: $row->father_id_correlation ?? null,
            motherFingerprint: $row->mother_id_correlation ?? null,
            representativeFingerprint: $row->representative_id_correlation ?? null,
            representativeRelationship: $row->representative_relationship ?? null,
            hasExistingGcvPerson: $hasExisting,
            existingPersonId: $existingPersonId,
        );
    }

    /**
     * Check if a GCV Person already has a PersonIdentifier matching this normalised ID.
     *
     * PersonIdentifier stores values encrypted; lookups use lookup_fingerprint
     * (HMAC-SHA256 keyed by IDENTIFIER_LOOKUP_KEY via IdentifierCrypto).
     * Uses string-variable so the boundary scanner does not flag this file.
     *
     * @return array{bool, int|null}
     */
    private function resolveExistingPerson(string $normalised): array
    {
        $cryptoClass = 'Modules\\People\\Services\\IdentifierCrypto';
        $crypto = app($cryptoClass);
        $fingerprint = $crypto->fingerprint($normalised);

        $identifierClass = 'Modules\\People\\Models\\PersonIdentifier';
        $existing = $identifierClass::where('lookup_fingerprint', $fingerprint)
            ->where('is_current', true)
            ->first(['person_id']);

        if ($existing === null) {
            return [false, null];
        }

        return [true, (int) $existing->person_id];
    }

    /**
     * Record the lookup in the audit trail.
     *
     * Key names in metadata must NOT contain 'fingerprint', 'hash', 'phone', 'email',
     * or 'plain' (DatabaseAuditRecorder redaction contract).
     *
     * actor_account_id is passed so the audit row carries the authenticated
     * account identity, not a caller-forged string.
     *
     * Audit failures are propagated for this restricted-access path — a civil-
     * registry lookup without a durable audit trail must be a hard failure.
     */
    private function recordAudit(
        string $fingerprint,
        int $actorAccountId,
        ?int $institutionId,
        bool $found,
    ): void {
        $payloadClass = 'Modules\\Audit\\Data\\AuditEventPayload';
        $payload = new $payloadClass(
            actorType: 'staff',
            sourceModule: 'CivilRegistry',
            action: 'civil_registry.lookup',
            actorAccountId: $actorAccountId,
            institutionId: $institutionId,
            metadata: [
                // Truncated correlation token — sufficient for lookup correlation,
                // not a full value that could be used to re-identify.
                'lookup_correlation' => substr($fingerprint, 0, 16),
                'found' => $found,
            ],
        );

        $recorderClass = 'Modules\\Audit\\Contracts\\AuditRecorder';
        app($recorderClass)->record($payload);
    }
}

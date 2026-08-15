<?php

declare(strict_types=1);

namespace Modules\CivilRegistry\Data;

/**
 * Result of a civil-registry lookup.
 *
 * A null match means no record was found for the supplied fingerprint.
 * A non-null match carries the raw registry fields plus a flag indicating
 * whether a GCV Person with a matching identifier already exists.
 *
 * PRIVACY: This DTO never carries the raw national_id that was looked up.
 * The caller receives only the demographic fields and the fingerprint used
 * internally to perform the lookup.
 *
 * All demographic fields are nullable because source data quality varies.
 *
 * is_deceased is advisory only — it NEVER changes a Person's lifecycle status.
 */
final class CivilRegistryMatch
{
    public function __construct(
        /** Record ID in the civil registry table. */
        public readonly int $registryRecordId,

        /** The lookup_fingerprint used to retrieve this record (not the raw ID). */
        public readonly string $lookupFingerprint,

        public readonly ?string $fullName,
        public readonly ?string $gender,
        public readonly ?string $area,
        public readonly ?string $city,
        public readonly ?string $street,
        public readonly ?string $birthDate,
        public readonly ?string $maritalStatus,
        public readonly ?bool $isDeceased,
        public readonly ?string $religion,
        public readonly ?string $birthCountry,

        /**
         * SHA-256 fingerprints of father/mother IDs (never raw IDs).
         * The caller may use these to trigger additional lookups.
         */
        public readonly ?string $fatherFingerprint,
        public readonly ?string $motherFingerprint,
        public readonly ?string $representativeFingerprint,
        public readonly ?string $representativeRelationship,

        /** Whether a GCV Person already exists with a matching identifier. */
        public readonly bool $hasExistingGcvPerson,

        /** The existing Person's ID if hasExistingGcvPerson is true. */
        public readonly ?int $existingPersonId,
    ) {}
}

<?php

declare(strict_types=1);

namespace Modules\CivilRegistry\Contracts;

use Modules\CivilRegistry\Data\CivilRegistryMatch;

/**
 * Public surface of the CivilRegistry module for ID lookups.
 *
 * The concrete implementation queries the registry dataset.
 * The null implementation (used in tests and when the module is disabled)
 * always returns null without touching the database.
 *
 * PRIVACY CONTRACT: the raw national_id must NEVER appear in log messages,
 * exceptions, or audit payloads. Only the HMAC lookup_fingerprint is used
 * for queries and audit correlation.
 *
 * AUTHORIZATION: authorization must be checked by the caller (LookupByNationalId
 * action) before invoking this contract. The contract accepts the resolved
 * actorAccountId for audit trail purposes only.
 */
interface CivilRegistryLookupContract
{
    /**
     * Look up a national ID in the civil registry.
     *
     * @param  string  $rawNationalId  Raw input, will be normalised and HMAC-fingerprinted internally.
     * @param  int  $actorAccountId  Authenticated actor account ID for audit trail.
     * @param  int|null  $institutionId  Institution context for audit, if available.
     * @return CivilRegistryMatch|null null if no match or registry is disabled.
     *
     * @throws \InvalidArgumentException if the ID cannot be normalised to 9 digits.
     */
    public function lookup(
        #[\SensitiveParameter] string $rawNationalId,
        int $actorAccountId,
        ?int $institutionId = null,
    ): ?CivilRegistryMatch;
}

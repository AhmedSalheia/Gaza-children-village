<?php

declare(strict_types=1);

namespace Modules\CivilRegistry\Services;

use Modules\CivilRegistry\Contracts\CivilRegistryLookupContract;
use Modules\CivilRegistry\Data\CivilRegistryMatch;

/**
 * No-op implementation of CivilRegistryLookupContract.
 *
 * Bound in the service container when:
 *  - `config('civil-registry.enabled')` is false (default in testing), OR
 *  - The application is running unit tests.
 *
 * Always returns null, making it safe to use in test suites without
 * any database seeding of civil-registry data.
 *
 * Still validates the national ID format so callers can rely on the
 * InvalidArgumentException contract even in tests.
 */
final class NullCivilRegistryLookup implements CivilRegistryLookupContract
{
    public function lookup(
        #[\SensitiveParameter] string $rawNationalId,
        int $actorAccountId,
        ?int $institutionId = null,
    ): ?CivilRegistryMatch {
        // Validate format so the normalisation contract is preserved.
        $normalizerClass = 'Modules\\People\\Services\\PalestinianIdNormalizer';
        $normalizer = new $normalizerClass;
        $normalizer->normalize($rawNationalId); // throws InvalidArgumentException on bad format

        return null;
    }
}

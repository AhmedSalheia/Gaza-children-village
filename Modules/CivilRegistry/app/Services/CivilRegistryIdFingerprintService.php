<?php

declare(strict_types=1);

namespace Modules\CivilRegistry\Services;

use RuntimeException;
use SensitiveParameter;

/**
 * HMAC-SHA256 fingerprinting for Gaza civil-registry national IDs.
 *
 * A 9-digit Palestinian national ID occupies only 10^9 possible values —
 * a preimage table would be trivial with plain SHA-256. HMAC with a
 * dedicated secret key makes offline enumeration infeasible.
 *
 * Key source: config('civil-registry.lookup_hmac_key')
 * Environment variable: CIVIL_REGISTRY_HMAC_KEY
 *
 * MUST differ from IDENTIFIER_LOOKUP_KEY (People module) and APP_KEY.
 * In non-production environments a test-stable fallback is used so test
 * suites do not require the secret to be provisioned.
 *
 * NEVER log, expose, or compare the raw national ID. Only pass the HMAC
 * output to other layers.
 */
final class CivilRegistryIdFingerprintService
{
    private readonly string $hmacKey;

    public function __construct()
    {
        $key = config('civil-registry.lookup_hmac_key');

        if (empty($key)) {
            if (app()->environment('production')) {
                throw new RuntimeException(
                    'CIVIL_REGISTRY_HMAC_KEY is not configured. '
                    .'Set this environment variable before running in production.'
                );
            }

            // Non-production fallback — stable for tests, explicitly insecure.
            // Must not equal the People module fallback key.
            $key = 'test-only-civil-registry-hmac-key-not-for-production';
        }

        $this->hmacKey = $key;
    }

    /**
     * Compute the HMAC-SHA256 fingerprint for a normalised (ASCII-digit) national ID.
     *
     * @param  string  $normalised  Output of PalestinianIdNormalizer::normalize().
     */
    public function fingerprint(#[SensitiveParameter] string $normalised): string
    {
        return hash_hmac('sha256', $normalised, $this->hmacKey);
    }
}

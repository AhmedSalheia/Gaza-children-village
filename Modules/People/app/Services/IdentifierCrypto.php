<?php

declare(strict_types=1);

namespace Modules\People\Services;

use Illuminate\Contracts\Encryption\Encrypter;
use RuntimeException;
use SensitiveParameter;

/**
 * Encryption and deterministic fingerprinting for sensitive identifier values.
 *
 * Two operations are provided:
 *
 *  - encrypt($plaintext): returns a Laravel-encrypted ciphertext for storage.
 *  - decrypt($ciphertext): returns the plaintext (authorized reveal only).
 *  - fingerprint($normalized): returns an HMAC-SHA256 hex string keyed with
 *    IDENTIFIER_LOOKUP_KEY. Used for database lookup without decryption.
 *
 * The fingerprint key MUST be set via the `people.identifier_lookup_key` config
 * key (sourced from the IDENTIFIER_LOOKUP_KEY environment variable). If the key
 * is absent and the application environment is `production`, this service throws
 * immediately. In non-production environments, a test-stable fallback is used so
 * that test suites do not require secrets.
 *
 * NEVER use APP_KEY as the fingerprint key.
 * NEVER log the plaintext, ciphertext, or fingerprint.
 */
final class IdentifierCrypto
{
    private string $lookupKey;

    public function __construct(private readonly Encrypter $encrypter)
    {
        $key = config('people.identifier_lookup_key');

        if (empty($key)) {
            if (app()->environment('production')) {
                throw new RuntimeException(
                    'IDENTIFIER_LOOKUP_KEY is not configured. '.
                    'Set this environment variable before running in production.'
                );
            }

            // Non-production fallback — stable for tests, explicitly insecure.
            $key = 'test-only-identifier-lookup-key-not-for-production';
        }

        $this->lookupKey = $key;
    }

    /**
     * Encrypt a plaintext identifier value for storage.
     *
     * @return string Laravel-serialized ciphertext.
     */
    public function encrypt(#[SensitiveParameter] string $plaintext): string
    {
        return $this->encrypter->encrypt($plaintext);
    }

    /**
     * Decrypt a stored ciphertext.
     *
     * Call only from explicitly authorized reveal actions.
     *
     * @return string The original plaintext value.
     */
    public function decrypt(string $ciphertext): string
    {
        return $this->encrypter->decrypt($ciphertext);
    }

    /**
     * Derive the deterministic lookup fingerprint for a normalized value.
     *
     * The fingerprint is an HMAC-SHA256 hex digest keyed with the configured
     * IDENTIFIER_LOOKUP_KEY. Equal normalized values produce equal fingerprints
     * and can be found with an exact-match database query.
     */
    public function fingerprint(#[SensitiveParameter] string $normalized): string
    {
        return hash_hmac('sha256', $normalized, $this->lookupKey);
    }

    /**
     * Mask a normalized identifier for safe display.
     *
     * Returns the last 4 characters preceded by 'X' padding to the original length.
     * Minimum output length is 4 characters.
     */
    public function mask(#[SensitiveParameter] string $normalized): string
    {
        $len = mb_strlen($normalized);
        $visible = min(4, $len);
        $masked = max(0, $len - $visible);

        return str_repeat('X', $masked).mb_substr($normalized, -$visible);
    }
}

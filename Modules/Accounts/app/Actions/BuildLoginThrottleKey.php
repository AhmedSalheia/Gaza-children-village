<?php

declare(strict_types=1);

namespace Modules\Accounts\Actions;

use Modules\Accounts\Data\ThrottleKeys;

/**
 * Derives portal-scoped, HMAC-keyed rate-limiter cache keys for a login attempt.
 *
 * Raw login identifiers and raw IP addresses NEVER appear in the cache store.
 * Each key is portal-prefixed so throttle events in one portal cannot consume
 * another portal's quota.
 *
 * The same HMAC key material is used to generate the short fingerprints stored
 * in authentication events, making it possible to correlate events with
 * throttle state without storing the raw identifier anywhere.
 */
final class BuildLoginThrottleKey
{
    public function __invoke(string $portal, string $normalizedIdentifier, string $ip): ThrottleKeys
    {
        // app.key is the HMAC secret. It is never written to the event log.
        $secret = config('app.key', '');

        $idHash = hash_hmac('sha256', $normalizedIdentifier, $secret);
        $ipHash = hash_hmac('sha256', $ip, $secret);

        return new ThrottleKeys(
            // Full hash in the cache key so collisions are negligible.
            identifierKey: "login.{$portal}.id.{$idHash}",
            ipKey: "login.{$portal}.ip.{$ipHash}",

            // Truncated to 16 hex chars for event log storage (still 64 bits of entropy).
            identifierFingerprint: substr($idHash, 0, 16),
            ipFingerprint: substr($ipHash, 0, 16),
        );
    }
}

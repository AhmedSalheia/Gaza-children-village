<?php

declare(strict_types=1);

namespace Modules\Accounts\Data;

use Modules\Accounts\Actions\BuildLoginThrottleKey;

/**
 * HMAC-derived rate-limiter cache keys for a single login attempt.
 *
 * Keys are opaque fingerprints — raw login identifiers and raw IP addresses
 * never appear in the cache store. Each key is portal-scoped so that throttle
 * events in one portal do not consume another portal's quota.
 *
 * @see BuildLoginThrottleKey
 */
final readonly class ThrottleKeys
{
    public function __construct(
        /** Per-portal, per-identifier cache key for the identifier-specific rate limiter. */
        public string $identifierKey,

        /** Per-portal, per-IP cache key for the IP-level rate limiter. */
        public string $ipKey,

        /** Truncated HMAC fingerprint of the identifier — safe to store in events. */
        public string $identifierFingerprint,

        /** Truncated HMAC fingerprint of the IP address — safe to store in events. */
        public string $ipFingerprint,
    ) {}
}

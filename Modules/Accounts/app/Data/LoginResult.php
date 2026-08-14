<?php

declare(strict_types=1);

namespace Modules\Accounts\Data;

/**
 * Result of an AuthenticatePortalAccount attempt.
 *
 * Carries only the outcome — no account object, no raw identifier, no session
 * data. Controllers inspect the outcome and produce the appropriate HTTP
 * response; they never receive credentials or account details.
 */
final readonly class LoginResult
{
    private function __construct(
        private bool $throttled,
        private bool $failed,
        private bool $succeeded,

        /**
         * Seconds until the rate-limit window resets.
         * Non-null only when $throttled is true.
         */
        public readonly ?int $retryAfter,
    ) {}

    public static function throttled(int $retryAfter): self
    {
        return new self(throttled: true, failed: false, succeeded: false, retryAfter: $retryAfter);
    }

    public static function failed(): self
    {
        return new self(throttled: false, failed: true, succeeded: false, retryAfter: null);
    }

    public static function success(): self
    {
        return new self(throttled: false, failed: false, succeeded: true, retryAfter: null);
    }

    public function isThrottled(): bool
    {
        return $this->throttled;
    }

    public function isFailed(): bool
    {
        return $this->failed;
    }

    public function isSucceeded(): bool
    {
        return $this->succeeded;
    }
}

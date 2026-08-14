<?php

declare(strict_types=1);

namespace Modules\Accounts\Enums;

/**
 * The outcome of a ValidateChallenge attempt.
 *
 * Callers should map all non-Valid results to a single generic public error
 * message so that no internal challenge state is disclosed.
 */
enum ChallengeValidationResult
{
    /** Token matched; challenge is now consumed. */
    case Valid;

    /** No challenge exists for this account+portal+purpose. */
    case NotFound;

    /** The submitted token did not match. */
    case InvalidToken;

    /** The challenge has been explicitly revoked. */
    case Revoked;

    /** The challenge expired before verification. */
    case Expired;

    /** Maximum verification attempts reached; challenge is closed. */
    case Exhausted;

    /** The challenge was already consumed (used more than once). */
    case AlreadyConsumed;

    public function isValid(): bool
    {
        return $this === self::Valid;
    }
}

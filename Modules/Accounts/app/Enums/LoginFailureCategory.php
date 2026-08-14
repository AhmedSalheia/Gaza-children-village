<?php

declare(strict_types=1);

namespace Modules\Accounts\Enums;

/**
 * Opaque failure categories for authentication-event analysis.
 *
 * These categories are stored in authentication_events.failure_category and
 * are used by security dashboards to distinguish failure types without exposing
 * account-existence information to end users.
 *
 * The public-facing error message is always identical regardless of category.
 */
enum LoginFailureCategory: string
{
    /**
     * The submitted identifier/password combination was invalid.
     * Used when the account was not found OR the password did not match.
     * Never reveal which sub-condition triggered this.
     */
    case BadCredentials = 'bad_credentials';

    /**
     * The account was found and the password matched, but the account status
     * does not permit authentication (pending, suspended, locked, or revoked).
     */
    case AccountNotActive = 'account_not_active';

    /**
     * The request was rejected by the rate limiter before credentials were checked.
     */
    case Throttled = 'throttled';
}

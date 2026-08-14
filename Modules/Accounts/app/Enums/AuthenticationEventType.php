<?php

declare(strict_types=1);

namespace Modules\Accounts\Enums;

/**
 * Supported authentication security-event types.
 *
 * These are the only event types the RecordAuthenticationEvent action will
 * persist. F18 business-audit infrastructure will bridge from this enum
 * without requiring changes to the authentication layer.
 */
enum AuthenticationEventType: string
{
    case LoginSucceeded = 'login_succeeded';
    case LoginFailed = 'login_failed';
    case LoginThrottled = 'login_throttled';
    case Logout = 'logout';
    case SessionsRevoked = 'sessions_revoked';

    public function isSuccess(): bool
    {
        return match ($this) {
            self::LoginSucceeded,
            self::Logout,
            self::SessionsRevoked => true,
            default => false,
        };
    }
}

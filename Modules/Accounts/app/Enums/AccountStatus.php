<?php

declare(strict_types=1);

namespace Modules\Accounts\Enums;

enum AccountStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Locked = 'locked';
    case Revoked = 'revoked';

    /**
     * Whether an account with this status may authenticate or retain an authenticated session.
     * Only Active accounts are permitted.
     */
    public function canAuthenticate(): bool
    {
        return $this === self::Active;
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Locked => 'Locked',
            self::Revoked => 'Revoked',
        };
    }
}

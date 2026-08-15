<?php

declare(strict_types=1);

namespace Modules\Students\Enums;

enum GuardianLifecycleStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Deceased = 'deceased';

    public function isOperational(): bool
    {
        return $this === self::Active;
    }
}

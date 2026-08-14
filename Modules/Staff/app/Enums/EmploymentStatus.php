<?php

declare(strict_types=1);

namespace Modules\Staff\Enums;

enum EmploymentStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Ended = 'ended';

    public function isOperational(): bool
    {
        return $this === self::Active;
    }
}

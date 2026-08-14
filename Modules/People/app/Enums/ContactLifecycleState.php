<?php

declare(strict_types=1);

namespace Modules\People\Enums;

enum ContactLifecycleState: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Inactive = 'inactive';

    public function isActive(): bool
    {
        return $this !== self::Inactive;
    }

    public function isVerified(): bool
    {
        return $this === self::Verified;
    }
}

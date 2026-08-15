<?php

declare(strict_types=1);

namespace Modules\Students\Enums;

enum VerificationStatus: string
{
    case Unverified = 'unverified';
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';

    public function isVerified(): bool
    {
        return $this === self::Verified;
    }
}

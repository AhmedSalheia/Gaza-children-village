<?php

declare(strict_types=1);

namespace Modules\Authorization\Data;

enum ActorCategory: string
{
    case AdminAccount = 'admin_account';
    case StaffAccount = 'staff_account';
    case GuardianAccount = 'guardian_account';
    case System = 'system';

    public function isCompatibleWith(Portal $portal): bool
    {
        return match ($this) {
            self::AdminAccount => $portal === Portal::Admin,
            self::StaffAccount => $portal === Portal::Staff,
            self::GuardianAccount => $portal === Portal::Guardian,
            self::System => true,
        };
    }
}

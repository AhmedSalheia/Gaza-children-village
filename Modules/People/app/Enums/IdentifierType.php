<?php

declare(strict_types=1);

namespace Modules\People\Enums;

enum IdentifierType: string
{
    case PsNationalId = 'ps_national_id';
    case Passport = 'passport';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PsNationalId => 'Palestinian National ID',
            self::Passport => 'Passport',
            self::Other => 'Other',
        };
    }

    public function requiresNormalization(): bool
    {
        return $this === self::PsNationalId;
    }
}

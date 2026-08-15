<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Enums;

enum MarkExceptionStatus: string
{
    case Absent  = 'absent';
    case Exempt  = 'exempt';
    case Medical = 'medical';

    public function labelAr(): string
    {
        return match ($this) {
            self::Absent  => 'غائب',
            self::Exempt  => 'معفى',
            self::Medical => 'إعفاء طبي',
        };
    }
}

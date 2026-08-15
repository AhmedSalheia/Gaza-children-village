<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Enums;

enum ClassGroupLifecycleStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    public function canArchive(): bool
    {
        return $this !== self::Archived;
    }

    public function isOperational(): bool
    {
        return $this === self::Active;
    }
}

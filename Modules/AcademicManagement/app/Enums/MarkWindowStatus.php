<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Enums;

enum MarkWindowStatus: string
{
    case Scheduled = 'scheduled';
    case Open      = 'open';
    case Extended  = 'extended';
    case Closed    = 'closed';
    case Cancelled = 'cancelled';

    public function isOpen(): bool
    {
        return $this === self::Open || $this === self::Extended;
    }

    public function isTerminal(): bool
    {
        return $this === self::Closed || $this === self::Cancelled;
    }

    public function canOpen(): bool
    {
        return $this === self::Scheduled;
    }

    public function canExtend(): bool
    {
        return $this->isOpen();
    }

    public function canClose(): bool
    {
        return $this->isOpen() || $this === self::Scheduled;
    }
}

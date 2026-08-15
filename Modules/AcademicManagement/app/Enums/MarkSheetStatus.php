<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Enums;

enum MarkSheetStatus: string
{
    case Draft      = 'draft';
    case Submitted  = 'submitted';
    case Returned   = 'returned';
    case Verified   = 'verified';
    case Approved   = 'approved';
    case Published  = 'published';
    case Superseded = 'superseded';

    /** Teacher may edit marks. */
    public function isEditable(): bool
    {
        return $this === self::Draft || $this === self::Returned;
    }

    /** Sheet has reached a terminal publication state. */
    public function isPublished(): bool
    {
        return $this === self::Published || $this === self::Superseded;
    }

    public function canSubmit(): bool
    {
        return $this->isEditable();
    }

    public function canReturn(): bool
    {
        return $this === self::Submitted;
    }

    public function canVerify(): bool
    {
        return $this === self::Submitted;
    }

    public function canApprove(): bool
    {
        return $this === self::Verified;
    }

    public function canPublish(): bool
    {
        return $this === self::Approved;
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::Draft      => 'مسودة',
            self::Submitted  => 'مقدم',
            self::Returned   => 'معاد',
            self::Verified   => 'موثق',
            self::Approved   => 'معتمد',
            self::Published  => 'منشور',
            self::Superseded => 'موقوف',
        };
    }
}

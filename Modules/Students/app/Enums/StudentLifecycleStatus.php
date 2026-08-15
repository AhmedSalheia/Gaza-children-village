<?php

declare(strict_types=1);

namespace Modules\Students\Enums;

/**
 * Lifecycle status for a StudentProfile.
 *
 * See ADR SR-student-profile-fields.md for the full transition table.
 * Terminal states: graduated, deceased (further mutations require explicit admin override).
 */
enum StudentLifecycleStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';
    case Withdrawn = 'withdrawn';
    case Graduated = 'graduated';
    case Deceased = 'deceased';

    /** States from which the profile may be activated. */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft => $target === self::Active,
            self::Active => in_array($target, [self::Inactive, self::Withdrawn, self::Graduated, self::Deceased], true),
            self::Inactive => $target === self::Active,
            self::Withdrawn => $target === self::Active,
            self::Graduated => false,
            self::Deceased => false,
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Graduated || $this === self::Deceased;
    }

    public function isOperational(): bool
    {
        return $this === self::Active;
    }
}

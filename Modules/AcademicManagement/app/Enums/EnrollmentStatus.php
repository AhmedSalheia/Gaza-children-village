<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Enums;

/**
 * Lifecycle status for a StudentEnrollment.
 *
 * draft       → Created but not yet activated; may be edited or cancelled.
 * active      → Student is actively enrolled for this semester.
 * completed   → Semester completed; awaiting promotion decision.
 * promoted    → Student promoted to next level (terminal for this enrollment).
 * repeating   → Student repeating this level (terminal; new enrollment created).
 * transferred → Student transferred out (terminal; new enrollment created elsewhere).
 * withdrawn   → Student withdrew (terminal for this enrollment).
 * suspended   → Temporarily suspended; may return to active.
 * graduated   → Student graduated (terminal).
 */
enum EnrollmentStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Completed = 'completed';
    case Promoted = 'promoted';
    case Repeating = 'repeating';
    case Transferred = 'transferred';
    case Withdrawn = 'withdrawn';
    case Suspended = 'suspended';
    case Graduated = 'graduated';

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Promoted,
            self::Repeating,
            self::Transferred,
            self::Withdrawn,
            self::Graduated,
        ], true);
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function canBeWithdrawn(): bool
    {
        return in_array($this, [self::Draft, self::Active], true);
    }

    public function canBeSuspended(): bool
    {
        return $this === self::Active;
    }

    public function canBeCompleted(): bool
    {
        return $this === self::Active;
    }

    public function canBeActivated(): bool
    {
        return $this === self::Draft;
    }
}

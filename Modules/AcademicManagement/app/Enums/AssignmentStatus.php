<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Enums;

/**
 * Lifecycle status for TeachingAssignment and HomeroomAssignment rows.
 *
 * Rows are never deleted — status tracks how the assignment ended.
 *
 * active      — currently in effect.
 * ended       — manually ended (teacher left class/subject, semester closed, etc.).
 * superseded  — replaced by a new assignment (swap/replace workflow).
 */
enum AssignmentStatus: string
{
    case Active = 'active';
    case Ended = 'ended';
    case Superseded = 'superseded';

    public function isTerminal(): bool
    {
        return $this !== self::Active;
    }
}

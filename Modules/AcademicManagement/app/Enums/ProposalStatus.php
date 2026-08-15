<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Enums;

/**
 * The proposed outcome for a student at semester end.
 *
 * promoted     → Move to next academic level.
 * repeating    → Repeat the same academic level.
 * graduated    → Student has completed the final level.
 * transferred  → Transfer to another institution or class group.
 * unresolved   → Outcome not yet determined.
 */
enum ProposalStatus: string
{
    case Promoted = 'promoted';
    case Repeating = 'repeating';
    case Graduated = 'graduated';
    case Transferred = 'transferred';
    case Unresolved = 'unresolved';
}

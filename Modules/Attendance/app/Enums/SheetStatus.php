<?php

declare(strict_types=1);

namespace Modules\Attendance\Enums;

/**
 * Lifecycle status for a student attendance sheet.
 *
 * State machine:
 *   draft     → submitted   (teacher submits for secretary review)
 *   submitted → returned    (secretary returns to teacher for correction)
 *   returned  → submitted   (teacher resubmits after editing)
 *   submitted → verified    (secretary approves)
 *   verified  → reopened    (secretary/principal reopens for post-verification correction)
 *   reopened  → verified    (secretary re-verifies after making corrections)
 *
 * "returned" goes back to draft-equivalent edit mode; the status is kept distinct
 * so the UI can show the return reason and highlight the pending correction.
 *
 * "reopened" is in-place on the same sheet (not a new row). Corrections are
 * made via CorrectVerifiedAttendance, then re-closed by VerifySheet which
 * accepts both 'submitted' and 'reopened' as valid source states.
 */
enum SheetStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Returned = 'returned';
    case Verified = 'verified';
    case Reopened = 'reopened';

    /** Whether the sheet is editable by the teacher (can update records). */
    public function isEditable(): bool
    {
        return match ($this) {
            self::Draft, self::Returned => true,
            default => false,
        };
    }

    /** Whether the sheet is in a terminal read-only state. */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::Verified => true,
            default => false,
        };
    }

    /** Whether corrections (CorrectVerifiedAttendance) are allowed. */
    public function allowsCorrection(): bool
    {
        return $this === self::Reopened;
    }

    /**
     * Whether the sheet is awaiting secretary action (verify or return).
     *
     * Both 'submitted' (initial review) and 'reopened' (re-verification after
     * correction) require secretary action to close. VerifySheet accepts both.
     */
    public function awaitingReview(): bool
    {
        return match ($this) {
            self::Submitted, self::Reopened => true,
            default => false,
        };
    }
}

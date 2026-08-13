<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Enums;

/**
 * Lifecycle status for AcademicYear and Semester.
 *
 * Persisted as a bounded string column (never a database ENUM).
 * PHP-level validation is the primary enforcement boundary; all transitions
 * occur through explicit application actions.
 *
 * Approved transitions:
 *
 *   draft    → open      (OpenAcademicYear / OpenSemester)
 *   open     → closed    (CloseAcademicYear / CloseSemester)
 *   closed   → open      (ReopenAcademicYear / ReopenSemester — requires reason)
 *   closed   → archived  (ArchiveAcademicYear / ArchiveSemester)
 *   archived → (none)    terminal for ordinary actions
 *
 * Additional business rules enforced in actions (not captured by this enum alone):
 *
 *   - Only one academic year may be open per organization.
 *   - A year must have at least one semester before opening.
 *   - Opening a year does not automatically open its semesters.
 *   - A semester may open only while its parent year is open.
 *   - Only one semester may be open within an academic year.
 *   - A year may close only when all semesters are closed or archived.
 *   - An archived year prevents ordinary mutation of its semesters.
 *   - No lifecycle transition silently cascades to children.
 *
 * F18 will persist actor-aware audit history for lifecycle transitions;
 * this enum carries no actor information.
 */
enum AcademicStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Closed = 'closed';
    case Archived = 'archived';

    /**
     * Whether this status is a terminal state for ordinary actions.
     *
     * Archived records remain readable but cannot be mutated through ordinary
     * application behavior.
     */
    public function isTerminal(): bool
    {
        return $this === self::Archived;
    }

    /**
     * Whether dates and sequence may be changed while in this status.
     *
     * Structural changes (dates, sequence) are permitted only while draft.
     * Corrections after opening require a future controlled workflow.
     */
    public function allowsDateChange(): bool
    {
        return $this === self::Draft;
    }

    /**
     * Whether display names (name_en, name_ar) may be changed in this status.
     *
     * Name changes are permitted unless the record itself is archived.
     * Callers also check the parent (academic year) status for semesters.
     */
    public function allowsNameChange(): bool
    {
        return $this !== self::Archived;
    }
}

<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Actions;

use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\InstitutionSemester;
use Modules\AcademicCalendar\Models\Semester;
use RuntimeException;

/**
 * Transition a semester from Open to Closed.
 *
 * F08 global-semester closure integration:
 *
 *   Closure is rejected while any InstitutionSemester linked to this global
 *   semester remains in Draft or Open status.
 *
 *   - Open institution semesters must be explicitly closed first via
 *     CloseInstitutionSemester.
 *   - Unused draft institution semesters must be explicitly archived first via
 *     ArchiveInstitutionSemester (with a reason).
 *
 *   This prevents silently orphaning active institution operations when the
 *   global semester is closed.
 *
 * Authorization boundary: this action does not check actor permissions.
 * Future callers must pass through the F17/F19 policy kernel.
 */
final readonly class CloseSemester
{
    public function execute(Semester $semester): Semester
    {
        if ($semester->status !== AcademicStatus::Open) {
            throw new RuntimeException(
                "Only an open semester can be closed. '{$semester->code}' is {$semester->status->value}."
            );
        }

        $blockingCount = InstitutionSemester::where('semester_id', $semester->id)
            ->whereIn('status', [AcademicStatus::Draft->value, AcademicStatus::Open->value])
            ->count();

        if ($blockingCount > 0) {
            throw new RuntimeException(
                "Cannot close semester '{$semester->code}': {$blockingCount} institution semester(s) are still Draft or Open. "
                .'Close or archive them before closing the global semester.'
            );
        }

        $semester->status = AcademicStatus::Closed;
        $semester->save();

        return $semester;
    }
}

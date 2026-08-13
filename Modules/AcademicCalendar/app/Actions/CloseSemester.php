<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Actions;

use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\Semester;
use RuntimeException;

/**
 * Transition a semester from Open to Closed.
 *
 * Closing a semester does not cascade to any child records. No child records
 * are deleted or modified by this action.
 *
 * F08 InstitutionSemester records are separate entities (not managed here).
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

        $semester->status = AcademicStatus::Closed;
        $semester->save();

        return $semester;
    }
}

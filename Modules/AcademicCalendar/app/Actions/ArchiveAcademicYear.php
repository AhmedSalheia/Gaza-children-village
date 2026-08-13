<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Actions;

use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\AcademicYear;
use RuntimeException;

/**
 * Transition an academic year from Closed to Archived.
 *
 * Archived is a terminal status for ordinary actions. Archived academic years
 * remain readable but cannot be mutated or transitioned further through ordinary
 * application behavior.
 *
 * Archiving does NOT automatically rewrite or delete child semesters. No
 * cascade occurs. Administrators must manage semester states separately.
 *
 * An archived year subsequently prevents ordinary mutation of its semesters
 * (enforced by semester actions that check the parent year status).
 *
 * Authorization boundary: this action does not check actor permissions.
 * Future callers must pass through the F17/F19 policy kernel.
 */
final readonly class ArchiveAcademicYear
{
    public function execute(AcademicYear $year): AcademicYear
    {
        if ($year->status !== AcademicStatus::Closed) {
            throw new RuntimeException(
                "Only a closed academic year can be archived. '{$year->code}' is {$year->status->value}."
            );
        }

        $year->status = AcademicStatus::Archived;
        $year->save();

        return $year;
    }
}

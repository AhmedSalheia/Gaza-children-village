<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Actions;

use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\InstitutionSemester;
use RuntimeException;

/**
 * Transition an institution semester from Open to Closed.
 *
 * Closing does not cascade to or modify any child operational periods.
 *
 * Authorization boundary: this action does not check actor permissions.
 * Future callers must pass through the F17/F19 policy kernel.
 */
final readonly class CloseInstitutionSemester
{
    public function execute(InstitutionSemester $is): InstitutionSemester
    {
        if ($is->status !== AcademicStatus::Open) {
            throw new RuntimeException(
                "Only an open institution semester can be closed. Current status: {$is->status->value}."
            );
        }

        $is->status = AcademicStatus::Closed;
        $is->save();

        return $is;
    }
}

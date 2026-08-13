<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Actions;

use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\OperationalPeriod;
use RuntimeException;

/**
 * Deactivate an operational period.
 *
 * Deactivation is the alternative to hard deletion. Deactivated periods remain
 * in the database for historical reference and are excluded from active-period
 * queries (overlap checks, open-semester validation).
 *
 * Ordinary deactivation is only allowed while the parent institution semester
 * is Draft. Changes to open or historical period configurations require a
 * future controlled correction workflow (outside F08 scope).
 *
 * Authorization boundary: this action does not check actor permissions.
 * Future callers must pass through the F17/F19 policy kernel.
 */
final readonly class DeactivateOperationalPeriod
{
    public function execute(OperationalPeriod $period): OperationalPeriod
    {
        $is = $period->institutionSemester()->first();

        if ($is->status !== AcademicStatus::Draft) {
            throw new RuntimeException(
                "Operational periods can only be deactivated while the institution semester is Draft. Current status: {$is->status->value}."
            );
        }

        if (! $period->is_active) {
            throw new RuntimeException(
                "Period '{$period->code}' is already deactivated."
            );
        }

        $period->is_active = false;
        $period->save();

        return $period;
    }
}

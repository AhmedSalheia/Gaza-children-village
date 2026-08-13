<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Actions;

use Illuminate\Support\Facades\DB;
use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\AcademicYear;
use RuntimeException;

/**
 * Transition an academic year from Closed back to Open.
 *
 * Requires a non-empty reason for audit purposes. F18 will persist the
 * actor-aware audit history for this transition; F07 accepts and passes
 * the reason through for later integration.
 *
 * Validates:
 *   - Year is currently Closed.
 *   - A non-empty reason is provided.
 *   - No other academic year is already Open for the same organization.
 *
 * Runs in a DB transaction with the one-open-year check to prevent races.
 *
 * Authorization boundary: this action does not check actor permissions.
 * Future callers must pass through the F17/F19 policy kernel.
 */
final readonly class ReopenAcademicYear
{
    public function execute(AcademicYear $year, string $reason): AcademicYear
    {
        if ($year->status !== AcademicStatus::Closed) {
            throw new RuntimeException(
                "Only a closed academic year can be reopened. '{$year->code}' is {$year->status->value}."
            );
        }

        if (trim($reason) === '') {
            throw new RuntimeException(
                "A non-empty reason is required to reopen academic year '{$year->code}'."
            );
        }

        return DB::transaction(function () use ($year): AcademicYear {
            $alreadyOpen = AcademicYear::where('organization_id', $year->organization_id)
                ->where('status', AcademicStatus::Open->value)
                ->exists();

            if ($alreadyOpen) {
                throw new RuntimeException(
                    'Another academic year is already open for this organization. Close it before reopening this one.'
                );
            }

            $year->status = AcademicStatus::Open;
            $year->save();

            return $year;
        });
    }
}

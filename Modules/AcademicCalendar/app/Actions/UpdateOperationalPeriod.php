<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Actions;

use Modules\AcademicCalendar\Data\UpdateOperationalPeriodData;
use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\OperationalPeriod;
use RuntimeException;

/**
 * Update mutable fields on a draft institution semester's operational period.
 *
 * Only name_en, name_ar, starts_at, and ends_at may be updated. Code and
 * sequence are stable identifiers and cannot be changed after creation.
 *
 * Validates:
 *   - The parent institution semester is Draft.
 *   - The period is active (deactivated periods are historical and immutable).
 *   - If either time is supplied, the resolved starts_at < ends_at.
 *   - Active sibling periods do not overlap after the update.
 *
 * Authorization boundary: this action does not check actor permissions.
 * Future callers must pass through the F17/F19 policy kernel.
 */
final readonly class UpdateOperationalPeriod
{
    public function execute(OperationalPeriod $period, UpdateOperationalPeriodData $data): OperationalPeriod
    {
        $is = $period->institutionSemester()->first();

        if ($is->status !== AcademicStatus::Draft) {
            throw new RuntimeException(
                "Operational periods can only be updated while the institution semester is Draft. Current status: {$is->status->value}."
            );
        }

        if (! $period->is_active) {
            throw new RuntimeException(
                "Deactivated period '{$period->code}' is historical and cannot be updated."
            );
        }

        // Resolve the effective time values after the partial update.
        $effectiveStartsAt = $data->startsAt ?? $period->starts_at;
        $effectiveEndsAt = $data->endsAt ?? $period->ends_at;

        if (strtotime($effectiveStartsAt) >= strtotime($effectiveEndsAt)) {
            throw new RuntimeException(
                "starts_at must be earlier than ends_at (overnight periods not supported in F08). Got: {$effectiveStartsAt}–{$effectiveEndsAt}."
            );
        }

        // Check for overlap against active siblings, excluding this period itself.
        $overlapping = OperationalPeriod::where('institution_semester_id', $is->id)
            ->where('is_active', true)
            ->where('id', '!=', $period->id)
            ->where('starts_at', '<', $effectiveEndsAt)
            ->where('ends_at', '>', $effectiveStartsAt)
            ->exists();

        if ($overlapping) {
            throw new RuntimeException(
                "The updated time range {$effectiveStartsAt}–{$effectiveEndsAt} overlaps with an existing active period."
            );
        }

        if ($data->nameEn !== null) {
            $period->name_en = $data->nameEn;
        }

        if ($data->nameAr !== null) {
            $period->name_ar = $data->nameAr;
        }

        if ($data->startsAt !== null) {
            $period->starts_at = $data->startsAt;
        }

        if ($data->endsAt !== null) {
            $period->ends_at = $data->endsAt;
        }

        $period->save();

        return $period;
    }
}

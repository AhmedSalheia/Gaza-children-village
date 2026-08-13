<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Actions;

use Modules\AcademicCalendar\Data\CreateAcademicYearData;
use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\AcademicYear;
use RuntimeException;

/**
 * Create a new academic year for an organization.
 *
 * Validates:
 *   - Organization is active (inactive organizations may not receive new years).
 *   - starts_on < ends_on.
 *   - code is unique within the organization.
 *
 * The new academic year starts in Draft status. No semesters are created.
 * No calendar is seeded automatically; administrators create their own.
 *
 * The organization parameter is typed as object to avoid importing the
 * Organization model class across a non-public module surface. Callers
 * pass an Organization model instance. The action
 * accesses is_active, id, and code properties which are public on that model.
 *
 * Authorization boundary: this action does not check actor permissions.
 * Future callers must pass through the F17/F19 policy kernel.
 */
final readonly class CreateAcademicYear
{
    /**
     * @param  object  $organization  Organization model instance
     */
    public function execute(object $organization, CreateAcademicYearData $data): AcademicYear
    {
        if (! $organization->is_active) {
            throw new RuntimeException(
                "Cannot create academic year for inactive organization '{$organization->code}'."
            );
        }

        if ($data->startsOn >= $data->endsOn) {
            throw new RuntimeException(
                'Academic year start date must precede end date.'
            );
        }

        if (AcademicYear::where('organization_id', $organization->id)
            ->where('code', $data->code)
            ->exists()) {
            throw new RuntimeException(
                "Academic year code '{$data->code}' already exists for this organization."
            );
        }

        $year = new AcademicYear;
        $year->organization_id = $organization->id;
        $year->code = $data->code;
        $year->name_en = $data->nameEn;
        $year->name_ar = $data->nameAr;
        $year->starts_on = $data->startsOn;
        $year->ends_on = $data->endsOn;
        $year->status = AcademicStatus::Draft;
        $year->save();

        return $year;
    }
}

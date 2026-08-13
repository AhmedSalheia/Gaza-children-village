<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Actions;

use Illuminate\Database\Eloquent\Collection;
use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\InstitutionSemester;

/**
 * List institution semesters for an institution.
 *
 * Returns all institution semesters for the given institution ID, optionally
 * filtered by status. Archived records are included by default since they
 * remain readable for historical reference.
 *
 * Authorization boundary: this action does not check actor permissions.
 * Future callers must pass through the F17/F19 policy kernel.
 */
final readonly class ListInstitutionSemesters
{
    /**
     * @param  AcademicStatus|null  $status  When supplied, filter to this status only.
     * @return Collection<int, InstitutionSemester>
     */
    public function execute(int $institutionId, ?AcademicStatus $status = null): Collection
    {
        $query = InstitutionSemester::where('institution_id', $institutionId)
            ->orderBy('id');

        if ($status !== null) {
            $query->where('status', $status->value);
        }

        return $query->get();
    }
}

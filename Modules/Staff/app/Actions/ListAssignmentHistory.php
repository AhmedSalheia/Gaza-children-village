<?php

declare(strict_types=1);

namespace Modules\Staff\Actions;

use Illuminate\Database\Eloquent\Collection;
use Modules\Staff\Models\StaffInstitutionAssignment;
use Modules\Staff\Models\StaffProfile;

/**
 * Return the full assignment history for a staff member, oldest first.
 *
 * @return Collection<int, StaffInstitutionAssignment>
 */
final class ListAssignmentHistory
{
    /** @return Collection<int, StaffInstitutionAssignment> */
    public function __invoke(StaffProfile $profile): Collection
    {
        return StaffInstitutionAssignment::where('staff_profile_id', $profile->id)
            ->orderBy('started_on')
            ->orderBy('id')
            ->get();
    }
}

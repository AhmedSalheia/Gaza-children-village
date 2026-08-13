<?php

declare(strict_types=1);

namespace Modules\Organization\Actions;

use Modules\Organization\Models\Institution;

/**
 * Deactivates an institution without deleting it.
 *
 * Deactivation preserves the record and all historical references to it.
 * Inactive institutions remain queryable for historical reporting.
 *
 * This is an internal application service; future HTTP callers must be
 * authorized through the F17/F19 policy kernel.
 */
final readonly class DeactivateInstitution
{
    public function execute(Institution $institution): Institution
    {
        $institution->is_active = false;
        $institution->save();

        return $institution;
    }
}

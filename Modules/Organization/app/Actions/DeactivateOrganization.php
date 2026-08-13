<?php

declare(strict_types=1);

namespace Modules\Organization\Actions;

use Modules\Organization\Models\Organization;

/**
 * Deactivates an organization.
 *
 * Deactivation preserves the record and all historical references to it.
 * Records are never deleted. Inactive organizations remain queryable.
 *
 * This is an internal application service; future HTTP callers must be
 * authorized through the F17/F19 policy kernel.
 */
final readonly class DeactivateOrganization
{
    public function execute(Organization $organization): Organization
    {
        $organization->is_active = false;
        $organization->save();

        return $organization;
    }
}

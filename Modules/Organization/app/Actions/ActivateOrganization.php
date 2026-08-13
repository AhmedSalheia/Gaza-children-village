<?php

declare(strict_types=1);

namespace Modules\Organization\Actions;

use Modules\Organization\Models\Organization;

/**
 * Activates an organization.
 *
 * This is an internal application service; future HTTP callers must be
 * authorized through the F17/F19 policy kernel.
 */
final readonly class ActivateOrganization
{
    public function execute(Organization $organization): Organization
    {
        $organization->is_active = true;
        $organization->save();

        return $organization;
    }
}

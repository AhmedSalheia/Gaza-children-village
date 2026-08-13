<?php

declare(strict_types=1);

namespace Modules\Organization\Actions;

use Modules\Organization\Data\ChangeOrganizationNameData;
use Modules\Organization\Models\Organization;

/**
 * Changes an organization's display names.
 *
 * Only display names may be changed through this action. The stable code
 * is never modified. This is an internal application service; future HTTP
 * callers must be authorized through the F17/F19 policy kernel.
 */
final readonly class ChangeOrganizationName
{
    public function execute(Organization $organization, ChangeOrganizationNameData $data): Organization
    {
        $organization->name_en = $data->nameEn;
        $organization->name_ar = $data->nameAr;
        $organization->save();

        return $organization;
    }
}

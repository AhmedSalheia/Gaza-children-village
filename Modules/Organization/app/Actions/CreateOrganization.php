<?php

declare(strict_types=1);

namespace Modules\Organization\Actions;

use Illuminate\Database\UniqueConstraintViolationException;
use Modules\Organization\Data\CreateOrganizationData;
use Modules\Organization\Models\Organization;
use RuntimeException;

/**
 * Creates a new organization record.
 *
 * This is an internal application service. Future HTTP callers must be
 * authorized through the F17/F19 policy kernel before invoking this action.
 * This action does not register or assume any authorization bypass.
 *
 * Stable codes are assigned at creation and may not be changed afterwards.
 */
final readonly class CreateOrganization
{
    /**
     * @throws RuntimeException if the code is already taken
     */
    public function execute(CreateOrganizationData $data): Organization
    {
        try {
            $organization = new Organization;
            $organization->code = $data->code;
            $organization->name_en = $data->nameEn;
            $organization->name_ar = $data->nameAr;
            $organization->is_active = true;
            $organization->save();
        } catch (UniqueConstraintViolationException) {
            throw new RuntimeException(
                "An organization with code '{$data->code}' already exists."
            );
        }

        return $organization;
    }
}

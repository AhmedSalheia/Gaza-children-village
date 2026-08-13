<?php

declare(strict_types=1);

namespace Modules\Organization\Actions;

use Illuminate\Database\UniqueConstraintViolationException;
use Modules\Organization\Data\CreateInstitutionData;
use Modules\Organization\Models\Institution;
use RuntimeException;

/**
 * Creates a new institution record.
 *
 * This is an internal application service. Future HTTP callers must be
 * authorized through the F17/F19 policy kernel before invoking this action.
 * This action does not register or assume any authorization bypass.
 *
 * Stable codes are assigned at creation and may not be changed afterwards.
 */
final readonly class CreateInstitution
{
    /**
     * @throws RuntimeException if the code is already taken
     */
    public function execute(CreateInstitutionData $data): Institution
    {
        try {
            $institution = new Institution;
            $institution->code = $data->code;
            $institution->organization_id = $data->organizationId;
            $institution->institution_type_id = $data->institutionTypeId;
            $institution->name_en = $data->nameEn;
            $institution->name_ar = $data->nameAr;
            $institution->is_active = true;
            $institution->save();
        } catch (UniqueConstraintViolationException) {
            throw new RuntimeException(
                "An institution with code '{$data->code}' already exists."
            );
        }

        return $institution;
    }
}

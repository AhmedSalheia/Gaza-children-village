<?php

declare(strict_types=1);

namespace Modules\Organization\Actions;

use Illuminate\Database\UniqueConstraintViolationException;
use Modules\Organization\Data\CreateInstitutionTypeData;
use Modules\Organization\Models\InstitutionType;
use RuntimeException;

/**
 * Creates a new institution-type record.
 *
 * This is an internal application service. Future HTTP callers must be
 * authorized through the F17/F19 policy kernel before invoking this action.
 * This action does not register or assume any authorization bypass.
 *
 * Stable codes are assigned at creation and may not be changed afterwards.
 */
final readonly class CreateInstitutionType
{
    /**
     * @throws RuntimeException if the code is already taken
     */
    public function execute(CreateInstitutionTypeData $data): InstitutionType
    {
        try {
            $type = new InstitutionType;
            $type->code = $data->code;
            $type->name_en = $data->nameEn;
            $type->name_ar = $data->nameAr;
            $type->is_active = true;
            $type->save();
        } catch (UniqueConstraintViolationException) {
            throw new RuntimeException(
                "An institution type with code '{$data->code}' already exists."
            );
        }

        return $type;
    }
}

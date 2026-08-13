<?php

declare(strict_types=1);

namespace Modules\Organization\Actions;

use Modules\Organization\Data\ChangeInstitutionTypeNameData;
use Modules\Organization\Models\InstitutionType;

/**
 * Changes an institution type's display names.
 *
 * Only display names may be changed through this action. The stable code
 * is never modified. This is an internal application service; future HTTP
 * callers must be authorized through the F17/F19 policy kernel.
 */
final readonly class ChangeInstitutionTypeName
{
    public function execute(InstitutionType $type, ChangeInstitutionTypeNameData $data): InstitutionType
    {
        $type->name_en = $data->nameEn;
        $type->name_ar = $data->nameAr;
        $type->save();

        return $type;
    }
}

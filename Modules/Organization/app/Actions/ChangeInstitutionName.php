<?php

declare(strict_types=1);

namespace Modules\Organization\Actions;

use Modules\Organization\Data\ChangeInstitutionNameData;
use Modules\Organization\Models\Institution;

/**
 * Changes an institution's display names.
 *
 * Only display names may be changed through this action. The stable code
 * is never modified. This is an internal application service; future HTTP
 * callers must be authorized through the F17/F19 policy kernel.
 */
final readonly class ChangeInstitutionName
{
    public function execute(Institution $institution, ChangeInstitutionNameData $data): Institution
    {
        $institution->name_en = $data->nameEn;
        $institution->name_ar = $data->nameAr;
        $institution->save();

        return $institution;
    }
}

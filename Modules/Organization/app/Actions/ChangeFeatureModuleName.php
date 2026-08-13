<?php

declare(strict_types=1);

namespace Modules\Organization\Actions;

use Modules\Organization\Data\ChangeFeatureModuleNameData;
use Modules\Organization\Models\FeatureModule;

/**
 * Changes a feature module's display names.
 *
 * Only display names may be changed through this action. The stable code
 * is never modified. Relationships to institution types are not affected.
 *
 * This is an internal application service; future HTTP callers must be
 * authorized through the F17/F19 policy kernel.
 */
final readonly class ChangeFeatureModuleName
{
    public function execute(FeatureModule $module, ChangeFeatureModuleNameData $data): FeatureModule
    {
        $module->name_en = $data->nameEn;
        $module->name_ar = $data->nameAr;
        $module->save();

        return $module;
    }
}

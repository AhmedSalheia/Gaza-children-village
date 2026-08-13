<?php

declare(strict_types=1);

namespace Modules\Organization\Actions;

use Illuminate\Database\UniqueConstraintViolationException;
use Modules\Organization\Data\CreateFeatureModuleData;
use Modules\Organization\Models\FeatureModule;
use RuntimeException;

/**
 * Creates a new feature-module definition.
 *
 * A feature module is a configurable GCV business capability, distinct from
 * a physical Laravel package. Stable codes are assigned at creation and may
 * not be changed afterwards.
 *
 * This is an internal application service. Future HTTP callers must be
 * authorized through the F17/F19 policy kernel. This action does not register
 * or assume any authorization bypass.
 */
final readonly class CreateFeatureModule
{
    /**
     * @throws RuntimeException if the code is already taken
     */
    public function execute(CreateFeatureModuleData $data): FeatureModule
    {
        try {
            $module = new FeatureModule;
            $module->code = $data->code;
            $module->name_en = $data->nameEn;
            $module->name_ar = $data->nameAr;
            $module->is_active = true;
            $module->save();
        } catch (UniqueConstraintViolationException) {
            throw new RuntimeException(
                "A feature module with code '{$data->code}' already exists."
            );
        }

        return $module;
    }
}

<?php

declare(strict_types=1);

namespace Modules\Organization\Actions;

use Modules\Organization\Models\FeatureModule;

/**
 * Activates a feature module definition.
 *
 * This is an internal application service; future HTTP callers must be
 * authorized through the F17/F19 policy kernel.
 */
final readonly class ActivateFeatureModule
{
    public function execute(FeatureModule $module): FeatureModule
    {
        $module->is_active = true;
        $module->save();

        return $module;
    }
}

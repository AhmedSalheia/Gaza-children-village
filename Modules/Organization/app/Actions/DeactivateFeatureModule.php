<?php

declare(strict_types=1);

namespace Modules\Organization\Actions;

use Modules\Organization\Models\FeatureModule;

/**
 * Deactivates a feature module definition.
 *
 * Deactivation preserves the record and all institution-type rules that
 * reference it. Existing rules remain historically inspectable. No global
 * scope hides inactive records; consumers that need only active definitions
 * must add an explicit where('is_active', true) filter.
 *
 * An inactive definition cannot receive new ordinary institution-type rule
 * assignments (enforced by AssignInstitutionTypeRule).
 *
 * This is an internal application service; future HTTP callers must be
 * authorized through the F17/F19 policy kernel.
 */
final readonly class DeactivateFeatureModule
{
    public function execute(FeatureModule $module): FeatureModule
    {
        $module->is_active = false;
        $module->save();

        return $module;
    }
}

<?php

declare(strict_types=1);

namespace Modules\Organization\Actions;

use Modules\Organization\Models\FeatureModule;
use Modules\Organization\Models\InstitutionType;
use Modules\Organization\Models\InstitutionTypeFeatureRule;

/**
 * Makes a feature module unavailable to an institution type by removing its rule.
 *
 * This is an explicit action; removal should be a conscious decision with
 * the understanding that it makes the feature unavailable (not merely disabled).
 * F06 institution overrides cannot re-enable a feature that has no type rule.
 *
 * If no rule exists for the given type/feature pair, this action is a no-op.
 *
 * This is an internal application service; future HTTP callers must be
 * authorized through the F17/F19 policy kernel.
 */
final readonly class RemoveInstitutionTypeRule
{
    public function execute(InstitutionType $institutionType, FeatureModule $featureModule): void
    {
        InstitutionTypeFeatureRule::where('institution_type_id', $institutionType->id)
            ->where('feature_module_id', $featureModule->id)
            ->delete();
    }
}

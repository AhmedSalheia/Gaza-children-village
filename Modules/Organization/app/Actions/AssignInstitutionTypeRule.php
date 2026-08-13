<?php

declare(strict_types=1);

namespace Modules\Organization\Actions;

use Modules\Organization\Data\AssignInstitutionTypeRuleData;
use Modules\Organization\Models\FeatureModule;
use Modules\Organization\Models\InstitutionType;
use Modules\Organization\Models\InstitutionTypeFeatureRule;
use RuntimeException;

/**
 * Assigns or replaces an institution-type feature rule.
 *
 * If a rule already exists for the given type/feature pair, the rule value
 * is updated. If no rule exists, a new one is created.
 *
 * Inactive feature modules cannot receive new rule assignments through this
 * action. Deactivation does not delete existing rules; they remain
 * historically inspectable through direct query.
 *
 * This is an internal application service. Future HTTP callers must be
 * authorized through the F17/F19 policy kernel. This action does not register
 * or assume any authorization bypass.
 */
final readonly class AssignInstitutionTypeRule
{
    /**
     * @throws RuntimeException if the feature module is inactive
     */
    public function execute(
        InstitutionType $institutionType,
        FeatureModule $featureModule,
        AssignInstitutionTypeRuleData $data,
    ): InstitutionTypeFeatureRule {
        if (! $featureModule->is_active) {
            throw new RuntimeException(
                "Cannot assign a rule to inactive feature module '{$featureModule->code}'."
            );
        }

        $existing = InstitutionTypeFeatureRule::where('institution_type_id', $institutionType->id)
            ->where('feature_module_id', $featureModule->id)
            ->first();

        if ($existing) {
            $existing->rule = $data->rule;
            $existing->save();

            return $existing;
        }

        $rule = new InstitutionTypeFeatureRule;
        $rule->institution_type_id = $institutionType->id;
        $rule->feature_module_id = $featureModule->id;
        $rule->rule = $data->rule;
        $rule->save();

        return $rule;
    }
}

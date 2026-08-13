<?php

declare(strict_types=1);

namespace Modules\Organization\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Organization\Enums\FeatureModuleRule;
use Modules\Organization\Models\FeatureModule;
use Modules\Organization\Models\InstitutionType;
use Modules\Organization\Models\InstitutionTypeFeatureRule;

/**
 * Idempotent seeder for the approved institution-type feature-module rules.
 *
 * Safe to run multiple times. Creates missing rules; does NOT silently
 * overwrite existing rules that an administrator may have changed. When a
 * rule exists but its value differs from the approved default, the seeder
 * leaves it unchanged. Tests should detect and surface such drift if needed.
 *
 * Rules are resolved via stable codes only. Display names are never used
 * as lookup keys. If a required institution type or feature module code is
 * missing from the database this seeder silently skips that pair (the
 * FeatureModuleReferenceSeeder and InstitutionTypeReferenceSeeder must run
 * first in the call chain).
 *
 * Approved mapping matrix:
 *
 *   academy:
 *     staff_management      → required
 *     academic_management   → required
 *     asset_management      → default
 *
 *   university_space:
 *     staff_management      → required
 *     academic_management   → required
 *     asset_management      → default
 *
 *   medical_point:
 *     staff_management      → required
 *     asset_management      → default
 *     medical_services      → allowed
 *
 *   womens_center:
 *     staff_management      → required
 *     womens_center_programs → required
 *     asset_management      → default
 *
 *   storage_unit:
 *     staff_management      → required
 *     inventory_management  → required
 *     asset_management      → default
 *
 * Absence of a rule means the feature is unavailable to that type.
 * F06 must not allow an institution to enable an unavailable feature.
 */
class InstitutionTypeFeatureRuleReferenceSeeder extends Seeder
{
    /**
     * Approved mapping: institution_type_code => [feature_code => rule_value]
     *
     * @var array<string, array<string, string>>
     */
    private const RULES = [
        'academy' => [
            'staff_management' => 'required',
            'academic_management' => 'required',
            'asset_management' => 'default',
        ],
        'university_space' => [
            'staff_management' => 'required',
            'academic_management' => 'required',
            'asset_management' => 'default',
        ],
        'medical_point' => [
            'staff_management' => 'required',
            'asset_management' => 'default',
            'medical_services' => 'allowed',
        ],
        'womens_center' => [
            'staff_management' => 'required',
            'womens_center_programs' => 'required',
            'asset_management' => 'default',
        ],
        'storage_unit' => [
            'staff_management' => 'required',
            'inventory_management' => 'required',
            'asset_management' => 'default',
        ],
    ];

    public function run(): void
    {
        foreach (self::RULES as $typeCode => $featureRules) {
            $type = InstitutionType::where('code', $typeCode)->first();

            if (! $type) {
                continue; // institution type not yet seeded; skip gracefully
            }

            foreach ($featureRules as $featureCode => $ruleValue) {
                $feature = FeatureModule::where('code', $featureCode)->first();

                if (! $feature) {
                    continue; // feature module not yet seeded; skip gracefully
                }

                $exists = InstitutionTypeFeatureRule::where('institution_type_id', $type->id)
                    ->where('feature_module_id', $feature->id)
                    ->exists();

                if ($exists) {
                    // Preserve administrator-edited rule values; do not silently overwrite.
                    continue;
                }

                $rule = new InstitutionTypeFeatureRule;
                $rule->institution_type_id = $type->id;
                $rule->feature_module_id = $feature->id;
                $rule->rule = FeatureModuleRule::from($ruleValue);
                $rule->save();
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace Modules\Organization\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Organization\Enums\FeatureModuleRule;
use Modules\Organization\Models\InstitutionType;
use Modules\Organization\Models\InstitutionTypeFeatureRule;

/**
 * Factory for InstitutionTypeFeatureRule test fixtures.
 *
 * Uses entirely synthetic data. Creates associated InstitutionType and
 * FeatureModule records automatically unless provided.
 *
 * @extends Factory<InstitutionTypeFeatureRule>
 */
class InstitutionTypeFeatureRuleFactory extends Factory
{
    protected $model = InstitutionTypeFeatureRule::class;

    public function definition(): array
    {
        return [
            'institution_type_id' => InstitutionTypeFactory::new(),
            'feature_module_id' => FeatureModuleFactory::new(),
            'rule' => FeatureModuleRule::Required,
        ];
    }

    public function required(): static
    {
        return $this->state(['rule' => FeatureModuleRule::Required]);
    }

    public function defaultEnabled(): static
    {
        return $this->state(['rule' => FeatureModuleRule::DefaultEnabled]);
    }

    public function allowed(): static
    {
        return $this->state(['rule' => FeatureModuleRule::Allowed]);
    }

    public function forType(InstitutionType $type): static
    {
        return $this->state(['institution_type_id' => $type->id]);
    }
}

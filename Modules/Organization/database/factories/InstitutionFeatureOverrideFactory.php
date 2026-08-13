<?php

declare(strict_types=1);

namespace Modules\Organization\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Organization\Models\InstitutionFeatureOverride;

/**
 * @extends Factory<InstitutionFeatureOverride>
 */
class InstitutionFeatureOverrideFactory extends Factory
{
    protected $model = InstitutionFeatureOverride::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'institution_id' => InstitutionFactory::new(),
            'feature_module_id' => FeatureModuleFactory::new(),
            'is_enabled' => true,
            'reason' => null,
        ];
    }

    /**
     * Override that enables an otherwise-disabled feature (Allowed rule).
     */
    public function enabled(): static
    {
        return $this->state(['is_enabled' => true]);
    }

    /**
     * Override that disables an otherwise-enabled feature (DefaultEnabled rule).
     */
    public function disabled(): static
    {
        return $this->state(['is_enabled' => false]);
    }

    /**
     * Override with an explicit reason recorded.
     */
    public function withReason(string $reason): static
    {
        return $this->state(['reason' => $reason]);
    }
}

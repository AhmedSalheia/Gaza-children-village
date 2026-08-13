<?php

declare(strict_types=1);

namespace Modules\Organization\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Organization\Models\FeatureModule;

/**
 * Factory for FeatureModule test fixtures.
 *
 * Uses entirely synthetic data. Never use real feature names or
 * organizational information from production data.
 *
 * @extends Factory<FeatureModule>
 */
class FeatureModuleFactory extends Factory
{
    protected $model = FeatureModule::class;

    public function definition(): array
    {
        return [
            'code' => 'feature-'.$this->faker->unique()->bothify('????##'),
            'name_en' => $this->faker->words(2, true).' Service (Test)',
            'name_ar' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function withArabicName(): static
    {
        return $this->state(['name_ar' => 'خدمة اختبارية']);
    }
}

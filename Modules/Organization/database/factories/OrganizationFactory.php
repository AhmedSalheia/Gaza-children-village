<?php

declare(strict_types=1);

namespace Modules\Organization\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Organization\Models\Organization;

/**
 * Factory for Organization test fixtures.
 *
 * Uses entirely synthetic data. Never use real organization names,
 * national IDs, or other personal/operational data in factories.
 *
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        return [
            'code' => 'org-'.$this->faker->unique()->bothify('????##'),
            'name_en' => $this->faker->company().' (Test)',
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
        return $this->state(['name_ar' => 'منظمة اختبارية']);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Organization\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Organization\Models\InstitutionType;

/**
 * Factory for InstitutionType test fixtures.
 *
 * Uses entirely synthetic data. Never use real institution names,
 * national IDs, or other personal/operational data in factories.
 *
 * @extends Factory<InstitutionType>
 */
class InstitutionTypeFactory extends Factory
{
    protected $model = InstitutionType::class;

    public function definition(): array
    {
        return [
            'code' => 'type-'.$this->faker->unique()->bothify('????##'),
            'name_en' => $this->faker->word().' Type (Test)',
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
        return $this->state(['name_ar' => 'نوع مؤسسة اختباري']);
    }
}

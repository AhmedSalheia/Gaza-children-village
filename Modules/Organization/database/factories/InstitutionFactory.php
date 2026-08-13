<?php

declare(strict_types=1);

namespace Modules\Organization\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Organization\Models\Institution;
use Modules\Organization\Models\InstitutionType;
use Modules\Organization\Models\Organization;

/**
 * Factory for Institution test fixtures.
 *
 * Uses entirely synthetic data. Never use real institution names,
 * national IDs, or other personal/operational data in factories.
 *
 * @extends Factory<Institution>
 */
class InstitutionFactory extends Factory
{
    protected $model = Institution::class;

    public function definition(): array
    {
        return [
            'code' => 'inst-'.$this->faker->unique()->bothify('????##'),
            'organization_id' => Organization::factory(),
            'institution_type_id' => InstitutionType::factory(),
            'name_en' => $this->faker->words(3, true).' Institution (Test)',
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
        return $this->state(['name_ar' => 'مؤسسة اختبارية']);
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state(['organization_id' => $organization->id]);
    }

    public function ofType(InstitutionType $type): static
    {
        return $this->state(['institution_type_id' => $type->id]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Staff\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Staff\Enums\EmploymentStatus;
use Modules\Staff\Models\StaffProfile;

/**
 * @extends Factory<StaffProfile>
 */
final class StaffProfileFactory extends Factory
{
    protected $model = StaffProfile::class;

    public function definition(): array
    {
        return [
            'person_id' => null, // must be provided explicitly in tests
            'staff_code' => 'STF-'.strtoupper($this->faker->unique()->lexify('????')),
            'employment_status' => EmploymentStatus::Active->value,
            'hired_on' => null,
            'ended_on' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(['employment_status' => EmploymentStatus::Active->value]);
    }

    public function inactive(): static
    {
        return $this->state(['employment_status' => EmploymentStatus::Inactive->value]);
    }

    public function ended(): static
    {
        return $this->state(['employment_status' => EmploymentStatus::Ended->value]);
    }
}

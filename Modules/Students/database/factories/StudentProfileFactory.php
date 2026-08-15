<?php

declare(strict_types=1);

namespace Modules\Students\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Students\Enums\StudentLifecycleStatus;
use Modules\Students\Models\StudentProfile;

/**
 * @extends Factory<StudentProfile>
 */
final class StudentProfileFactory extends Factory
{
    protected $model = StudentProfile::class;

    public function definition(): array
    {
        static $counter = 0;
        $counter++;

        return [
            'person_id' => null, // must be provided explicitly in tests
            'student_code' => 'STU-TEST-'.str_pad((string) $counter, 5, '0', STR_PAD_LEFT),
            'lifecycle_status' => StudentLifecycleStatus::Active->value,
            'registered_on' => now()->toDateString(),
            'orphan_status' => null,
            'displacement_status' => null,
            'displacement_location' => null,
            'family_member_count' => null,
            'family_order' => null,
            'accessibility_indicator' => false,
        ];
    }

    public function draft(): static
    {
        return $this->state(['lifecycle_status' => StudentLifecycleStatus::Draft->value]);
    }

    public function active(): static
    {
        return $this->state(['lifecycle_status' => StudentLifecycleStatus::Active->value]);
    }

    public function inactive(): static
    {
        return $this->state(['lifecycle_status' => StudentLifecycleStatus::Inactive->value]);
    }

    public function withdrawn(): static
    {
        return $this->state(['lifecycle_status' => StudentLifecycleStatus::Withdrawn->value]);
    }

    public function graduated(): static
    {
        return $this->state(['lifecycle_status' => StudentLifecycleStatus::Graduated->value]);
    }

    public function deceased(): static
    {
        return $this->state(['lifecycle_status' => StudentLifecycleStatus::Deceased->value]);
    }
}

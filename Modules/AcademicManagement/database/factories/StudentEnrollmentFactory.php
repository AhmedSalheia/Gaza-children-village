<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AcademicManagement\Enums\EnrollmentStatus;
use Modules\AcademicManagement\Models\ClassGroup;
use Modules\AcademicManagement\Models\StudentEnrollment;

/**
 * @extends Factory<StudentEnrollment>
 */
final class StudentEnrollmentFactory extends Factory
{
    protected $model = StudentEnrollment::class;

    public function definition(): array
    {
        // Cross-module integer references: caller may override student_profile_id
        // and institution_semester_id with real IDs from their respective modules.
        return [
            'student_profile_id' => 0,
            'institution_semester_id' => 0,
            'class_group_id' => ClassGroup::factory(),
            'enrollment_status' => EnrollmentStatus::Draft->value,
            'enrolled_on' => now()->toDateString(),
            'activated_on' => null,
            'completed_on' => null,
            'notes' => null,
        ];
    }

    public function active(): static
    {
        return $this->state([
            'enrollment_status' => EnrollmentStatus::Active->value,
            'activated_on' => now()->toDateString(),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'enrollment_status' => EnrollmentStatus::Completed->value,
            'activated_on' => now()->subDays(90)->toDateString(),
            'completed_on' => now()->toDateString(),
        ]);
    }

    public function withdrawn(): static
    {
        return $this->state([
            'enrollment_status' => EnrollmentStatus::Withdrawn->value,
        ]);
    }

    public function transferred(): static
    {
        return $this->state([
            'enrollment_status' => EnrollmentStatus::Transferred->value,
        ]);
    }

    public function promoted(): static
    {
        return $this->state([
            'enrollment_status' => EnrollmentStatus::Promoted->value,
        ]);
    }

    public function graduated(): static
    {
        return $this->state([
            'enrollment_status' => EnrollmentStatus::Graduated->value,
        ]);
    }

    public function forStudent(int $studentProfileId): static
    {
        return $this->state(['student_profile_id' => $studentProfileId]);
    }

    public function forSemester(int $institutionSemesterId): static
    {
        return $this->state(['institution_semester_id' => $institutionSemesterId]);
    }
}

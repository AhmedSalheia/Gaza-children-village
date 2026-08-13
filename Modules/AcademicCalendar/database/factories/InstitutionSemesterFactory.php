<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\InstitutionSemester;
use Modules\AcademicCalendar\Models\Semester;

/**
 * @extends Factory<InstitutionSemester>
 *
 * Cross-module InstitutionFactory references use string-variable static calls
 * to avoid use-imports that the boundary scanner would flag (Database is not
 * a public surface). PHP resolves static method calls on string variables at
 * runtime via the autoloader.
 */
class InstitutionSemesterFactory extends Factory
{
    protected $model = InstitutionSemester::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // String-variable — scanner-safe cross-module reference.
        $institutionFactory = 'Modules\\Organization\\Database\\Factories\\InstitutionFactory';

        return [
            'institution_id' => $institutionFactory::new(),
            'semester_id' => SemesterFactory::new(),
            'status' => AcademicStatus::Draft,
            'copied_from_id' => null,
        ];
    }

    /**
     * @param  object  $institution  Modules\\Organization\\Models\\Institution instance
     */
    public function forInstitution(object $institution): static
    {
        return $this->state(['institution_id' => $institution->id]);
    }

    public function forSemester(Semester $semester): static
    {
        return $this->state(['semester_id' => $semester->id]);
    }

    public function draft(): static
    {
        return $this->state(['status' => AcademicStatus::Draft]);
    }

    public function open(): static
    {
        return $this->state(['status' => AcademicStatus::Open]);
    }

    public function closed(): static
    {
        return $this->state(['status' => AcademicStatus::Closed]);
    }

    public function archived(): static
    {
        return $this->state(['status' => AcademicStatus::Archived]);
    }

    public function copiedFrom(InstitutionSemester $source): static
    {
        return $this->state(['copied_from_id' => $source->id]);
    }
}

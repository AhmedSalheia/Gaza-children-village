<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AcademicManagement\Models\Classroom;

/**
 * @extends Factory<Classroom>
 *
 * institution_id must be provided explicitly in tests.
 * Cross-module Institution references use string-variable static calls.
 */
final class ClassroomFactory extends Factory
{
    protected $model = Classroom::class;

    public function definition(): array
    {
        static $seq = 0;
        $seq++;

        // String-variable — scanner-safe cross-module reference.
        $institutionFactory = 'Modules\\Organization\\Database\\Factories\\InstitutionFactory';

        return [
            'institution_id' => $institutionFactory::new(),
            'code' => 'CR-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT),
            'name_ar' => 'قاعة '.$seq,
            'name_en' => 'Room '.$seq,
            'capacity' => null,
            'is_active' => true,
        ];
    }

    public function forInstitution(int $institutionId): static
    {
        return $this->state(['institution_id' => $institutionId]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function withCapacity(int $capacity): static
    {
        return $this->state(['capacity' => $capacity]);
    }
}

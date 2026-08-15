<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AcademicManagement\Models\Subject;

/**
 * @extends Factory<Subject>
 */
final class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        static $seq = 0;
        $seq++;

        return [
            'code' => 'SUBJ-'.$seq,
            'name_ar' => 'مادة '.$seq,
            'name_en' => 'Subject '.$seq,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}

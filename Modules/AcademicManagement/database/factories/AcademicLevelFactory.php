<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AcademicManagement\Models\AcademicLevel;

/**
 * @extends Factory<AcademicLevel>
 */
final class AcademicLevelFactory extends Factory
{
    protected $model = AcademicLevel::class;

    public function definition(): array
    {
        static $seq = 0;
        $seq++;

        return [
            'code' => 'LEVEL-'.$seq,
            'name_ar' => 'مستوى '.$seq,
            'name_en' => 'Level '.$seq,
            'sequence' => $seq,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}

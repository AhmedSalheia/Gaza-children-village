<?php

declare(strict_types=1);

namespace Modules\People\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\People\Models\Person;

/**
 * @extends Factory<Person>
 */
final class PersonFactory extends Factory
{
    protected $model = Person::class;

    public function definition(): array
    {
        return [
            'full_name_ar' => $this->faker->name(),
            'full_name_en' => $this->faker->optional()->name(),
            'birth_date' => null,
            'birth_date_precision' => null,
        ];
    }

    public function withBirthDate(\DateTimeInterface $date, string $precision = 'exact'): static
    {
        return $this->state([
            'birth_date' => $date->format('Y-m-d'),
            'birth_date_precision' => $precision,
        ]);
    }
}

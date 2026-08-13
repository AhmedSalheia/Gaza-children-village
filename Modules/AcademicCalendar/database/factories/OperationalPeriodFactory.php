<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AcademicCalendar\Models\InstitutionSemester;
use Modules\AcademicCalendar\Models\OperationalPeriod;

/**
 * @extends Factory<OperationalPeriod>
 */
class OperationalPeriodFactory extends Factory
{
    protected $model = OperationalPeriod::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'institution_semester_id' => InstitutionSemesterFactory::new(),
            'name_en' => 'Period '.$this->faker->unique()->numberBetween(1, 99_999),
            'name_ar' => null,
            'starts_at' => '08:00:00',
            'ends_at' => '12:00:00',
            'is_active' => true,
        ];
    }

    /**
     * Assign a stable code and sequence after making. Both are excluded from
     * $fillable to prevent accidental overwrites.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (OperationalPeriod $period): void {
            if (empty($period->code)) {
                $period->code = strtoupper($this->faker->unique()->lexify('PER-????????'));
            }

            if (empty($period->sequence)) {
                $period->sequence = $this->faker->unique()->numberBetween(1, 99_999);
            }
        });
    }

    public function forInstitutionSemester(InstitutionSemester $is): static
    {
        return $this->state(['institution_semester_id' => $is->id]);
    }

    public function withCode(string $code): static
    {
        return $this->afterMaking(function (OperationalPeriod $period) use ($code): void {
            $period->code = $code;
        });
    }

    public function withSequence(int $sequence): static
    {
        return $this->afterMaking(function (OperationalPeriod $period) use ($sequence): void {
            $period->sequence = $sequence;
        });
    }

    public function withTimes(string $startsAt, string $endsAt): static
    {
        return $this->state(['starts_at' => $startsAt, 'ends_at' => $endsAt]);
    }

    public function withArabicName(string $nameAr = 'فترة تشغيلية'): static
    {
        return $this->state(['name_ar' => $nameAr]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function afternoon(): static
    {
        return $this->state(['starts_at' => '13:00:00', 'ends_at' => '17:00:00']);
    }
}

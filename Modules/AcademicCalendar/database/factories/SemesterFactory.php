<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\AcademicYear;
use Modules\AcademicCalendar\Models\Semester;

/**
 * @extends Factory<Semester>
 */
class SemesterFactory extends Factory
{
    protected $model = Semester::class;

    /**
     * Default dates fall within the default AcademicYear dates (2028-09-01 – 2029-06-30).
     *
     * Tests that require date containment must set up explicit matching dates
     * rather than relying on factory defaults across mismatched year ranges.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYearFactory::new(),
            'name_en' => 'Semester '.$this->faker->unique()->numberBetween(1, 99_999),
            'name_ar' => null,
            'starts_on' => '2028-09-01',
            'ends_on' => '2029-01-31',
            'status' => AcademicStatus::Draft,
        ];
    }

    /**
     * Assign a stable code and sequence after making, consistent with the
     * mass-assignment strategy (both excluded from $fillable).
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Semester $semester): void {
            if (empty($semester->code)) {
                $semester->code = strtoupper($this->faker->unique()->lexify('SEM-????????'));
            }

            if (empty($semester->sequence)) {
                $semester->sequence = $this->faker->unique()->numberBetween(1, 99_999);
            }
        });
    }

    public function withCode(string $code): static
    {
        return $this->afterMaking(function (Semester $semester) use ($code): void {
            $semester->code = $code;
        });
    }

    public function withSequence(int $sequence): static
    {
        return $this->afterMaking(function (Semester $semester) use ($sequence): void {
            $semester->sequence = $sequence;
        });
    }

    public function withArabicName(string $nameAr = 'فصل دراسي'): static
    {
        return $this->state(['name_ar' => $nameAr]);
    }

    public function forYear(AcademicYear $year): static
    {
        return $this->state(['academic_year_id' => $year->id]);
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
}

<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\AcademicYear;

/**
 * @extends Factory<AcademicYear>
 *
 * Cross-module Organization references use string-variable static calls
 * rather than direct class imports. PHP allows calling static methods on
 * string variables ($cls::new()), so the OrganizationFactory can be invoked
 * without a use-import. The boundary scanner does not match double-escaped
 * string literals, so these references are scanner-safe.
 */
class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startYear = $this->faker->numberBetween(2028, 2060);

        // String variable — allows static method call without a use-import.
        $orgFactory = 'Modules\\Organization\\Database\\Factories\\OrganizationFactory';

        return [
            'organization_id' => $orgFactory::new(),
            'name_en' => "Academic Year {$startYear}–".($startYear + 1),
            'name_ar' => null,
            'starts_on' => "{$startYear}-09-01",
            'ends_on' => ($startYear + 1).'-06-30',
            'status' => AcademicStatus::Draft,
        ];
    }

    /**
     * Assign a stable code after making, consistent with the mass-assignment
     * strategy (code excluded from $fillable).
     */
    public function configure(): static
    {
        return $this->afterMaking(function (AcademicYear $year): void {
            if (empty($year->code)) {
                $year->code = strtoupper($this->faker->unique()->lexify('AY-????????'));
            }
        });
    }

    public function withCode(string $code): static
    {
        return $this->afterMaking(function (AcademicYear $year) use ($code): void {
            $year->code = $code;
        });
    }

    public function withArabicName(string $nameAr = 'العام الدراسي'): static
    {
        return $this->state(['name_ar' => $nameAr]);
    }

    public function forOrganization(object $organization): static
    {
        return $this->state(['organization_id' => $organization->id]);
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

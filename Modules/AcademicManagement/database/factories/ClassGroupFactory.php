<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AcademicManagement\Enums\ClassGroupLifecycleStatus;
use Modules\AcademicManagement\Models\AcademicLevel;
use Modules\AcademicManagement\Models\ClassGroup;

/**
 * @extends Factory<ClassGroup>
 *
 * institution_semester_id and operational_period_id must be provided explicitly
 * in tests. Cross-module references use string-variable static calls.
 */
final class ClassGroupFactory extends Factory
{
    protected $model = ClassGroup::class;

    public function definition(): array
    {
        static $seq = 0;
        $seq++;

        // Cross-module string-variable references.
        $iSemFactory = 'Modules\\AcademicCalendar\\Database\\Factories\\InstitutionSemesterFactory';
        $opFactory = 'Modules\\AcademicCalendar\\Database\\Factories\\OperationalPeriodFactory';

        return [
            'institution_semester_id' => $iSemFactory::new(),
            'operational_period_id' => $opFactory::new(),
            'academic_level_id' => AcademicLevelFactory::new(),
            'classroom_id' => null,
            'code' => 'GRP-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT),
            'name_ar' => 'مجموعة '.$seq,
            'name_en' => 'Group '.$seq,
            'capacity' => null,
            'lifecycle_status' => ClassGroupLifecycleStatus::Draft->value,
        ];
    }

    public function draft(): static
    {
        return $this->state(['lifecycle_status' => ClassGroupLifecycleStatus::Draft->value]);
    }

    public function active(): static
    {
        return $this->state(['lifecycle_status' => ClassGroupLifecycleStatus::Active->value]);
    }

    public function archived(): static
    {
        return $this->state(['lifecycle_status' => ClassGroupLifecycleStatus::Archived->value]);
    }

    public function forSemester(int $institutionSemesterId): static
    {
        return $this->state(['institution_semester_id' => $institutionSemesterId]);
    }

    public function forPeriod(int $operationalPeriodId): static
    {
        return $this->state(['operational_period_id' => $operationalPeriodId]);
    }

    public function forLevel(AcademicLevel $level): static
    {
        return $this->state(['academic_level_id' => $level->id]);
    }
}

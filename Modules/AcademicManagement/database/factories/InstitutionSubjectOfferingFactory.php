<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AcademicManagement\Models\InstitutionSubjectOffering;

/**
 * @extends Factory<InstitutionSubjectOffering>
 *
 * institution_semester_id uses a cross-module string-variable reference.
 */
final class InstitutionSubjectOfferingFactory extends Factory
{
    protected $model = InstitutionSubjectOffering::class;

    public function definition(): array
    {
        $iSemFactory = 'Modules\\AcademicCalendar\\Database\\Factories\\InstitutionSemesterFactory';

        return [
            'institution_semester_id' => $iSemFactory::new(),
            'subject_id' => SubjectFactory::new(),
        ];
    }

    public function forSemester(int $institutionSemesterId): static
    {
        return $this->state(['institution_semester_id' => $institutionSemesterId]);
    }
}

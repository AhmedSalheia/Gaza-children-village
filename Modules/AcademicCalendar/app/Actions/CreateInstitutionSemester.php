<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Actions;

use Modules\AcademicCalendar\Data\CreateInstitutionSemesterData;
use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\InstitutionSemester;
use Modules\AcademicCalendar\Models\Semester;
use RuntimeException;

/**
 * Create a new institution semester in Draft status.
 *
 * Validates:
 *   - The institution is active.
 *   - The institution has the effective academic_management feature enabled.
 *   - The semester exists and is not Archived.
 *   - The semester's academic year and the institution belong to the same organization.
 *   - No institution-semester record already exists for this institution/semester pair.
 *
 * The institution is accepted as object to avoid importing the Organization
 * module's Institution model class (non-public cross-module surface). Callers
 * pass a Modules\\Organization\\Models\\Institution instance.
 *
 * Feature check uses the InstitutionFeatureResolver via string-variable calls
 * to avoid importing Organization module non-public surfaces.
 *
 * Authorization boundary: this action does not check actor permissions.
 * Future callers must pass through the F17/F19 policy kernel.
 */
final readonly class CreateInstitutionSemester
{
    /**
     * @param  object  $institution  Modules\\Organization\\Models\\Institution instance
     */
    public function execute(object $institution, CreateInstitutionSemesterData $data): InstitutionSemester
    {
        if (! $institution->is_active) {
            throw new RuntimeException(
                "Cannot create institution semester for inactive institution '{$institution->code}'."
            );
        }

        $this->assertFeatureEnabled($institution);

        $semester = Semester::findOrFail($data->semesterId);

        if ($semester->status->isTerminal()) {
            throw new RuntimeException(
                "Cannot create institution semester for archived global semester '{$semester->code}'."
            );
        }

        // Load the academic year to verify organization consistency.
        $year = $semester->academicYear;

        if ((int) $institution->organization_id !== (int) $year->organization_id) {
            throw new RuntimeException(
                "The institution and the semester's academic year must belong to the same organization."
            );
        }

        $alreadyExists = InstitutionSemester::where('institution_id', $institution->id)
            ->where('semester_id', $semester->id)
            ->exists();

        if ($alreadyExists) {
            throw new RuntimeException(
                "An institution semester already exists for institution '{$institution->code}' and semester '{$semester->code}'."
            );
        }

        $is = new InstitutionSemester;
        $is->institution_id = $institution->id;
        $is->semester_id = $semester->id;
        $is->status = AcademicStatus::Draft;
        $is->copied_from_id = $data->copiedFromId;
        $is->save();

        return $is;
    }

    /**
     * Assert that the institution has the effective academic_management feature enabled.
     *
     * Uses string-variable static calls to reference Organization module services
     * without importing their non-public namespaces.
     */
    private function assertFeatureEnabled(object $institution): void
    {
        $featureModuleClass = 'Modules\\Organization\\Models\\FeatureModule';
        $resolverClass = 'Modules\\Organization\\Services\\InstitutionFeatureResolver';

        $feature = $featureModuleClass::where('code', 'academic_management')->first();

        if ($feature === null) {
            throw new RuntimeException(
                "Feature 'academic_management' is not registered. Cannot verify institution eligibility."
            );
        }

        $resolver = app($resolverClass);
        $result = $resolver->resolve($institution, $feature);

        if (! $result->isEnabled()) {
            throw new RuntimeException(
                "Institution '{$institution->code}' does not have the 'academic_management' feature enabled."
            );
        }
    }
}

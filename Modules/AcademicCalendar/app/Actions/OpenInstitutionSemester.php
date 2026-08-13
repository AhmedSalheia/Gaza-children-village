<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Actions;

use Illuminate\Support\Facades\DB;
use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\InstitutionSemester;
use RuntimeException;

/**
 * Transition an institution semester from Draft to Open.
 *
 * Validates (all checked before the transaction):
 *   - Institution semester is currently Draft.
 *   - The parent global academic year is Open.
 *   - The parent global semester is Open.
 *   - The institution is still active.
 *   - The institution still has the effective academic_management feature enabled.
 *   - At least one active operational period exists.
 *
 * Inside the transaction:
 *   - Re-verifies that no other institution semester for the same institution is Open.
 *     This prevents races without relying on lockForUpdate (SQLite incompatible).
 *   - Saves the status transition.
 *
 * Authorization boundary: this action does not check actor permissions.
 * Future callers must pass through the F17/F19 policy kernel.
 */
final readonly class OpenInstitutionSemester
{
    public function execute(InstitutionSemester $is): InstitutionSemester
    {
        if ($is->status !== AcademicStatus::Draft) {
            throw new RuntimeException(
                "Only a draft institution semester can be opened. Current status: {$is->status->value}."
            );
        }

        // Load semester and year fresh to avoid stale relationship cache.
        $semester = $is->semester()->first();
        $year = $semester->academicYear()->first();

        if ($year->status !== AcademicStatus::Open) {
            throw new RuntimeException(
                "Cannot open institution semester: the parent academic year '{$year->code}' is not open (status: {$year->status->value})."
            );
        }

        if ($semester->status !== AcademicStatus::Open) {
            throw new RuntimeException(
                "Cannot open institution semester: the parent global semester '{$semester->code}' is not open (status: {$semester->status->value})."
            );
        }

        // Load institution bypassing the ActiveInstitutionScope is not needed here;
        // if the institution has been deactivated it won't be found via the global scope.
        $institution = $is->institution()->first();

        if ($institution === null || ! $institution->is_active) {
            throw new RuntimeException(
                'Cannot open institution semester: the institution is not active.'
            );
        }

        $this->assertFeatureEnabled($institution);

        $hasActivePeriods = $is->activePeriods()->exists();

        if (! $hasActivePeriods) {
            throw new RuntimeException(
                'Cannot open institution semester: at least one active operational period is required.'
            );
        }

        return DB::transaction(function () use ($is): InstitutionSemester {
            $alreadyOpen = InstitutionSemester::where('institution_id', $is->institution_id)
                ->where('status', AcademicStatus::Open->value)
                ->where('id', '!=', $is->id)
                ->exists();

            if ($alreadyOpen) {
                throw new RuntimeException(
                    'Another institution semester is already open for this institution. Close it before opening a new one.'
                );
            }

            $is->status = AcademicStatus::Open;
            $is->save();

            return $is;
        });
    }

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
                'Cannot open institution semester: the academic_management feature is not enabled for this institution.'
            );
        }
    }
}

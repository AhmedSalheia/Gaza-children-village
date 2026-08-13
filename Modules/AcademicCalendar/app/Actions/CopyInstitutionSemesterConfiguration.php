<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Actions;

use Illuminate\Support\Facades\DB;
use Modules\AcademicCalendar\Data\CreateInstitutionSemesterData;
use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\InstitutionSemester;
use Modules\AcademicCalendar\Models\OperationalPeriod;
use Modules\AcademicCalendar\Models\Semester;
use RuntimeException;

/**
 * Copy configuration from one institution semester into a new target semester.
 *
 * Copies:
 *   - Period definitions (code, name_en, name_ar, sequence, starts_at, ends_at, is_active).
 *
 * Never copies:
 *   - Attendance, enrolment, audit records, or any operational facts.
 *
 * Validates:
 *   - The target global semester belongs to the same organization as the source.
 *   - The target global semester is Draft or Open (not Closed or Archived).
 *   - No institution semester already exists for the institution/target-semester pair.
 *   - The source institution must be active and have academic_management enabled
 *     (delegated to CreateInstitutionSemester).
 *
 * The operation is atomic: either the new institution semester and all periods are
 * created together, or nothing is written.
 *
 * Authorization boundary: this action does not check actor permissions.
 * Future callers must pass through the F17/F19 policy kernel.
 */
final readonly class CopyInstitutionSemesterConfiguration
{
    public function __construct(private CreateInstitutionSemester $createInstitutionSemester) {}

    /**
     * @param  object  $institution  Modules\\Organization\\Models\\Institution instance
     */
    public function execute(InstitutionSemester $source, object $institution, int $targetSemesterId): InstitutionSemester
    {
        $targetSemester = Semester::findOrFail($targetSemesterId);

        // Verify same organization as source semester's academic year.
        $sourceYear = $source->semester()->first()->academicYear;
        $targetYear = $targetSemester->academicYear;

        if ((int) $sourceYear->organization_id !== (int) $targetYear->organization_id) {
            throw new RuntimeException(
                'The target semester must belong to the same organization as the source.'
            );
        }

        // Target semester must be Draft or Open.
        $targetAllowed = $targetSemester->status === AcademicStatus::Draft
            || $targetSemester->status === AcademicStatus::Open;

        if (! $targetAllowed) {
            throw new RuntimeException(
                "The target global semester '{$targetSemester->code}' must be Draft or Open for a copy operation (current status: {$targetSemester->status->value})."
            );
        }

        // Pre-check to provide a clear error before the transaction.
        $alreadyExists = InstitutionSemester::where('institution_id', $institution->id)
            ->where('semester_id', $targetSemesterId)
            ->exists();

        if ($alreadyExists) {
            throw new RuntimeException(
                'An institution semester already exists for the target semester. Copy rejected to prevent duplicates.'
            );
        }

        return DB::transaction(function () use ($source, $institution, $targetSemesterId): InstitutionSemester {
            // CreateInstitutionSemester validates institution active state and feature.
            $newIs = $this->createInstitutionSemester->execute(
                $institution,
                new CreateInstitutionSemesterData(
                    semesterId: $targetSemesterId,
                    copiedFromId: $source->id,
                )
            );

            // Copy all period definitions (all states, including inactive).
            $sourcePeriods = $source->operationalPeriods()->ordered()->get();

            foreach ($sourcePeriods as $srcPeriod) {
                $newPeriod = new OperationalPeriod;
                $newPeriod->institution_semester_id = $newIs->id;
                $newPeriod->code = $srcPeriod->code;
                $newPeriod->name_en = $srcPeriod->name_en;
                $newPeriod->name_ar = $srcPeriod->name_ar;
                $newPeriod->sequence = $srcPeriod->sequence;
                $newPeriod->starts_at = $srcPeriod->starts_at;
                $newPeriod->ends_at = $srcPeriod->ends_at;
                $newPeriod->is_active = $srcPeriod->is_active;
                $newPeriod->save();
            }

            return $newIs;
        });
    }
}

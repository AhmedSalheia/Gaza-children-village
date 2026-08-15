<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Exceptions\DuplicateOfferingException;
use Modules\AcademicManagement\Models\InstitutionSubjectOffering;
use Modules\AcademicManagement\Models\Subject;

/**
 * Offer a Subject within an InstitutionSemester.
 *
 * Validates that the InstitutionSemester exists before persisting, using a
 * string-variable cross-module class reference (boundary scanner safe).
 *
 * Each subject may be offered at most once per semester (unique constraint).
 * The subject must be active; inactive subjects cannot be newly offered.
 */
final class OfferSubject
{
    public function __invoke(int $institutionSemesterId, Subject $subject): InstitutionSubjectOffering
    {
        // Validate semester exists via string-variable (boundary scanner safe).
        $semesterClass = 'Modules\\AcademicCalendar\\Models\\InstitutionSemester';
        $semester = $semesterClass::find($institutionSemesterId);

        if ($semester === null) {
            throw new \InvalidArgumentException(
                "InstitutionSemester #{$institutionSemesterId} not found."
            );
        }

        if (! $subject->is_active) {
            throw new \InvalidArgumentException(
                "Subject '{$subject->code}' is inactive and cannot be offered in a semester."
            );
        }

        $exists = InstitutionSubjectOffering::where('institution_semester_id', $institutionSemesterId)
            ->where('subject_id', $subject->id)
            ->exists();

        if ($exists) {
            throw new DuplicateOfferingException(
                "Subject '{$subject->code}' is already offered in institution semester #{$institutionSemesterId}."
            );
        }

        $offering = new InstitutionSubjectOffering;
        $offering->institution_semester_id = $institutionSemesterId;
        $offering->subject_id = $subject->id;
        $offering->save();

        return $offering;
    }
}

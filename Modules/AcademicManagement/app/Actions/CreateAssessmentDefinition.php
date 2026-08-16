<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Illuminate\Support\Facades\DB;
use Modules\AcademicManagement\Enums\AssessmentType;
use Modules\AcademicManagement\Exceptions\MarksException;
use Modules\AcademicManagement\Models\AssessmentDefinition;

/**
 * Create a new assessment definition for a semester/class/subject context.
 *
 * Enforced rules:
 *  1. max_score must be > 0.
 *  2. weight must be >= 0.
 *  3. assessment_date (if supplied) must be a valid date string.
 *  4. If class_group_id is given, it must belong to institution_semester_id.
 *  5. If subject_offering_id is given, it must belong to institution_semester_id.
 *
 * Cross-module context checks (institution_semester, class_group semester match)
 * are the caller's responsibility to validate before invoking this action.
 */
final class CreateAssessmentDefinition
{
    public function __invoke(
        int $institutionSemesterId,
        ?int $classGroupId,
        ?int $subjectOfferingId,
        string $nameAr,
        ?string $nameEn,
        AssessmentType $assessmentType,
        float $maxScore,
        float $weight = 0.0,
        ?string $assessmentDate = null,
    ): AssessmentDefinition {
        if ($maxScore <= 0) {
            throw new MarksException("max_score must be greater than zero (got {$maxScore}).");
        }

        if ($weight < 0) {
            throw new MarksException("weight must be >= 0 (got {$weight}).");
        }

        // Validate class group belongs to the given semester (if provided)
        if ($classGroupId !== null) {
            $belongs = DB::table('class_groups')
                ->where('id', $classGroupId)
                ->where('institution_semester_id', $institutionSemesterId)
                ->exists();

            if (! $belongs) {
                throw new MarksException(
                    "Class group #{$classGroupId} does not belong to institution semester #{$institutionSemesterId}."
                );
            }
        }

        // Validate subject offering belongs to the given semester (if provided)
        if ($subjectOfferingId !== null) {
            $belongs = DB::table('institution_subject_offerings')
                ->where('id', $subjectOfferingId)
                ->where('institution_semester_id', $institutionSemesterId)
                ->exists();

            if (! $belongs) {
                throw new MarksException(
                    "Subject offering #{$subjectOfferingId} does not belong to institution semester #{$institutionSemesterId}."
                );
            }
        }

        $definition = new AssessmentDefinition;
        $definition->institution_semester_id = $institutionSemesterId;
        $definition->class_group_id = $classGroupId;
        $definition->subject_offering_id = $subjectOfferingId;
        $definition->name_ar = $nameAr;
        $definition->name_en = $nameEn;
        $definition->assessment_type = $assessmentType->value;
        $definition->max_score = $maxScore;
        $definition->weight = $weight;
        $definition->assessment_date = $assessmentDate;
        $definition->status = 'active';
        $definition->save();

        return $definition;
    }
}

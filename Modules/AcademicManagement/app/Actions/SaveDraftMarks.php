<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Illuminate\Support\Facades\DB;
use Modules\AcademicManagement\Enums\MarkExceptionStatus;
use Modules\AcademicManagement\Exceptions\MarksException;
use Modules\AcademicManagement\Models\AssessmentDefinition;
use Modules\AcademicManagement\Models\MarkSheet;
use Modules\AcademicManagement\Models\StudentMark;

/**
 * Save or update a single student mark within a draft/returned mark sheet.
 *
 * Enforced rules:
 *  1. Sheet must be in draft or returned status (teacher-editable).
 *  2. If a window is attached, it must still be open.
 *  3. The enrollment must belong to the sheet's class group (scope check).
 *  4. The assessment definition must belong to the sheet's semester+context.
 *  5. Score must be in [0, max_score] or null (when exception is set).
 *  6. Exactly one of (score, exception_status) must be set.
 */
final class SaveDraftMarks
{
    public function __invoke(
        MarkSheet $sheet,
        int $enrollmentId,
        int $assessmentDefinitionId,
        ?float $score,
        ?string $exceptionStatus,
        ?string $teacherNote = null,
    ): StudentMark {
        if (! $sheet->isEditable()) {
            throw new MarksException(
                "Mark sheet #{$sheet->id} is in status '{$sheet->status->value}' and cannot be edited."
            );
        }

        // Scope check — enrollment must be active in the sheet's class group
        $enrollmentBelongs = DB::table('student_enrollments')
            ->where('id', $enrollmentId)
            ->where('class_group_id', $sheet->class_group_id)
            ->where('enrollment_status', 'active')
            ->exists();

        if (! $enrollmentBelongs) {
            throw new MarksException(
                "Enrollment #{$enrollmentId} is not an active enrollment in this mark sheet's class group."
            );
        }

        // Scope check — assessment definition must be active and apply to this sheet's context
        $definitionApplicable = DB::table('assessment_definitions')
            ->where('id', $assessmentDefinitionId)
            ->where('institution_semester_id', $sheet->institution_semester_id)
            ->where('status', 'active')
            ->where(function ($q) use ($sheet): void {
                $q->where(function ($inner) use ($sheet): void {
                    // Most specific: class + subject match
                    $inner->where('class_group_id', $sheet->class_group_id)
                        ->where('subject_offering_id', $sheet->subject_offering_id);
                })->orWhere(function ($inner) use ($sheet): void {
                    // Subject-only scope
                    $inner->whereNull('class_group_id')
                        ->where('subject_offering_id', $sheet->subject_offering_id);
                })->orWhere(function ($inner) use ($sheet): void {
                    // Class-only scope
                    $inner->where('class_group_id', $sheet->class_group_id)
                        ->whereNull('subject_offering_id');
                })->orWhere(function ($inner): void {
                    // Semester-wide
                    $inner->whereNull('class_group_id')
                        ->whereNull('subject_offering_id');
                });
            })
            ->exists();

        if (! $definitionApplicable) {
            throw new MarksException(
                "Assessment definition #{$assessmentDefinitionId} is not applicable to this mark sheet's class/subject/semester."
            );
        }

        // Window enforcement
        if ($sheet->mark_entry_window_id !== null) {
            $window = $sheet->markEntryWindow;

            if ($window && ! $window->isCurrentlyOpen()) {
                throw new MarksException(
                    "Mark entry window '{$window->name_ar}' is not currently open."
                );
            }
        }

        // Validate score vs exception mutual exclusivity
        if ($score !== null && $exceptionStatus !== null) {
            throw new MarksException('Provide either a score or an exception status — not both.');
        }

        // Validate score range
        if ($score !== null) {
            $definition = AssessmentDefinition::find($assessmentDefinitionId);

            if ($definition === null) {
                throw new \InvalidArgumentException(
                    "AssessmentDefinition #{$assessmentDefinitionId} not found."
                );
            }

            if ($score < 0 || $score > $definition->max_score) {
                throw new MarksException(
                    "Score {$score} is out of range [0, {$definition->max_score}] ".
                    "for assessment '{$definition->name_ar}'."
                );
            }
        }

        // Validate exception status value
        if ($exceptionStatus !== null && ! MarkExceptionStatus::tryFrom($exceptionStatus)) {
            throw new MarksException(
                "Invalid exception_status value: '{$exceptionStatus}'."
            );
        }

        // Upsert the mark row
        $mark = StudentMark::where('mark_sheet_id', $sheet->id)
            ->where('enrollment_id', $enrollmentId)
            ->where('assessment_definition_id', $assessmentDefinitionId)
            ->first();

        if ($mark === null) {
            $mark = new StudentMark;
            $mark->mark_sheet_id = $sheet->id;
            $mark->enrollment_id = $enrollmentId;
            $mark->assessment_definition_id = $assessmentDefinitionId;
        }

        $mark->score = $score;
        $mark->exception_status = $exceptionStatus;
        $mark->teacher_note = $teacherNote !== '' ? $teacherNote : null;
        $mark->save();

        return $mark;
    }
}

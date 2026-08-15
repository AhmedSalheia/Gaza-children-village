<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Illuminate\Support\Facades\DB;
use Modules\AcademicManagement\Enums\MarkSheetStatus;
use Modules\AcademicManagement\Exceptions\MarksException;
use Modules\AcademicManagement\Models\AssessmentDefinition;
use Modules\AcademicManagement\Models\MarkSheet;
use Modules\AcademicManagement\Models\StudentEnrollment;
use Modules\AcademicManagement\Models\TeachingAssignment;

/**
 * Create a MarkSheet for a TeachingAssignment within a MarkEntryWindow.
 *
 * On creation, a StudentMark placeholder row is seeded for every
 * active enrollment in the class group × every active assessment definition
 * that matches the class+subject context. Mark rows start with score=null
 * and no exception; the teacher fills them in via SaveDraftMarks.
 *
 * Enforced rules:
 *  1. TeachingAssignment must be active.
 *  2. If a window is supplied it must be open (status open/extended).
 *  3. Only one non-superseded sheet per (teaching_assignment, window).
 *  4. At least one active enrollment must exist in the class group.
 *  5. At least one matching active assessment definition must exist.
 */
final class OpenMarkSheet
{
    public function __invoke(
        TeachingAssignment $assignment,
        ?int $markEntryWindowId = null,
        ?int $gradingScaleId = null,
    ): MarkSheet {
        if (! $assignment->isActive()) {
            throw new MarksException(
                "TeachingAssignment #{$assignment->id} is not active and cannot receive a mark sheet."
            );
        }

        if ($markEntryWindowId !== null) {
            $windowRow = DB::table('mark_entry_windows')->where('id', $markEntryWindowId)->first();

            if (! $windowRow) {
                throw new \InvalidArgumentException("MarkEntryWindow #{$markEntryWindowId} not found.");
            }

            if (! in_array($windowRow->status, ['open', 'extended'], true)) {
                throw new MarksException(
                    "MarkEntryWindow #{$markEntryWindowId} is not open (status: {$windowRow->status})."
                );
            }

            // Validate window belongs to the same institution semester as the assignment
            if ((int) $windowRow->institution_semester_id !== (int) $assignment->institution_semester_id) {
                throw new MarksException(
                    "MarkEntryWindow #{$markEntryWindowId} does not belong to the assignment's semester."
                );
            }

            // Validate window class/subject scope covers the assignment
            if ($windowRow->class_group_id !== null &&
                (int) $windowRow->class_group_id !== (int) $assignment->class_group_id) {
                throw new MarksException(
                    "MarkEntryWindow #{$markEntryWindowId} does not cover the assignment's class group."
                );
            }

            if ($windowRow->subject_offering_id !== null &&
                (int) $windowRow->subject_offering_id !== (int) $assignment->subject_offering_id) {
                throw new MarksException(
                    "MarkEntryWindow #{$markEntryWindowId} does not cover the assignment's subject."
                );
            }
        }

        return DB::transaction(function () use ($assignment, $markEntryWindowId, $gradingScaleId): MarkSheet {
            // Prevent duplicate — lock on the teaching assignment row
            $existing = MarkSheet::where('teaching_assignment_id', $assignment->id)
                ->where('mark_entry_window_id', $markEntryWindowId)
                ->whereNotIn('status', [MarkSheetStatus::Superseded->value])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            // Compute version
            $version = MarkSheet::where('teaching_assignment_id', $assignment->id)
                ->where('mark_entry_window_id', $markEntryWindowId)
                ->max('version') ?? 0;

            $sheet = new MarkSheet;
            $sheet->institution_semester_id  = (int) $assignment->institution_semester_id;
            $sheet->class_group_id           = (int) $assignment->class_group_id;
            $sheet->subject_offering_id      = (int) $assignment->subject_offering_id;
            $sheet->teaching_assignment_id   = (int) $assignment->id;
            $sheet->mark_entry_window_id     = $markEntryWindowId;
            $sheet->grading_scale_id         = $gradingScaleId;
            $sheet->version                  = $version + 1;
            $sheet->status                   = MarkSheetStatus::Draft->value;
            $sheet->save();

            $this->seedStudentMarkRows($sheet, $assignment);

            return $sheet;
        });
    }

    /**
     * Pre-populate StudentMark placeholder rows for each active enrollment
     * × each matching active assessment definition.
     */
    private function seedStudentMarkRows(MarkSheet $sheet, TeachingAssignment $assignment): void
    {
        $enrollments = StudentEnrollment::active()
            ->forClassGroup((int) $assignment->class_group_id)
            ->forSemester((int) $assignment->institution_semester_id)
            ->pluck('id');

        $definitions = AssessmentDefinition::active()
            ->forSemester((int) $assignment->institution_semester_id)
            ->where(function ($q) use ($assignment): void {
                $q->where(function ($inner) use ($assignment): void {
                    // Most specific: class + subject match
                    $inner->where('class_group_id', $assignment->class_group_id)
                        ->where('subject_offering_id', $assignment->subject_offering_id);
                })->orWhere(function ($inner) use ($assignment): void {
                    // Subject-only scope (null class_group_id)
                    $inner->whereNull('class_group_id')
                        ->where('subject_offering_id', $assignment->subject_offering_id);
                })->orWhere(function ($inner) use ($assignment): void {
                    // Class-only scope (null subject_offering_id)
                    $inner->where('class_group_id', $assignment->class_group_id)
                        ->whereNull('subject_offering_id');
                })->orWhere(function ($inner): void {
                    // Semester-wide: both null
                    $inner->whereNull('class_group_id')
                        ->whereNull('subject_offering_id');
                });
            })
            ->pluck('id');

        if ($enrollments->isEmpty() || $definitions->isEmpty()) {
            return;
        }

        $now  = now()->toDateTimeString();
        $rows = [];

        foreach ($enrollments as $enrollmentId) {
            foreach ($definitions as $definitionId) {
                $rows[] = [
                    'mark_sheet_id'            => $sheet->id,
                    'enrollment_id'            => $enrollmentId,
                    'assessment_definition_id' => $definitionId,
                    'score'                    => null,
                    'exception_status'         => null,
                    'teacher_note'             => null,
                    'correction_of_id'         => null,
                    'corrected_by_staff_profile_id' => null,
                    'corrected_at'             => null,
                    'correction_reason'        => null,
                    'created_at'               => $now,
                    'updated_at'               => $now,
                ];
            }
        }

        // Insert in chunks to avoid parameter limits
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('student_marks')->insertOrIgnore($chunk);
        }
    }
}

<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Illuminate\Support\Facades\DB;
use Modules\AcademicManagement\Enums\MarkExceptionStatus;
use Modules\AcademicManagement\Enums\MarkSheetStatus;
use Modules\AcademicManagement\Exceptions\MarksException;
use Modules\AcademicManagement\Models\MarkSheet;
use Modules\AcademicManagement\Models\StudentMark;

/**
 * Create a correction for a student mark on a published/approved sheet.
 *
 * Correction creates a NEW StudentMark row pointing to the original via
 * correction_of_id. The original row is never modified — immutable audit trail.
 *
 * For sheets that are only approved (not yet published), the correction can
 * be applied in-place since results have not been distributed.
 *
 * Enforced rules:
 *  1. Sheet must be approved or published (terminal editable state).
 *  2. The original mark must belong to the sheet.
 *  3. A non-empty reason must be provided.
 *  4. Score must be in [0, max_score] or null (with exception).
 *  5. No duplicate correction for the same original mark (one active correction).
 */
final class CorrectMark
{
    public function __invoke(
        MarkSheet $sheet,
        int $originalMarkId,
        ?float $newScore,
        ?string $newExceptionStatus,
        string $reason,
        int $actorStaffProfileId,
    ): StudentMark {
        if (! in_array($sheet->status->value, [MarkSheetStatus::Approved->value, MarkSheetStatus::Published->value], true)) {
            throw new MarksException(
                "Corrections can only be applied to approved or published sheets ".
                "(current status: '{$sheet->status->value}')."
            );
        }

        if (trim($reason) === '') {
            throw new MarksException('A correction reason is required.');
        }

        if ($newScore !== null && $newExceptionStatus !== null) {
            throw new MarksException('Provide either a new score or an exception status — not both.');
        }

        if ($newScore === null && $newExceptionStatus === null) {
            throw new MarksException('A correction must provide either a new score or an exception status.');
        }

        if ($newExceptionStatus !== null && ! MarkExceptionStatus::tryFrom($newExceptionStatus)) {
            throw new MarksException("Invalid exception_status: '{$newExceptionStatus}'.");
        }

        return DB::transaction(function () use (
            $sheet, $originalMarkId, $newScore, $newExceptionStatus, $reason, $actorStaffProfileId
        ): StudentMark {
            $original = StudentMark::where('id', $originalMarkId)
                ->where('mark_sheet_id', $sheet->id)
                ->lockForUpdate()
                ->first();

            if (! $original) {
                throw new \InvalidArgumentException(
                    "StudentMark #{$originalMarkId} not found on sheet #{$sheet->id}."
                );
            }

            // Validate score range
            if ($newScore !== null) {
                $definition = $original->assessmentDefinition;

                if ($newScore < 0 || $newScore > $definition->max_score) {
                    throw new MarksException(
                        "Score {$newScore} is out of range [0, {$definition->max_score}]."
                    );
                }
            }

            // Prevent duplicate correction
            $existingCorrection = StudentMark::where('correction_of_id', $originalMarkId)
                ->lockForUpdate()
                ->exists();

            if ($existingCorrection) {
                throw new MarksException(
                    "StudentMark #{$originalMarkId} already has a correction. ".
                    'Correct the correction record instead.'
                );
            }

            $correction = new StudentMark;
            $correction->mark_sheet_id                 = $sheet->id;
            $correction->enrollment_id                 = (int) $original->enrollment_id;
            $correction->assessment_definition_id      = (int) $original->assessment_definition_id;
            $correction->score                         = $newScore;
            $correction->exception_status              = $newExceptionStatus;
            $correction->teacher_note                  = $original->teacher_note;
            $correction->correction_of_id              = $original->id;
            $correction->corrected_by_staff_profile_id = $actorStaffProfileId;
            $correction->corrected_at                  = now();
            $correction->correction_reason             = $reason;
            $correction->save();

            return $correction;
        });
    }
}

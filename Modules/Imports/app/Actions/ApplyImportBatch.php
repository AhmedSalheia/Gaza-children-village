<?php

declare(strict_types=1);

namespace Modules\Imports\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Imports\Enums\BatchStatus;
use Modules\Imports\Enums\RowResultStatus;
use Modules\Imports\Models\ImportAppliedRecord;
use Modules\Imports\Models\ImportBatch;
use Modules\Imports\Models\ImportRowResult;

/**
 * Apply validated ImportRows through domain actions — one failing row does NOT
 * corrupt the batch or roll back previously applied rows.
 *
 * For each row with a non-blocking result (status ∉ {conflict, invalid, unauthorized}):
 *  - proposed_action = 'create_student' → CreatePersonAndStudentAtomically
 *  - proposed_action = 'update_student' → CorrectStudentData
 *  - proposed_action = 'skip'           → do nothing (SkippedExisting)
 *
 * Each row is applied in its own savepoint transaction. If the domain action
 * throws, the result status is updated to 'failed' and processing continues.
 *
 * Domain actions are called using string-variable references (boundary scanner).
 *
 * After apply:
 *  - batch.status → completed | completed_with_errors
 *  - batch.applied_at is set
 *  - batch.applied_rows is updated
 */
final class ApplyImportBatch
{
    public function __invoke(
        ImportBatch $batch,
        string $actorReference = 'import',
    ): ImportBatch {
        $batch->transitionTo(BatchStatus::Applying);

        $appliedCount = 0;
        $hadError = false;

        $batch->rowResults()
            ->with('row')
            ->whereNotIn('status', [
                RowResultStatus::Conflict->value,
                RowResultStatus::Invalid->value,
                RowResultStatus::Unauthorized->value,
            ])
            ->chunkById(500, function ($results) use (
                $batch, $actorReference, &$appliedCount, &$hadError,
            ): void {
                foreach ($results as $result) {
                    $row = $result->row;

                    if ($row === null) {
                        continue;
                    }

                    $normalised = (array) ($row->mapped_data ?? []);

                    try {
                        DB::transaction(function () use (
                            $batch, $row, $result, $normalised, $actorReference,
                            &$appliedCount,
                        ): void {
                            $this->applyRow($batch, $row, $result, $normalised, $actorReference);
                            $appliedCount++;
                        });
                    } catch (\Throwable $e) {
                        // Capture per-row failure without aborting the batch.
                        $result->status = RowResultStatus::Failed;
                        $result->summary = 'Apply error: '.mb_substr($e->getMessage(), 0, 200);
                        $result->save();
                        $hadError = true;
                    }
                }
            });

        $batch->applied_rows = $appliedCount;
        $batch->status = $hadError ? BatchStatus::CompletedWithErrors : BatchStatus::Completed;
        $batch->applied_at = now();
        $batch->save();

        return $batch;
    }

    private function applyRow(
        ImportBatch $batch,
        object $row,
        ImportRowResult $result,
        array $normalised,
        string $actorReference,
    ): void {
        $proposedAction = $result->proposed_action;

        if ($proposedAction === 'skip') {
            // Already skipped — nothing to do; result stays skipped_existing.
            return;
        }

        if ($proposedAction === 'create_student') {
            $this->createStudent($batch, $row, $result, $normalised);
        } elseif ($proposedAction === 'update_student') {
            $this->updateStudent($batch, $row, $result, $normalised, $actorReference);
        }
    }

    private function createStudent(
        ImportBatch $batch,
        object $row,
        ImportRowResult $result,
        array $normalised,
    ): void {
        $createActionClass = 'Modules\\Students\\Actions\\CreatePersonAndStudentAtomically';
        $createAction = app($createActionClass);

        $birthDate = isset($normalised['birth_date'])
            ? new \DateTime($normalised['birth_date'])
            : null;

        $registeredOn = isset($normalised['registered_on'])
            ? new \DateTime($normalised['registered_on'])
            : null;

        $outcome = $createAction(
            fullNameAr: $normalised['full_name_ar'],
            fullNameEn: $normalised['full_name_en'] ?? null,
            birthDate: $birthDate,
            birthDatePrecision: $birthDate !== null ? 'exact' : null,
            registeredOn: $registeredOn,
        );

        $person = $outcome['person'];
        $student = $outcome['student'];

        // Record applied entities.
        $this->recordApplied($batch->id, $row->id, $result->id, 'person', $person->id, 'created');
        $this->recordApplied($batch->id, $row->id, $result->id, 'student_profile', $student->id, 'created');

        $result->status = RowResultStatus::Created;
        $result->summary = "Created student: {$normalised['full_name_ar']} (person #{$person->id})";
        $result->save();
    }

    private function updateStudent(
        ImportBatch $batch,
        object $row,
        ImportRowResult $result,
        array $normalised,
        string $actorReference,
    ): void {
        $studentId = $result->matched_student_id;

        if ($studentId === null) {
            throw new \RuntimeException('update_student result has no matched_student_id');
        }

        $studentCls = 'Modules\\Students\\Models\\StudentProfile';
        $student = $studentCls::findOrFail($studentId);

        $correctActionClass = 'Modules\\Students\\Actions\\CorrectStudentData';
        $correctAction = app($correctActionClass);

        $registeredOn = isset($normalised['registered_on'])
            ? new \DateTime($normalised['registered_on'])
            : null;

        $orphanStatusCls = 'Modules\\Students\\Enums\\OrphanStatus';
        $displacementStatusCls = 'Modules\\Students\\Enums\\DisplacementStatus';

        $orphanStatus = isset($normalised['orphan_status'])
            ? $orphanStatusCls::tryFrom($normalised['orphan_status'])
            : null;

        $displacementStatus = isset($normalised['displacement_status'])
            ? $displacementStatusCls::tryFrom($normalised['displacement_status'])
            : null;

        $correctAction(
            profile: $student,
            actorReference: $actorReference,
            reason: 'Updated via import batch #'.$batch->id,
            orphanStatus: $orphanStatus,
            displacementStatus: $displacementStatus,
            displacementLocation: $normalised['displacement_location'] ?? null,
            familyMemberCount: isset($normalised['family_member_count']) ? (int) $normalised['family_member_count'] : null,
            familyOrder: isset($normalised['family_order']) ? (int) $normalised['family_order'] : null,
            accessibilityIndicator: isset($normalised['accessibility_indicator']) ? (bool) $normalised['accessibility_indicator'] : null,
            registeredOn: $registeredOn,
        );

        $this->recordApplied($batch->id, $row->id, $result->id, 'student_profile', $studentId, 'updated');

        $result->status = RowResultStatus::Updated;
        $result->summary = "Updated student #{$studentId}";
        $result->save();
    }

    private function recordApplied(
        int $batchId,
        int $rowId,
        int $resultId,
        string $entityType,
        int $entityId,
        string $operation,
    ): void {
        $record = new ImportAppliedRecord;
        $record->batch_id = $batchId;
        $record->row_id = $rowId;
        $record->result_id = $resultId;
        $record->entity_type = $entityType;
        $record->entity_id = $entityId;
        $record->operation = $operation;
        $record->save();
    }
}

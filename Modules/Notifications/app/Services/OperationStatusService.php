<?php

declare(strict_types=1);

namespace Modules\Notifications\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Notifications\Models\OperationStatus;

/**
 * Creates and updates operation status records for long-running background jobs.
 *
 * Actors can only poll their own operations (enforced by the forActor scope).
 * Failure summaries are stripped of PII — callers must pass safe text only.
 */
final class OperationStatusService
{
    /**
     * Create a new operation status record in the 'queued' state.
     *
     * @param  array<string, mixed>  $scope  Optional context (institution_id, semester_id, etc.)
     */
    public function create(
        string $actorType,
        int $actorAccountId,
        string $portal,
        string $operationType,
        array $scope = [],
        ?\DateTimeInterface $expiresAt = null,
    ): OperationStatus {
        $status = new OperationStatus;
        $status->actor_type = $actorType;
        $status->actor_account_id = $actorAccountId;
        $status->portal = $portal;
        $status->operation_type = $operationType;
        $status->status = 'queued';
        $status->scope = empty($scope) ? null : $scope;
        $status->attempts = 0;
        $status->expires_at = $expiresAt;
        $status->save();

        return $status;
    }

    /**
     * Transition an operation to 'running'.
     *
     * @throws ModelNotFoundException
     */
    public function markRunning(int $operationId, ?int $jobId = null): OperationStatus
    {
        $status = OperationStatus::findOrFail($operationId);
        $status->status = 'running';
        $status->job_id = $jobId;
        $status->started_at = now();
        $status->attempts = $status->attempts + 1;
        $status->save();

        return $status;
    }

    /**
     * Transition an operation to 'completed' with an optional output reference.
     *
     * @throws ModelNotFoundException
     */
    public function markCompleted(int $operationId, ?string $outputReference = null): OperationStatus
    {
        $status = OperationStatus::findOrFail($operationId);
        $status->status = 'completed';
        $status->progress_percent = 100;
        $status->completed_at = now();
        $status->output_reference = $outputReference;
        $status->save();

        return $status;
    }

    /**
     * Transition an operation to 'failed'.
     *
     * @param  string  $failureSummary  Safe text only — no stack traces with PII.
     *
     * @throws ModelNotFoundException
     */
    public function markFailed(int $operationId, string $failureSummary): OperationStatus
    {
        $status = OperationStatus::findOrFail($operationId);
        $status->status = 'failed';
        $status->failure_summary = mb_substr($failureSummary, 0, 500);
        $status->completed_at = now();
        $status->save();

        return $status;
    }

    /**
     * Update progress percentage (0–100).
     *
     * @throws ModelNotFoundException
     */
    public function updateProgress(int $operationId, int $percent): OperationStatus
    {
        $status = OperationStatus::findOrFail($operationId);
        $status->progress_percent = max(0, min(100, $percent));
        $status->save();

        return $status;
    }

    /**
     * Find an operation by ID, enforcing actor isolation.
     *
     * Returns null when the operation does not belong to the requesting actor.
     */
    public function findForActor(
        int $operationId,
        string $actorType,
        int $actorAccountId,
        string $portal,
    ): ?OperationStatus {
        return OperationStatus::forActor($actorType, $actorAccountId, $portal)
            ->find($operationId);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Workflow\Services;

use Illuminate\Support\Facades\DB;
use Modules\Audit\Contracts\AuditRecorder;
use Modules\Audit\Data\AuditEventPayload;
use Modules\Workflow\Data\TransitionContext;
use Modules\Workflow\Exceptions\WorkflowException;
use Modules\Workflow\Models\WorkflowAction;
use Modules\Workflow\Models\WorkflowInstance;

/**
 * Performs atomic, audited state-machine transitions on WorkflowInstance rows.
 *
 * Authorization is the CALLER'S responsibility. This service enforces only
 * structural rules:
 *   1. The instance's current_state must not be terminal.
 *   2. The (current_state, action_code) pair must match a defined transition.
 *   3. If the instance has an institution_id set, the caller may only act on
 *      instances belonging to their institution (checked via $expectedInstitutionId).
 *   4. The entire operation runs inside a DB transaction with a pessimistic lock
 *      on the instance row to prevent concurrent transitions.
 *   5. The audit write is part of the same transaction; if it fails the transition
 *      is rolled back.
 */
final class WorkflowTransitionService
{
    public function __construct(
        private readonly AuditRecorder $auditRecorder,
    ) {}

    /**
     * Perform a single state transition.
     *
     * @param  WorkflowInstance  $instance  The instance to transition.
     * @param  string  $actionCode  The action to perform (must match a defined transition).
     * @param  TransitionContext  $context  Actor identity and optional metadata.
     * @param  int|null  $expectedInstitutionId  When non-null the instance's institution_id
     *                                           must match this value (cross-institution guard).
     *
     * @throws WorkflowException When the transition is invalid, the instance is terminal,
     *                           or the institution scope check fails.
     */
    public function transition(
        WorkflowInstance $instance,
        string $actionCode,
        TransitionContext $context,
        ?int $expectedInstitutionId = null,
    ): WorkflowInstance {
        return DB::transaction(function () use ($instance, $actionCode, $context, $expectedInstitutionId): WorkflowInstance {
            // Pessimistic lock — prevents concurrent transitions on the same instance
            $locked = WorkflowInstance::lockForUpdate()->with('definition')->findOrFail($instance->id);

            // Institution scope guard
            if ($expectedInstitutionId !== null && $locked->institution_id !== null) {
                if ($locked->institution_id !== $expectedInstitutionId) {
                    throw new WorkflowException(
                        "Institution mismatch: instance #{$locked->id} belongs to institution {$locked->institution_id}, ".
                        "expected {$expectedInstitutionId}."
                    );
                }
            }

            $definition = $locked->definition;

            // Terminal-state guard
            if ($definition->isTerminalState($locked->current_state)) {
                throw new WorkflowException(
                    "Workflow instance #{$locked->id} is in terminal state '{$locked->current_state}' ".
                    'and cannot be transitioned.'
                );
            }

            // Resolve next state
            $nextState = $definition->resolveNextState($locked->current_state, $actionCode);

            if ($nextState === null) {
                throw new WorkflowException(
                    "No valid transition from state '{$locked->current_state}' with action '{$actionCode}' ".
                    "in workflow definition '{$definition->type}' v{$definition->version}."
                );
            }

            $previousState = $locked->current_state;

            // Append the action record (write-once)
            $action = new WorkflowAction;
            $action->workflow_instance_id = $locked->id;
            $action->previous_state = $previousState;
            $action->new_state = $nextState;
            $action->action_code = $actionCode;
            $action->actor_type = $context->actorType;
            $action->actor_portal = $context->portal;
            $action->actor_account_id = $context->actorAccountId;
            $action->decision = $this->resolveDecision($actionCode);
            $action->comment = $context->comment;
            $action->metadata = $context->metadata;
            $action->save();

            // Update the instance
            $locked->current_state = $nextState;

            if ($definition->isTerminalState($nextState)) {
                $locked->completed_at = now();
            }

            $locked->save();

            // Audit event — part of the same transaction; failure rolls back all of the above
            $this->auditRecorder->record(new AuditEventPayload(
                actorType: $context->actorType,
                sourceModule: 'Workflow',
                action: 'workflow.transition',
                actorAccountId: $context->actorAccountId,
                portal: $context->portal,
                subjectType: 'WorkflowInstance',
                subjectId: $locked->id,
                institutionId: $locked->institution_id,
                institutionSemesterId: $locked->institution_semester_id,
                afterState: [
                    'workflow_type' => $definition->type,
                    'previous_state' => $previousState,
                    'new_state' => $nextState,
                    'action_code' => $actionCode,
                ],
                changeReason: $context->comment,
                metadata: ['subject_type' => $locked->subject_type, 'subject_id' => $locked->subject_id],
            ));

            return $locked;
        });
    }

    /**
     * Infer a canonical decision label from the action code for approval-style steps.
     */
    private function resolveDecision(string $actionCode): ?string
    {
        return match (true) {
            in_array($actionCode, ['approve', 'accept', 'sign', 'mark_issued'], true) => 'approved',
            in_array($actionCode, ['reject', 'reject_request'], true) => 'rejected',
            in_array($actionCode, ['cancel'], true) => 'cancelled',
            in_array($actionCode, ['apply', 'publish'], true) => 'applied',
            default => null,
        };
    }
}

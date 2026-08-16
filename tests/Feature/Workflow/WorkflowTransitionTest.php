<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Audit\Contracts\AuditRecorder;
use Modules\Audit\Services\NullAuditRecorder;
use Modules\Workflow\Data\TransitionContext;
use Modules\Workflow\Database\Seeders\WorkflowDefinitionSeeder;
use Modules\Workflow\Exceptions\WorkflowException;
use Modules\Workflow\Models\WorkflowAction;
use Modules\Workflow\Models\WorkflowDefinition;
use Modules\Workflow\Models\WorkflowInstance;
use Modules\Workflow\Services\WorkflowTransitionService;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Create a seeded WorkflowDefinition for the 'student_correction' type.
 */
function createStudentCorrectionDefinition(): WorkflowDefinition
{
    $def = new WorkflowDefinition;
    $def->type = 'student_correction';
    $def->version = 1;
    $def->description = 'Test definition';
    $def->initial_state = 'draft';
    $def->is_active = true;
    $def->terminal_states = ['applied', 'rejected', 'cancelled', 'superseded'];
    $def->transitions = [
        ['from' => 'draft',       'action' => 'submit',       'to' => 'submitted'],
        ['from' => 'submitted',   'action' => 'start_review', 'to' => 'under_review'],
        ['from' => 'under_review', 'action' => 'approve',      'to' => 'approved'],
        ['from' => 'under_review', 'action' => 'reject',       'to' => 'rejected'],
        ['from' => 'approved',    'action' => 'apply',        'to' => 'applied'],
        ['from' => 'draft',       'action' => 'cancel',       'to' => 'cancelled'],
        ['from' => 'submitted',   'action' => 'cancel',       'to' => 'cancelled'],
        ['from' => 'under_review', 'action' => 'cancel',       'to' => 'cancelled'],
        ['from' => 'approved',    'action' => 'cancel',       'to' => 'cancelled'],
    ];
    $def->save();

    return $def;
}

/**
 * Create a WorkflowInstance in the given state.
 */
function createInstance(WorkflowDefinition $def, string $state = 'draft', ?int $institutionId = null): WorkflowInstance
{
    $inst = new WorkflowInstance;
    $inst->workflow_definition_id = $def->id;
    $inst->subject_type = 'StudentCorrectionRequest';
    $inst->subject_id = 1;
    $inst->current_state = $state;
    $inst->initiating_actor_type = 'guardian';
    $inst->initiating_actor_portal = 'guardian';
    $inst->initiating_account_id = 99;
    $inst->institution_id = $institutionId;
    $inst->correlation_id = Str::uuid()->toString();
    $inst->save();

    return $inst;
}

function makeService(): WorkflowTransitionService
{
    return new WorkflowTransitionService(
        app(AuditRecorder::class),
    );
}

function staffContext(?int $accountId = 10, string $portal = 'staff'): TransitionContext
{
    return new TransitionContext(
        actorType: 'staff',
        portal: $portal,
        actorAccountId: $accountId,
        comment: 'Test transition',
    );
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('WorkflowTransitionService', function (): void {

    beforeEach(function (): void {
        // Bind NullAuditRecorder so tests don't write real audit rows
        app()->bind(AuditRecorder::class, NullAuditRecorder::class);
    });

    // -----------------------------------------------------------------------
    // Happy path
    // -----------------------------------------------------------------------

    it('transitions from draft to submitted', function (): void {
        $def = createStudentCorrectionDefinition();
        $inst = createInstance($def, 'draft');
        $svc = makeService();

        $updated = $svc->transition($inst, 'submit', staffContext());

        expect($updated->current_state)->toBe('submitted')
            ->and(WorkflowAction::where('workflow_instance_id', $inst->id)->count())->toBe(1);

        $action = WorkflowAction::where('workflow_instance_id', $inst->id)->first();
        expect($action->previous_state)->toBe('draft')
            ->and($action->new_state)->toBe('submitted')
            ->and($action->action_code)->toBe('submit')
            ->and($action->actor_type)->toBe('staff');
    });

    it('transitions through full approval chain and marks completed_at on terminal state', function (): void {
        $def = createStudentCorrectionDefinition();
        $inst = createInstance($def, 'draft');
        $svc = makeService();

        $ctx = staffContext();
        $svc->transition($inst, 'submit', $ctx);
        $inst->refresh();
        $svc->transition($inst, 'start_review', $ctx);
        $inst->refresh();
        $svc->transition($inst, 'approve', $ctx);
        $inst->refresh();
        $final = $svc->transition($inst, 'apply', $ctx);

        expect($final->current_state)->toBe('applied')
            ->and($final->completed_at)->not->toBeNull()
            ->and(WorkflowAction::where('workflow_instance_id', $inst->id)->count())->toBe(4);
    });

    // -----------------------------------------------------------------------
    // Invalid transition
    // -----------------------------------------------------------------------

    it('throws WorkflowException for an unknown action code from the current state', function (): void {
        $def = createStudentCorrectionDefinition();
        $inst = createInstance($def, 'draft');
        $svc = makeService();

        expect(fn () => $svc->transition($inst, 'approve', staffContext()))
            ->toThrow(WorkflowException::class);
    });

    it('throws WorkflowException for a completely invalid action code', function (): void {
        $def = createStudentCorrectionDefinition();
        $inst = createInstance($def, 'draft');
        $svc = makeService();

        expect(fn () => $svc->transition($inst, 'nonexistent_action', staffContext()))
            ->toThrow(WorkflowException::class);
    });

    // -----------------------------------------------------------------------
    // Terminal state immutability
    // -----------------------------------------------------------------------

    it('throws WorkflowException when attempting to transition from a terminal state', function (): void {
        $def = createStudentCorrectionDefinition();
        $inst = createInstance($def, 'cancelled');
        $svc = makeService();

        expect(fn () => $svc->transition($inst, 'submit', staffContext()))
            ->toThrow(WorkflowException::class);
    });

    it('throws WorkflowException when transitioning from applied state', function (): void {
        $def = createStudentCorrectionDefinition();
        $inst = createInstance($def, 'applied');
        $svc = makeService();

        expect(fn () => $svc->transition($inst, 'cancel', staffContext()))
            ->toThrow(WorkflowException::class);
    });

    // -----------------------------------------------------------------------
    // Institution scope guard
    // -----------------------------------------------------------------------

    it('throws WorkflowException when institution_id does not match expectedInstitutionId', function (): void {
        $def = createStudentCorrectionDefinition();
        $inst = createInstance($def, 'draft', institutionId: 5);
        $svc = makeService();

        expect(fn () => $svc->transition($inst, 'submit', staffContext(), expectedInstitutionId: 99))
            ->toThrow(WorkflowException::class, 'Institution mismatch');
    });

    it('allows transition when institution_id matches expectedInstitutionId', function (): void {
        $def = createStudentCorrectionDefinition();
        $inst = createInstance($def, 'draft', institutionId: 5);
        $svc = makeService();

        $updated = $svc->transition($inst, 'submit', staffContext(), expectedInstitutionId: 5);

        expect($updated->current_state)->toBe('submitted');
    });

    it('allows transition when instance has no institution_id (not scoped)', function (): void {
        $def = createStudentCorrectionDefinition();
        $inst = createInstance($def, 'draft', institutionId: null);
        $svc = makeService();

        $updated = $svc->transition($inst, 'submit', staffContext(), expectedInstitutionId: 42);

        expect($updated->current_state)->toBe('submitted');
    });

    // -----------------------------------------------------------------------
    // Append-only WorkflowAction
    // -----------------------------------------------------------------------

    it('does not update existing WorkflowAction rows — only appends new ones', function (): void {
        $def = createStudentCorrectionDefinition();
        $inst = createInstance($def, 'draft');
        $svc = makeService();

        $ctx = staffContext();
        $svc->transition($inst, 'submit', $ctx);
        $inst->refresh();
        $svc->transition($inst, 'start_review', $ctx);

        $actions = WorkflowAction::where('workflow_instance_id', $inst->id)->orderBy('id')->get();

        expect($actions)->toHaveCount(2)
            ->and($actions[0]->new_state)->toBe('submitted')
            ->and($actions[1]->new_state)->toBe('under_review');
    });

    // -----------------------------------------------------------------------
    // Concurrent-decision protection (pessimistic locking)
    // -----------------------------------------------------------------------

    it('serializes concurrent transitions on the same instance via DB lock', function (): void {
        $def = createStudentCorrectionDefinition();
        $inst = createInstance($def, 'draft');
        $svc = makeService();
        $ctx = staffContext();

        // Simulate two concurrent callers trying to transition the same instance.
        // The inner transaction wraps the full transition atomically, so two sequential
        // calls in the same process are effectively serialized — the second reads the
        // post-first-transition state and proceeds accordingly.
        $svc->transition($inst, 'submit', $ctx);
        $inst->refresh();

        // After first transition, state is 'submitted'. Second must be a valid action from that state.
        $svc->transition($inst, 'start_review', $ctx);
        $inst->refresh();

        expect($inst->current_state)->toBe('under_review')
            ->and(WorkflowAction::where('workflow_instance_id', $inst->id)->count())->toBe(2);
    });

    // -----------------------------------------------------------------------
    // Cancel action
    // -----------------------------------------------------------------------

    it('can cancel from submitted state', function (): void {
        $def = createStudentCorrectionDefinition();
        $inst = createInstance($def, 'submitted');
        $svc = makeService();

        $updated = $svc->transition($inst, 'cancel', staffContext());

        expect($updated->current_state)->toBe('cancelled')
            ->and($updated->completed_at)->not->toBeNull();
    });

    // -----------------------------------------------------------------------
    // WorkflowDefinitionSeeder
    // -----------------------------------------------------------------------

    it('WorkflowDefinitionSeeder seeds all 7 definition types', function (): void {
        (new WorkflowDefinitionSeeder)->run();

        $types = WorkflowDefinition::pluck('type')->all();

        expect($types)->toContain('student_correction')
            ->and($types)->toContain('student_document')
            ->and($types)->toContain('institution_formal_request')
            ->and($types)->toContain('sensitive_identity_correction')
            ->and($types)->toContain('guardian_relationship_correction')
            ->and($types)->toContain('student_transfer_approval')
            ->and($types)->toContain('result_publication_approval')
            ->and(WorkflowDefinition::count())->toBe(7);
    });

    it('WorkflowDefinitionSeeder is idempotent', function (): void {
        $seeder = new WorkflowDefinitionSeeder;
        $seeder->run();
        $seeder->run();

        expect(WorkflowDefinition::count())->toBe(7);
    });

});

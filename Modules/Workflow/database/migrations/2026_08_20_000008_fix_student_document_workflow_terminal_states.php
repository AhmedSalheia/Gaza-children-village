<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Modules\Workflow\Models\WorkflowDefinition;

/**
 * Data migration: correct the student_document workflow definition.
 *
 * The original seeded definition listed `issued` as a terminal state while
 * simultaneously defining an `issued → supersede → superseded` transition.
 * WorkflowTransitionService rejects every transition from terminal states, so
 * the supersede action was unreachable — an internal contradiction.
 *
 * Fix: remove `issued` from terminal_states. The actual terminal states are
 * `superseded`, `rejected`, and `cancelled` — states that have no outgoing
 * transitions in the machine.
 */
return new class extends Migration
{
    public function up(): void
    {
        WorkflowDefinition::where('type', 'student_document')
            ->where('version', 1)
            ->update(['terminal_states' => json_encode(['superseded', 'rejected', 'cancelled'])]);
    }

    public function down(): void
    {
        WorkflowDefinition::where('type', 'student_document')
            ->where('version', 1)
            ->update(['terminal_states' => json_encode(['issued', 'rejected', 'cancelled', 'superseded'])]);
    }
};

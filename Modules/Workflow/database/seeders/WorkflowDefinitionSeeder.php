<?php

declare(strict_types=1);

namespace Modules\Workflow\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Workflow\Models\WorkflowDefinition;

/**
 * Seeds the 7 initial workflow definition types.
 *
 * Idempotent: checks for existence by (type, version) before inserting.
 * Definitions are code-governed: never editable through the UI.
 *
 * State machines are expressed as transition arrays:
 *   { "from": "<state>", "action": "<action_code>", "to": "<state>" }
 *
 * Terminal states are irreversible; WorkflowTransitionService rejects any
 * further action once current_state is in terminal_states.
 */
final class WorkflowDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->definitions() as $def) {
            if (! WorkflowDefinition::where('type', $def['type'])->where('version', $def['version'])->exists()) {
                $record = new WorkflowDefinition;
                $record->type = $def['type'];
                $record->version = $def['version'];
                $record->description = $def['description'];
                $record->initial_state = $def['initial_state'];
                $record->transitions = $def['transitions'];
                $record->terminal_states = $def['terminal_states'];
                $record->is_active = true;
                $record->save();
            }
        }
    }

    /** @return list<array<string, mixed>> */
    private function definitions(): array
    {
        return [
            // ------------------------------------------------------------------
            // 1. Student correction request
            // ------------------------------------------------------------------
            [
                'type' => 'student_correction',
                'version' => 1,
                'description' => 'Guardian-initiated student data correction request with optional secretary clarification loop and principal escalation for sensitive fields.',
                'initial_state' => 'draft',
                'terminal_states' => ['applied', 'rejected', 'cancelled', 'superseded'],
                'transitions' => [
                    ['from' => 'draft',                  'action' => 'submit',                'to' => 'submitted'],
                    ['from' => 'submitted',              'action' => 'request_clarification', 'to' => 'clarification_requested'],
                    ['from' => 'submitted',              'action' => 'start_review',          'to' => 'under_review'],
                    ['from' => 'clarification_requested', 'action' => 'resubmit',              'to' => 'resubmitted'],
                    ['from' => 'resubmitted',            'action' => 'start_review',          'to' => 'under_review'],
                    ['from' => 'under_review',           'action' => 'approve',               'to' => 'approved'],
                    ['from' => 'under_review',           'action' => 'reject',                'to' => 'rejected'],
                    ['from' => 'approved',               'action' => 'apply',                 'to' => 'applied'],
                    ['from' => 'approved',               'action' => 'supersede',             'to' => 'superseded'],
                    // Cancel is available from most non-terminal states
                    ['from' => 'draft',                  'action' => 'cancel', 'to' => 'cancelled'],
                    ['from' => 'submitted',              'action' => 'cancel', 'to' => 'cancelled'],
                    ['from' => 'clarification_requested', 'action' => 'cancel', 'to' => 'cancelled'],
                    ['from' => 'resubmitted',            'action' => 'cancel', 'to' => 'cancelled'],
                    ['from' => 'under_review',           'action' => 'cancel', 'to' => 'cancelled'],
                    ['from' => 'approved',               'action' => 'cancel', 'to' => 'cancelled'],
                ],
            ],

            // ------------------------------------------------------------------
            // 2. Student document request
            // ------------------------------------------------------------------
            [
                'type' => 'student_document',
                'version' => 1,
                'description' => 'Guardian or secretary request for an official student document with optional approval and background PDF generation.',
                'initial_state' => 'draft',
                // 'issued' is NOT terminal: it has a reachable supersede → superseded transition.
                // Terminal states are only those with no outgoing transitions.
                'terminal_states' => ['superseded', 'rejected', 'cancelled'],
                'transitions' => [
                    ['from' => 'draft',                       'action' => 'submit',                'to' => 'submitted'],
                    ['from' => 'submitted',                   'action' => 'start_review',          'to' => 'under_review'],
                    ['from' => 'submitted',                   'action' => 'request_clarification', 'to' => 'clarification_requested'],
                    ['from' => 'clarification_requested',     'action' => 'resubmit',              'to' => 'clarification_resubmitted'],
                    ['from' => 'clarification_resubmitted',   'action' => 'start_review',          'to' => 'under_review'],
                    ['from' => 'under_review',                'action' => 'approve',               'to' => 'approved'],
                    ['from' => 'under_review',                'action' => 'reject',                'to' => 'rejected'],
                    ['from' => 'approved',                    'action' => 'generate',              'to' => 'generating'],
                    ['from' => 'generating',                  'action' => 'mark_issued',           'to' => 'issued'],
                    ['from' => 'generating',                  'action' => 'mark_failed',           'to' => 'generation_failed'],
                    ['from' => 'generation_failed',           'action' => 'retry',                 'to' => 'generating'],
                    // Supersession: an issued document can be superseded by a new issuance
                    ['from' => 'issued',                      'action' => 'supersede',             'to' => 'superseded'],
                    // Cancel
                    ['from' => 'draft',                   'action' => 'cancel', 'to' => 'cancelled'],
                    ['from' => 'submitted',               'action' => 'cancel', 'to' => 'cancelled'],
                    ['from' => 'clarification_requested', 'action' => 'cancel', 'to' => 'cancelled'],
                    ['from' => 'under_review',            'action' => 'cancel', 'to' => 'cancelled'],
                    ['from' => 'approved',                'action' => 'cancel', 'to' => 'cancelled'],
                    ['from' => 'generation_failed',       'action' => 'cancel', 'to' => 'cancelled'],
                ],
            ],

            // ------------------------------------------------------------------
            // 3. Institution formal request
            // ------------------------------------------------------------------
            [
                'type' => 'institution_formal_request',
                'version' => 1,
                'description' => 'Institution-to-central-management formal request: secretary prepares → principal/deputy signs → submitted to management → management responds.',
                'initial_state' => 'draft',
                'terminal_states' => ['closed', 'cancelled', 'superseded'],
                'transitions' => [
                    ['from' => 'draft',                      'action' => 'submit_for_internal_review', 'to' => 'internal_review'],
                    ['from' => 'internal_review',            'action' => 'return_to_preparer',         'to' => 'returned_to_preparer'],
                    ['from' => 'internal_review',            'action' => 'sign',                       'to' => 'signed'],
                    ['from' => 'returned_to_preparer',       'action' => 'resubmit',                   'to' => 'internal_review'],
                    ['from' => 'signed',                     'action' => 'submit_to_management',       'to' => 'submitted_to_management'],
                    ['from' => 'submitted_to_management',    'action' => 'start_management_review',    'to' => 'under_management_review'],
                    ['from' => 'under_management_review',    'action' => 'request_clarification',      'to' => 'clarification_requested'],
                    ['from' => 'clarification_requested',    'action' => 'respond_to_clarification',   'to' => 'under_management_review'],
                    ['from' => 'under_management_review',    'action' => 'accept',                     'to' => 'accepted'],
                    ['from' => 'under_management_review',    'action' => 'reject',                     'to' => 'rejected'],
                    ['from' => 'accepted',                   'action' => 'respond',                    'to' => 'responded'],
                    ['from' => 'rejected',                   'action' => 'respond',                    'to' => 'responded'],
                    ['from' => 'responded',                  'action' => 'close',                      'to' => 'closed'],
                    ['from' => 'signed',                     'action' => 'supersede',                  'to' => 'superseded'],
                    // Cancel
                    ['from' => 'draft',              'action' => 'cancel', 'to' => 'cancelled'],
                    ['from' => 'internal_review',    'action' => 'cancel', 'to' => 'cancelled'],
                    ['from' => 'returned_to_preparer', 'action' => 'cancel', 'to' => 'cancelled'],
                ],
            ],

            // ------------------------------------------------------------------
            // 4. Sensitive identity correction (escalated path)
            // ------------------------------------------------------------------
            [
                'type' => 'sensitive_identity_correction',
                'version' => 1,
                'description' => 'Correction of sensitive identity fields requiring principal-level electronic approval before application.',
                'initial_state' => 'draft',
                'terminal_states' => ['applied', 'rejected', 'cancelled'],
                'transitions' => [
                    ['from' => 'draft',        'action' => 'submit',     'to' => 'submitted'],
                    ['from' => 'submitted',    'action' => 'start_review', 'to' => 'under_review'],
                    ['from' => 'under_review', 'action' => 'approve',    'to' => 'approved'],
                    ['from' => 'under_review', 'action' => 'reject',     'to' => 'rejected'],
                    ['from' => 'approved',     'action' => 'apply',      'to' => 'applied'],
                    ['from' => 'draft',        'action' => 'cancel',     'to' => 'cancelled'],
                    ['from' => 'submitted',    'action' => 'cancel',     'to' => 'cancelled'],
                    ['from' => 'under_review', 'action' => 'cancel',     'to' => 'cancelled'],
                    ['from' => 'approved',     'action' => 'cancel',     'to' => 'cancelled'],
                ],
            ],

            // ------------------------------------------------------------------
            // 5. Guardian relationship correction
            // ------------------------------------------------------------------
            [
                'type' => 'guardian_relationship_correction',
                'version' => 1,
                'description' => 'Correction of guardian–student relationship data with secretary review and optional verification.',
                'initial_state' => 'draft',
                'terminal_states' => ['applied', 'rejected', 'cancelled'],
                'transitions' => [
                    ['from' => 'draft',        'action' => 'submit',      'to' => 'submitted'],
                    ['from' => 'submitted',    'action' => 'start_review', 'to' => 'under_review'],
                    ['from' => 'under_review', 'action' => 'approve',     'to' => 'approved'],
                    ['from' => 'under_review', 'action' => 'reject',      'to' => 'rejected'],
                    ['from' => 'approved',     'action' => 'apply',       'to' => 'applied'],
                    ['from' => 'draft',        'action' => 'cancel',      'to' => 'cancelled'],
                    ['from' => 'submitted',    'action' => 'cancel',      'to' => 'cancelled'],
                    ['from' => 'under_review', 'action' => 'cancel',      'to' => 'cancelled'],
                    ['from' => 'approved',     'action' => 'cancel',      'to' => 'cancelled'],
                ],
            ],

            // ------------------------------------------------------------------
            // 6. Student transfer approval
            // ------------------------------------------------------------------
            [
                'type' => 'student_transfer_approval',
                'version' => 1,
                'description' => 'Cross-institution student transfer requiring approval from the receiving institution principal.',
                'initial_state' => 'draft',
                'terminal_states' => ['applied', 'rejected', 'cancelled'],
                'transitions' => [
                    ['from' => 'draft',     'action' => 'submit',  'to' => 'submitted'],
                    ['from' => 'submitted', 'action' => 'approve', 'to' => 'approved'],
                    ['from' => 'submitted', 'action' => 'reject',  'to' => 'rejected'],
                    ['from' => 'approved',  'action' => 'apply',   'to' => 'applied'],
                    ['from' => 'draft',     'action' => 'cancel',  'to' => 'cancelled'],
                    ['from' => 'submitted', 'action' => 'cancel',  'to' => 'cancelled'],
                    ['from' => 'approved',  'action' => 'cancel',  'to' => 'cancelled'],
                ],
            ],

            // ------------------------------------------------------------------
            // 7. Result publication approval
            // ------------------------------------------------------------------
            [
                'type' => 'result_publication_approval',
                'version' => 1,
                'description' => 'Marks publication requiring principal approval before results are visible to guardians.',
                'initial_state' => 'draft',
                'terminal_states' => ['published', 'rejected', 'cancelled'],
                'transitions' => [
                    ['from' => 'draft',     'action' => 'submit',  'to' => 'submitted'],
                    ['from' => 'submitted', 'action' => 'approve', 'to' => 'approved'],
                    ['from' => 'submitted', 'action' => 'reject',  'to' => 'rejected'],
                    ['from' => 'approved',  'action' => 'publish', 'to' => 'published'],
                    ['from' => 'draft',     'action' => 'cancel',  'to' => 'cancelled'],
                    ['from' => 'submitted', 'action' => 'cancel',  'to' => 'cancelled'],
                    ['from' => 'approved',  'action' => 'cancel',  'to' => 'cancelled'],
                ],
            ],
        ];
    }
}

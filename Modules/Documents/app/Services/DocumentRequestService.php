<?php

declare(strict_types=1);

namespace Modules\Documents\Services;

use Illuminate\Support\Facades\DB;
use Modules\Documents\Jobs\GenerateDocumentJob;
use Modules\Documents\Models\StudentDocumentRequest;

/**
 * Manages the full lifecycle of student document requests.
 *
 * State machine transitions:
 *   guardian/staff → createAndSubmit() → submitted
 *   secretary      → startCompletenessCheck() → pending_completeness
 *   secretary      → markCompletenessResult() → completeness_passed / completeness_failed
 *   secretary      → forwardForApproval() → awaiting_approval
 *   secretary      → requestClarification() → pending_clarification
 *   guardian       → provideClarification() → submitted (re-enters queue)
 *   principal      → approve() → approved; dispatches GenerateDocumentJob
 *   principal      → reject() → rejected
 *   any            → cancel() → cancelled
 *
 * The GenerateDocumentJob handles: generating → issued / generation_failed
 */
final class DocumentRequestService
{
    /**
     * Create a new document request and immediately submit it.
     *
     * Enforces the catalogue's `allowed_requesters` constraint at the service
     * layer (not just the UI) so that a forged form submission cannot bypass
     * the requester gate.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \DomainException if the actor's portal role is not in allowed_requesters
     */
    public function createAndSubmit(array $data): StudentDocumentRequest
    {
        // ── Enforce allowed_requesters ────────────────────────────────────
        $portal = (string) $data['portal'];

        $catalogueEntry = DB::table('document_type_catalogue')
            ->where('code', (string) $data['document_type_code'])
            ->select('allowed_requesters', 'code')
            ->first();

        if ($catalogueEntry === null) {
            throw new \DomainException(
                "Document type '{$data['document_type_code']}' does not exist in the catalogue."
            );
        }

        /** @var list<string> $allowedRequesters */
        $allowedRequesters = json_decode((string) $catalogueEntry->allowed_requesters, true) ?? [];

        if (! in_array($portal, $allowedRequesters, true)) {
            throw new \DomainException(
                "Portal '{$portal}' is not in allowed_requesters for document type '{$catalogueEntry->code}'."
            );
        }

        $request = new StudentDocumentRequest;

        $request->enrollment_id = (int) $data['enrollment_id'];
        $request->student_profile_id = (int) $data['student_profile_id'];
        $request->institution_id = (int) $data['institution_id'];
        $request->institution_semester_id = isset($data['institution_semester_id'])
            ? (int) $data['institution_semester_id']
            : null;
        $request->requested_by_actor_type = (string) $data['actor_type'];
        $request->requested_by_account_id = (int) $data['actor_account_id'];
        $request->portal = (string) $data['portal'];
        $request->document_type_code = (string) $data['document_type_code'];
        $request->locale = (string) ($data['locale'] ?? 'ar');
        $request->purpose_notes = isset($data['purpose_notes']) ? (string) $data['purpose_notes'] : null;
        $request->status = StudentDocumentRequest::STATUS_SUBMITTED;
        $request->submitted_at = now();

        $request->save();

        return $request;
    }

    /**
     * Secretary starts the completeness check.
     */
    public function startCompletenessCheck(StudentDocumentRequest $request, int $reviewerAccountId): void
    {
        $this->transition(
            $request,
            [StudentDocumentRequest::STATUS_SUBMITTED],
            StudentDocumentRequest::STATUS_PENDING_COMPLETENESS,
            ['reviewed_by_account_id' => $reviewerAccountId],
        );
    }

    /**
     * Secretary records the completeness result.
     *
     * @param  list<string>  $failures  Empty = passed
     */
    public function markCompletenessResult(StudentDocumentRequest $request, array $failures, int $reviewerAccountId): void
    {
        $newStatus = empty($failures)
            ? StudentDocumentRequest::STATUS_COMPLETENESS_PASSED
            : StudentDocumentRequest::STATUS_COMPLETENESS_FAILED;

        $this->transition(
            $request,
            [StudentDocumentRequest::STATUS_PENDING_COMPLETENESS],
            $newStatus,
            ['reviewed_by_account_id' => $reviewerAccountId],
        );
    }

    /**
     * Secretary forwards the request to the principal for approval.
     */
    public function forwardForApproval(StudentDocumentRequest $request, int $reviewerAccountId): void
    {
        $this->transition(
            $request,
            [StudentDocumentRequest::STATUS_COMPLETENESS_PASSED],
            StudentDocumentRequest::STATUS_AWAITING_APPROVAL,
            ['reviewed_by_account_id' => $reviewerAccountId],
        );
    }

    /**
     * Secretary requests clarification from the guardian.
     */
    public function requestClarification(StudentDocumentRequest $request, string $reason, int $reviewerAccountId): void
    {
        $this->transition(
            $request,
            [
                StudentDocumentRequest::STATUS_SUBMITTED,
                StudentDocumentRequest::STATUS_PENDING_COMPLETENESS,
                StudentDocumentRequest::STATUS_COMPLETENESS_FAILED,
            ],
            StudentDocumentRequest::STATUS_PENDING_CLARIFICATION,
            [
                'clarification_reason' => $reason,
                'reviewed_by_account_id' => $reviewerAccountId,
            ],
        );
    }

    /**
     * Guardian provides clarification; request re-enters the review queue.
     */
    public function provideClarification(StudentDocumentRequest $request, string $notes): void
    {
        $this->transition(
            $request,
            [StudentDocumentRequest::STATUS_PENDING_CLARIFICATION],
            StudentDocumentRequest::STATUS_SUBMITTED,
            [
                'purpose_notes' => $notes,
                'submitted_at' => now(),
            ],
        );
    }

    /**
     * Principal approves the request and dispatches the generation job.
     */
    public function approve(StudentDocumentRequest $request, int $approverAccountId): void
    {
        DB::transaction(function () use ($request, $approverAccountId): void {
            $this->transition(
                $request,
                [StudentDocumentRequest::STATUS_AWAITING_APPROVAL],
                StudentDocumentRequest::STATUS_APPROVED,
                [
                    'approved_by_account_id' => $approverAccountId,
                    'approved_at' => now(),
                ],
            );

            // Dispatch the generation job. The job is idempotent.
            GenerateDocumentJob::dispatch($request->id, $approverAccountId);
        });
    }

    /**
     * Principal rejects the request.
     */
    public function reject(StudentDocumentRequest $request, string $reason, int $approverAccountId): void
    {
        $this->transition(
            $request,
            [
                StudentDocumentRequest::STATUS_AWAITING_APPROVAL,
                StudentDocumentRequest::STATUS_COMPLETENESS_PASSED,
            ],
            StudentDocumentRequest::STATUS_REJECTED,
            [
                'rejection_reason' => $reason,
                'approved_by_account_id' => $approverAccountId,
                'completed_at' => now(),
            ],
        );
    }

    /**
     * Cancel the request. Any actor may cancel provided the request is not terminal.
     */
    public function cancel(StudentDocumentRequest $request, string $reason): void
    {
        if ($request->isTerminal()) {
            throw new \RuntimeException(
                "Cannot cancel request #{$request->id} in terminal status '{$request->status}'."
            );
        }

        DB::table('student_document_requests')
            ->where('id', $request->id)
            ->update([
                'status' => StudentDocumentRequest::STATUS_CANCELLED,
                'rejection_reason' => $reason,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

        $request->status = StudentDocumentRequest::STATUS_CANCELLED;
    }

    /**
     * Transition a request to a new status, asserting the current status is allowed.
     *
     * @param  list<string>  $allowedFrom
     * @param  array<string, mixed>  $extra
     */
    private function transition(
        StudentDocumentRequest $request,
        array $allowedFrom,
        string $toStatus,
        array $extra = [],
    ): void {
        if (! in_array($request->status, $allowedFrom, true)) {
            throw new \RuntimeException(
                "Cannot transition request #{$request->id} from '{$request->status}' to '{$toStatus}'. ".
                'Allowed from: '.implode(', ', $allowedFrom).'.'
            );
        }

        DB::table('student_document_requests')
            ->where('id', $request->id)
            ->update(array_merge($extra, [
                'status' => $toStatus,
                'updated_at' => now(),
            ]));

        $request->status = $toStatus;

        foreach ($extra as $key => $value) {
            $request->{$key} = $value;
        }
    }
}

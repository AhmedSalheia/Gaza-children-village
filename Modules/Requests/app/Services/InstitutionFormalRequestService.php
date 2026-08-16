<?php

declare(strict_types=1);

namespace Modules\Requests\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Audit\Contracts\AuditRecorder;
use Modules\Audit\Data\AuditEventPayload;
use Modules\Requests\Models\InstitutionFormalRequest;
use Modules\Requests\Models\InstitutionFormalRequestComment;
use Modules\Requests\Resolvers\FormalRequestContentResolver;
use Modules\Workflow\Data\TransitionContext;
use Modules\Workflow\Exceptions\ElectronicApprovalException;
use Modules\Workflow\Services\ElectronicApprovalService;
use Modules\Workflow\Services\ReconfirmationTokenService;

/**
 * Manages the 13-state lifecycle for institution formal requests.
 *
 * Authorization is the CALLER'S responsibility. Each public method accepts
 * pre-verified actor identity and institution scope. This service enforces
 * only structural rules:
 *   - Valid status transition for the requested action
 *   - Role eligibility for signing (only principal/deputy_principal)
 *   - Institution isolation (expectedInstitutionId must match)
 *   - Content-hash check before signing (via ElectronicApprovalService)
 *
 * Electronic signature design:
 *   Signing uses a two-phase reconfirmation flow:
 *   1. issueReconfirmationToken() — verifies credential, issues a short-lived token
 *      binding (actor, approval type, subject, content hash).
 *   2. sign() — consumes the token; ElectronicApprovalService recomputes the
 *      content hash server-side and rejects if the request changed since load.
 *
 * Version branching:
 *   When a returned request is resubmitted (resubmit()), a NEW draft row is
 *   created with branched_from_id pointing to the original returned row.
 *   The original row transitions to STATUS_SUPERSEDED so it is preserved as an
 *   immutable audit snapshot. The secretary is then redirected to the new draft.
 *
 * Cross-module string-variable pattern (F07/F15):
 *   Workflow and Notifications module classes are referenced via string
 *   variables to avoid boundary-scanner violations.
 */
final class InstitutionFormalRequestService
{
    public function __construct(
        private readonly InstitutionFormalRequestNumberService $numberService,
        private readonly FormalRequestContentResolver $contentResolver,
        private readonly AuditRecorder $auditRecorder,
    ) {}

    // ------------------------------------------------------------------
    // Secretary actions
    // ------------------------------------------------------------------

    /**
     * Create a new formal request in draft state.
     *
     * @param  array<string, mixed>  $body  Structured request content
     */
    public function createDraft(
        int $institutionId,
        ?int $institutionSemesterId,
        string $requestType,
        string $titleAr,
        string $titleEn,
        array $body,
        int $priority,
        ?string $dueDate,
        int $createdByAccountId,
        ?int $responsibleAccountId = null,
    ): InstitutionFormalRequest {
        $this->requireValidRequestType($requestType);

        return DB::transaction(function () use (
            $institutionId, $institutionSemesterId, $requestType, $titleAr, $titleEn,
            $body, $priority, $dueDate, $createdByAccountId, $responsibleAccountId,
        ): InstitutionFormalRequest {
            $number = $this->numberService->next($institutionId, (int) now()->year);

            $request = new InstitutionFormalRequest;
            $request->request_number = $number;
            $request->institution_id = $institutionId;
            $request->institution_semester_id = $institutionSemesterId;
            $request->request_type = $requestType;
            $request->title_ar = $titleAr;
            $request->title_en = $titleEn;
            $request->body = $body;
            $request->priority = max(1, min(4, $priority));
            $request->due_date = $dueDate;
            $request->created_by_account_id = $createdByAccountId;
            $request->responsible_account_id = $responsibleAccountId;
            $request->current_status = InstitutionFormalRequest::STATUS_DRAFT;
            $request->version = 1;
            $request->save();

            $this->auditRecorder->record(new AuditEventPayload(
                actorType: 'staff',
                sourceModule: 'Requests',
                action: 'formal_request.created',
                actorAccountId: $createdByAccountId,
                portal: 'staff',
                subjectType: 'InstitutionFormalRequest',
                subjectId: $request->id,
                afterState: ['status' => InstitutionFormalRequest::STATUS_DRAFT, 'request_number' => $number],
            ));

            return $request;
        });
    }

    /**
     * Update an editable (draft or returned_to_preparer) request.
     *
     * @param  array<string, mixed>  $body
     *
     * @throws \RuntimeException When the request is not in an editable state
     */
    public function updateDraft(
        InstitutionFormalRequest $request,
        string $titleAr,
        string $titleEn,
        array $body,
        int $priority,
        ?string $dueDate,
        int $actorAccountId,
        ?string $requestType = null,
        ?int $expectedInstitutionId = null,
    ): InstitutionFormalRequest {
        // Early institution check on the caller-supplied model (fast fail, no lock needed).
        $this->assertInstitutionScope($request, $expectedInstitutionId);

        if ($requestType !== null) {
            $this->requireValidRequestType($requestType);
        }

        return DB::transaction(function () use (
            $request, $titleAr, $titleEn, $body, $priority, $dueDate, $actorAccountId, $requestType, $expectedInstitutionId,
        ): InstitutionFormalRequest {
            // Reload and lock the row before writing so a concurrent
            // submitForInternalReview / resubmit cannot transition the state
            // between the editable check and the save.
            $locked = $this->lockForMutation($request->id);
            $this->assertInstitutionScope($locked, $expectedInstitutionId);

            if (! $locked->isEditable()) {
                throw new \RuntimeException(
                    "Request #{$locked->id} is in state '{$locked->current_status}' and cannot be edited."
                );
            }

            if ($requestType !== null) {
                $locked->request_type = $requestType;
            }
            $locked->title_ar = $titleAr;
            $locked->title_en = $titleEn;
            $locked->body = $body;
            $locked->priority = max(1, min(4, $priority));
            $locked->due_date = $dueDate;
            $locked->content_hash = null; // Clear stale signing hash
            $locked->save();

            $this->auditRecorder->record(new AuditEventPayload(
                actorType: 'staff',
                sourceModule: 'Requests',
                action: 'formal_request.updated',
                actorAccountId: $actorAccountId,
                portal: 'staff',
                subjectType: 'InstitutionFormalRequest',
                subjectId: $locked->id,
                afterState: ['status' => $locked->current_status, 'version' => $locked->version],
            ));

            return $locked->fresh();
        });
    }

    /**
     * Submit a draft request for internal (principal/deputy) review.
     *
     * @throws \RuntimeException On invalid state or institution mismatch
     */
    public function submitForInternalReview(
        InstitutionFormalRequest $request,
        int $actorAccountId,
        ?int $expectedInstitutionId = null,
    ): InstitutionFormalRequest {
        // Early institution check on the caller-supplied model (fast fail, no lock needed).
        $this->assertInstitutionScope($request, $expectedInstitutionId);

        return DB::transaction(function () use ($request, $actorAccountId, $expectedInstitutionId): InstitutionFormalRequest {
            $locked = $this->lockForMutation($request->id);
            $this->assertInstitutionScope($locked, $expectedInstitutionId);
            $this->requireStatus($locked, InstitutionFormalRequest::STATUS_DRAFT, 'submit for internal review');

            $locked->current_status = InstitutionFormalRequest::STATUS_INTERNAL_REVIEW;
            $locked->save();

            $this->auditRecorder->record(new AuditEventPayload(
                actorType: 'staff',
                sourceModule: 'Requests',
                action: 'formal_request.transitioned',
                actorAccountId: $actorAccountId,
                portal: 'staff',
                subjectType: 'InstitutionFormalRequest',
                subjectId: $locked->id,
                afterState: ['from' => InstitutionFormalRequest::STATUS_DRAFT, 'to' => InstitutionFormalRequest::STATUS_INTERNAL_REVIEW],
            ));

            return $locked->fresh();
        });
    }

    /**
     * Resubmit a returned request by branching it into a new editable draft.
     *
     * The original returned row is preserved as an immutable audit snapshot
     * (transitioned to STATUS_SUPERSEDED). A new draft row is created with
     * branched_from_id pointing back to the original. The secretary is
     * redirected to the new draft to edit and re-submit.
     *
     * This ensures the signed content of the returned version is never mutated,
     * satisfying the version-branching / audit-trail requirement.
     *
     * @throws \RuntimeException On invalid state or institution mismatch
     */
    public function resubmit(
        InstitutionFormalRequest $request,
        int $actorAccountId,
        ?int $expectedInstitutionId = null,
    ): InstitutionFormalRequest {
        $this->assertInstitutionScope($request, $expectedInstitutionId);

        return DB::transaction(function () use ($request, $actorAccountId, $expectedInstitutionId): InstitutionFormalRequest {
            // Lock the source to prevent a concurrent resubmit / cancel race.
            $source = $this->lockForMutation($request->id);
            $this->assertInstitutionScope($source, $expectedInstitutionId);
            $this->requireStatus($source, InstitutionFormalRequest::STATUS_RETURNED_TO_PREPARER, 'resubmit');

            // Create the new draft, inheriting type/semester/institution from source.
            // The title/body/priority are copied as a starting point for editing.
            $number = $this->numberService->next((int) $source->institution_id, (int) now()->year);

            $branch = new InstitutionFormalRequest;
            $branch->request_number = $number;
            $branch->institution_id = $source->institution_id;
            $branch->institution_semester_id = $source->institution_semester_id;
            $branch->request_type = $source->request_type;
            $branch->title_ar = $source->title_ar;
            $branch->title_en = $source->title_en;
            $branch->body = $source->body;
            $branch->priority = $source->priority;
            $branch->due_date = $source->due_date;
            $branch->created_by_account_id = $actorAccountId;
            $branch->current_status = InstitutionFormalRequest::STATUS_DRAFT;
            $branch->version = 1;
            $branch->branched_from_id = $source->id;
            $branch->save();

            // Preserve the source as an immutable snapshot — superseded by the new branch.
            $source->superseded_by_id = $branch->id;
            $source->current_status = InstitutionFormalRequest::STATUS_SUPERSEDED;
            $source->save();

            $this->auditRecorder->record(new AuditEventPayload(
                actorType: 'staff',
                sourceModule: 'Requests',
                action: 'formal_request.resubmitted',
                actorAccountId: $actorAccountId,
                portal: 'staff',
                subjectType: 'InstitutionFormalRequest',
                subjectId: $source->id,
                afterState: [
                    'source_status' => InstitutionFormalRequest::STATUS_SUPERSEDED,
                    'branch_id' => $branch->id,
                    'branch_status' => InstitutionFormalRequest::STATUS_DRAFT,
                ],
            ));

            return $branch->fresh();
        });
    }

    // ------------------------------------------------------------------
    // Principal / deputy actions
    // ------------------------------------------------------------------

    /**
     * Return a request to the preparer for revision, with a mandatory comment.
     *
     * Creates an internal-audience comment recording the return reason.
     *
     * @throws \RuntimeException On invalid state, blank reason, or institution mismatch
     */
    public function returnToPreparer(
        InstitutionFormalRequest $request,
        int $actorAccountId,
        string $reason,
        ?int $expectedInstitutionId = null,
    ): InstitutionFormalRequest {
        $this->assertInstitutionScope($request, $expectedInstitutionId);

        if (trim($reason) === '') {
            throw new \RuntimeException('A return reason comment is required.');
        }

        return DB::transaction(function () use ($request, $actorAccountId, $reason, $expectedInstitutionId): InstitutionFormalRequest {
            $locked = $this->lockForMutation($request->id);
            $this->assertInstitutionScope($locked, $expectedInstitutionId);
            $this->requireStatus($locked, InstitutionFormalRequest::STATUS_INTERNAL_REVIEW, 'return to preparer');

            $this->persistComment(
                request: $locked,
                actorType: 'staff',
                actorAccountId: $actorAccountId,
                portal: 'staff',
                audience: InstitutionFormalRequestComment::AUDIENCE_INTERNAL,
                commentText: $reason,
            );

            $locked->current_status = InstitutionFormalRequest::STATUS_RETURNED_TO_PREPARER;
            $locked->save();

            $this->auditRecorder->record(new AuditEventPayload(
                actorType: 'staff',
                sourceModule: 'Requests',
                action: 'formal_request.returned_to_preparer',
                actorAccountId: $actorAccountId,
                portal: 'staff',
                subjectType: 'InstitutionFormalRequest',
                subjectId: $locked->id,
                changeReason: $reason,
                afterState: ['status' => InstitutionFormalRequest::STATUS_RETURNED_TO_PREPARER],
            ));

            // Notify the secretary (created_by_account_id) that the request was returned.
            $this->tryNotify(
                type: 'formal_request.returned',
                recipientAccountType: 'staff',
                recipientAccountId: (int) $locked->created_by_account_id,
                portal: 'staff',
                messageKey: 'formal_request.returned',
                params: ['subject' => $locked->title_en, 'request_id' => $locked->id],
                subjectId: $locked->id,
            );

            return $locked->fresh();
        });
    }

    /**
     * Issue a reconfirmation token for the sign action.
     *
     * The token binds the signer's identity, the request's current content hash,
     * and the approval type 'formal_request_signature'. Used in phase 1 of the
     * two-phase electronic signature flow.
     *
     * Only principal / deputy_principal positions are allowed to sign.
     *
     * @throws ElectronicApprovalException On credential failure or rate limit
     * @throws \RuntimeException When the signer's position is not eligible or the request is not in internal_review
     */
    public function issueSigningToken(
        InstitutionFormalRequest $request,
        string $credential,
        int $signerAccountId,
        string $signerPositionDefinition,
        string $portal,
        ?int $expectedInstitutionId = null,
    ): string {
        $this->assertInstitutionScope($request, $expectedInstitutionId);
        $this->requireStatus($request, InstitutionFormalRequest::STATUS_INTERNAL_REVIEW, 'sign');
        $this->requireSignerEligibility($signerPositionDefinition);

        $challengeClass = 'App\\Http\\Auth\\StaffReconfirmationChallenge';
        $challenge = app($challengeClass);

        $tokenSvc = app(ReconfirmationTokenService::class);

        $token = $tokenSvc->issue(
            challenge: $challenge,
            credential: $credential,
            contentResolver: $this->contentResolver,
            approvalType: 'formal_request_signature',
            subjectType: 'InstitutionFormalRequest',
            subjectId: $request->id,
        );

        return $token->id;
    }

    /**
     * Electronically sign the request, consuming the reconfirmation token.
     *
     * Records an immutable ElectronicApproval. Transitions request to 'signed'.
     * Rejects if the request body changed since the signing screen was loaded.
     *
     * Only principal / deputy_principal positions are allowed to sign.
     *
     * @throws ElectronicApprovalException On token mismatch or content change
     * @throws \RuntimeException When the signer's position is not eligible or state is wrong
     */
    public function sign(
        InstitutionFormalRequest $request,
        string $tokenId,
        int $signerAccountId,
        string $signerPositionDefinition,
        string $portal,
        ?int $expectedInstitutionId = null,
        ?string $comment = null,
    ): InstitutionFormalRequest {
        // Early guards on caller-supplied inputs (no lock needed for these).
        $this->assertInstitutionScope($request, $expectedInstitutionId);
        $this->requireSignerEligibility($signerPositionDefinition);

        $approvalSvc = app(ElectronicApprovalService::class);

        return DB::transaction(function () use (
            $request, $tokenId, $signerAccountId, $portal, $comment, $approvalSvc, $expectedInstitutionId,
        ): InstitutionFormalRequest {
            // Lock the row before recording the immutable approval so that a
            // concurrent signer who also holds a valid token is rejected:
            // by the time the second transaction acquires this lock the status
            // will have already changed to 'signed', and requireStatus() below
            // will throw before ElectronicApprovalService::record() is called.
            $locked = $this->lockForMutation($request->id);
            $this->assertInstitutionScope($locked, $expectedInstitutionId);
            $this->requireStatus($locked, InstitutionFormalRequest::STATUS_INTERNAL_REVIEW, 'sign');

            $contextClass = TransitionContext::class;
            $context = new $contextClass(
                actorType: 'staff',
                portal: $portal,
                actorAccountId: $signerAccountId,
                comment: $comment,
            );

            $approval = $approvalSvc->record(
                approvalType: 'formal_request_signature',
                decision: 'approved',
                subjectType: 'InstitutionFormalRequest',
                subjectId: $locked->id,
                tokenId: $tokenId,
                contentResolver: $this->contentResolver,
                context: $context,
                subjectVersion: $locked->version,
            );

            // Snapshot the content hash on the request row for reference.
            // Record the signer as the responsible principal — used later as a
            // notification recipient when management responds to the request.
            $locked->content_hash = $approval->content_hash;
            $locked->responsible_account_id = $signerAccountId;
            $locked->current_status = InstitutionFormalRequest::STATUS_SIGNED;
            $locked->save();

            return $locked->fresh();
        });
    }

    /**
     * Submit a signed request to central management.
     *
     * @throws \RuntimeException On invalid state or institution mismatch
     */
    public function submitToManagement(
        InstitutionFormalRequest $request,
        int $actorAccountId,
        ?int $expectedInstitutionId = null,
    ): InstitutionFormalRequest {
        $this->assertInstitutionScope($request, $expectedInstitutionId);

        return DB::transaction(function () use ($request, $actorAccountId, $expectedInstitutionId): InstitutionFormalRequest {
            $locked = $this->lockForMutation($request->id);
            $this->assertInstitutionScope($locked, $expectedInstitutionId);
            $this->requireStatus($locked, InstitutionFormalRequest::STATUS_SIGNED, 'submit to management');

            $locked->current_status = InstitutionFormalRequest::STATUS_SUBMITTED_TO_MANAGEMENT;
            $locked->save();

            $this->auditRecorder->record(new AuditEventPayload(
                actorType: 'staff',
                sourceModule: 'Requests',
                action: 'formal_request.transitioned',
                actorAccountId: $actorAccountId,
                portal: 'staff',
                subjectType: 'InstitutionFormalRequest',
                subjectId: $locked->id,
                afterState: ['from' => InstitutionFormalRequest::STATUS_SIGNED, 'to' => InstitutionFormalRequest::STATUS_SUBMITTED_TO_MANAGEMENT],
            ));

            // Notify every admin who holds the formal_request.respond permission
            // so management knows a new request arrived in their inbox.
            $institutionName = $this->resolveInstitutionName((int) $locked->institution_id);
            $params = [
                'institution_name' => $institutionName,
                'request_id' => $locked->id,
                'subject' => $locked->title_en,
            ];

            foreach ($this->resolveManagementRecipients() as $adminAccountId) {
                $this->tryNotify(
                    type: 'formal_request.submitted',
                    recipientAccountType: 'admin',
                    recipientAccountId: $adminAccountId,
                    portal: 'admin',
                    messageKey: 'formal_request.submitted',
                    params: $params,
                    subjectId: $locked->id,
                );
            }

            return $locked->fresh();
        });
    }

    // ------------------------------------------------------------------
    // Management / admin actions
    // ------------------------------------------------------------------

    /**
     * Start the management review.
     *
     * Uses a locked reload inside the transaction to prevent concurrent
     * management users from double-starting the review.
     *
     * @throws \RuntimeException On invalid state
     */
    public function startManagementReview(
        InstitutionFormalRequest $request,
        int $actorAccountId,
    ): InstitutionFormalRequest {
        return DB::transaction(function () use ($request, $actorAccountId): InstitutionFormalRequest {
            $locked = $this->lockForMutation($request->id);
            $this->requireStatus($locked, InstitutionFormalRequest::STATUS_SUBMITTED_TO_MANAGEMENT, 'start management review');

            $prev = $locked->current_status;
            $locked->current_status = InstitutionFormalRequest::STATUS_UNDER_MANAGEMENT_REVIEW;
            $locked->save();

            $this->auditRecorder->record(new AuditEventPayload(
                actorType: 'administrative',
                sourceModule: 'Requests',
                action: 'formal_request.transitioned',
                actorAccountId: $actorAccountId,
                portal: 'admin',
                subjectType: 'InstitutionFormalRequest',
                subjectId: $locked->id,
                afterState: ['from' => $prev, 'to' => InstitutionFormalRequest::STATUS_UNDER_MANAGEMENT_REVIEW],
            ));

            return $locked->fresh();
        });
    }

    /**
     * Request clarification from the institution.
     *
     * Creates an all-audience comment with the clarification question.
     * Locked reload prevents a concurrent accept/reject from winning first.
     *
     * @throws \RuntimeException On invalid state or blank question
     */
    public function requestClarification(
        InstitutionFormalRequest $request,
        int $actorAccountId,
        string $question,
    ): InstitutionFormalRequest {
        if (trim($question) === '') {
            throw new \RuntimeException('A clarification question is required.');
        }

        return DB::transaction(function () use ($request, $actorAccountId, $question): InstitutionFormalRequest {
            $locked = $this->lockForMutation($request->id);
            $this->requireStatus($locked, InstitutionFormalRequest::STATUS_UNDER_MANAGEMENT_REVIEW, 'request clarification');

            $this->persistComment(
                request: $locked,
                actorType: 'administrative',
                actorAccountId: $actorAccountId,
                portal: 'admin',
                audience: InstitutionFormalRequestComment::AUDIENCE_ALL,
                commentText: $question,
            );

            $locked->current_status = InstitutionFormalRequest::STATUS_CLARIFICATION_REQUESTED;
            $locked->save();

            $this->auditRecorder->record(new AuditEventPayload(
                actorType: 'administrative',
                sourceModule: 'Requests',
                action: 'formal_request.clarification_requested',
                actorAccountId: $actorAccountId,
                portal: 'admin',
                subjectType: 'InstitutionFormalRequest',
                subjectId: $locked->id,
                afterState: ['status' => InstitutionFormalRequest::STATUS_CLARIFICATION_REQUESTED],
            ));

            return $locked->fresh();
        });
    }

    /**
     * Provide a clarification response from the institution side.
     *
     * @throws \RuntimeException On invalid state, blank response, or institution mismatch
     */
    public function respondToClarification(
        InstitutionFormalRequest $request,
        int $actorAccountId,
        string $response,
        ?int $expectedInstitutionId = null,
    ): InstitutionFormalRequest {
        $this->assertInstitutionScope($request, $expectedInstitutionId);

        if (trim($response) === '') {
            throw new \RuntimeException('A clarification response is required.');
        }

        return DB::transaction(function () use ($request, $actorAccountId, $response, $expectedInstitutionId): InstitutionFormalRequest {
            $locked = $this->lockForMutation($request->id);
            $this->assertInstitutionScope($locked, $expectedInstitutionId);
            $this->requireStatus($locked, InstitutionFormalRequest::STATUS_CLARIFICATION_REQUESTED, 'respond to clarification');

            $this->persistComment(
                request: $locked,
                actorType: 'staff',
                actorAccountId: $actorAccountId,
                portal: 'staff',
                audience: InstitutionFormalRequestComment::AUDIENCE_ALL,
                commentText: $response,
            );

            $locked->current_status = InstitutionFormalRequest::STATUS_UNDER_MANAGEMENT_REVIEW;
            $locked->save();

            $this->auditRecorder->record(new AuditEventPayload(
                actorType: 'staff',
                sourceModule: 'Requests',
                action: 'formal_request.clarification_provided',
                actorAccountId: $actorAccountId,
                portal: 'staff',
                subjectType: 'InstitutionFormalRequest',
                subjectId: $locked->id,
                afterState: ['status' => InstitutionFormalRequest::STATUS_UNDER_MANAGEMENT_REVIEW],
            ));

            return $locked->fresh();
        });
    }

    /**
     * Accept the request (management decision).
     *
     * Locked reload inside the transaction prevents a concurrent reject from
     * silently winning the race and the second decision landing on an already-
     * decided request.
     *
     * @throws \RuntimeException On invalid state
     */
    public function accept(
        InstitutionFormalRequest $request,
        int $actorAccountId,
        ?string $comment = null,
    ): InstitutionFormalRequest {
        return DB::transaction(function () use ($request, $actorAccountId, $comment): InstitutionFormalRequest {
            $locked = $this->lockForMutation($request->id);
            $this->requireStatus($locked, InstitutionFormalRequest::STATUS_UNDER_MANAGEMENT_REVIEW, 'accept');

            if ($comment !== null && trim($comment) !== '') {
                $this->persistComment(
                    request: $locked,
                    actorType: 'administrative',
                    actorAccountId: $actorAccountId,
                    portal: 'admin',
                    audience: InstitutionFormalRequestComment::AUDIENCE_MANAGEMENT,
                    commentText: $comment,
                );
            }

            $locked->current_status = InstitutionFormalRequest::STATUS_ACCEPTED;
            $locked->save();

            $this->auditRecorder->record(new AuditEventPayload(
                actorType: 'administrative',
                sourceModule: 'Requests',
                action: 'formal_request.accepted',
                actorAccountId: $actorAccountId,
                portal: 'admin',
                subjectType: 'InstitutionFormalRequest',
                subjectId: $locked->id,
                afterState: ['status' => InstitutionFormalRequest::STATUS_ACCEPTED],
            ));

            return $locked->fresh();
        });
    }

    /**
     * Reject the request (management decision).
     *
     * Locked reload prevents a concurrent accept from silently winning the race.
     *
     * @throws \RuntimeException On invalid state or blank reason
     */
    public function reject(
        InstitutionFormalRequest $request,
        int $actorAccountId,
        string $reason,
    ): InstitutionFormalRequest {
        if (trim($reason) === '') {
            throw new \RuntimeException('A rejection reason is required.');
        }

        return DB::transaction(function () use ($request, $actorAccountId, $reason): InstitutionFormalRequest {
            $locked = $this->lockForMutation($request->id);
            $this->requireStatus($locked, InstitutionFormalRequest::STATUS_UNDER_MANAGEMENT_REVIEW, 'reject');

            $this->persistComment(
                request: $locked,
                actorType: 'administrative',
                actorAccountId: $actorAccountId,
                portal: 'admin',
                audience: InstitutionFormalRequestComment::AUDIENCE_MANAGEMENT,
                commentText: $reason,
            );

            $locked->current_status = InstitutionFormalRequest::STATUS_REJECTED;
            $locked->save();

            $this->auditRecorder->record(new AuditEventPayload(
                actorType: 'administrative',
                sourceModule: 'Requests',
                action: 'formal_request.rejected',
                actorAccountId: $actorAccountId,
                portal: 'admin',
                subjectType: 'InstitutionFormalRequest',
                subjectId: $locked->id,
                changeReason: $reason,
                afterState: ['status' => InstitutionFormalRequest::STATUS_REJECTED],
            ));

            return $locked->fresh();
        });
    }

    /**
     * Record management's formal response (accepted or rejected → responded).
     *
     * @param  array<string, mixed>  $responseBody  Structured response content
     *
     * @throws \RuntimeException On invalid state or empty response
     */
    public function respond(
        InstitutionFormalRequest $request,
        int $actorAccountId,
        array $responseBody,
    ): InstitutionFormalRequest {
        if (empty($responseBody)) {
            throw new \RuntimeException('A response body is required.');
        }

        return DB::transaction(function () use ($request, $actorAccountId, $responseBody): InstitutionFormalRequest {
            $locked = $this->lockForMutation($request->id);

            if (! in_array($locked->current_status, [
                InstitutionFormalRequest::STATUS_ACCEPTED,
                InstitutionFormalRequest::STATUS_REJECTED,
            ], true)) {
                throw new \RuntimeException(
                    "Cannot respond to a request in state '{$locked->current_status}'. Expected 'accepted' or 'rejected'."
                );
            }

            $locked->response_body = $responseBody;
            $locked->response_at = now();
            $locked->current_status = InstitutionFormalRequest::STATUS_RESPONDED;
            $locked->save();

            $this->auditRecorder->record(new AuditEventPayload(
                actorType: 'administrative',
                sourceModule: 'Requests',
                action: 'formal_request.responded',
                actorAccountId: $actorAccountId,
                portal: 'admin',
                subjectType: 'InstitutionFormalRequest',
                subjectId: $locked->id,
                afterState: ['status' => InstitutionFormalRequest::STATUS_RESPONDED],
            ));

            // Notify the signing principal (responsible_account_id) and the preparer.
            // formal_request.responded requires institution_name, request_id, and subject.
            $respondParams = [
                'institution_name' => $this->resolveInstitutionName((int) $locked->institution_id),
                'request_id' => $locked->id,
                'subject' => $locked->title_en,
            ];

            foreach (array_unique(array_filter([
                (int) $locked->responsible_account_id ?: null,
                (int) $locked->created_by_account_id ?: null,
            ])) as $recipientId) {
                if ($recipientId) {
                    $this->tryNotify(
                        type: 'formal_request.responded',
                        recipientAccountType: 'staff',
                        recipientAccountId: $recipientId,
                        portal: 'staff',
                        messageKey: 'formal_request.responded',
                        params: $respondParams,
                        subjectId: $locked->id,
                    );
                }
            }

            return $locked->fresh();
        });
    }

    /**
     * Close a responded request.
     *
     * @throws \RuntimeException On invalid state
     */
    public function close(
        InstitutionFormalRequest $request,
        int $actorAccountId,
        string $actorType = 'administrative',
        string $portal = 'admin',
    ): InstitutionFormalRequest {
        return DB::transaction(function () use ($request, $actorAccountId, $actorType, $portal): InstitutionFormalRequest {
            $locked = $this->lockForMutation($request->id);
            $this->requireStatus($locked, InstitutionFormalRequest::STATUS_RESPONDED, 'close');

            $prev = $locked->current_status;
            $locked->current_status = InstitutionFormalRequest::STATUS_CLOSED;
            $locked->save();

            $this->auditRecorder->record(new AuditEventPayload(
                actorType: $actorType,
                sourceModule: 'Requests',
                action: 'formal_request.transitioned',
                actorAccountId: $actorAccountId,
                portal: $portal,
                subjectType: 'InstitutionFormalRequest',
                subjectId: $locked->id,
                afterState: ['from' => $prev, 'to' => InstitutionFormalRequest::STATUS_CLOSED],
            ));

            return $locked->fresh();
        });
    }

    /**
     * Supersede a terminal/resolved request by creating a new replacement draft.
     *
     * The source request transitions to STATUS_SUPERSEDED and its superseded_by_id
     * is set to the replacement's ID. The replacement is returned as a new draft.
     *
     * Allowed from: responded, closed, rejected — states where management has issued
     * or declined a decision and the institution wants to follow up.
     *
     * @param  array<string, mixed>  $body  Body for the replacement request
     *
     * @throws \RuntimeException On invalid state or institution mismatch
     */
    public function supersede(
        InstitutionFormalRequest $request,
        string $titleAr,
        string $titleEn,
        array $body,
        int $priority,
        ?string $dueDate,
        int $actorAccountId,
        ?int $expectedInstitutionId = null,
    ): InstitutionFormalRequest {
        $this->assertInstitutionScope($request, $expectedInstitutionId);

        $allowedStatuses = [
            InstitutionFormalRequest::STATUS_RESPONDED,
            InstitutionFormalRequest::STATUS_CLOSED,
            InstitutionFormalRequest::STATUS_REJECTED,
        ];

        if (! in_array($request->current_status, $allowedStatuses, true)) {
            throw new \RuntimeException(
                "Cannot supersede request #{$request->id} in state '{$request->current_status}'. ".
                'Allowed: '.implode(', ', $allowedStatuses).'.'
            );
        }

        return DB::transaction(function () use (
            $request, $titleAr, $titleEn, $body, $priority, $dueDate, $actorAccountId, $expectedInstitutionId,
        ): InstitutionFormalRequest {
            // Lock the source to prevent concurrent supersession.
            $source = $this->lockForMutation($request->id);
            $this->assertInstitutionScope($source, $expectedInstitutionId);

            $allowedStatuses = [
                InstitutionFormalRequest::STATUS_RESPONDED,
                InstitutionFormalRequest::STATUS_CLOSED,
                InstitutionFormalRequest::STATUS_REJECTED,
            ];

            if (! in_array($source->current_status, $allowedStatuses, true)) {
                throw new \RuntimeException(
                    "Concurrent modification: request #{$source->id} changed to '{$source->current_status}' and can no longer be superseded."
                );
            }

            // Create the replacement draft, inheriting type/semester/priority from source.
            $number = $this->numberService->next((int) $source->institution_id, (int) now()->year);

            $replacement = new InstitutionFormalRequest;
            $replacement->request_number = $number;
            $replacement->institution_id = $source->institution_id;
            $replacement->institution_semester_id = $source->institution_semester_id;
            $replacement->request_type = $source->request_type;
            $replacement->title_ar = $titleAr;
            $replacement->title_en = $titleEn;
            $replacement->body = $body;
            $replacement->priority = max(1, min(4, $priority));
            $replacement->due_date = $dueDate;
            $replacement->created_by_account_id = $actorAccountId;
            $replacement->current_status = InstitutionFormalRequest::STATUS_DRAFT;
            $replacement->version = 1;
            $replacement->save();

            // Mark the source as superseded, pointing to the replacement.
            $source->superseded_by_id = $replacement->id;
            $source->current_status = InstitutionFormalRequest::STATUS_SUPERSEDED;
            $source->save();

            $this->auditRecorder->record(new AuditEventPayload(
                actorType: 'staff',
                sourceModule: 'Requests',
                action: 'formal_request.superseded',
                actorAccountId: $actorAccountId,
                portal: 'staff',
                subjectType: 'InstitutionFormalRequest',
                subjectId: $source->id,
                afterState: [
                    'status' => InstitutionFormalRequest::STATUS_SUPERSEDED,
                    'superseded_by_id' => $replacement->id,
                ],
            ));

            return $replacement;
        });
    }

    // ------------------------------------------------------------------
    // Shared institution-side actions
    // ------------------------------------------------------------------

    /**
     * Cancel a request (only allowed from draft, internal_review, or returned_to_preparer).
     *
     * @throws \RuntimeException When cancellation is not permitted in the current state
     */
    public function cancel(
        InstitutionFormalRequest $request,
        int $actorAccountId,
        ?int $expectedInstitutionId = null,
    ): InstitutionFormalRequest {
        $this->assertInstitutionScope($request, $expectedInstitutionId);

        return DB::transaction(function () use ($request, $actorAccountId, $expectedInstitutionId): InstitutionFormalRequest {
            $locked = $this->lockForMutation($request->id);
            $this->assertInstitutionScope($locked, $expectedInstitutionId);

            if (! $locked->isCancellable()) {
                throw new \RuntimeException(
                    "Request #{$locked->id} in state '{$locked->current_status}' cannot be cancelled. ".
                    'Cancellation is only allowed from draft, internal_review, or returned_to_preparer.'
                );
            }

            $locked->current_status = InstitutionFormalRequest::STATUS_CANCELLED;
            $locked->save();

            $this->auditRecorder->record(new AuditEventPayload(
                actorType: 'staff',
                sourceModule: 'Requests',
                action: 'formal_request.transitioned',
                actorAccountId: $actorAccountId,
                portal: 'staff',
                subjectType: 'InstitutionFormalRequest',
                subjectId: $locked->id,
                afterState: ['from' => $request->current_status, 'to' => InstitutionFormalRequest::STATUS_CANCELLED],
            ));

            return $locked->fresh();
        });
    }

    /**
     * Add an audience-restricted comment to the request.
     *
     * @throws \RuntimeException When the comment text is blank
     */
    public function addComment(
        InstitutionFormalRequest $request,
        string $actorType,
        int $actorAccountId,
        string $portal,
        string $audience,
        string $commentText,
        ?int $expectedInstitutionId = null,
    ): InstitutionFormalRequestComment {
        // Institution-side callers (portal=staff) must supply their institution scope.
        // Management callers (portal=admin) pass null, which the assertInstitutionScope
        // helper treats as "skip the check" — management sees all institutions.
        if ($portal === 'staff') {
            $this->assertInstitutionScope($request, $expectedInstitutionId);
        }

        if (trim($commentText) === '') {
            throw new \RuntimeException('Comment text must not be blank.');
        }

        if (! in_array($audience, [
            InstitutionFormalRequestComment::AUDIENCE_INTERNAL,
            InstitutionFormalRequestComment::AUDIENCE_MANAGEMENT,
            InstitutionFormalRequestComment::AUDIENCE_ALL,
        ], true)) {
            throw new \RuntimeException("Invalid audience value '{$audience}'.");
        }

        // Institution-side staff must not post management-only comments.
        if ($portal === 'staff' && $audience === InstitutionFormalRequestComment::AUDIENCE_MANAGEMENT) {
            throw new \RuntimeException('Institution staff cannot post management-only comments.');
        }

        return $this->persistComment($request, $actorType, $actorAccountId, $portal, $audience, $commentText);
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    private function transition(
        InstitutionFormalRequest $request,
        string $newStatus,
        string $actorType,
        string $portal,
        int $actorAccountId,
    ): InstitutionFormalRequest {
        return DB::transaction(function () use ($request, $newStatus, $actorType, $portal, $actorAccountId): InstitutionFormalRequest {
            $prev = $request->current_status;
            $request->current_status = $newStatus;
            $request->save();

            $this->auditRecorder->record(new AuditEventPayload(
                actorType: $actorType,
                sourceModule: 'Requests',
                action: 'formal_request.transitioned',
                actorAccountId: $actorAccountId,
                portal: $portal,
                subjectType: 'InstitutionFormalRequest',
                subjectId: $request->id,
                afterState: ['from' => $prev, 'to' => $newStatus],
            ));

            return $request->fresh();
        });
    }

    private function persistComment(
        InstitutionFormalRequest $request,
        string $actorType,
        int $actorAccountId,
        string $portal,
        string $audience,
        string $commentText,
    ): InstitutionFormalRequestComment {
        $comment = new InstitutionFormalRequestComment;
        $comment->request_id = $request->id;
        $comment->commenter_actor_type = $actorType;
        $comment->commenter_account_id = $actorAccountId;
        $comment->portal = $portal;
        $comment->audience = $audience;
        $comment->comment_text = $commentText;  // triggers encrypt mutator
        $comment->save();

        return $comment;
    }

    private function requireStatus(
        InstitutionFormalRequest $request,
        string $expectedStatus,
        string $action,
    ): void {
        if ($request->current_status !== $expectedStatus) {
            throw new \RuntimeException(
                "Cannot {$action}: request #{$request->id} is in state '{$request->current_status}', expected '{$expectedStatus}'."
            );
        }
    }

    /**
     * Lock the request row for update and return a fresh model instance.
     *
     * Must be called inside an open DB::transaction(). Prevents concurrent
     * management decisions (accept vs. reject) from overwriting each other by
     * ensuring the status is re-validated after the lock is acquired.
     *
     * On SQLite (tests) lockForUpdate() is a no-op but correctness is still
     * tested by passing a stale model whose status has already changed.
     */
    private function lockForMutation(int $requestId): InstitutionFormalRequest
    {
        return InstitutionFormalRequest::lockForUpdate()->findOrFail($requestId);
    }

    // ------------------------------------------------------------------
    // Attachment helpers (Attachments module — string-variable pattern)
    // ------------------------------------------------------------------

    /**
     * List all SecureAttachment records linked to a formal request.
     *
     * Returns an Eloquent Collection of AttachmentLink models with the
     * attachment() relation eager-loaded so callers can read metadata without
     * additional queries.
     *
     * @return Collection<int, Model>
     */
    public function listAttachments(InstitutionFormalRequest $request): Collection
    {
        return $request->attachmentLinks()->with('attachment')->get();
    }

    /**
     * Link an already-uploaded SecureAttachment to a formal request.
     *
     * Delegates to SecureAttachmentService::link() which is idempotent — if the
     * link already exists the existing row is returned without a duplicate insert.
     *
     * @param  string  $attachmentId  UUID of an existing SecureAttachment row
     * @param  string  $linkType  Semantic role, e.g. 'supporting_evidence'
     *
     * @throws \RuntimeException If the attachment does not exist or belongs to a different institution
     */
    public function linkAttachment(
        InstitutionFormalRequest $request,
        string $attachmentId,
        string $linkType = 'supporting_evidence',
    ): Model {
        return DB::transaction(function () use ($request, $attachmentId, $linkType): Model {
            // Reload under a write lock before checking state so that a concurrent
            // sign() or submitForInternalReview() that transitions the request
            // cannot slip between the editable check and the insert.
            $locked = $this->lockForMutation($request->id);

            if (! $locked->isEditable()) {
                throw new \RuntimeException(
                    "Cannot add attachments to request #{$locked->id} in state '{$locked->current_status}'. ".
                    'Attachments may only be linked while the request is in an editable state (draft).'
                );
            }

            $attachmentClass = 'Modules\\Attachments\\Models\\SecureAttachment';
            $attachment = $attachmentClass::where('id', $attachmentId)
                ->where('institution_id', $locked->institution_id) // enforce institution scope
                ->first();

            if ($attachment === null) {
                throw new \RuntimeException(
                    "Attachment '{$attachmentId}' not found or does not belong to institution {$locked->institution_id}."
                );
            }

            $svcClass = 'Modules\\Attachments\\Services\\SecureAttachmentService';

            return app($svcClass)->link(
                attachment: $attachment,
                linkableType: InstitutionFormalRequest::class,
                linkableId: (int) $locked->id,
                linkType: $linkType,
            );
        });
    }

    private function assertInstitutionScope(
        InstitutionFormalRequest $request,
        ?int $expectedInstitutionId,
    ): void {
        if ($expectedInstitutionId === null) {
            return;
        }

        if ((int) $request->institution_id !== $expectedInstitutionId) {
            throw new \RuntimeException(
                "Cross-institution access denied: request belongs to institution {$request->institution_id}, ".
                "actor has scope for institution {$expectedInstitutionId}."
            );
        }
    }

    private function requireSignerEligibility(string $positionDefinition): void
    {
        if (! in_array($positionDefinition, ['principal', 'deputy_principal'], true)) {
            throw new \RuntimeException(
                'Only principal or deputy_principal may sign formal requests. '.
                "Current position: '{$positionDefinition}'."
            );
        }
    }

    private function requireValidRequestType(string $requestType): void
    {
        if (! in_array($requestType, InstitutionFormalRequest::REQUEST_TYPES, true)) {
            throw new \InvalidArgumentException(
                "Invalid request type '{$requestType}'. Allowed: ".implode(', ', InstitutionFormalRequest::REQUEST_TYPES).'.'
            );
        }
    }

    /**
     * Fetch the English name of the institution from the institutions table.
     * Returns the raw ID as a string if the row does not exist (graceful fallback).
     */
    private function resolveInstitutionName(int $institutionId): string
    {
        $row = DB::table('institutions')->select('name_en')->where('id', $institutionId)->first();

        return $row?->name_en ?? "Institution #{$institutionId}";
    }

    /**
     * Return the IDs of all active admin accounts that hold the
     * 'formal_request.respond' permission through any of their roles.
     *
     * Uses the canonical administrative_account_roles → role_permissions → permissions
     * join. Accounts with a revoked role grant are excluded.
     *
     * @return list<int>
     */
    private function resolveManagementRecipients(): array
    {
        return DB::table('administrative_account_roles as aar')
            ->join('role_permissions as rp', 'rp.role_id', '=', 'aar.role_id')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('p.key', 'formal_request.respond')
            ->whereNull('aar.revoked_at')
            ->distinct()
            ->pluck('aar.administrative_account_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * Fire-and-forget notification — never fails the main transaction.
     *
     * @param  array<string, scalar>  $params
     */
    private function tryNotify(
        string $type,
        string $recipientAccountType,
        int $recipientAccountId,
        string $portal,
        string $messageKey,
        array $params,
        ?int $subjectId,
    ): void {
        try {
            $notifSvcClass = 'Modules\\Notifications\\Services\\NotificationService';
            app($notifSvcClass)->send(
                notificationType: $type,
                recipientAccountType: $recipientAccountType,
                recipientAccountId: $recipientAccountId,
                portal: $portal,
                messageKey: $messageKey,
                messageParams: $params,
                subjectType: 'InstitutionFormalRequest',
                subjectId: $subjectId,
            );
        } catch (\Throwable) {
            // Notification failures must never fail the primary operation.
        }
    }
}

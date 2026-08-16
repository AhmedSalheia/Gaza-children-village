<?php

declare(strict_types=1);

namespace Modules\Workflow\Services;

use Illuminate\Support\Facades\DB;
use Modules\Audit\Contracts\AuditRecorder;
use Modules\Audit\Data\AuditEventPayload;
use Modules\Workflow\Contracts\SubjectContentResolverContract;
use Modules\Workflow\Data\TransitionContext;
use Modules\Workflow\Exceptions\ElectronicApprovalException;
use Modules\Workflow\Models\ElectronicApproval;

/**
 * Records immutable electronic approval decisions against consumed reconfirmation tokens.
 *
 * Security design:
 *   - record() requires a valid, unconsumed reconfirmation token issued by
 *     ReconfirmationTokenService. The token binds: actor identity, approval type,
 *     subject type + ID, content hash at review-screen load time, and a short TTL.
 *   - record() wraps token validation, approval persistence, and the audit write in
 *     ONE DB transaction. If the audit write fails (e.g. due to a forbidden key),
 *     the approval row and the consumed-token mark both roll back atomically.
 *   - content_hash stored on the approval is the hash bound server-side at token
 *     issuance, not a caller-supplied value at submission time.
 *   - reconfirmation_method is copied from the token, not asserted by the caller.
 *
 * Revocation:
 *   - revoke() wraps all mutations in a DB transaction with a pessimistic lock on the
 *     original approval row to prevent concurrent revocations producing competing chains.
 */
final class ElectronicApprovalService
{
    public function __construct(
        private readonly AuditRecorder $auditRecorder,
        private readonly ReconfirmationTokenService $tokenService,
    ) {}

    /**
     * Record an electronic approval by consuming a reconfirmation token.
     *
     * All three operations — token consumption, approval persistence, and audit write —
     * execute inside ONE DB transaction. A failure in any of them rolls back all three.
     *
     * @param  string  $approvalType  Stable code (e.g. 'sensitive_field_correction');
     *                                must match the token's bound approval type
     * @param  string  $decision  'approved'|'rejected'
     * @param  string  $subjectType  Domain model class name; must match the token's bound subject type
     * @param  int  $subjectId  Domain model primary key; must match the token's bound subject ID
     * @param  string  $tokenId  UUID from ReconfirmationTokenService::issue()
     * @param  SubjectContentResolverContract  $contentResolver  Domain-owned resolver that
     *                                                           recomputes the canonical subject
     *                                                           content hash from the database.
     *                                                           The service calls this server-side
     *                                                           and compares the result with the
     *                                                           hash stored in the token at issuance.
     *                                                           If the subject changed after the
     *                                                           approver loaded the review screen,
     *                                                           the hashes differ and the approval
     *                                                           is rejected.
     * @param  TransitionContext  $context  Actor identity (must match the token's actor)
     *
     * @throws ElectronicApprovalException When the token is invalid, expired, consumed,
     *                                     actor/type/subject/content hash mismatches,
     *                                     or decision is invalid
     */
    public function record(
        string $approvalType,
        string $decision,
        string $subjectType,
        int $subjectId,
        string $tokenId,
        SubjectContentResolverContract $contentResolver,
        TransitionContext $context,
        ?int $subjectVersion = null,
        ?string $comment = null,
        ?string $deviceFingerprint = null,
    ): ElectronicApproval {
        // Validate decision before any DB work so we don't waste a transaction
        if (! in_array($decision, ['approved', 'rejected'], true)) {
            throw new ElectronicApprovalException(
                "Invalid decision '{$decision}'. Accepted values: 'approved', 'rejected'."
            );
        }

        if ($context->actorAccountId === null) {
            throw new ElectronicApprovalException(
                'Electronic approvals require an authenticated actor account.'
            );
        }

        // Recompute the canonical content hash server-side BEFORE entering the transaction.
        // The resolver reads the subject from the database fresh, so if the subject changed
        // after the approver loaded the review screen, the hash will differ from the token's
        // stored hash and the approval will be rejected — even with a valid, unexpired token.
        $currentContentHash = $contentResolver->computeCanonicalHash($subjectType, $subjectId);

        // ONE encompassing transaction: consume token + persist approval + write audit.
        // If the audit recorder throws (e.g. forbidden key in afterState), both the
        // approval row and the consumed-token mark roll back automatically.
        return DB::transaction(function () use (
            $approvalType, $decision, $subjectType, $subjectId,
            $tokenId, $currentContentHash, $context,
            $subjectVersion, $comment, $deviceFingerprint,
        ): ElectronicApproval {
            // 1. Validate and mark the token consumed (pessimistic lock inside this transaction)
            $token = $this->tokenService->validateAndMarkConsumed(
                tokenId: $tokenId,
                actorType: $context->actorType,
                actorAccountId: $context->actorAccountId,
                portal: $context->portal,
                contentHash: $currentContentHash,
                expectedApprovalType: $approvalType,
                expectedSubjectType: $subjectType,
                expectedSubjectId: $subjectId,
            );

            // 2. Create the approval row.
            //    content_hash and reconfirmation_method come from the token (server-side),
            //    not from caller-supplied values.
            $approval = new ElectronicApproval;
            $approval->approver_actor_type = $context->actorType;
            $approval->approver_actor_portal = $context->portal;
            $approval->approver_account_id = $context->actorAccountId;
            $approval->approval_type = $approvalType;
            $approval->decision = $decision;
            $approval->subject_type = $subjectType;
            $approval->subject_id = $subjectId;
            $approval->subject_version = $subjectVersion;
            $approval->content_hash = $token->content_hash;
            $approval->comment = $comment;
            $approval->reconfirmation_method = $token->reconfirmation_method;
            $approval->is_revoked = false;
            $approval->device_fingerprint = $deviceFingerprint;
            $approval->save();

            // 3. Audit write — inside the same transaction.
            //    Key names deliberately avoid the forbidden substrings enforced by
            //    DatabaseAuditRecorder (password, token, secret, session, national_id,
            //    contact, phone, email, hash, fingerprint, plain).
            $this->auditRecorder->record(new AuditEventPayload(
                actorType: $context->actorType,
                sourceModule: 'Workflow',
                action: 'electronic_approval.recorded',
                actorAccountId: $context->actorAccountId,
                portal: $context->portal,
                subjectType: $subjectType,
                subjectId: $subjectId,
                afterState: [
                    'approval_type' => $approvalType,
                    'decision' => $decision,
                    'reconfirmation_method' => $token->reconfirmation_method,
                    'approval_id' => $approval->id,
                ],
                changeReason: $comment,
            ));

            return $approval;
        });
    }

    /**
     * Revoke a previously recorded approval.
     *
     * The entire mutation is wrapped in a DB transaction with a pessimistic lock
     * on the original approval row. Concurrent revocations are serialised.
     *
     * @throws ElectronicApprovalException When the approval is already revoked
     */
    public function revoke(
        ElectronicApproval $approval,
        TransitionContext $context,
        string $reason,
    ): ElectronicApproval {
        return DB::transaction(function () use ($approval, $context, $reason): ElectronicApproval {
            // Pessimistic lock: reload the row inside the transaction to prevent
            // concurrent revocations producing competing chains
            $locked = ElectronicApproval::lockForUpdate()->findOrFail($approval->id);

            if ($locked->is_revoked) {
                throw new ElectronicApprovalException(
                    "Electronic approval #{$locked->id} is already revoked."
                );
            }

            // Create the revocation record first so we have its ID
            $revocation = new ElectronicApproval;
            $revocation->approver_actor_type = $context->actorType;
            $revocation->approver_actor_portal = $context->portal;
            $revocation->approver_account_id = $context->actorAccountId;
            $revocation->approval_type = $locked->approval_type;
            $revocation->decision = 'revoked';
            $revocation->subject_type = $locked->subject_type;
            $revocation->subject_id = $locked->subject_id;
            $revocation->subject_version = $locked->subject_version;
            $revocation->content_hash = $locked->content_hash;
            $revocation->comment = $reason;
            $revocation->reconfirmation_method = $locked->reconfirmation_method;
            $revocation->is_revoked = false;
            $revocation->superseded_by_id = null;
            $revocation->save();

            // Link the original to its revocation and mark it revoked
            $locked->is_revoked = true;
            $locked->superseded_by_id = $revocation->id;
            $locked->save();

            // Audit write inside the same transaction
            $this->auditRecorder->record(new AuditEventPayload(
                actorType: $context->actorType,
                sourceModule: 'Workflow',
                action: 'electronic_approval.revoked',
                actorAccountId: $context->actorAccountId,
                portal: $context->portal,
                subjectType: $locked->subject_type,
                subjectId: $locked->subject_id,
                changeReason: $reason,
                metadata: [
                    'revoked_approval_id' => $locked->id,
                    'revocation_id' => $revocation->id,
                ],
            ));

            return $revocation;
        });
    }
}

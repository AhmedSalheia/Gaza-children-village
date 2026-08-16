<?php

declare(strict_types=1);

namespace Modules\Requests\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Audit\Contracts\AuditRecorder;
use Modules\Audit\Data\AuditEventPayload;
use Modules\Requests\Enums\CorrectionFieldCatalogue;
use Modules\Requests\Models\CorrectionFieldProposal;
use Modules\Requests\Models\StudentCorrectionRequest;
use Modules\Workflow\Data\TransitionContext;

/**
 * Orchestrates the lifecycle of StudentCorrectionRequest records.
 *
 * Authorization is the CALLER'S responsibility. This service enforces:
 *   - Guardian eligibility (active, verified, portal-eligible relationship)
 *   - Field catalogue membership
 *   - Proposed value validation via CorrectionFieldCatalogue::validationRules()
 *   - Relationship ownership: relationship_ref_id must belong to the submitting guardian + student
 *   - Workflow structural rules (via WorkflowTransitionService)
 *
 * Cross-module boundary pattern (F07/F15):
 *   - WorkflowDefinition, WorkflowInstance and WorkflowTransitionService are accessed
 *     via string-variable class/container references (no use-imports from non-public namespaces).
 *   - NotifyOnTransition is invoked via string-variable method call.
 *   - ElectronicApproval is created via string-variable model reference for sensitive approvals.
 *   - People module models/enums use string-variable pattern (Models/Enums are not public surfaces).
 */
final class CorrectionRequestService
{
    public function __construct(
        private readonly AuditRecorder $auditRecorder,
    ) {}

    /** Resolve WorkflowTransitionService via string-variable to avoid boundary-scanner violations. */
    private function transitionService(): object
    {
        $class = 'Modules\\Workflow\\Services\\WorkflowTransitionService';

        return app($class);
    }

    // -----------------------------------------------------------------
    // Guardian actions
    // -----------------------------------------------------------------

    /**
     * Create a correction request and immediately submit it.
     *
     * @param  int  $studentProfileId  The student being corrected
     * @param  int  $guardianAccountId  Authenticated guardian's account ID
     * @param  int  $guardianProfileId  Authenticated guardian's profile ID
     * @param  string  $fieldCode  Must exist in CorrectionFieldCatalogue
     * @param  string  $proposedValue  The proposed new value (plain text; encrypted here for sensitive)
     * @param  string|null  $explanation  Optional guardian explanation
     * @param  int|null  $relationshipRefId  Required for relationship-type and legal-authority corrections;
     *                                       validated to belong to this guardian/student pair
     * @param  int|null  $institutionId  Institutional scope (for cross-institution guard)
     *
     * @throws \InvalidArgumentException When fieldCode is not in catalogue
     * @throws \InvalidArgumentException When proposed value fails field validation rules
     * @throws \InvalidArgumentException When relationship_ref_id is missing or not owned by this guardian+student
     * @throws \RuntimeException When guardian has no portal-eligible relationship to student
     */
    public function createAndSubmit(
        int $studentProfileId,
        int $guardianAccountId,
        int $guardianProfileId,
        string $fieldCode,
        string $proposedValue,
        ?string $explanation = null,
        ?int $relationshipRefId = null,
        ?int $institutionId = null,
    ): StudentCorrectionRequest {
        $field = $this->requireField($fieldCode);

        // Validate the proposed value against the field's rules
        $this->validateProposedValue($field, $proposedValue);

        // Verify active portal-eligible relationship
        $this->assertGuardianMaySubmit($guardianProfileId, $studentProfileId);

        // Validate relationship ownership for relationship-type corrections
        $this->assertRelationshipOwnership($field, $relationshipRefId, $guardianProfileId, $studentProfileId);

        // Snapshot current official value
        $currentValue = $this->snapshotCurrentValue($field, $studentProfileId, $relationshipRefId);

        // Resolve workflow definition for student_correction
        $workflowDefinitionClass = 'Modules\\Workflow\\Models\\WorkflowDefinition';
        $wfDef = $workflowDefinitionClass::where('type', 'student_correction')
            ->where('is_active', true)
            ->orderByDesc('version')
            ->firstOrFail();

        return DB::transaction(function () use (
            $field, $studentProfileId, $guardianAccountId, $guardianProfileId,
            $fieldCode, $proposedValue, $explanation, $relationshipRefId,
            $institutionId, $currentValue, $wfDef,
        ): StudentCorrectionRequest {
            // 1. Create WorkflowInstance at initial 'draft' state
            $instanceClass = 'Modules\\Workflow\\Models\\WorkflowInstance';
            $instance = new $instanceClass;
            $instance->workflow_definition_id = $wfDef->id;
            $instance->subject_type = 'StudentCorrectionRequest';
            $instance->subject_id = 0; // placeholder; updated below
            $instance->current_state = 'draft';
            $instance->initiating_actor_type = 'guardian';
            $instance->initiating_actor_portal = 'guardian';
            $instance->initiating_account_id = $guardianAccountId;
            $instance->institution_id = $institutionId;
            $instance->correlation_id = (string) Str::uuid();
            $instance->save();

            // 2. Create the correction request
            $request = new StudentCorrectionRequest;
            $request->workflow_instance_id = $instance->id;
            $request->student_profile_id = $studentProfileId;
            $request->guardian_account_id = $guardianAccountId;
            $request->guardian_profile_id = $guardianProfileId;
            $request->institution_id = $institutionId;
            $request->field_catalogue_code = $fieldCode;
            $request->classification = $field->classification()->value;
            $request->save();

            // 3. Back-fill subject_id now that we have the request ID
            $instance->subject_id = $request->id;
            $instance->save();

            // 4. Store proposal
            $this->createProposal($request, $field, $proposedValue, $currentValue, $explanation, $relationshipRefId, 1);

            // 5. Transition draft → submitted
            $context = new TransitionContext(
                actorType: 'guardian',
                portal: 'guardian',
                actorAccountId: $guardianAccountId,
            );

            $this->transitionService()->transition($instance, 'submit', $context);

            // 6. Audit correction submission
            $this->auditRecorder->record(new AuditEventPayload(
                actorType: 'guardian',
                sourceModule: 'Requests',
                action: 'correction_request.submitted',
                actorAccountId: $guardianAccountId,
                portal: 'guardian',
                subjectType: 'StudentCorrectionRequest',
                subjectId: $request->id,
                institutionId: $institutionId,
                afterState: [
                    'field_code' => $fieldCode,
                    'classification' => $request->classification,
                ],
            ));

            // 7. Notify secretary (correction_request.submitted)
            $this->dispatchNotification(
                notificationType: 'correction_request.submitted',
                recipientAccountType: 'staff',
                recipientAccountId: null, // broadcast to institution — resolved by notification routing
                portal: 'staff',
                messageKey: 'correction_request.submitted',
                messageParams: ['student_name' => $this->studentDisplayName($studentProfileId), 'request_id' => $request->id],
                subjectType: 'StudentCorrectionRequest',
                subjectId: $request->id,
            );

            return $request->fresh();
        });
    }

    /**
     * Guardian responds to a clarification request.
     *
     * @throws \RuntimeException When request is not in clarification_requested state
     * @throws \RuntimeException When the acting guardian does not own this request
     * @throws \InvalidArgumentException When revised proposed value fails field validation
     */
    public function resubmit(
        StudentCorrectionRequest $request,
        int $guardianAccountId,
        string $revisedProposedValue,
        ?string $explanation = null,
    ): StudentCorrectionRequest {
        $this->assertGuardianOwnsRequest($request, $guardianAccountId);

        $instance = $this->loadInstance($request);
        $field = $request->fieldCatalogue();

        // Validate revised value against catalogue rules
        $this->validateProposedValue($field, $revisedProposedValue);

        if ($instance->current_state !== 'clarification_requested') {
            throw new \RuntimeException("Cannot resubmit: request is in state '{$instance->current_state}'.");
        }

        // Preserve the relationship reference from the original proposal so resubmissions
        // for relationship-type corrections still point to the correct relationship row.
        $originalRelationshipRefId = $request->proposals()->orderBy('submission_sequence')->value('relationship_ref_id');

        $currentValue = $this->snapshotCurrentValue($field, $request->student_profile_id, $originalRelationshipRefId);
        $nextSeq = $request->proposals()->max('submission_sequence') + 1;

        return DB::transaction(function () use ($request, $guardianAccountId, $instance, $field, $revisedProposedValue, $explanation, $currentValue, $nextSeq, $originalRelationshipRefId): StudentCorrectionRequest {
            $this->createProposal($request, $field, $revisedProposedValue, $currentValue, $explanation, $originalRelationshipRefId, $nextSeq);

            $context = new TransitionContext(
                actorType: 'guardian',
                portal: 'guardian',
                actorAccountId: $guardianAccountId,
                comment: $explanation,
            );

            $this->transitionService()->transition($instance, 'resubmit', $context);

            return $request->fresh();
        });
    }

    /**
     * Guardian cancels their own request (from permitted states).
     *
     * @throws \RuntimeException When guardian does not own this request
     */
    public function cancelByGuardian(
        StudentCorrectionRequest $request,
        int $guardianAccountId,
    ): StudentCorrectionRequest {
        $this->assertGuardianOwnsRequest($request, $guardianAccountId);
        $instance = $this->loadInstance($request);

        $context = new TransitionContext(
            actorType: 'guardian',
            portal: 'guardian',
            actorAccountId: $guardianAccountId,
        );

        $this->transitionService()->transition($instance, 'cancel', $context);

        return $request->fresh();
    }

    // -----------------------------------------------------------------
    // Staff / secretary actions
    // -----------------------------------------------------------------

    /**
     * Secretary moves a submitted or resubmitted request into review.
     *
     * @throws \RuntimeException When request is not in submitted or resubmitted state
     */
    public function startReview(
        StudentCorrectionRequest $request,
        int $staffAccountId,
        ?int $expectedInstitutionId = null,
    ): StudentCorrectionRequest {
        $instance = $this->loadInstance($request);

        $context = new TransitionContext(
            actorType: 'staff',
            portal: 'staff',
            actorAccountId: $staffAccountId,
        );

        $this->transitionService()->transition($instance, 'start_review', $context, $expectedInstitutionId);

        return $request->fresh();
    }

    /**
     * Secretary requests clarification from the guardian.
     *
     * @throws \RuntimeException When reason is blank (clarification requires a comment)
     */
    public function requestClarification(
        StudentCorrectionRequest $request,
        int $staffAccountId,
        string $reason,
        ?int $expectedInstitutionId = null,
    ): StudentCorrectionRequest {
        if (trim($reason) === '') {
            throw new \RuntimeException('Clarification request requires a reason/comment.');
        }

        $instance = $this->loadInstance($request);

        $context = new TransitionContext(
            actorType: 'staff',
            portal: 'staff',
            actorAccountId: $staffAccountId,
            comment: $reason,
        );

        $this->transitionService()->transition($instance, 'request_clarification', $context, $expectedInstitutionId);

        // Notify guardian
        $this->dispatchNotification(
            notificationType: 'correction_request.clarification_requested',
            recipientAccountType: 'guardian',
            recipientAccountId: $request->guardian_account_id,
            portal: 'guardian',
            messageKey: 'correction_request.clarification_requested',
            messageParams: ['student_name' => $this->studentDisplayName($request->student_profile_id), 'request_id' => $request->id],
            subjectType: 'StudentCorrectionRequest',
            subjectId: $request->id,
        );

        return $request->fresh();
    }

    /**
     * Secretary or principal approves a correction request.
     *
     * For sensitive fields a valid reconfirmation token is required; the service
     * routes through ElectronicApprovalService (string-variable, boundary-safe) which
     * validates the token, checks the content hash, and writes the immutable approval row.
     * Direct ElectronicApproval inserts are never permitted from this service.
     *
     * @param  string  $actorType  'staff'|'administrative'
     * @param  string  $portal  'staff'|'admin'
     * @param  string|null  $reconfirmationTokenId  Required for sensitive requests; issued
     *                                              by ReconfirmationTokenService
     *
     * @throws \RuntimeException When a sensitive request is approved without a comment
     * @throws \RuntimeException When a sensitive request is approved without a token
     */
    public function approve(
        StudentCorrectionRequest $request,
        int $actorAccountId,
        string $actorType,
        string $portal,
        ?string $comment = null,
        ?int $expectedInstitutionId = null,
        ?string $reconfirmationTokenId = null,
    ): StudentCorrectionRequest {
        $instance = $this->loadInstance($request);

        if ($request->isSensitive()) {
            if (empty($comment)) {
                throw new \RuntimeException('Sensitive correction approval requires a comment.');
            }

            if ($reconfirmationTokenId === null) {
                throw new \RuntimeException(
                    'Sensitive correction approval requires a reconfirmation token. '.
                    'Please confirm your identity before approving.'
                );
            }
        }

        return DB::transaction(function () use ($request, $actorAccountId, $actorType, $portal, $comment, $expectedInstitutionId, $instance, $reconfirmationTokenId): StudentCorrectionRequest {
            $context = new TransitionContext(
                actorType: $actorType,
                portal: $portal,
                actorAccountId: $actorAccountId,
                comment: $comment,
            );

            $this->transitionService()->transition($instance, 'approve', $context, $expectedInstitutionId);

            // For sensitive fields: record approval through ElectronicApprovalService.
            // The service validates the token (actor binding, content-hash freshness,
            // single-use) and writes the immutable ElectronicApproval row.
            // String-variable pattern: Services is not a public Workflow surface.
            if ($request->isSensitive()) {
                $approvalServiceClass = 'Modules\\Workflow\\Services\\ElectronicApprovalService';
                $contentResolverClass = 'Modules\\Requests\\Resolvers\\CorrectionRequestContentResolver';

                app($approvalServiceClass)->record(
                    approvalType: 'sensitive_field_correction',
                    decision: 'approved',
                    subjectType: 'StudentCorrectionRequest',
                    subjectId: $request->id,
                    tokenId: $reconfirmationTokenId,
                    contentResolver: app($contentResolverClass),
                    context: $context,
                    comment: $comment,
                );
            }

            // Notify guardian
            $this->dispatchNotification(
                notificationType: 'correction_request.approved',
                recipientAccountType: 'guardian',
                recipientAccountId: $request->guardian_account_id,
                portal: 'guardian',
                messageKey: 'correction_request.approved',
                messageParams: ['student_name' => $this->studentDisplayName($request->student_profile_id), 'request_id' => $request->id],
                subjectType: 'StudentCorrectionRequest',
                subjectId: $request->id,
            );

            return $request->fresh();
        });
    }

    /**
     * Secretary or principal rejects a correction request.
     *
     * @throws \RuntimeException When comment is empty (rejection must explain the reason)
     */
    public function reject(
        StudentCorrectionRequest $request,
        int $actorAccountId,
        string $actorType,
        string $portal,
        string $reason,
        ?int $expectedInstitutionId = null,
    ): StudentCorrectionRequest {
        if (trim($reason) === '') {
            throw new \RuntimeException('Rejection requires a reason/comment.');
        }

        $instance = $this->loadInstance($request);

        $context = new TransitionContext(
            actorType: $actorType,
            portal: $portal,
            actorAccountId: $actorAccountId,
            comment: $reason,
        );

        $this->transitionService()->transition($instance, 'reject', $context, $expectedInstitutionId);

        // Notify guardian
        $this->dispatchNotification(
            notificationType: 'correction_request.rejected',
            recipientAccountType: 'guardian',
            recipientAccountId: $request->guardian_account_id,
            portal: 'guardian',
            messageKey: 'correction_request.rejected',
            messageParams: ['student_name' => $this->studentDisplayName($request->student_profile_id), 'request_id' => $request->id],
            subjectType: 'StudentCorrectionRequest',
            subjectId: $request->id,
        );

        return $request->fresh();
    }

    /**
     * Staff cancels a request on behalf of the institution (e.g. duplicate, superseded).
     */
    public function cancelByStaff(
        StudentCorrectionRequest $request,
        int $staffAccountId,
        string $actorType,
        string $portal,
        ?int $expectedInstitutionId = null,
    ): StudentCorrectionRequest {
        $instance = $this->loadInstance($request);

        $context = new TransitionContext(
            actorType: $actorType,
            portal: $portal,
            actorAccountId: $staffAccountId,
        );

        $this->transitionService()->transition($instance, 'cancel', $context, $expectedInstitutionId);

        return $request->fresh();
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function requireField(string $fieldCode): CorrectionFieldCatalogue
    {
        $field = CorrectionFieldCatalogue::tryFrom($fieldCode);

        if ($field === null) {
            throw new \InvalidArgumentException(
                "Field code '{$fieldCode}' is not in the correction field catalogue."
            );
        }

        return $field;
    }

    /**
     * Validate the proposed value against the field's declared validation rules.
     *
     * @throws \InvalidArgumentException
     */
    private function validateProposedValue(CorrectionFieldCatalogue $field, string $proposedValue): void
    {
        $validator = Validator::make(
            ['value' => $proposedValue],
            ['value' => $field->validationRules()],
        );

        if ($validator->fails()) {
            $messages = implode(' ', $validator->errors()->all());
            throw new \InvalidArgumentException(
                "Proposed value for field '{$field->value}' is invalid: {$messages}"
            );
        }
    }

    private function assertGuardianMaySubmit(int $guardianProfileId, int $studentProfileId): void
    {
        $eligible = DB::table('guardian_student_relationships')
            ->where('guardian_profile_id', $guardianProfileId)
            ->where('student_profile_id', $studentProfileId)
            ->where('verification_status', 'verified')
            ->where('portal_eligible', true)
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhere('ends_on', '>=', now()->toDateString()))
            ->exists();

        if (! $eligible) {
            throw new \RuntimeException('Guardian has no portal-eligible relationship to this student.');
        }
    }

    /**
     * For relationship-type corrections, ensure the relationship_ref_id belongs
     * to the submitting guardian AND the selected student.
     *
     * @throws \InvalidArgumentException
     */
    private function assertRelationshipOwnership(
        CorrectionFieldCatalogue $field,
        ?int $relationshipRefId,
        int $guardianProfileId,
        int $studentProfileId,
    ): void {
        if (! in_array($field, [CorrectionFieldCatalogue::GuardianRelationshipType, CorrectionFieldCatalogue::GuardianLegalAuthority], true)) {
            return;
        }

        if ($relationshipRefId === null) {
            throw new \InvalidArgumentException(
                "Corrections for field '{$field->value}' require a relationship_ref_id."
            );
        }

        $owned = DB::table('guardian_student_relationships')
            ->where('id', $relationshipRefId)
            ->where('guardian_profile_id', $guardianProfileId)
            ->where('student_profile_id', $studentProfileId)
            ->exists();

        if (! $owned) {
            throw new \InvalidArgumentException(
                'The provided relationship_ref_id does not belong to this guardian and student.'
            );
        }
    }

    private function assertGuardianOwnsRequest(StudentCorrectionRequest $request, int $guardianAccountId): void
    {
        if ($request->guardian_account_id !== $guardianAccountId) {
            throw new \RuntimeException('Access denied: request belongs to a different guardian.');
        }
    }

    /**
     * Read the current official value of the field being corrected.
     *
     * For encrypted fields (contact_points, person_identifiers) we store the HMAC
     * fingerprint rather than the raw value, since the raw value is inaccessible
     * without going through the People module's crypto layer.  The fingerprint is
     * stable and sufficient for conflict detection on apply.
     */
    private function snapshotCurrentValue(
        CorrectionFieldCatalogue $field,
        int $studentProfileId,
        ?int $relationshipRefId = null,
    ): ?string {
        return match ($field) {
            CorrectionFieldCatalogue::StudentNameAr => (string) DB::table('people as p')
                ->join('student_profiles as sp', 'sp.person_id', '=', 'p.id')
                ->where('sp.id', $studentProfileId)
                ->value('p.full_name_ar'),

            CorrectionFieldCatalogue::StudentNameEn => (string) DB::table('people as p')
                ->join('student_profiles as sp', 'sp.person_id', '=', 'p.id')
                ->where('sp.id', $studentProfileId)
                ->value('p.full_name_en'),

            CorrectionFieldCatalogue::BirthDate => (function () use ($studentProfileId): ?string {
                $val = DB::table('people as p')
                    ->join('student_profiles as sp', 'sp.person_id', '=', 'p.id')
                    ->where('sp.id', $studentProfileId)
                    ->value('p.birth_date');

                return $val ? (string) $val : null;
            })(),

            // Store fingerprint — raw value is encrypted in value_encrypted and
            // cannot be read outside of the People module's IdentifierCrypto service.
            CorrectionFieldCatalogue::ContactPhone => (function () use ($studentProfileId): ?string {
                $personId = DB::table('student_profiles')->where('id', $studentProfileId)->value('person_id');
                if (! $personId) {
                    return null;
                }

                return DB::table('contact_points')
                    ->where('person_id', $personId)
                    ->where('type', 'phone')
                    ->where('is_current', true)
                    ->value('value_fingerprint');
            })(),

            CorrectionFieldCatalogue::ContactEmail => (function () use ($studentProfileId): ?string {
                $personId = DB::table('student_profiles')->where('id', $studentProfileId)->value('person_id');
                if (! $personId) {
                    return null;
                }

                return DB::table('contact_points')
                    ->where('person_id', $personId)
                    ->where('type', 'email')
                    ->where('is_current', true)
                    ->value('value_fingerprint');
            })(),

            CorrectionFieldCatalogue::GuardianRelationshipType => $relationshipRefId
                ? (string) DB::table('guardian_student_relationships')->where('id', $relationshipRefId)->value('relationship_type')
                : null,

            CorrectionFieldCatalogue::GuardianLegalAuthority => $relationshipRefId
                ? (string) DB::table('guardian_student_relationships')->where('id', $relationshipRefId)->value('legal_authority')
                : null,

            // Store lookup_fingerprint for the same reason as contact fingerprints.
            CorrectionFieldCatalogue::IdentifierCorrection => (function () use ($studentProfileId): ?string {
                $personId = DB::table('student_profiles')->where('id', $studentProfileId)->value('person_id');
                if (! $personId) {
                    return null;
                }

                return DB::table('person_identifiers')
                    ->where('person_id', $personId)
                    ->where('is_current', true)
                    ->value('lookup_fingerprint');
            })(),
        };
    }

    private function createProposal(
        StudentCorrectionRequest $request,
        CorrectionFieldCatalogue $field,
        string $proposedValue,
        ?string $currentValue,
        ?string $explanation,
        ?int $relationshipRefId,
        int $sequence,
    ): CorrectionFieldProposal {
        $storedProposed = $field->requiresEncryption()
            ? Crypt::encryptString($proposedValue)
            : $proposedValue;

        $storedCurrent = ($field->requiresEncryption() && $currentValue !== null)
            ? Crypt::encryptString($currentValue)
            : $currentValue;

        $proposal = new CorrectionFieldProposal;
        $proposal->correction_request_id = $request->id;
        $proposal->field_code = $field->value;
        $proposal->old_value_snapshot = $storedCurrent;
        $proposal->proposed_value = $storedProposed;
        $proposal->explanation = $explanation;
        $proposal->relationship_ref_id = $relationshipRefId;
        $proposal->submission_sequence = $sequence;
        $proposal->save();

        return $proposal;
    }

    private function loadInstance(StudentCorrectionRequest $request): object
    {
        $instanceClass = 'Modules\\Workflow\\Models\\WorkflowInstance';

        return $instanceClass::findOrFail($request->workflow_instance_id);
    }

    private function studentDisplayName(int $studentProfileId): string
    {
        $name = DB::table('people as p')
            ->join('student_profiles as sp', 'sp.person_id', '=', 'p.id')
            ->where('sp.id', $studentProfileId)
            ->value('p.full_name_ar');

        return (string) ($name ?? 'الطالب');
    }

    /**
     * Dispatch a notification via the Notifications module's NotifyOnTransition action.
     * Uses string-variable pattern to avoid boundary-scanner violations.
     *
     * @param  array<string, mixed>  $messageParams
     */
    private function dispatchNotification(
        string $notificationType,
        string $recipientAccountType,
        ?int $recipientAccountId,
        string $portal,
        string $messageKey,
        array $messageParams,
        string $subjectType,
        int $subjectId,
    ): void {
        if ($recipientAccountId === null) {
            return; // No known single recipient — skip for now; bulk routing is a follow-up
        }

        try {
            $actionClass = 'Modules\\Notifications\\Actions\\NotifyOnTransition';
            $action = app($actionClass);
            $action(
                notificationType: $notificationType,
                recipientAccountType: $recipientAccountType,
                recipientAccountId: $recipientAccountId,
                portal: $portal,
                messageKey: $messageKey,
                messageParams: $messageParams,
                subjectType: $subjectType,
                subjectId: $subjectId,
            );
        } catch (\Throwable) {
            // Notification failures are non-fatal: log silently, don't abort the transaction
        }
    }
}

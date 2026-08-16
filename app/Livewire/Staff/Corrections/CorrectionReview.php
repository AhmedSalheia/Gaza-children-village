<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Corrections;

use App\Http\Auth\StaffReconfirmationChallenge;
use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Authorization\Data\PermissionKey;
use Modules\Requests\Enums\CorrectionFieldCatalogue;
use Modules\Requests\Exceptions\CorrectionConflictException;
use Modules\Requests\Models\StudentCorrectionRequest;
use Modules\Requests\Services\CorrectionApplicationService;
use Modules\Requests\Services\CorrectionRequestService;

/**
 * Staff portal: review, approve, reject, or clarify a correction request.
 *
 * Shows proposed value vs. current official value side-by-side.
 * Evidence attachments are linked from the attachment_links table.
 *
 * Role gates:
 *   correction.review  — required to view and submit clarification/rejection
 *   correction.approve — required to approve (principal for sensitive fields)
 *   correction.apply   — required to apply an approved correction
 *
 * Sensitive approval flow (two-phase):
 *   1. Staff clicks "Approve" → showCredentialForm becomes true.
 *   2. Staff enters their portal password → confirmAndApprove() issues a
 *      reconfirmation token via ReconfirmationTokenService and passes it to
 *      CorrectionRequestService::approve(), which delegates to
 *      ElectronicApprovalService for immutable approval recording.
 */
final class CorrectionReview extends Component
{
    use HasStaffAuth;

    public int $requestId;

    public string $comment = '';

    /** For sensitive approval: the raw credential entered by the principal. */
    public string $credentialInput = '';

    /** True while the credential confirmation form is shown. */
    public bool $showCredentialForm = false;

    /** @var string[] */
    public array $errors = [];

    public ?string $flashMessage = null;

    public bool $conflictDetected = false;

    public function mount(int $requestId): void
    {
        $this->requirePermission(PermissionKey::CORRECTION_REVIEW);
        $this->requestId = $requestId;
        $this->assertInstitutionScope();
    }

    // -----------------------------------------------------------------
    // Actions
    // -----------------------------------------------------------------

    public function startReview(): void
    {
        $this->errors = [];
        $scope = $this->staffScope();

        try {
            app(CorrectionRequestService::class)->startReview(
                request: $this->loadRequest(),
                staffAccountId: (int) auth('staff')->id(),
                expectedInstitutionId: $scope['institution_id'] ?? null,
            );

            $this->flashMessage = __('requests.review_started', [], null, 'Review started.');
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function requestClarification(): void
    {
        $this->errors = [];

        if (trim($this->comment) === '') {
            $this->errors[] = __('requests.error_comment_required', [], null, 'A comment is required to request clarification.');

            return;
        }

        $scope = $this->staffScope();

        try {
            app(CorrectionRequestService::class)->requestClarification(
                request: $this->loadRequest(),
                staffAccountId: (int) auth('staff')->id(),
                reason: $this->comment,
                expectedInstitutionId: $scope['institution_id'] ?? null,
            );

            $this->comment = '';
            $this->flashMessage = __('requests.clarification_requested', [], null, 'Clarification requested from guardian.');
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    /**
     * Initiate the approve action.
     *
     * For standard corrections: approves immediately (no credential needed).
     * For sensitive corrections: shows the credential confirmation form so the
     * principal must verify their identity before the approval is recorded.
     */
    public function initiateApprove(): void
    {
        $this->errors = [];

        if (! $this->staffCan(PermissionKey::CORRECTION_APPROVE)) {
            abort(403);
        }

        $request = $this->loadRequest();

        if ($request->isSensitive() && trim($this->comment) === '') {
            $this->errors[] = __('requests.error_sensitive_comment', [], null, 'Approval of a sensitive correction requires a comment.');

            return;
        }

        if ($request->isSensitive()) {
            // Show credential form — principal must confirm identity
            $this->showCredentialForm = true;

            return;
        }

        // Standard correction: approve directly (no reconfirmation token required)
        $this->executeApprove($request, null);
    }

    /**
     * Phase 2 of sensitive approval: issue a reconfirmation token via the
     * staff challenge (which verifies the credential against the live session)
     * and then call approve() with the token ID.
     */
    public function confirmAndApprove(): void
    {
        $this->errors = [];

        if (! $this->staffCan(PermissionKey::CORRECTION_APPROVE)) {
            abort(403);
        }

        if (trim($this->credentialInput) === '') {
            $this->errors[] = __('requests.error_credential_required', [], null, 'Please enter your password to confirm this approval.');

            return;
        }

        $request = $this->loadRequest();

        if (trim($this->comment) === '') {
            $this->errors[] = __('requests.error_sensitive_comment', [], null, 'A comment is required for sensitive approvals.');

            return;
        }

        try {
            // Issue a single-use reconfirmation token that binds the actor identity,
            // content hash at this instant, and the approval type.
            // String-variable pattern: Services is not a public Workflow surface.
            $tokenServiceClass = 'Modules\\Workflow\\Services\\ReconfirmationTokenService';
            $contentResolverClass = 'Modules\\Requests\\Resolvers\\CorrectionRequestContentResolver';

            $token = app($tokenServiceClass)->issue(
                challenge: app(StaffReconfirmationChallenge::class),
                credential: $this->credentialInput,
                contentResolver: app($contentResolverClass),
                approvalType: 'sensitive_field_correction',
                subjectType: 'StudentCorrectionRequest',
                subjectId: $request->id,
            );

            $this->credentialInput = '';
            $this->showCredentialForm = false;

            $this->executeApprove($request, $token->id);
        } catch (\Throwable $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function cancelCredentialForm(): void
    {
        $this->showCredentialForm = false;
        $this->credentialInput = '';
        $this->errors = [];
    }

    /**
     * Kept as the existing wire:click target in the Blade view.
     * Routes to initiateApprove() — retained for backward compatibility with
     * any existing wire:click="approve" in the view template.
     */
    public function approve(): void
    {
        $this->initiateApprove();
    }

    public function reject(): void
    {
        $this->errors = [];

        if (trim($this->comment) === '') {
            $this->errors[] = __('requests.error_reject_comment', [], null, 'A reason is required to reject a correction request.');

            return;
        }

        $scope = $this->staffScope();

        try {
            app(CorrectionRequestService::class)->reject(
                request: $this->loadRequest(),
                actorAccountId: (int) auth('staff')->id(),
                actorType: 'staff',
                portal: 'staff',
                reason: $this->comment,
                expectedInstitutionId: $scope['institution_id'] ?? null,
            );

            $this->comment = '';
            $this->flashMessage = __('requests.rejected', [], null, 'Correction rejected.');
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function apply(): void
    {
        $this->errors = [];
        $this->conflictDetected = false;

        if (! $this->staffCan(PermissionKey::CORRECTION_APPLY)) {
            abort(403);
        }

        $scope = $this->staffScope();

        try {
            app(CorrectionApplicationService::class)->apply(
                request: $this->loadRequest(),
                appliedByAccountId: (int) auth('staff')->id(),
                actorType: 'staff',
                portal: 'staff',
                expectedInstitutionId: $scope['institution_id'] ?? null,
            );

            $this->flashMessage = __('requests.applied', [], null, 'Correction applied successfully.');
        } catch (CorrectionConflictException $e) {
            $this->conflictDetected = true;
            $this->errors[] = $e->getMessage();
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    // -----------------------------------------------------------------
    // Render
    // -----------------------------------------------------------------

    public function render(): View
    {
        $request = $this->loadRequest();
        $instance = $this->loadInstance($request);
        $timeline = $this->loadTimeline($instance->id);
        $proposal = $request->proposals()->orderByDesc('submission_sequence')->first();
        $canApprove = $this->staffCan(PermissionKey::CORRECTION_APPROVE);

        // Decrypt sensitive proposal values for the principal (CORRECTION_APPROVE holder).
        // All other staff see a placeholder — the ciphertext is never exposed to reviewers.
        $field = $proposal
            ? CorrectionFieldCatalogue::tryFrom($request->field_catalogue_code)
            : null;
        $valuesAreSensitive = $field?->requiresEncryption() ?? false;

        $proposedValueDisplay = null;
        $currentValueDisplay = null;

        if ($proposal) {
            if ($valuesAreSensitive && $canApprove) {
                // Only principals may see the plaintext in order to make an informed decision.
                try {
                    $proposedValueDisplay = Crypt::decryptString($proposal->proposed_value);
                    $currentValueDisplay = ! empty($proposal->old_value_snapshot)
                        ? Crypt::decryptString($proposal->old_value_snapshot)
                        : null;
                } catch (\Exception) {
                    $proposedValueDisplay = __('requests.decrypt_error', [], null, '(decryption error — data may be corrupt)');
                    $currentValueDisplay = null;
                }
            } elseif ($valuesAreSensitive) {
                $proposedValueDisplay = __('requests.sensitive_value_hidden', [], null, '(sensitive — principal eyes only)');
                $currentValueDisplay = __('requests.sensitive_value_hidden', [], null, '(sensitive)');
            } else {
                $proposedValueDisplay = $proposal->proposed_value;
                $currentValueDisplay = $proposal->old_value_snapshot;
            }
        }

        return view('livewire.staff.corrections.correction-review', [
            'request' => $request,
            'instance' => $instance,
            'timeline' => $timeline,
            'proposal' => $proposal,
            'canApprove' => $canApprove,
            'canApply' => $this->staffCan(PermissionKey::CORRECTION_APPLY),
            'showCredentialForm' => $this->showCredentialForm,
            'proposedValueDisplay' => $proposedValueDisplay,
            'currentValueDisplay' => $currentValueDisplay,
            'valuesAreSensitive' => $valuesAreSensitive,
        ])->layout('layouts.staff');
    }

    // -----------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------

    private function executeApprove(StudentCorrectionRequest $request, ?string $tokenId): void
    {
        $scope = $this->staffScope();

        try {
            app(CorrectionRequestService::class)->approve(
                request: $request,
                actorAccountId: (int) auth('staff')->id(),
                actorType: 'staff',
                portal: 'staff',
                comment: $this->comment ?: null,
                expectedInstitutionId: $scope['institution_id'] ?? null,
                reconfirmationTokenId: $tokenId,
            );

            $this->comment = '';
            $this->flashMessage = __('requests.approved', [], null, 'Correction approved.');
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    private function assertInstitutionScope(): void
    {
        $scope = $this->staffScope();

        if (($scope['institution_id'] ?? null) === null) {
            return; // Full-scope actors may view any request
        }

        $belongs = DB::table('student_correction_requests')
            ->where('id', $this->requestId)
            ->where('institution_id', $scope['institution_id'])
            ->exists();

        if (! $belongs) {
            abort(403, 'This request does not belong to your institution.');
        }
    }

    private function loadRequest(): StudentCorrectionRequest
    {
        return StudentCorrectionRequest::findOrFail($this->requestId);
    }

    private function loadInstance(StudentCorrectionRequest $request): object
    {
        $instanceClass = 'Modules\\Workflow\\Models\\WorkflowInstance';

        return $instanceClass::findOrFail($request->workflow_instance_id);
    }

    private function loadTimeline(int $instanceId): Collection
    {
        return DB::table('workflow_actions as wa')
            ->where('wa.workflow_instance_id', $instanceId)
            ->select('wa.id', 'wa.action_code', 'wa.previous_state', 'wa.new_state', 'wa.decision', 'wa.comment', 'wa.actor_type', 'wa.created_at')
            ->orderBy('wa.id')
            ->get();
    }
}

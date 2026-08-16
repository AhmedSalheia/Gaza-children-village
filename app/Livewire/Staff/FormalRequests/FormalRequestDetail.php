<?php

declare(strict_types=1);

namespace App\Livewire\Staff\FormalRequests;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Attachments\Data\UploaderContext;
use Modules\Attachments\Services\SecureAttachmentService;
use Modules\Authorization\Data\PermissionKey;
use Modules\Requests\Models\InstitutionFormalRequest;
use Modules\Requests\Services\InstitutionFormalRequestService;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Staff portal: view, edit, and action a formal request.
 *
 * Secretary (formal_request.prepare) — view, edit draft/returned, submit,
 *   cancel, supersede (from responded/rejected/closed), respond to clarification.
 * Principal/deputy (formal_request.review + .sign) — view, return, sign, submit.
 *
 * Sign action (principal/deputy only) uses a two-phase credential confirmation:
 *   1. Staff clicks "Sign" → showSignForm = true.
 *   2. Staff enters portal password → issueToken() calls issueSigningToken().
 *   3. On success: tokenId stored in component state.
 *   4. Staff confirms → sign() consumes the token and records electronic approval.
 *
 * Authorization:
 *   - $requestId is #[Locked] — Livewire rejects forged hydration values.
 *   - Every load/mutation re-resolves the live staff scope (staffScope()) and
 *     queries forInstitution(currentInstitutionId()), so a mid-session position
 *     change (institution A → B) is reflected on the next Livewire request rather
 *     than relying on a mount-time captured institution ID.
 *   - $lockedInstitutionId is a secondary belt-and-suspenders guard. The primary
 *     guard is the live-scope re-check in each loadRequest()/mutation.
 */
final class FormalRequestDetail extends Component
{
    use HasStaffAuth;
    use WithFileUploads;

    /** Route-bound request ID — locked to prevent Livewire hydration forgery. */
    #[Locked]
    public int $requestId;

    /**
     * Institution ID captured at mount — secondary guard only.
     * Primary authorization is the live staffScope() re-check in every action.
     */
    #[Locked]
    public int $lockedInstitutionId;

    // Edit fields
    public string $titleAr = '';

    public string $titleEn = '';

    public string $bodyText = '';

    public int $priority = 2;

    public string $dueDate = '';

    public bool $editMode = false;

    // Comment
    public string $newComment = '';

    public string $commentAudience = 'internal';

    // Return-to-preparer
    public string $returnReason = '';

    public bool $showReturnForm = false;

    // Signing
    public string $credential = '';

    public bool $showSignForm = false;

    public ?string $pendingTokenId = null;

    // Supersede
    public bool $showSupersedeForm = false;

    public string $supersedeTitleAr = '';

    public string $supersedeTitleEn = '';

    public string $supersedeBodyText = '';

    public int $supersedePriority = 2;

    public string $supersedeDueDate = '';

    // Attachment upload
    public mixed $attachmentFile = null;

    public ?string $flashMessage = null;

    /** @var list<string> */
    public array $errors = [];

    public function mount(int $requestId): void
    {
        $this->requestId = $requestId;

        $currentInstitutionId = $this->currentInstitutionId();
        $this->lockedInstitutionId = $currentInstitutionId;

        // Require at minimum one relevant permission.
        if (! $this->staffCan(PermissionKey::FORMAL_REQUEST_PREPARE)
            && ! $this->staffCan(PermissionKey::FORMAL_REQUEST_REVIEW)) {
            abort(403);
        }

        // Verify the request belongs to the staff member's current institution.
        $this->loadRequest();

        $this->loadRequestFields();
    }

    public function toggleEdit(): void
    {
        $this->requirePermission(PermissionKey::FORMAL_REQUEST_PREPARE);
        $this->editMode = ! $this->editMode;
        if ($this->editMode) {
            $this->loadRequestFields();
        }
    }

    public function saveEdit(): void
    {
        $this->requirePermission(PermissionKey::FORMAL_REQUEST_PREPARE);

        // Server-side validation — equivalent rules to creation so forged Livewire
        // property values (e.g. empty title, invalid due date) cannot bypass the UI.
        $this->validate([
            'titleAr' => ['required', 'string', 'max:500'],
            'titleEn' => ['required', 'string', 'max:500'],
            'bodyText' => ['required', 'string', 'min:10'],
            'priority' => ['required', 'integer', 'min:1', 'max:4'],
            'dueDate' => ['nullable', 'date', 'after:today'],
        ]);

        $this->errors = [];

        $request = $this->loadRequest();

        try {
            app(InstitutionFormalRequestService::class)->updateDraft(
                request: $request,
                titleAr: $this->titleAr,
                titleEn: $this->titleEn,
                body: ['text' => $this->bodyText],
                priority: $this->priority,
                dueDate: $this->dueDate !== '' ? $this->dueDate : null,
                actorAccountId: (int) auth('staff')->id(),
                requestType: null,
                expectedInstitutionId: $this->currentInstitutionId(),
            );
            $this->editMode = false;
            $this->flashMessage = 'Request updated.';
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function submitForReview(): void
    {
        $this->requirePermission(PermissionKey::FORMAL_REQUEST_PREPARE);

        try {
            app(InstitutionFormalRequestService::class)->submitForInternalReview(
                request: $this->loadRequest(),
                actorAccountId: (int) auth('staff')->id(),
                expectedInstitutionId: $this->currentInstitutionId(),
            );
            $this->flashMessage = 'Request submitted for internal review.';
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function resubmit(): void
    {
        $this->requirePermission(PermissionKey::FORMAL_REQUEST_PREPARE);

        try {
            $branch = app(InstitutionFormalRequestService::class)->resubmit(
                request: $this->loadRequest(),
                actorAccountId: (int) auth('staff')->id(),
                expectedInstitutionId: $this->currentInstitutionId(),
            );
            // Redirect to the new draft — the original row is now superseded.
            $this->redirect(route('staff.formal-requests.detail', $branch->id), navigate: true);
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function uploadAttachment(): void
    {
        $this->requirePermission(PermissionKey::FORMAL_REQUEST_PREPARE);

        $this->validate(['attachmentFile' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png']);

        $request = $this->loadRequest();

        // Attachments may only be added while the request is in an editable state
        // (draft only). Allowing upload after signing would silently alter supporting
        // evidence of an electronically signed request without invalidating the
        // signature — a document-integrity violation.
        if (! $request->isEditable()) {
            $this->errors[] = 'Attachments can only be added while the request is in draft state.';

            return;
        }

        $institutionId = $this->currentInstitutionId();

        $uploader = new UploaderContext(
            actorType: 'staff',
            accountId: (int) auth('staff')->id(),
            portal: 'staff',
            institutionId: $institutionId,
        );

        try {
            $attachment = app(SecureAttachmentService::class)->store(
                file: $this->attachmentFile,
                uploader: $uploader,
                purpose: 'evidence',
            );

            app(InstitutionFormalRequestService::class)->linkAttachment(
                request: $request,
                attachmentId: $attachment->id,
                linkType: 'supporting_evidence',
            );

            $this->attachmentFile = null;
            $this->flashMessage = 'Attachment uploaded and linked.';
        } catch (\Throwable $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function submitToManagement(): void
    {
        $this->requirePermission(PermissionKey::FORMAL_REQUEST_SUBMIT);

        try {
            app(InstitutionFormalRequestService::class)->submitToManagement(
                request: $this->loadRequest(),
                actorAccountId: (int) auth('staff')->id(),
                expectedInstitutionId: $this->currentInstitutionId(),
            );
            $this->flashMessage = 'Request submitted to central management.';
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function showReturn(): void
    {
        $this->requirePermission(PermissionKey::FORMAL_REQUEST_REVIEW);
        $this->showReturnForm = true;
    }

    public function returnToPreparer(): void
    {
        $this->requirePermission(PermissionKey::FORMAL_REQUEST_REVIEW);
        $this->errors = [];

        if (trim($this->returnReason) === '') {
            $this->errors[] = 'A return reason is required.';

            return;
        }

        try {
            app(InstitutionFormalRequestService::class)->returnToPreparer(
                request: $this->loadRequest(),
                actorAccountId: (int) auth('staff')->id(),
                reason: $this->returnReason,
                expectedInstitutionId: $this->currentInstitutionId(),
            );
            $this->showReturnForm = false;
            $this->returnReason = '';
            $this->flashMessage = 'Request returned to preparer.';
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function showSign(): void
    {
        $this->requirePermission(PermissionKey::FORMAL_REQUEST_SIGN);
        $this->showSignForm = true;
        $this->credential = '';
        $this->pendingTokenId = null;
    }

    public function issueSigningToken(): void
    {
        $this->requirePermission(PermissionKey::FORMAL_REQUEST_SIGN);
        $this->errors = [];

        if (trim($this->credential) === '') {
            $this->errors[] = 'Please enter your password.';

            return;
        }

        $pos = $this->resolveActivePositionForSigning();

        try {
            $tokenId = app(InstitutionFormalRequestService::class)->issueSigningToken(
                request: $this->loadRequest(),
                credential: $this->credential,
                signerAccountId: (int) auth('staff')->id(),
                signerPositionDefinition: $pos,
                portal: 'staff',
                expectedInstitutionId: $this->currentInstitutionId(),
            );
            $this->pendingTokenId = $tokenId;
            $this->credential = '';
            $this->flashMessage = 'Credential verified. Click "Confirm Signature" to sign.';
        } catch (\Throwable $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function confirmSign(): void
    {
        $this->requirePermission(PermissionKey::FORMAL_REQUEST_SIGN);
        $this->errors = [];

        if ($this->pendingTokenId === null) {
            $this->errors[] = 'No active signing token. Please re-enter your password.';

            return;
        }

        $pos = $this->resolveActivePositionForSigning();

        try {
            app(InstitutionFormalRequestService::class)->sign(
                request: $this->loadRequest(),
                tokenId: $this->pendingTokenId,
                signerAccountId: (int) auth('staff')->id(),
                signerPositionDefinition: $pos,
                portal: 'staff',
                expectedInstitutionId: $this->currentInstitutionId(),
            );
            $this->showSignForm = false;
            $this->pendingTokenId = null;
            $this->flashMessage = 'Request signed electronically.';
        } catch (\Throwable $e) {
            $this->pendingTokenId = null;
            $this->errors[] = $e->getMessage();
        }
    }

    public function addComment(): void
    {
        // Any staff member who can prepare or review may add comments.
        if (! $this->staffCan(PermissionKey::FORMAL_REQUEST_PREPARE)
            && ! $this->staffCan(PermissionKey::FORMAL_REQUEST_REVIEW)) {
            abort(403);
        }

        $this->errors = [];

        if (trim($this->newComment) === '') {
            $this->errors[] = 'Comment cannot be blank.';

            return;
        }

        try {
            app(InstitutionFormalRequestService::class)->addComment(
                request: $this->loadRequest(),
                actorType: 'staff',
                actorAccountId: (int) auth('staff')->id(),
                portal: 'staff',
                audience: $this->commentAudience,
                commentText: $this->newComment,
                expectedInstitutionId: $this->currentInstitutionId(),
            );
            $this->newComment = '';
            $this->flashMessage = 'Comment added.';
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function respondToClarification(): void
    {
        $this->requirePermission(PermissionKey::FORMAL_REQUEST_PREPARE);
        $this->errors = [];

        if (trim($this->newComment) === '') {
            $this->errors[] = 'A clarification response is required.';

            return;
        }

        try {
            app(InstitutionFormalRequestService::class)->respondToClarification(
                request: $this->loadRequest(),
                actorAccountId: (int) auth('staff')->id(),
                response: $this->newComment,
                expectedInstitutionId: $this->currentInstitutionId(),
            );
            $this->newComment = '';
            $this->flashMessage = 'Clarification response submitted.';
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function showSupersede(): void
    {
        $this->requirePermission(PermissionKey::FORMAL_REQUEST_PREPARE);
        $request = $this->loadRequest();
        // Pre-fill fields from the current request for convenience.
        $this->supersedeTitleAr = $request->title_ar;
        $this->supersedeTitleEn = $request->title_en;
        $this->supersedeBodyText = is_array($request->body) ? ($request->body['text'] ?? '') : '';
        $this->supersedePriority = $request->priority;
        $this->supersedeDueDate = $request->due_date ? $request->due_date->format('Y-m-d') : '';
        $this->showSupersedeForm = true;
    }

    public function cancelSupersede(): void
    {
        $this->showSupersedeForm = false;
        $this->supersedeTitleAr = $this->supersedeTitleEn = $this->supersedeBodyText = '';
        $this->supersedePriority = 2;
        $this->supersedeDueDate = '';
    }

    public function supersede(): void
    {
        $this->requirePermission(PermissionKey::FORMAL_REQUEST_PREPARE);
        $this->errors = [];

        if (trim($this->supersedeTitleEn) === '' || trim($this->supersedeTitleAr) === '') {
            $this->errors[] = 'Both Arabic and English titles are required.';

            return;
        }

        if (trim($this->supersedeBodyText) === '') {
            $this->errors[] = 'A body is required for the replacement request.';

            return;
        }

        try {
            $replacement = app(InstitutionFormalRequestService::class)->supersede(
                request: $this->loadRequest(),
                titleAr: $this->supersedeTitleAr,
                titleEn: $this->supersedeTitleEn,
                body: ['text' => $this->supersedeBodyText],
                priority: $this->supersedePriority,
                dueDate: $this->supersedeDueDate !== '' ? $this->supersedeDueDate : null,
                actorAccountId: (int) auth('staff')->id(),
                expectedInstitutionId: $this->currentInstitutionId(),
            );

            // Redirect to the new replacement draft.
            $this->redirect(route('staff.formal-requests.detail', $replacement->id), navigate: true);
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function render(): View
    {
        // Re-check on every Livewire round-trip so that revoking the grant
        // from an active staff session takes effect immediately.
        if (! $this->staffCan(PermissionKey::FORMAL_REQUEST_PREPARE)
            && ! $this->staffCan(PermissionKey::FORMAL_REQUEST_REVIEW)) {
            abort(403);
        }

        $request = $this->loadRequest();
        $comments = $request->comments()
            ->visibleToInstitution()
            ->get();

        // Load attachment links with their SecureAttachment for the view.
        $attachments = app(InstitutionFormalRequestService::class)->listAttachments($request);

        $supersedableStatuses = [
            InstitutionFormalRequest::STATUS_RESPONDED,
            InstitutionFormalRequest::STATUS_CLOSED,
            InstitutionFormalRequest::STATUS_REJECTED,
        ];

        $canPrepare = $this->staffCan(PermissionKey::FORMAL_REQUEST_PREPARE);

        return view('staff.formal-requests.detail', [
            'request' => $request,
            'comments' => $comments,
            'attachments' => $attachments,
            'canPrepare' => $canPrepare,
            'canReview' => $this->staffCan(PermissionKey::FORMAL_REQUEST_REVIEW),
            'canSign' => $this->staffCan(PermissionKey::FORMAL_REQUEST_SIGN),
            'canSubmit' => $this->staffCan(PermissionKey::FORMAL_REQUEST_SUBMIT),
            'canSupersede' => $canPrepare
                && in_array($request->current_status, $supersedableStatuses, true),
        ]);
    }

    /**
     * Scope-aware request loader.
     *
     * Re-resolves the live staff scope on every call so that a mid-session
     * position/institution change is enforced on the next Livewire request.
     * Uses forInstitution() to ensure the row belongs to the actor's current
     * institution — findOrFail() on the bare ID would not catch a scope drift.
     *
     * @throws ModelNotFoundException (→ 404)
     * @throws HttpException (→ 403)
     */
    private function loadRequest(): InstitutionFormalRequest
    {
        $currentInstitutionId = $this->currentInstitutionId();

        return InstitutionFormalRequest::forInstitution($currentInstitutionId)
            ->where('id', $this->requestId)
            ->firstOrFail();
    }

    /**
     * Resolve the current (live) institution ID from the authenticated staff scope.
     * Called fresh on every action — never reads the mount-time $lockedInstitutionId
     * as the authoritative value.
     *
     * @throws HttpException (403)
     */
    private function currentInstitutionId(): int
    {
        $scope = $this->staffScope();

        if ($scope['institution_id'] === null) {
            abort(403, 'No active institutional scope for your account.');
        }

        return $scope['institution_id'];
    }

    private function loadRequestFields(): void
    {
        $request = $this->loadRequest();
        $this->titleAr = $request->title_ar ?? '';
        $this->titleEn = $request->title_en ?? '';
        $this->bodyText = is_array($request->body) ? ($request->body['text'] ?? '') : '';
        $this->priority = $request->priority ?? 2;
        $this->dueDate = $request->due_date ? $request->due_date->format('Y-m-d') : '';
    }

    private function resolveActivePositionForSigning(): string
    {
        $pos = $this->resolveActivePosition();

        if ($pos === null) {
            throw new \RuntimeException('No active position found for signing.');
        }

        return (string) $pos->position_definition;
    }
}

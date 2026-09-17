<?php

declare(strict_types=1);

namespace App\Livewire\Admin\FormalRequests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Authorization\Data\PermissionKey;
use Modules\Requests\Models\InstitutionFormalRequest;
use Modules\Requests\Models\InstitutionFormalRequestComment;
use Modules\Requests\Services\InstitutionFormalRequestService;

#[Layout('layouts.admin')]
final class ManagementReview extends Component
{
    /** Route-bound request ID — locked to prevent Livewire hydration forgery. */
    #[Locked]
    public int $requestId;

    public string $action = '';

    public string $comment = '';

    public string $responseText = '';

    public ?string $flashMessage = null;

    /** @var list<string> */
    public array $errors = [];

    public function mount(int $requestId): void
    {
        $this->requestId = $requestId;
        $this->requirePermission();
    }

    public function startReview(): void
    {
        $this->requirePermission();
        $this->errors = [];

        try {
            app(InstitutionFormalRequestService::class)->startManagementReview(
                request: $this->loadRequest(),
                actorAccountId: (int) Auth::guard('admin')->id(),
            );

            $this->flashMessage = 'Review started.';
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function requestClarification(): void
    {
        $this->requirePermission();
        $this->errors = [];

        if (trim($this->comment) === '') {
            $this->errors[] = 'Please enter your clarification question.';

            return;
        }

        try {
            app(InstitutionFormalRequestService::class)->requestClarification(
                request: $this->loadRequest(),
                actorAccountId: (int) Auth::guard('admin')->id(),
                question: $this->comment,
            );

            $this->comment = '';
            $this->action = '';
            $this->flashMessage = 'Clarification requested.';
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function accept(): void
    {
        $this->requirePermission();
        $this->errors = [];

        try {
            app(InstitutionFormalRequestService::class)->accept(
                request: $this->loadRequest(),
                actorAccountId: (int) Auth::guard('admin')->id(),
                comment: $this->comment !== '' ? $this->comment : null,
            );

            $this->comment = '';
            $this->action = '';
            $this->flashMessage = 'Request accepted.';
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function reject(): void
    {
        $this->requirePermission();
        $this->errors = [];

        if (trim($this->comment) === '') {
            $this->errors[] = 'A rejection reason is required.';

            return;
        }

        try {
            app(InstitutionFormalRequestService::class)->reject(
                request: $this->loadRequest(),
                actorAccountId: (int) Auth::guard('admin')->id(),
                reason: $this->comment,
            );

            $this->comment = '';
            $this->action = '';
            $this->flashMessage = 'Request rejected.';
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function respond(): void
    {
        $this->requirePermission();
        $this->errors = [];

        if (trim($this->responseText) === '') {
            $this->errors[] = 'A response is required.';

            return;
        }

        try {
            app(InstitutionFormalRequestService::class)->respond(
                request: $this->loadRequest(),
                actorAccountId: (int) Auth::guard('admin')->id(),
                responseBody: ['text' => $this->responseText],
            );

            $this->responseText = '';
            $this->action = '';
            $this->flashMessage = 'Response recorded.';
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function close(): void
    {
        $this->requirePermission();
        $this->errors = [];

        try {
            app(InstitutionFormalRequestService::class)->close(
                request: $this->loadRequest(),
                actorAccountId: (int) Auth::guard('admin')->id(),
            );

            $this->flashMessage = 'Request closed.';
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function addComment(): void
    {
        $this->requirePermission();
        $this->errors = [];

        if (trim($this->comment) === '') {
            $this->errors[] = 'Comment cannot be blank.';

            return;
        }

        try {
            app(InstitutionFormalRequestService::class)->addComment(
                request: $this->loadRequest(),
                actorType: 'administrative',
                actorAccountId: (int) Auth::guard('admin')->id(),
                portal: 'admin',
                audience: InstitutionFormalRequestComment::AUDIENCE_MANAGEMENT,
                commentText: $this->comment,
            );

            $this->comment = '';
            $this->flashMessage = 'Comment added.';
        } catch (\RuntimeException $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function render(): View
    {
        $this->requirePermission();

        $request = $this->loadRequest();

        $comments = $request->comments()
            ->visibleToManagement()
            ->get();

        $attachments = app(InstitutionFormalRequestService::class)
            ->listAttachments($request);

        return view('admin.formal-requests.review', [
            'request' => $request,
            'comments' => $comments,
            'attachments' => $attachments,
        ]);
    }

    private function loadRequest(): InstitutionFormalRequest
    {
        return InstitutionFormalRequest::managementVisible()
            ->where('id', $this->requestId)
            ->firstOrFail();
    }

    private function requirePermission(): void
    {
        $account = Auth::guard('admin')->user();

        if ($account === null) {
            abort(403);
        }

        $hasPermission = DB::table('administrative_account_roles as aar')
            ->join('role_permissions as rp', 'rp.role_id', '=', 'aar.role_id')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where(
                'aar.administrative_account_id',
                $account->getKey()
            )
            ->whereNull('aar.revoked_at')
            ->where(
                'p.key',
                PermissionKey::FORMAL_REQUEST_RESPOND
            )
            ->exists();

        if (! $hasPermission) {
            abort(
                403,
                __('ui.unauthorized', [], null, 'You are not authorised to access this page.')
            );
        }
    }
}

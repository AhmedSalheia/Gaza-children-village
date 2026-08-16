<?php

declare(strict_types=1);

namespace App\Livewire\Staff\FormalRequests;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Authorization\Data\PermissionKey;
use Modules\Requests\Models\InstitutionFormalRequest;
use Modules\Requests\Services\InstitutionFormalRequestService;

/**
 * Staff portal: list of formal requests for the current institution.
 *
 * Secretary (formal_request.prepare) — sees all requests for their institution.
 * Principal/deputy (formal_request.review) — sees all requests for their institution.
 * Filters by status, request type.
 */
final class FormalRequestList extends Component
{
    use HasStaffAuth;
    use WithPagination;

    public string $statusFilter = '';

    public string $typeFilter = '';

    public ?string $flashMessage = null;

    public function mount(): void
    {
        $canPrepare = $this->staffCan(PermissionKey::FORMAL_REQUEST_PREPARE);
        $canReview = $this->staffCan(PermissionKey::FORMAL_REQUEST_REVIEW);

        if (! $canPrepare && ! $canReview) {
            abort(403, __('ui.unauthorized', [], null, 'You are not authorised to access this page.'));
        }
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function cancel(int $requestId): void
    {
        // Re-check on every action so revoked grants take effect immediately.
        $this->requirePermission(PermissionKey::FORMAL_REQUEST_PREPARE);

        $institutionId = $this->currentInstitutionId();

        $request = InstitutionFormalRequest::find($requestId);

        if ($request === null) {
            return;
        }

        try {
            app(InstitutionFormalRequestService::class)->cancel(
                request: $request,
                actorAccountId: (int) auth('staff')->id(),
                expectedInstitutionId: $institutionId,
            );
            $this->flashMessage = 'Request cancelled.';
        } catch (\RuntimeException $e) {
            $this->flashMessage = $e->getMessage();
        }
    }

    public function render(): View
    {
        // Re-check on every Livewire round-trip so that revoking the grant or
        // removing the active position takes effect immediately.
        if (! $this->staffCan(PermissionKey::FORMAL_REQUEST_PREPARE)
            && ! $this->staffCan(PermissionKey::FORMAL_REQUEST_REVIEW)) {
            abort(403);
        }

        // Fail closed when the active position is gone (null scope).
        $institutionId = $this->currentInstitutionId();

        $query = InstitutionFormalRequest::query()
            ->forInstitution($institutionId)  // always applied — never conditional
            ->when($this->statusFilter !== '', fn ($q) => $q->withStatus($this->statusFilter))
            ->when($this->typeFilter !== '', fn ($q) => $q->where('request_type', $this->typeFilter))
            ->orderByDesc('created_at');

        $requests = $query->paginate(20);

        return view('staff.formal-requests.list', [
            'requests' => $requests,
            'statusOptions' => $this->statusOptions(),
            'typeOptions' => InstitutionFormalRequest::REQUEST_TYPES,
            'canPrepare' => $this->staffCan(PermissionKey::FORMAL_REQUEST_PREPARE),
            'canReview' => $this->staffCan(PermissionKey::FORMAL_REQUEST_REVIEW),
            'canSign' => $this->staffCan(PermissionKey::FORMAL_REQUEST_SIGN),
        ]);
    }

    /**
     * Resolve the current (live) institution ID from the authenticated staff scope.
     * Aborts with 403 when no active position exists (position removed mid-session).
     */
    private function currentInstitutionId(): int
    {
        $scope = $this->staffScope();

        if ($scope['institution_id'] === null) {
            abort(403, 'No active institutional scope for your account.');
        }

        return $scope['institution_id'];
    }

    /** @return list<string> */
    private function statusOptions(): array
    {
        return [
            InstitutionFormalRequest::STATUS_DRAFT,
            InstitutionFormalRequest::STATUS_INTERNAL_REVIEW,
            InstitutionFormalRequest::STATUS_RETURNED_TO_PREPARER,
            InstitutionFormalRequest::STATUS_SIGNED,
            InstitutionFormalRequest::STATUS_SUBMITTED_TO_MANAGEMENT,
            InstitutionFormalRequest::STATUS_UNDER_MANAGEMENT_REVIEW,
            InstitutionFormalRequest::STATUS_CLARIFICATION_REQUESTED,
            InstitutionFormalRequest::STATUS_ACCEPTED,
            InstitutionFormalRequest::STATUS_REJECTED,
            InstitutionFormalRequest::STATUS_RESPONDED,
            InstitutionFormalRequest::STATUS_CLOSED,
            InstitutionFormalRequest::STATUS_CANCELLED,
            InstitutionFormalRequest::STATUS_SUPERSEDED,
        ];
    }
}

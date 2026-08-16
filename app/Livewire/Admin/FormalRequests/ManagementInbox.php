<?php

declare(strict_types=1);

namespace App\Livewire\Admin\FormalRequests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Authorization\Data\PermissionKey;
use Modules\Requests\Models\InstitutionFormalRequest;

/**
 * Admin portal: management inbox for formal institution requests.
 *
 * Requires formal_request.respond permission.
 * Cross-institution: shows requests from ALL institutions in submitted/under-review/etc. states.
 *
 * Authorization: only administrative accounts with formal_request.respond permission
 * may access this screen. The permission check uses the admin portal's RBAC system.
 */
final class ManagementInbox extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public string $institutionFilter = '';

    public ?string $flashMessage = null;

    public function mount(): void
    {
        $this->requirePermission();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedInstitutionFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        // Re-check on every Livewire round-trip so that revoking the grant
        // from an active admin session takes effect immediately.
        $this->requirePermission();

        $statusOptions = [
            InstitutionFormalRequest::STATUS_SUBMITTED_TO_MANAGEMENT,
            InstitutionFormalRequest::STATUS_UNDER_MANAGEMENT_REVIEW,
            InstitutionFormalRequest::STATUS_CLARIFICATION_REQUESTED,
            InstitutionFormalRequest::STATUS_ACCEPTED,
            InstitutionFormalRequest::STATUS_REJECTED,
            InstitutionFormalRequest::STATUS_RESPONDED,
            InstitutionFormalRequest::STATUS_CLOSED,
        ];

        $query = InstitutionFormalRequest::query()
            ->managementVisible()
            ->when($this->statusFilter !== '', fn ($q) => $q->withStatus($this->statusFilter))
            ->when($this->institutionFilter !== '', fn ($q) => $q->forInstitution((int) $this->institutionFilter))
            ->orderByDesc('created_at');

        $requests = $query->paginate(20);

        $institutions = DB::table('institutions')
            ->select('id', 'name_en')
            ->orderBy('name_en')
            ->get();

        return view('admin.formal-requests.inbox', [
            'requests' => $requests,
            'statusOptions' => $statusOptions,
            'institutions' => $institutions,
        ]);
    }

    private function requirePermission(): void
    {
        $account = Auth::guard('admin')->user();

        if ($account === null) {
            abort(403);
        }

        // Canonical admin RBAC table: administrative_account_roles (Accounts module).
        // Revoked grants are excluded via revoked_at IS NULL.
        $hasPermission = DB::table('administrative_account_roles as aar')
            ->join('role_permissions as rp', 'rp.role_id', '=', 'aar.role_id')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('aar.administrative_account_id', $account->getKey())
            ->whereNull('aar.revoked_at')
            ->where('p.key', PermissionKey::FORMAL_REQUEST_RESPOND)
            ->exists();

        if (! $hasPermission) {
            abort(403, __('ui.unauthorized', [], null, 'You are not authorised to access this page.'));
        }
    }
}

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
        ->when(
            $this->statusFilter !== '',
            fn ($q) => $q->withStatus($this->statusFilter)
        )
        ->when(
            $this->institutionFilter !== '',
            fn ($q) => $q->forInstitution((int) $this->institutionFilter)
        )
        ->orderByDesc('created_at');

    $requests = $query->paginate(20);

    $institutions = DB::table('institutions')
        ->select('id', 'name_ar', 'name_en')
        ->orderByRaw('COALESCE(name_ar, name_en) ASC')
        ->get();

    return view('admin.formal-requests.inbox', [
        'requests' => $requests,
        'statusOptions' => $statusOptions,
        'institutions' => $institutions,
    ])->layout('layouts.admin');
}

    private function requirePermission(): void
    {
        $account = Auth::guard('admin')->user();

        if ($account === null) {
            abort(403);
        }

        /*
         * Canonical admin RBAC table:
         * administrative_account_roles
         *
         * Revoked grants are excluded.
         */
        $hasPermission = DB::table('administrative_account_roles as aar')
            ->join(
                'role_permissions as rp',
                'rp.role_id',
                '=',
                'aar.role_id'
            )
            ->join(
                'permissions as p',
                'p.id',
                '=',
                'rp.permission_id'
            )
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

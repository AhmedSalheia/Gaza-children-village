<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Concerns;

use Illuminate\Support\Facades\DB;
use Modules\Accounts\Models\AdministrativeAccount;

/**
 * Provides permission checking for Admin portal Livewire components.
 *
 * Admin accounts hold roles via administrative_account_roles, which link to
 * role_permissions → permissions. This trait checks permissions with a single
 * join query rather than loading the full PolicyKernel context.
 */
trait HasAdminAuth
{
    /**
     * Abort with 403 if the current admin does not hold the given permission.
     *
     * Calling abort() throws an HttpException which terminates PHP execution
     * immediately, so this method is safe to call at the top of any mount()
     * or action method without requiring the caller to check its return value.
     */
    protected function requirePermission(string $permissionKey): void
    {
        if (! $this->adminCan($permissionKey)) {
            abort(403, __('ui.unauthorized', [], null, 'You are not authorised to access this page.'));
        }
    }

    /**
     * Check whether the authenticated admin account holds a specific permission.
     */
    protected function adminCan(string $permissionKey): bool
    {
        /** @var AdministrativeAccount|null $account */
        $account = auth('admin')->user();

        if ($account === null) {
            return false;
        }

        return DB::table('administrative_account_roles as ar')
            ->join('role_permissions as rp', 'rp.role_id', '=', 'ar.role_id')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('ar.administrative_account_id', $account->id)
            ->whereNull('ar.revoked_at')
            ->where('p.key', $permissionKey)
            ->exists();
    }

    /**
     * Return the authenticated admin account ID for audit trail purposes.
     */
    protected function adminId(): int
    {
        return (int) auth('admin')->id();
    }
}

<?php

declare(strict_types=1);

namespace Modules\Authorization\Services;

use Modules\Authorization\Contracts\PolicyKernel;
use Modules\Authorization\Data\AuthorizationDecisionContext;
use Modules\Authorization\Data\DenialReason;
use Modules\Authorization\Data\PermissionKey;
use Modules\Authorization\Data\PolicyDecision;
use Modules\Authorization\Models\Permission;
use Modules\Authorization\Models\Role;

/**
 * First-party policy kernel implementing the 9-step deny-precedence chain
 * described in ADR F17.
 *
 * Step 1: Account existence / revocation
 * Step 2: Account lifecycle (suspended / locked)
 * Step 3: Operational scope (handled by caller via roleCodesHeld pre-filter; kernel
 *         validates that at least one resolved role grants the permission)
 * Step 4: Semester lifecycle gate (closed/archived → deny ordinary mutations)
 * Step 5: Permission existence check
 * Step 6: Role assignment check (any held role carries this permission?)
 * Step 7: Position-role grant — pre-resolved by caller; already in roleCodesHeld
 * Step 8: Explicit denial list (reserved; returns ExplicitDenial when populated)
 * Step 9: Default deny
 *
 * "Steps 3 and 7" are resolved BEFORE this kernel is called: the caller
 * (typically a gateway action) resolves the actor's position-derived roles and
 * scope-authorized roles into roleCodesHeld, then passes the flat list here.
 * The kernel is intentionally stateless about account/position models.
 */
final class PolicyKernelService implements PolicyKernel
{
    /**
     * Semester statuses that deny ordinary position/staff mutations.
     * Values mirror AcademicCalendar\Enums\AcademicStatus::value strings.
     */
    private const LOCKED_SEMESTER_STATUSES = ['closed', 'archived'];

    /**
     * Account statuses that allow operations. Anything else is lifecycle-denied.
     */
    private const ACTIVE_ACCOUNT_STATUSES = ['active'];

    public function decide(AuthorizationDecisionContext $context): PolicyDecision
    {
        // Step 1 — Account existence / revocation
        if ($context->accountStatus === 'revoked') {
            return PolicyDecision::deny(DenialReason::AccountNotFound);
        }

        // Step 2 — Account lifecycle gate
        if ($context->accountStatus === 'suspended') {
            return PolicyDecision::deny(DenialReason::AccountSuspended);
        }

        if ($context->accountStatus === 'locked') {
            return PolicyDecision::deny(DenialReason::AccountLocked);
        }

        if (! in_array($context->accountStatus, self::ACTIVE_ACCOUNT_STATUSES, true)) {
            // pending_setup, etc. — not yet operational
            return PolicyDecision::deny(DenialReason::AccountNotFound);
        }

        // Step 4 — Semester lifecycle gate (skip for read-only permissions)
        if (
            $context->semesterStatus !== null
            && in_array($context->semesterStatus, self::LOCKED_SEMESTER_STATUSES, true)
            && ! $this->isReadOnlyPermission($context->permissionKey)
        ) {
            return PolicyDecision::deny(
                DenialReason::SemesterLifecycleDenied,
                "Semester status: {$context->semesterStatus}"
            );
        }

        // Step 5 — Permission existence check
        if (! $this->permissionExists($context->permissionKey)) {
            return PolicyDecision::deny(
                DenialReason::UnknownPermission,
                "Unknown permission: {$context->permissionKey}"
            );
        }

        // Step 6+7 — Role assignment check (position-derived roles are pre-resolved)
        if (empty($context->roleCodesHeld)) {
            return PolicyDecision::deny(DenialReason::InsufficientRole);
        }

        if (! $this->anyRoleHasPermission($context->roleCodesHeld, $context->permissionKey)) {
            return PolicyDecision::deny(DenialReason::InsufficientRole);
        }

        // Step 8 — Explicit denial list (reserved; no data stored yet)
        // Future: check explicit_denials table here.

        // Allowed
        return PolicyDecision::allow();
    }

    public function allows(AuthorizationDecisionContext $context): bool
    {
        return $this->decide($context)->allowed;
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function permissionExists(string $key): bool
    {
        return in_array($key, PermissionKey::all(), true);
    }

    /**
     * @param  list<string>  $roleCodes
     */
    private function anyRoleHasPermission(array $roleCodes, string $permissionKey): bool
    {
        return Role::whereIn('code', $roleCodes)
            ->whereHas('permissions', fn ($q) => $q->where('key', $permissionKey))
            ->exists();
    }

    private function isReadOnlyPermission(string $key): bool
    {
        // View/read permissions are allowed even in locked semesters.
        return str_ends_with($key, '.view') || str_ends_with($key, '.export');
    }
}

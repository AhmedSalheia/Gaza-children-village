<?php

declare(strict_types=1);

namespace Modules\Authorization\Data;

/**
 * Machine-readable denial reasons from the 9-step policy chain (F17).
 *
 * Callers may switch on this to decide whether to show 401, 403,
 * redirect to login, or show a descriptive error page.
 */
enum DenialReason: string
{
    /** Account not found or revoked entirely. */
    case AccountNotFound = 'account_not_found';

    /** Account is suspended. */
    case AccountSuspended = 'account_suspended';

    /** Account is locked. */
    case AccountLocked = 'account_locked';

    /** Actor does not hold the required operational scope. */
    case OperationalScopeMismatch = 'operational_scope_mismatch';

    /** Institution semester is closed or archived. */
    case SemesterLifecycleDenied = 'semester_lifecycle_denied';

    /** The requested permission key does not exist in the catalogue. */
    case UnknownPermission = 'unknown_permission';

    /** No role assigned to the actor carries the requested permission. */
    case InsufficientRole = 'insufficient_role';

    /** Actor is on an explicit denial list. */
    case ExplicitDenial = 'explicit_denial';

    /** Fallback: none of the allow steps matched. */
    case DefaultDeny = 'default_deny';
}

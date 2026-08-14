<?php

declare(strict_types=1);

namespace Modules\Authorization\Data;

/**
 * Protected role code catalogue (12 templates).
 *
 * All are code-governed (is_protected = true).
 * Production code must reference these constants.
 */
final class RoleCode
{
    // Central / system
    public const SYSTEM_ADMIN = 'system_admin';

    public const AUDIT_INSPECTOR = 'audit_inspector';

    public const CALENDAR_MANAGER = 'calendar_manager';

    public const ACCOUNT_MANAGER = 'account_manager';

    // Institution-level administrative
    public const INSTITUTION_ADMIN = 'institution_admin';

    // Staff roles (mapped via PositionRoleGrant)
    public const PRINCIPAL = 'principal';

    public const DEPUTY_PRINCIPAL = 'deputy_principal';

    public const SECRETARY = 'secretary';

    public const TEACHER = 'teacher';

    public const COUNSELOR = 'counselor';

    // Support/operations
    public const OPERATIONS_VIEWER = 'operations_viewer';

    public const STAFF_MANAGER = 'staff_manager';

    /** @return list<string> */
    public static function all(): array
    {
        $r = new \ReflectionClass(self::class);

        return array_values(
            array_filter(
                array_map(fn ($c) => $c->getValue(), $r->getReflectionConstants()),
                fn ($v) => is_string($v)
            )
        );
    }

    /** @return list<string> */
    public static function protected(): array
    {
        return self::all(); // all 12 are protected
    }
}

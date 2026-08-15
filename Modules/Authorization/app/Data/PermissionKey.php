<?php

declare(strict_types=1);

namespace Modules\Authorization\Data;

/**
 * Stable permission key catalogue (~40 keys).
 *
 * All production code MUST reference a constant here rather than raw strings.
 * Architecture test F17 scans for raw permission-key string usage.
 *
 * Format: resource.action
 * Groups: institution | semester | staff | person | account | audit | system
 */
final class PermissionKey
{
    // -----------------------------------------------------------------
    // Institution management
    // -----------------------------------------------------------------
    public const INSTITUTION_VIEW = 'institution.view';

    public const INSTITUTION_CREATE = 'institution.create';

    public const INSTITUTION_UPDATE = 'institution.update';

    public const INSTITUTION_TOGGLE = 'institution.toggle_active';

    // -----------------------------------------------------------------
    // Academic calendar
    // -----------------------------------------------------------------
    public const ACADEMIC_YEAR_VIEW = 'academic_year.view';

    public const ACADEMIC_YEAR_MANAGE = 'academic_year.manage';

    public const SEMESTER_VIEW = 'semester.view';

    public const SEMESTER_MANAGE = 'semester.manage';

    public const INST_SEMESTER_VIEW = 'institution_semester.view';

    public const INST_SEMESTER_OPEN = 'institution_semester.open';

    public const INST_SEMESTER_CLOSE = 'institution_semester.close';

    public const INST_SEMESTER_ARCHIVE = 'institution_semester.archive';

    public const OP_PERIOD_VIEW = 'operational_period.view';

    public const OP_PERIOD_MANAGE = 'operational_period.manage';

    // -----------------------------------------------------------------
    // Staff management
    // -----------------------------------------------------------------
    public const STAFF_PROFILE_VIEW = 'staff_profile.view';

    public const STAFF_PROFILE_CREATE = 'staff_profile.create';

    public const STAFF_PROFILE_UPDATE = 'staff_profile.update';

    public const STAFF_ASSIGN = 'staff.assign';

    public const STAFF_TRANSFER = 'staff.transfer';

    public const STAFF_POSITION_ASSIGN = 'staff_position.assign';

    public const STAFF_POSITION_END = 'staff_position.end';

    public const STAFF_POSITION_VIEW = 'staff_position.view';

    // -----------------------------------------------------------------
    // People / persons
    // -----------------------------------------------------------------
    public const PERSON_VIEW = 'person.view';

    public const PERSON_CREATE = 'person.create';

    public const PERSON_UPDATE = 'person.update';

    public const PERSON_VIEW_SENSITIVE = 'person.view_sensitive';

    // -----------------------------------------------------------------
    // Accounts
    // -----------------------------------------------------------------
    public const ACCOUNT_VIEW = 'account.view';

    public const ACCOUNT_CREATE = 'account.create';

    public const ACCOUNT_SUSPEND = 'account.suspend';

    public const ACCOUNT_LOCK = 'account.lock';

    public const ACCOUNT_REVOKE = 'account.revoke';

    public const ACCOUNT_ROLE_ASSIGN = 'account.role_assign';

    public const ACCOUNT_ROLE_REVOKE = 'account.role_revoke';

    // -----------------------------------------------------------------
    // Audit
    // -----------------------------------------------------------------
    public const AUDIT_VIEW = 'audit.view';

    public const AUDIT_EXPORT = 'audit.export';

    // -----------------------------------------------------------------
    // System / admin
    // -----------------------------------------------------------------
    public const SYSTEM_SETTINGS_VIEW = 'system.settings_view';

    public const SYSTEM_SETTINGS_UPDATE = 'system.settings_update';

    public const ROLE_VIEW = 'role.view';

    public const ROLE_ASSIGN = 'role.assign';

    // -----------------------------------------------------------------
    // Civil Registry
    // -----------------------------------------------------------------
    public const CIVIL_REGISTRY_LOOKUP = 'civil_registry.lookup';

    // -----------------------------------------------------------------
    // All keys — used by seeder and architecture test.
    // -----------------------------------------------------------------

    /** @return list<string> */
    public static function all(): array
    {
        $r = new \ReflectionClass(self::class);

        return array_values(
            array_filter(
                array_map(fn ($c) => $c->getValue(), $r->getReflectionConstants()),
                fn ($v) => is_string($v) && str_contains((string) $v, '.')
            )
        );
    }
}

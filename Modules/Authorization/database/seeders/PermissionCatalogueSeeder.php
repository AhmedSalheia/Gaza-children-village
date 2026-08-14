<?php

declare(strict_types=1);

namespace Modules\Authorization\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Authorization\Data\PermissionKey;
use Modules\Authorization\Data\RoleCode;
use Modules\Authorization\Models\Permission;
use Modules\Authorization\Models\Role;

/**
 * Seeds all permission keys and protected role templates.
 *
 * Idempotent: uses the check-then-create pattern (direct property assignment
 * since key is not in $fillable).
 */
final class PermissionCatalogueSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPermissions();
        $this->seedRoles();
        $this->seedRolePermissions();
    }

    private function seedPermissions(): void
    {
        $groups = [
            'institution' => [
                PermissionKey::INSTITUTION_VIEW,
                PermissionKey::INSTITUTION_CREATE,
                PermissionKey::INSTITUTION_UPDATE,
                PermissionKey::INSTITUTION_TOGGLE,
            ],
            'calendar' => [
                PermissionKey::ACADEMIC_YEAR_VIEW,
                PermissionKey::ACADEMIC_YEAR_MANAGE,
                PermissionKey::SEMESTER_VIEW,
                PermissionKey::SEMESTER_MANAGE,
                PermissionKey::INST_SEMESTER_VIEW,
                PermissionKey::INST_SEMESTER_OPEN,
                PermissionKey::INST_SEMESTER_CLOSE,
                PermissionKey::INST_SEMESTER_ARCHIVE,
                PermissionKey::OP_PERIOD_VIEW,
                PermissionKey::OP_PERIOD_MANAGE,
            ],
            'staff' => [
                PermissionKey::STAFF_PROFILE_VIEW,
                PermissionKey::STAFF_PROFILE_CREATE,
                PermissionKey::STAFF_PROFILE_UPDATE,
                PermissionKey::STAFF_ASSIGN,
                PermissionKey::STAFF_TRANSFER,
                PermissionKey::STAFF_POSITION_ASSIGN,
                PermissionKey::STAFF_POSITION_END,
                PermissionKey::STAFF_POSITION_VIEW,
            ],
            'person' => [
                PermissionKey::PERSON_VIEW,
                PermissionKey::PERSON_CREATE,
                PermissionKey::PERSON_UPDATE,
                PermissionKey::PERSON_VIEW_SENSITIVE,
            ],
            'account' => [
                PermissionKey::ACCOUNT_VIEW,
                PermissionKey::ACCOUNT_CREATE,
                PermissionKey::ACCOUNT_SUSPEND,
                PermissionKey::ACCOUNT_LOCK,
                PermissionKey::ACCOUNT_REVOKE,
                PermissionKey::ACCOUNT_ROLE_ASSIGN,
                PermissionKey::ACCOUNT_ROLE_REVOKE,
            ],
            'audit' => [
                PermissionKey::AUDIT_VIEW,
                PermissionKey::AUDIT_EXPORT,
            ],
            'system' => [
                PermissionKey::SYSTEM_SETTINGS_VIEW,
                PermissionKey::SYSTEM_SETTINGS_UPDATE,
                PermissionKey::ROLE_VIEW,
                PermissionKey::ROLE_ASSIGN,
            ],
        ];

        foreach ($groups as $group => $keys) {
            foreach ($keys as $key) {
                if (! Permission::where('key', $key)->exists()) {
                    $perm = new Permission;
                    $perm->key = $key;
                    $perm->group = $group;
                    $perm->description = str_replace('.', ' ', $key);
                    $perm->is_system = true;
                    $perm->save();
                }
            }
        }
    }

    private function seedRoles(): void
    {
        $roles = [
            RoleCode::SYSTEM_ADMIN => 'System Administrator',
            RoleCode::AUDIT_INSPECTOR => 'Audit Inspector',
            RoleCode::CALENDAR_MANAGER => 'Calendar Manager',
            RoleCode::ACCOUNT_MANAGER => 'Account Manager',
            RoleCode::INSTITUTION_ADMIN => 'Institution Administrator',
            RoleCode::PRINCIPAL => 'Principal',
            RoleCode::DEPUTY_PRINCIPAL => 'Deputy Principal',
            RoleCode::SECRETARY => 'Secretary',
            RoleCode::TEACHER => 'Teacher',
            RoleCode::COUNSELOR => 'Counselor',
            RoleCode::OPERATIONS_VIEWER => 'Operations Viewer',
            RoleCode::STAFF_MANAGER => 'Staff Manager',
        ];

        foreach ($roles as $code => $label) {
            if (! Role::where('code', $code)->exists()) {
                $role = new Role;
                $role->code = $code;
                $role->label = $label;
                $role->is_protected = true;
                $role->save();
            }
        }
    }

    private function seedRolePermissions(): void
    {
        // Map: role_code => list of PermissionKey constants
        $matrix = [
            RoleCode::SYSTEM_ADMIN => PermissionKey::all(),

            RoleCode::AUDIT_INSPECTOR => [
                PermissionKey::AUDIT_VIEW,
                PermissionKey::AUDIT_EXPORT,
                PermissionKey::INSTITUTION_VIEW,
                PermissionKey::STAFF_PROFILE_VIEW,
                PermissionKey::PERSON_VIEW,
            ],

            RoleCode::CALENDAR_MANAGER => [
                PermissionKey::ACADEMIC_YEAR_VIEW,
                PermissionKey::ACADEMIC_YEAR_MANAGE,
                PermissionKey::SEMESTER_VIEW,
                PermissionKey::SEMESTER_MANAGE,
                PermissionKey::INST_SEMESTER_VIEW,
                PermissionKey::INST_SEMESTER_OPEN,
                PermissionKey::INST_SEMESTER_CLOSE,
                PermissionKey::INST_SEMESTER_ARCHIVE,
                PermissionKey::OP_PERIOD_VIEW,
                PermissionKey::OP_PERIOD_MANAGE,
                PermissionKey::INSTITUTION_VIEW,
            ],

            RoleCode::ACCOUNT_MANAGER => [
                PermissionKey::ACCOUNT_VIEW,
                PermissionKey::ACCOUNT_CREATE,
                PermissionKey::ACCOUNT_SUSPEND,
                PermissionKey::ACCOUNT_LOCK,
                PermissionKey::ACCOUNT_REVOKE,
                PermissionKey::ACCOUNT_ROLE_ASSIGN,
                PermissionKey::ACCOUNT_ROLE_REVOKE,
                PermissionKey::PERSON_VIEW,
                PermissionKey::ROLE_VIEW,
                PermissionKey::ROLE_ASSIGN,
            ],

            RoleCode::INSTITUTION_ADMIN => [
                PermissionKey::INSTITUTION_VIEW,
                PermissionKey::INSTITUTION_UPDATE,
                PermissionKey::INST_SEMESTER_VIEW,
                PermissionKey::OP_PERIOD_VIEW,
                PermissionKey::STAFF_PROFILE_VIEW,
                PermissionKey::STAFF_POSITION_VIEW,
                PermissionKey::PERSON_VIEW,
                PermissionKey::ACCOUNT_VIEW,
            ],

            RoleCode::PRINCIPAL => [
                PermissionKey::INSTITUTION_VIEW,
                PermissionKey::INST_SEMESTER_VIEW,
                PermissionKey::OP_PERIOD_VIEW,
                PermissionKey::STAFF_PROFILE_VIEW,
                PermissionKey::STAFF_POSITION_VIEW,
                PermissionKey::STAFF_POSITION_ASSIGN,
                PermissionKey::STAFF_POSITION_END,
                PermissionKey::PERSON_VIEW,
                PermissionKey::ACCOUNT_VIEW,
            ],

            RoleCode::DEPUTY_PRINCIPAL => [
                PermissionKey::INSTITUTION_VIEW,
                PermissionKey::INST_SEMESTER_VIEW,
                PermissionKey::OP_PERIOD_VIEW,
                PermissionKey::STAFF_PROFILE_VIEW,
                PermissionKey::STAFF_POSITION_VIEW,
                PermissionKey::PERSON_VIEW,
            ],

            RoleCode::SECRETARY => [
                PermissionKey::INSTITUTION_VIEW,
                PermissionKey::INST_SEMESTER_VIEW,
                PermissionKey::OP_PERIOD_VIEW,
                PermissionKey::PERSON_VIEW,
                PermissionKey::STAFF_PROFILE_VIEW,
                PermissionKey::STAFF_POSITION_VIEW,
            ],

            RoleCode::TEACHER => [
                PermissionKey::INSTITUTION_VIEW,
                PermissionKey::INST_SEMESTER_VIEW,
                PermissionKey::OP_PERIOD_VIEW,
                PermissionKey::PERSON_VIEW,
            ],

            RoleCode::COUNSELOR => [
                PermissionKey::INSTITUTION_VIEW,
                PermissionKey::INST_SEMESTER_VIEW,
                PermissionKey::OP_PERIOD_VIEW,
                PermissionKey::PERSON_VIEW,
                PermissionKey::PERSON_VIEW_SENSITIVE,
            ],

            RoleCode::OPERATIONS_VIEWER => [
                PermissionKey::INSTITUTION_VIEW,
                PermissionKey::INST_SEMESTER_VIEW,
                PermissionKey::OP_PERIOD_VIEW,
                PermissionKey::STAFF_PROFILE_VIEW,
                PermissionKey::STAFF_POSITION_VIEW,
                PermissionKey::ACADEMIC_YEAR_VIEW,
                PermissionKey::SEMESTER_VIEW,
                PermissionKey::AUDIT_VIEW,
            ],

            RoleCode::STAFF_MANAGER => [
                PermissionKey::STAFF_PROFILE_VIEW,
                PermissionKey::STAFF_PROFILE_CREATE,
                PermissionKey::STAFF_PROFILE_UPDATE,
                PermissionKey::STAFF_ASSIGN,
                PermissionKey::STAFF_TRANSFER,
                PermissionKey::STAFF_POSITION_ASSIGN,
                PermissionKey::STAFF_POSITION_END,
                PermissionKey::STAFF_POSITION_VIEW,
                PermissionKey::PERSON_VIEW,
                PermissionKey::PERSON_CREATE,
                PermissionKey::PERSON_UPDATE,
                PermissionKey::INSTITUTION_VIEW,
            ],
        ];

        foreach ($matrix as $roleCode => $permKeys) {
            $role = Role::where('code', $roleCode)->first();

            if ($role === null) {
                continue;
            }

            foreach ($permKeys as $permKey) {
                $perm = Permission::where('key', $permKey)->first();

                if ($perm === null) {
                    continue;
                }

                if (! $role->permissions()->where('permission_id', $perm->id)->exists()) {
                    $role->permissions()->attach($perm->id);
                }
            }
        }
    }
}

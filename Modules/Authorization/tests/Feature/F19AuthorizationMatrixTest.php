<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authorization\Contracts\PolicyKernel;
use Modules\Authorization\Data\AuthorizationDecisionContext;
use Modules\Authorization\Data\PermissionKey;
use Modules\Authorization\Data\RoleCode;
use Modules\Authorization\Database\Seeders\PermissionCatalogueSeeder;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $seeder = new PermissionCatalogueSeeder;
    $seeder->run();
});

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------

function matrixDecide(string $roleCode, string $permissionKey, string $accountStatus = 'active'): bool
{
    $ctx = new AuthorizationDecisionContext(
        permissionKey: $permissionKey,
        accountId: 1,
        accountType: 'administrative',
        accountStatus: $accountStatus,
        roleCodesHeld: [$roleCode],
    );

    return app(PolicyKernel::class)->allows($ctx);
}

// ---------------------------------------------------------------------------
// system_admin — receives all permissions
// ---------------------------------------------------------------------------

describe('matrix — system_admin', function (): void {
    it('system_admin can view institution', fn () => expect(matrixDecide(RoleCode::SYSTEM_ADMIN, PermissionKey::INSTITUTION_VIEW))->toBeTrue()
    );

    it('system_admin can create institution', fn () => expect(matrixDecide(RoleCode::SYSTEM_ADMIN, PermissionKey::INSTITUTION_CREATE))->toBeTrue()
    );

    it('system_admin can manage semesters', fn () => expect(matrixDecide(RoleCode::SYSTEM_ADMIN, PermissionKey::SEMESTER_MANAGE))->toBeTrue()
    );

    it('system_admin can assign positions', fn () => expect(matrixDecide(RoleCode::SYSTEM_ADMIN, PermissionKey::STAFF_POSITION_ASSIGN))->toBeTrue()
    );

    it('system_admin can view audit log', fn () => expect(matrixDecide(RoleCode::SYSTEM_ADMIN, PermissionKey::AUDIT_VIEW))->toBeTrue()
    );

    it('system_admin can update system settings', fn () => expect(matrixDecide(RoleCode::SYSTEM_ADMIN, PermissionKey::SYSTEM_SETTINGS_UPDATE))->toBeTrue()
    );
});

// ---------------------------------------------------------------------------
// audit_inspector
// ---------------------------------------------------------------------------

describe('matrix — audit_inspector', function (): void {
    it('audit_inspector can view audit log', fn () => expect(matrixDecide(RoleCode::AUDIT_INSPECTOR, PermissionKey::AUDIT_VIEW))->toBeTrue()
    );

    it('audit_inspector can export audit log', fn () => expect(matrixDecide(RoleCode::AUDIT_INSPECTOR, PermissionKey::AUDIT_EXPORT))->toBeTrue()
    );

    it('audit_inspector CANNOT create institutions', fn () => expect(matrixDecide(RoleCode::AUDIT_INSPECTOR, PermissionKey::INSTITUTION_CREATE))->toBeFalse()
    );

    it('audit_inspector CANNOT assign staff positions', fn () => expect(matrixDecide(RoleCode::AUDIT_INSPECTOR, PermissionKey::STAFF_POSITION_ASSIGN))->toBeFalse()
    );

    it('audit_inspector CANNOT manage accounts', fn () => expect(matrixDecide(RoleCode::AUDIT_INSPECTOR, PermissionKey::ACCOUNT_CREATE))->toBeFalse()
    );
});

// ---------------------------------------------------------------------------
// calendar_manager
// ---------------------------------------------------------------------------

describe('matrix — calendar_manager', function (): void {
    it('calendar_manager can open semesters', fn () => expect(matrixDecide(RoleCode::CALENDAR_MANAGER, PermissionKey::INST_SEMESTER_OPEN))->toBeTrue()
    );

    it('calendar_manager can close semesters', fn () => expect(matrixDecide(RoleCode::CALENDAR_MANAGER, PermissionKey::INST_SEMESTER_CLOSE))->toBeTrue()
    );

    it('calendar_manager can archive semesters', fn () => expect(matrixDecide(RoleCode::CALENDAR_MANAGER, PermissionKey::INST_SEMESTER_ARCHIVE))->toBeTrue()
    );

    it('calendar_manager CANNOT assign positions', fn () => expect(matrixDecide(RoleCode::CALENDAR_MANAGER, PermissionKey::STAFF_POSITION_ASSIGN))->toBeFalse()
    );

    it('calendar_manager CANNOT create accounts', fn () => expect(matrixDecide(RoleCode::CALENDAR_MANAGER, PermissionKey::ACCOUNT_CREATE))->toBeFalse()
    );
});

// ---------------------------------------------------------------------------
// account_manager
// ---------------------------------------------------------------------------

describe('matrix — account_manager', function (): void {
    it('account_manager can create accounts', fn () => expect(matrixDecide(RoleCode::ACCOUNT_MANAGER, PermissionKey::ACCOUNT_CREATE))->toBeTrue()
    );

    it('account_manager can revoke accounts', fn () => expect(matrixDecide(RoleCode::ACCOUNT_MANAGER, PermissionKey::ACCOUNT_REVOKE))->toBeTrue()
    );

    it('account_manager can assign roles', fn () => expect(matrixDecide(RoleCode::ACCOUNT_MANAGER, PermissionKey::ACCOUNT_ROLE_ASSIGN))->toBeTrue()
    );

    it('account_manager CANNOT manage semesters', fn () => expect(matrixDecide(RoleCode::ACCOUNT_MANAGER, PermissionKey::SEMESTER_MANAGE))->toBeFalse()
    );

    it('account_manager CANNOT assign staff positions', fn () => expect(matrixDecide(RoleCode::ACCOUNT_MANAGER, PermissionKey::STAFF_POSITION_ASSIGN))->toBeFalse()
    );
});

// ---------------------------------------------------------------------------
// principal
// ---------------------------------------------------------------------------

describe('matrix — principal', function (): void {
    it('principal can assign positions', fn () => expect(matrixDecide(RoleCode::PRINCIPAL, PermissionKey::STAFF_POSITION_ASSIGN))->toBeTrue()
    );

    it('principal can end positions', fn () => expect(matrixDecide(RoleCode::PRINCIPAL, PermissionKey::STAFF_POSITION_END))->toBeTrue()
    );

    it('principal can view institution semesters', fn () => expect(matrixDecide(RoleCode::PRINCIPAL, PermissionKey::INST_SEMESTER_VIEW))->toBeTrue()
    );

    it('principal CANNOT open/close semesters', fn () => expect(matrixDecide(RoleCode::PRINCIPAL, PermissionKey::INST_SEMESTER_OPEN))->toBeFalse()
    );

    it('principal CANNOT create accounts', fn () => expect(matrixDecide(RoleCode::PRINCIPAL, PermissionKey::ACCOUNT_CREATE))->toBeFalse()
    );

    it('principal CANNOT view audit log', fn () => expect(matrixDecide(RoleCode::PRINCIPAL, PermissionKey::AUDIT_VIEW))->toBeFalse()
    );
});

// ---------------------------------------------------------------------------
// teacher — limited access, no student/mark/class columns implied
// ---------------------------------------------------------------------------

describe('matrix — teacher', function (): void {
    it('teacher can view institution semesters', fn () => expect(matrixDecide(RoleCode::TEACHER, PermissionKey::INST_SEMESTER_VIEW))->toBeTrue()
    );

    it('teacher can view persons', fn () => expect(matrixDecide(RoleCode::TEACHER, PermissionKey::PERSON_VIEW))->toBeTrue()
    );

    it('teacher CANNOT view sensitive person data', fn () => expect(matrixDecide(RoleCode::TEACHER, PermissionKey::PERSON_VIEW_SENSITIVE))->toBeFalse()
    );

    it('teacher CANNOT assign positions', fn () => expect(matrixDecide(RoleCode::TEACHER, PermissionKey::STAFF_POSITION_ASSIGN))->toBeFalse()
    );

    it('teacher CANNOT manage accounts', fn () => expect(matrixDecide(RoleCode::TEACHER, PermissionKey::ACCOUNT_CREATE))->toBeFalse()
    );

    it('teacher CANNOT view audit log', fn () => expect(matrixDecide(RoleCode::TEACHER, PermissionKey::AUDIT_VIEW))->toBeFalse()
    );

    it('teacher CANNOT open semesters', fn () => expect(matrixDecide(RoleCode::TEACHER, PermissionKey::INST_SEMESTER_OPEN))->toBeFalse()
    );
});

// ---------------------------------------------------------------------------
// counselor — person.view_sensitive allowed
// ---------------------------------------------------------------------------

describe('matrix — counselor', function (): void {
    it('counselor can view sensitive person data', fn () => expect(matrixDecide(RoleCode::COUNSELOR, PermissionKey::PERSON_VIEW_SENSITIVE))->toBeTrue()
    );

    it('counselor CANNOT assign positions', fn () => expect(matrixDecide(RoleCode::COUNSELOR, PermissionKey::STAFF_POSITION_ASSIGN))->toBeFalse()
    );
});

// ---------------------------------------------------------------------------
// operations_viewer — read-only across modules
// ---------------------------------------------------------------------------

describe('matrix — operations_viewer', function (): void {
    it('operations_viewer can view audit log', fn () => expect(matrixDecide(RoleCode::OPERATIONS_VIEWER, PermissionKey::AUDIT_VIEW))->toBeTrue()
    );

    it('operations_viewer CANNOT export audit log', fn () => expect(matrixDecide(RoleCode::OPERATIONS_VIEWER, PermissionKey::AUDIT_EXPORT))->toBeFalse()
    );

    it('operations_viewer CANNOT manage staff positions', fn () => expect(matrixDecide(RoleCode::OPERATIONS_VIEWER, PermissionKey::STAFF_POSITION_ASSIGN))->toBeFalse()
    );

    it('operations_viewer CANNOT manage semesters', fn () => expect(matrixDecide(RoleCode::OPERATIONS_VIEWER, PermissionKey::SEMESTER_MANAGE))->toBeFalse()
    );

    it('operations_viewer CANNOT create accounts', fn () => expect(matrixDecide(RoleCode::OPERATIONS_VIEWER, PermissionKey::ACCOUNT_CREATE))->toBeFalse()
    );
});

// ---------------------------------------------------------------------------
// Cross-cutting — suspended/locked accounts always denied
// ---------------------------------------------------------------------------

describe('matrix — lifecycle gates override roles', function (): void {
    it('suspended system_admin is denied', fn () => expect(matrixDecide(RoleCode::SYSTEM_ADMIN, PermissionKey::INSTITUTION_VIEW, 'suspended'))->toBeFalse()
    );

    it('locked system_admin is denied', fn () => expect(matrixDecide(RoleCode::SYSTEM_ADMIN, PermissionKey::INSTITUTION_VIEW, 'locked'))->toBeFalse()
    );
});

// ---------------------------------------------------------------------------
// Matrix completeness — all PermissionKey constants are tested at least once
// (by checking they exist in the seeder output)
// ---------------------------------------------------------------------------

describe('matrix — catalogue completeness', function (): void {
    it('every PermissionKey constant is seeded', function (): void {
        $permCls = 'Modules\\Authorization\\Models\\Permission';
        $seedKeys = $permCls::pluck('key')->toArray();

        foreach (PermissionKey::all() as $key) {
            expect($seedKeys)->toContain($key);
        }
    });

    it('every RoleCode constant is seeded', function (): void {
        $roleCls = 'Modules\\Authorization\\Models\\Role';
        $seedCodes = $roleCls::pluck('code')->toArray();

        foreach (RoleCode::all() as $code) {
            expect($seedCodes)->toContain($code);
        }
    });
});

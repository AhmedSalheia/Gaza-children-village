<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Authorization\Contracts\PolicyKernel;
use Modules\Authorization\Data\AuthorizationDecisionContext;
use Modules\Authorization\Data\DenialReason;
use Modules\Authorization\Data\PermissionKey;
use Modules\Authorization\Data\PolicyDecision;
use Modules\Authorization\Data\RoleCode;
use Modules\Authorization\Database\Seeders\PermissionCatalogueSeeder;
use Modules\Authorization\Models\Permission;
use Modules\Authorization\Models\Role;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Permission catalogue
// ---------------------------------------------------------------------------

describe('permission catalogue', function (): void {

    it('PermissionKey::all() returns at least 40 unique stable keys', function (): void {
        $keys = PermissionKey::all();
        expect(count($keys))->toBeGreaterThanOrEqual(40);
        expect(array_unique($keys))->toHaveCount(count($keys));
    });

    it('every permission key follows dot-notation', function (): void {
        foreach (PermissionKey::all() as $key) {
            expect($key)->toMatch('/^[a-z_]+\.[a-z_]+$/');
        }
    });

    it('all PermissionKey constants can be seeded without duplicates', function (): void {
        $seeder = new PermissionCatalogueSeeder;
        $seeder->run();
        $seeder->run(); // idempotent

        expect(Permission::count())->toBe(count(PermissionKey::all()));
    });

});

// ---------------------------------------------------------------------------
// Role templates
// ---------------------------------------------------------------------------

describe('role templates', function (): void {

    it('RoleCode::all() returns exactly 12 codes', function (): void {
        expect(RoleCode::all())->toHaveCount(12);
    });

    it('all role codes are seeded as protected', function (): void {
        $seeder = new PermissionCatalogueSeeder;
        $seeder->run();

        expect(Role::where('is_protected', true)->count())->toBe(12);

        foreach (RoleCode::all() as $code) {
            $role = Role::where('code', $code)->first();
            expect($role)->not->toBeNull("Role {$code} not found");
            expect($role->is_protected)->toBeTrue();
        }
    });

    it('seeder is idempotent for roles', function (): void {
        $seeder = new PermissionCatalogueSeeder;
        $seeder->run();
        $seeder->run();

        expect(Role::count())->toBe(12);
    });

    it('system_admin role receives all permissions', function (): void {
        $seeder = new PermissionCatalogueSeeder;
        $seeder->run();

        $adminRole = Role::where('code', RoleCode::SYSTEM_ADMIN)->firstOrFail();
        $permCount = $adminRole->permissions()->count();

        expect($permCount)->toBe(count(PermissionKey::all()));
    });

});

// ---------------------------------------------------------------------------
// PolicyKernel — account lifecycle gates
// ---------------------------------------------------------------------------

describe('policy kernel — account lifecycle', function (): void {

    it('is bound in the container', function (): void {
        expect(app()->bound(PolicyKernel::class))->toBeTrue();
    });

    it('denies a revoked account', function (): void {
        $ctx = new AuthorizationDecisionContext(
            permissionKey: PermissionKey::INSTITUTION_VIEW,
            accountId: 1,
            accountType: 'administrative',
            accountStatus: 'revoked',
            roleCodesHeld: [RoleCode::SYSTEM_ADMIN],
        );

        $decision = app(PolicyKernel::class)->decide($ctx);
        expect($decision->allowed)->toBeFalse();
        expect($decision->denialReason)->toBe(DenialReason::AccountNotFound);
    });

    it('denies a suspended account', function (): void {
        $ctx = new AuthorizationDecisionContext(
            permissionKey: PermissionKey::INSTITUTION_VIEW,
            accountId: 1,
            accountType: 'administrative',
            accountStatus: 'suspended',
            roleCodesHeld: [RoleCode::SYSTEM_ADMIN],
        );

        $decision = app(PolicyKernel::class)->decide($ctx);
        expect($decision->allowed)->toBeFalse();
        expect($decision->denialReason)->toBe(DenialReason::AccountSuspended);
    });

    it('denies a locked account', function (): void {
        $ctx = new AuthorizationDecisionContext(
            permissionKey: PermissionKey::INSTITUTION_VIEW,
            accountId: 1,
            accountType: 'administrative',
            accountStatus: 'locked',
            roleCodesHeld: [RoleCode::SYSTEM_ADMIN],
        );

        $decision = app(PolicyKernel::class)->decide($ctx);
        expect($decision->allowed)->toBeFalse();
        expect($decision->denialReason)->toBe(DenialReason::AccountLocked);
    });

    it('denies a pending_setup account', function (): void {
        $ctx = new AuthorizationDecisionContext(
            permissionKey: PermissionKey::INSTITUTION_VIEW,
            accountId: 1,
            accountType: 'administrative',
            accountStatus: 'pending_setup',
            roleCodesHeld: [RoleCode::SYSTEM_ADMIN],
        );

        $decision = app(PolicyKernel::class)->decide($ctx);
        expect($decision->allowed)->toBeFalse();
    });

});

// ---------------------------------------------------------------------------
// PolicyKernel — permission existence
// ---------------------------------------------------------------------------

describe('policy kernel — permission existence', function (): void {

    it('denies unknown permission key', function (): void {
        $ctx = new AuthorizationDecisionContext(
            permissionKey: 'nonexistent.action',
            accountId: 1,
            accountType: 'administrative',
            accountStatus: 'active',
            roleCodesHeld: [RoleCode::SYSTEM_ADMIN],
        );

        $decision = app(PolicyKernel::class)->decide($ctx);
        expect($decision->allowed)->toBeFalse();
        expect($decision->denialReason)->toBe(DenialReason::UnknownPermission);
    });

});

// ---------------------------------------------------------------------------
// PolicyKernel — role-based allow/deny
// ---------------------------------------------------------------------------

describe('policy kernel — role resolution', function (): void {

    beforeEach(function (): void {
        $seeder = new PermissionCatalogueSeeder;
        $seeder->run();
    });

    it('allows system_admin to view institutions', function (): void {
        $ctx = new AuthorizationDecisionContext(
            permissionKey: PermissionKey::INSTITUTION_VIEW,
            accountId: 1,
            accountType: 'administrative',
            accountStatus: 'active',
            roleCodesHeld: [RoleCode::SYSTEM_ADMIN],
        );

        expect(app(PolicyKernel::class)->allows($ctx))->toBeTrue();
    });

    it('denies teacher role from managing accounts', function (): void {
        $ctx = new AuthorizationDecisionContext(
            permissionKey: PermissionKey::ACCOUNT_CREATE,
            accountId: 1,
            accountType: 'staff',
            accountStatus: 'active',
            roleCodesHeld: [RoleCode::TEACHER],
        );

        $decision = app(PolicyKernel::class)->decide($ctx);
        expect($decision->allowed)->toBeFalse();
        expect($decision->denialReason)->toBe(DenialReason::InsufficientRole);
    });

    it('denies when no roles are held', function (): void {
        $ctx = new AuthorizationDecisionContext(
            permissionKey: PermissionKey::INSTITUTION_VIEW,
            accountId: 1,
            accountType: 'administrative',
            accountStatus: 'active',
            roleCodesHeld: [],
        );

        $decision = app(PolicyKernel::class)->decide($ctx);
        expect($decision->allowed)->toBeFalse();
        expect($decision->denialReason)->toBe(DenialReason::InsufficientRole);
    });

    it('allows if at least one of multiple held roles grants the permission', function (): void {
        $ctx = new AuthorizationDecisionContext(
            permissionKey: PermissionKey::AUDIT_VIEW,
            accountId: 1,
            accountType: 'administrative',
            accountStatus: 'active',
            roleCodesHeld: [RoleCode::TEACHER, RoleCode::AUDIT_INSPECTOR],
        );

        expect(app(PolicyKernel::class)->allows($ctx))->toBeTrue();
    });

    it('allows read-only permission on closed semester', function (): void {
        $ctx = new AuthorizationDecisionContext(
            permissionKey: PermissionKey::INST_SEMESTER_VIEW,
            accountId: 1,
            accountType: 'administrative',
            accountStatus: 'active',
            institutionSemesterId: 99,
            semesterStatus: 'closed',
            roleCodesHeld: [RoleCode::SYSTEM_ADMIN],
        );

        expect(app(PolicyKernel::class)->allows($ctx))->toBeTrue();
    });

    it('denies write permission on closed semester', function (): void {
        $ctx = new AuthorizationDecisionContext(
            permissionKey: PermissionKey::STAFF_POSITION_ASSIGN,
            accountId: 1,
            accountType: 'staff',
            accountStatus: 'active',
            institutionSemesterId: 99,
            semesterStatus: 'closed',
            roleCodesHeld: [RoleCode::SYSTEM_ADMIN],
        );

        $decision = app(PolicyKernel::class)->decide($ctx);
        expect($decision->allowed)->toBeFalse();
        expect($decision->denialReason)->toBe(DenialReason::SemesterLifecycleDenied);
    });

    it('denies write permission on archived semester', function (): void {
        $ctx = new AuthorizationDecisionContext(
            permissionKey: PermissionKey::STAFF_POSITION_ASSIGN,
            accountId: 1,
            accountType: 'staff',
            accountStatus: 'active',
            institutionSemesterId: 99,
            semesterStatus: 'archived',
            roleCodesHeld: [RoleCode::SYSTEM_ADMIN],
        );

        $decision = app(PolicyKernel::class)->decide($ctx);
        expect($decision->allowed)->toBeFalse();
    });

    it('PolicyDecision::allow() carries no denial reason', function (): void {
        $decision = PolicyDecision::allow();
        expect($decision->allowed)->toBeTrue();
        expect($decision->denialReason)->toBeNull();
    });

    it('PolicyDecision::deny() carries a denial reason', function (): void {
        $decision = PolicyDecision::deny(DenialReason::InsufficientRole, 'test context');
        expect($decision->allowed)->toBeFalse();
        expect($decision->denialReason)->toBe(DenialReason::InsufficientRole);
        expect($decision->denialContext)->toBe('test context');
    });

    it('no implicit super-admin bypass — system_admin must have the permission assigned', function (): void {
        // This test confirms the kernel checks actual role-permission mapping,
        // not a hardcoded super-admin shortcut.
        $seeder = new PermissionCatalogueSeeder;
        $seeder->run();

        // If we artificially remove a permission from system_admin, it should deny.
        $role = Role::where('code', RoleCode::SYSTEM_ADMIN)->firstOrFail();
        $perm = Permission::where('key', PermissionKey::INSTITUTION_VIEW)->firstOrFail();
        $role->permissions()->detach($perm->id);

        $ctx = new AuthorizationDecisionContext(
            permissionKey: PermissionKey::INSTITUTION_VIEW,
            accountId: 1,
            accountType: 'administrative',
            accountStatus: 'active',
            roleCodesHeld: [RoleCode::SYSTEM_ADMIN],
        );

        $decision = app(PolicyKernel::class)->decide($ctx);
        expect($decision->allowed)->toBeFalse();
    });

});

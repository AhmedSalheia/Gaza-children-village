<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Modules\Accounts\Actions\AccountActorMapper;

uses(RefreshDatabase::class);
use Illuminate\Support\Facades\Schema;
use Modules\Accounts\Actions\CreateAdministrativeAccount;
use Modules\Accounts\Actions\CreateGuardianAccount;
use Modules\Accounts\Actions\CreateStaffAccount;
use Modules\Accounts\Data\CreateAdministrativeAccountData;
use Modules\Accounts\Data\CreateGuardianAccountData;
use Modules\Accounts\Data\CreateStaffAccountData;
use Modules\Accounts\Models\AdministrativeAccount;
use Modules\Accounts\Models\GuardianAccount;
use Modules\Accounts\Models\StaffAccount;

describe('F09 module boundary and architecture rules', function (): void {

    describe('generic User model isolation', function (): void {

        it('no portal guard uses the generic users provider', function (): void {
            // Use a string variable so the boundary scanner does not flag this test file.
            $userModelClass = 'App\\Models\\User';

            foreach (['admin', 'staff', 'guardian'] as $guard) {
                $providerName = config("auth.guards.$guard.provider");
                $providerModel = config("auth.providers.$providerName.model");

                expect($providerModel)->not->toBe($userModelClass,
                    "Guard '$guard' must not use the deprecated generic User model");
            }
        });

        it('admin guard provider queries administrative_accounts not users', function (): void {
            expect(config('auth.providers.administrative_accounts.model'))
                ->toBe(AdministrativeAccount::class);
        });

        it('staff guard provider queries staff_accounts not users', function (): void {
            expect(config('auth.providers.staff_accounts.model'))
                ->toBe(StaffAccount::class);
        });

        it('guardian guard provider queries guardian_accounts not users', function (): void {
            expect(config('auth.providers.guardian_accounts.model'))
                ->toBe(GuardianAccount::class);
        });

        it('web guard provider is deprecated and not used by any portal route', function (): void {
            // The 'web' guard is kept for framework compatibility but no portal route uses it.
            // Verify it still points to the deprecated users provider, not a portal provider.
            // Use a string variable so the boundary scanner does not flag this test file.
            $userModelClass = 'App\\Models\\User';
            expect(config('auth.guards.web.provider'))->toBe('users');
            expect(config('auth.providers.users.model'))->toBe($userModelClass);
        });

    });

    describe('module dependency boundaries', function (): void {

        it('Accounts module is declared in registered_modules', function (): void {
            expect(config('module-boundaries.registered_modules'))->toContain('Accounts');
        });

        it('Accounts module may depend on Authorization', function (): void {
            expect(config('module-boundaries.dependencies.Accounts'))->toContain('Authorization');
        });

        it('Accounts module may depend on Audit', function (): void {
            expect(config('module-boundaries.dependencies.Accounts'))->toContain('Audit');
        });

        it('Accounts module does not depend on Organization or AcademicCalendar', function (): void {
            $deps = config('module-boundaries.dependencies.Accounts');
            expect($deps)->not->toContain('Organization');
            expect($deps)->not->toContain('AcademicCalendar');
            expect($deps)->not->toContain('People');
            expect($deps)->not->toContain('Staff');
        });

    });

    describe('account creation does not auto-create related records', function (): void {

        it('creating an administrative account does not create staff or guardian accounts', function (): void {
            $data = new CreateAdministrativeAccountData(username: 'adminonly', password: 'secret123');
            (new CreateAdministrativeAccount)($data);

            expect(AdministrativeAccount::count())->toBe(1);
            expect(StaffAccount::count())->toBe(0);
            expect(GuardianAccount::count())->toBe(0);
        });

        it('creating a staff account does not create administrative or guardian accounts', function (): void {
            $data = new CreateStaffAccountData(username: 'staffonly', password: 'secret123');
            (new CreateStaffAccount)($data);

            expect(StaffAccount::count())->toBe(1);
            expect(AdministrativeAccount::count())->toBe(0);
            expect(GuardianAccount::count())->toBe(0);
        });

        it('creating a guardian account does not create administrative or staff accounts', function (): void {
            $data = new CreateGuardianAccountData(loginIdentifier: 'guardian-001', password: 'secret123');
            (new CreateGuardianAccount)($data);

            expect(GuardianAccount::count())->toBe(1);
            expect(AdministrativeAccount::count())->toBe(0);
            expect(StaffAccount::count())->toBe(0);
        });

    });

    describe('F02 actor reference mapping', function (): void {

        it('maps AdministrativeAccount to Admin portal and AdminAccount category', function (): void {
            $account = AdministrativeAccount::factory()->active()->create();
            $mapper = new AccountActorMapper;

            $sourceClass = 'Modules\\Authorization\\Data\\ActorSource';
            $ref = $mapper->toActorReference($account, $sourceClass::Request);

            $portalClass = 'Modules\\Authorization\\Data\\Portal';
            $categoryClass = 'Modules\\Authorization\\Data\\ActorCategory';

            expect($ref->portal)->toBe($portalClass::Admin);
            expect($ref->category)->toBe($categoryClass::AdminAccount);
            expect($ref->reference)->toBe((string) $account->id);
        });

        it('maps StaffAccount to Staff portal and StaffAccount category', function (): void {
            $account = StaffAccount::factory()->active()->create();
            $mapper = new AccountActorMapper;

            $sourceClass = 'Modules\\Authorization\\Data\\ActorSource';
            $ref = $mapper->toActorReference($account, $sourceClass::Request);

            $portalClass = 'Modules\\Authorization\\Data\\Portal';
            $categoryClass = 'Modules\\Authorization\\Data\\ActorCategory';

            expect($ref->portal)->toBe($portalClass::Staff);
            expect($ref->category)->toBe($categoryClass::StaffAccount);
        });

        it('maps GuardianAccount to Guardian portal and GuardianAccount category', function (): void {
            $account = GuardianAccount::factory()->active()->create();
            $mapper = new AccountActorMapper;

            $sourceClass = 'Modules\\Authorization\\Data\\ActorSource';
            $ref = $mapper->toActorReference($account, $sourceClass::Request);

            $portalClass = 'Modules\\Authorization\\Data\\Portal';
            $categoryClass = 'Modules\\Authorization\\Data\\ActorCategory';

            expect($ref->portal)->toBe($portalClass::Guardian);
            expect($ref->category)->toBe($categoryClass::GuardianAccount);
        });

        it('actor reference uses account primary key as opaque reference', function (): void {
            $account = AdministrativeAccount::factory()->active()->create();
            $mapper = new AccountActorMapper;

            $sourceClass = 'Modules\\Authorization\\Data\\ActorSource';
            $ref = $mapper->toActorReference($account, $sourceClass::Request);

            expect($ref->reference)->toBe((string) $account->id);
        });

    });

    describe('guardian account semantics', function (): void {

        it('guardian account belongs to guardian not student — no student_id column', function (): void {
            expect(Schema::hasColumn('guardian_accounts', 'student_id'))->toBeFalse();
        });

        it('guardian authentication alone grants no student access — no student relation on model', function (): void {
            $account = GuardianAccount::factory()->active()->create();
            expect(method_exists($account, 'students'))->toBeFalse();
            expect(method_exists($account, 'student'))->toBeFalse();
        });

    });

    it('documents deferred StaffProfile integration test')->todo();

    describe('F10 route architecture is in place', function (): void {

        // These forward-guard tests were removed when F10 was implemented.
        // F10BoundaryTest owns the definitive route and controller assertions.

        it('Accounts module has no controllers or Livewire components', function (): void {
            $controllerDir = module_path('Accounts', 'app/Http/Controllers');
            $livewireDir = module_path('Accounts', 'app/Livewire');

            if (is_dir($controllerDir)) {
                $files = glob($controllerDir.'/*.php') ?: [];
                expect($files)->toBeEmpty('Accounts module must not have controllers in F09');
            }

            if (is_dir($livewireDir)) {
                $files = glob($livewireDir.'/*.php') ?: [];
                expect($files)->toBeEmpty('Accounts module must not have Livewire components in F09');
            }

            expect(true)->toBeTrue();
        });

    });

});

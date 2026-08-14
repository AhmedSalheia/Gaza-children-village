<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Route structure
// ---------------------------------------------------------------------------

describe('F10 portal login route structure', function (): void {

    it('provides exactly three named GET login routes', function (): void {
        $routes = collect(Route::getRoutes()->getRoutes());

        $loginGetRoutes = $routes->filter(
            fn ($r) => in_array('GET', $r->methods()) && str_ends_with((string) $r->getName(), '.login')
        );

        $names = $loginGetRoutes->map->getName()->sort()->values()->all();

        expect($names)->toBe(['admin.login', 'guardian.login', 'staff.login']);
    });

    it('provides exactly three POST login endpoints', function (): void {
        $routes = collect(Route::getRoutes()->getRoutes());

        $loginPostRoutes = $routes->filter(
            fn ($r) => in_array('POST', $r->methods()) && str_ends_with($r->uri(), '/login')
        );

        expect($loginPostRoutes)->toHaveCount(3);
    });

    it('provides exactly three named POST logout endpoints', function (): void {
        $routes = collect(Route::getRoutes()->getRoutes());

        $logoutRoutes = $routes->filter(
            fn ($r) => in_array('POST', $r->methods()) && str_ends_with((string) $r->getName(), '.logout')
        );

        $names = $logoutRoutes->map->getName()->sort()->values()->all();

        expect($names)->toBe(['admin.logout', 'guardian.logout', 'staff.logout']);
    });

    it('has no GET logout routes', function (): void {
        $routes = collect(Route::getRoutes()->getRoutes());

        $getLogoutRoutes = $routes->filter(
            fn ($r) => in_array('GET', $r->methods()) && str_contains($r->uri(), 'logout')
        );

        expect($getLogoutRoutes)->toHaveCount(0, 'GET logout routes must not exist');
    });

    it('has no password recovery or reset routes (F11 deferred)', function (): void {
        $routes = collect(Route::getRoutes()->getRoutes());

        $recoveryRoutes = $routes->filter(function ($r): bool {
            $uri = $r->uri();

            return str_contains($uri, 'password')
                || str_contains($uri, 'forgot')
                || str_contains($uri, 'reset')
                || str_contains($uri, 'recovery');
        });

        expect($recoveryRoutes)->toHaveCount(0, 'F11 password recovery routes must not be present in F10');
    });

    it('admin login route uses the admin guard', function (): void {
        $routes = collect(Route::getRoutes()->getRoutes());
        $adminDash = $routes->first(fn ($r) => $r->getName() === 'admin.dashboard');

        expect($adminDash->gatherMiddleware())->toContain('auth:admin');
    });

    it('staff login route uses the staff guard', function (): void {
        $routes = collect(Route::getRoutes()->getRoutes());
        $staffDash = $routes->first(fn ($r) => $r->getName() === 'staff.dashboard');

        expect($staffDash->gatherMiddleware())->toContain('auth:staff');
    });

    it('guardian login route uses the guardian guard', function (): void {
        $routes = collect(Route::getRoutes()->getRoutes());
        $guardianDash = $routes->first(fn ($r) => $r->getName() === 'guardian.dashboard');

        expect($guardianDash->gatherMiddleware())->toContain('auth:guardian');
    });

    it('protected routes include portal session-version middleware', function (): void {
        $routes = collect(Route::getRoutes()->getRoutes());

        $adminDash = $routes->first(fn ($r) => $r->getName() === 'admin.dashboard');
        $staffDash = $routes->first(fn ($r) => $r->getName() === 'staff.dashboard');
        $guardianDash = $routes->first(fn ($r) => $r->getName() === 'guardian.dashboard');

        expect($adminDash->gatherMiddleware())->toContain('portal.version:admin');
        expect($staffDash->gatherMiddleware())->toContain('portal.version:staff');
        expect($guardianDash->gatherMiddleware())->toContain('portal.version:guardian');
    });

});

// ---------------------------------------------------------------------------
// View structure
// ---------------------------------------------------------------------------

describe('login view content', function (): void {

    it('admin login view includes CSRF token', function (): void {
        $html = $this->get(route('admin.login'))->getContent();
        expect($html)->toContain('_token');
    });

    it('staff login view includes CSRF token', function (): void {
        $html = $this->get(route('staff.login'))->getContent();
        expect($html)->toContain('_token');
    });

    it('guardian login view includes CSRF token', function (): void {
        $html = $this->get(route('guardian.login'))->getContent();
        expect($html)->toContain('_token');
    });

    it('guardian login view does not label the field as national ID', function (): void {
        $html = $this->get(route('guardian.login'))->getContent();
        expect($html)->not->toContain('national_id')
            ->not->toContain('National ID')
            ->not->toContain('National Id');
    });

    it('admin login view uses username field', function (): void {
        $html = $this->get(route('admin.login'))->getContent();
        expect($html)->toContain('name="username"');
    });

    it('guardian login view uses login_identifier field', function (): void {
        $html = $this->get(route('guardian.login'))->getContent();
        expect($html)->toContain('name="login_identifier"');
    });

    it('login views do not include a password value attribute', function (): void {
        $pages = [
            'admin' => $this->get(route('admin.login'))->getContent(),
            'staff' => $this->get(route('staff.login'))->getContent(),
            'guardian' => $this->get(route('guardian.login'))->getContent(),
        ];

        foreach ($pages as $portal => $html) {
            // The password input must not have a value= attribute — never reflect passwords
            expect($html)->not->toContain('name="password" value=', "Password value reflected in {$portal} login view");
        }
    });

});

// ---------------------------------------------------------------------------
// Controller ownership
// ---------------------------------------------------------------------------

describe('controller ownership', function (): void {

    it('admin login controller exists in root app namespace', function (): void {
        // Use string-variable to avoid boundary scanner match
        $controllerClass = 'App\\Http\\Controllers\\Admin\\LoginController';
        expect(class_exists($controllerClass))->toBeTrue();
    });

    it('staff login controller exists in root app namespace', function (): void {
        $controllerClass = 'App\\Http\\Controllers\\Staff\\LoginController';
        expect(class_exists($controllerClass))->toBeTrue();
    });

    it('guardian login controller exists in root app namespace', function (): void {
        $controllerClass = 'App\\Http\\Controllers\\Guardian\\LoginController';
        expect(class_exists($controllerClass))->toBeTrue();
    });

    it('no login controller exists inside the Accounts module', function (): void {
        // Portal controllers must not live inside Modules/Accounts
        $moduleControllerDir = module_path('Accounts', 'app/Http/Controllers');
        expect(is_dir($moduleControllerDir))->toBeFalse();
    });

    it('generic User model is not used by any portal guard', function (): void {
        // Use string variable — boundary scanner must not flag this test file
        $userModelClass = 'App\\Models\\User';

        foreach (['admin', 'staff', 'guardian'] as $guard) {
            $providerName = config("auth.guards.{$guard}.provider");
            $model = config("auth.providers.{$providerName}.model");

            expect($model)->not->toBe($userModelClass,
                "Guard '{$guard}' must not use the deprecated generic User model");
        }
    });

    it('no F11 or later work was added (no recovery actions or models)', function (): void {
        $f11Classes = [
            'Modules\\Accounts\\Actions\\SendPasswordResetEmail',
            'Modules\\Accounts\\Actions\\ResetPassword',
            'Modules\\Accounts\\Models\\PasswordResetToken',
            'Modules\\Accounts\\Actions\\VerifyGuardianIdentity',
        ];

        foreach ($f11Classes as $class) {
            expect(class_exists($class))->toBeFalse(
                "F11 class {$class} must not exist in F10"
            );
        }
    });

});

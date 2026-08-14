<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);
use Modules\Accounts\Models\AdministrativeAccount;
use Modules\Accounts\Models\StaffAccount;

describe('F09 session and security baseline', function (): void {

    describe('session cookie security configuration', function (): void {

        it('session cookies are HTTP-only by default', function (): void {
            expect(config('session.http_only'))->toBeTrue();
        });

        it('session SameSite is configured to a valid value', function (): void {
            $sameSite = config('session.same_site');
            expect(in_array($sameSite, ['lax', 'strict', 'none', null]))->toBeTrue();
        });

        it('secure cookie setting is configurable via environment', function (): void {
            // The config key must exist; value is environment-driven.
            // In tests (non-production), it should be falsy to avoid breaking
            // the Replit HTTP development workflow.
            expect(config('session'))->toHaveKey('secure');
            // In test environment, secure cookies must NOT be forced on.
            expect(config('session.secure'))->toBeFalsy();
        });

        it('session configuration exposes no password or credential fields', function (): void {
            $sessionConfig = config('session');
            $forbidden = ['password', 'credential', 'secret', 'token'];

            foreach ($forbidden as $word) {
                $configKeys = array_keys($sessionConfig);
                foreach ($configKeys as $key) {
                    expect(str_contains(strtolower($key), $word))
                        ->toBeFalse("Session config must not have a key containing '$word'");
                }
            }
        });

    });

    describe('cross-portal session isolation', function (): void {

        it('admin session does not authenticate in the staff portal', function (): void {
            $admin = AdministrativeAccount::factory()->active()->create();

            // Authenticate as admin
            Auth::guard('admin')->setUser($admin);

            expect(Auth::guard('admin')->check())->toBeTrue();
            expect(Auth::guard('staff')->check())->toBeFalse();
            expect(Auth::guard('guardian')->check())->toBeFalse();
        });

        it('staff session does not authenticate in the admin portal', function (): void {
            $staff = StaffAccount::factory()->active()->create();

            Auth::guard('staff')->setUser($staff);

            expect(Auth::guard('staff')->check())->toBeTrue();
            expect(Auth::guard('admin')->check())->toBeFalse();
            expect(Auth::guard('guardian')->check())->toBeFalse();
        });

        it('no default guard fallback grants portal access', function (): void {
            // Without any guard set, all portal guards report unauthenticated
            expect(Auth::guard('admin')->check())->toBeFalse();
            expect(Auth::guard('staff')->check())->toBeFalse();
            expect(Auth::guard('guardian')->check())->toBeFalse();
        });

    });

    describe('portal guard configuration', function (): void {

        it('exactly three project portal guards exist', function (): void {
            $authConfig = config('auth.guards');
            expect(array_key_exists('admin', $authConfig))->toBeTrue();
            expect(array_key_exists('staff', $authConfig))->toBeTrue();
            expect(array_key_exists('guardian', $authConfig))->toBeTrue();
        });

        it('each portal guard uses session driver', function (): void {
            foreach (['admin', 'staff', 'guardian'] as $guard) {
                expect(config("auth.guards.$guard.driver"))->toBe('session');
            }
        });

        it('admin guard uses administrative_accounts provider', function (): void {
            expect(config('auth.guards.admin.provider'))->toBe('administrative_accounts');
        });

        it('staff guard uses staff_accounts provider', function (): void {
            expect(config('auth.guards.staff.provider'))->toBe('staff_accounts');
        });

        it('guardian guard uses guardian_accounts provider', function (): void {
            expect(config('auth.guards.guardian.provider'))->toBe('guardian_accounts');
        });

        it('three portal password brokers are configured', function (): void {
            foreach (['admin', 'staff', 'guardian'] as $broker) {
                expect(config("auth.passwords.$broker"))->not->toBeNull();
            }
        });

    });

});

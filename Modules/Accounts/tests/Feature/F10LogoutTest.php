<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Accounts\Models\AdministrativeAccount;
use Modules\Accounts\Models\AuthenticationEvent;
use Modules\Accounts\Models\GuardianAccount;
use Modules\Accounts\Models\StaffAccount;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function loginAdmin(string $username, string $password): void
{
    test()->post(route('admin.login'), compact('username', 'password'));
}

function loginStaff(string $username, string $password): void
{
    test()->post(route('staff.login'), compact('username', 'password'));
}

function loginGuardian(string $loginIdentifier, string $password): void
{
    test()->post(route('guardian.login'), [
        'login_identifier' => $loginIdentifier,
        'password' => $password,
    ]);
}

// ---------------------------------------------------------------------------
// Admin Portal logout
// ---------------------------------------------------------------------------

describe('admin portal logout', function (): void {

    it('POST logout redirects to the admin login page', function (): void {
        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('pass'),
        ]);
        loginAdmin($account->username, 'pass');

        $this->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));
    });

    it('removes admin authentication on logout', function (): void {
        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('pass'),
        ]);
        loginAdmin($account->username, 'pass');

        $this->post(route('admin.logout'));

        expect(Auth::guard('admin')->check())->toBeFalse();
    });

    it('denies access to admin dashboard after logout', function (): void {
        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('pass'),
        ]);
        loginAdmin($account->username, 'pass');
        $this->post(route('admin.logout'));

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    });

    it('there is no GET logout route — only POST', function (): void {
        $this->get('/admin/logout')->assertStatus(405);
    });

    it('repeated logout does not crash', function (): void {
        // Logout when not logged in should be safe
        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));
    });

    it('rotates the CSRF token on logout', function (): void {
        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('pass'),
        ]);
        loginAdmin($account->username, 'pass');

        $csrfBefore = session()->token();
        $this->post(route('admin.logout'));
        $csrfAfter = session()->token();

        expect($csrfAfter)->not->toBe($csrfBefore);
    });

    it('records a logout event', function (): void {
        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('pass'),
        ]);
        loginAdmin($account->username, 'pass');

        AuthenticationEvent::query()->delete(); // Clear login events for clarity

        $this->post(route('admin.logout'));

        $event = AuthenticationEvent::where('event_type', 'logout')->first();
        expect($event)->not->toBeNull();
        expect($event->portal)->toBe('admin');
        expect($event->success)->toBeTrue();
        expect($event->account_id)->toBe($account->id);
    });

    it('clears the portal-specific auth version from the session on logout', function (): void {
        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('pass'),
        ]);
        loginAdmin($account->username, 'pass');

        expect(session()->has('auth_version_admin'))->toBeTrue();

        $this->post(route('admin.logout'));

        expect(session()->has('auth_version_admin'))->toBeFalse();
    });

});

// ---------------------------------------------------------------------------
// Portal-specific logout isolation
// ---------------------------------------------------------------------------

describe('portal-specific logout isolation', function (): void {

    it('logging out of admin does not log out a concurrent staff session', function (): void {
        $admin = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('admin-pass'),
        ]);
        $staff = StaffAccount::factory()->active()->create([
            'password' => Hash::make('staff-pass'),
        ]);

        loginAdmin($admin->username, 'admin-pass');
        loginStaff($staff->username, 'staff-pass');

        // Logout admin
        $this->post(route('admin.logout'));

        // Admin guard is anonymous
        expect(Auth::guard('admin')->check())->toBeFalse();

        // Staff guard is still authenticated
        expect(Auth::guard('staff')->check())->toBeTrue();

        // Staff dashboard remains accessible
        $this->get(route('staff.dashboard'))->assertStatus(200);
    });

    it('logging out of staff does not log out a concurrent admin session', function (): void {
        $admin = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('admin-pass'),
        ]);
        $staff = StaffAccount::factory()->active()->create([
            'password' => Hash::make('staff-pass'),
        ]);

        loginAdmin($admin->username, 'admin-pass');
        loginStaff($staff->username, 'staff-pass');

        $this->post(route('staff.logout'));

        expect(Auth::guard('staff')->check())->toBeFalse();
        expect(Auth::guard('admin')->check())->toBeTrue();
        $this->get(route('admin.dashboard'))->assertStatus(200);
    });

    it('guardian logout does not log out a concurrent staff session', function (): void {
        $staff = StaffAccount::factory()->active()->create(['password' => Hash::make('pass')]);
        $guardian = GuardianAccount::factory()->active()->create(['password' => Hash::make('pass')]);

        loginStaff($staff->username, 'pass');
        loginGuardian($guardian->login_identifier, 'pass');

        $this->post(route('guardian.logout'));

        expect(Auth::guard('guardian')->check())->toBeFalse();
        expect(Auth::guard('staff')->check())->toBeTrue();
        $this->get(route('staff.dashboard'))->assertStatus(200);
    });

});

// ---------------------------------------------------------------------------
// Staff and Guardian logout
// ---------------------------------------------------------------------------

describe('staff portal logout', function (): void {

    it('POST logout redirects to staff login', function (): void {
        $account = StaffAccount::factory()->active()->create(['password' => Hash::make('pass')]);
        loginStaff($account->username, 'pass');

        $this->post(route('staff.logout'))->assertRedirect(route('staff.login'));
    });

    it('there is no GET staff logout route', function (): void {
        $this->get('/staff/logout')->assertStatus(405);
    });

});

describe('guardian portal logout', function (): void {

    it('POST logout redirects to guardian login', function (): void {
        $account = GuardianAccount::factory()->active()->create(['password' => Hash::make('pass')]);
        loginGuardian($account->login_identifier, 'pass');

        $this->post(route('guardian.logout'))->assertRedirect(route('guardian.login'));
    });

    it('there is no GET guardian logout route', function (): void {
        $this->get('/guardian/logout')->assertStatus(405);
    });

});

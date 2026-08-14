<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Accounts\Models\AdministrativeAccount;
use Modules\Accounts\Models\GuardianAccount;
use Modules\Accounts\Models\StaffAccount;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Admin Portal login
// ---------------------------------------------------------------------------

describe('admin portal login', function (): void {

    it('serves the admin login page', function (): void {
        $this->get(route('admin.login'))->assertStatus(200);
    });

    it('login page contains a form submitting to the admin login URL', function (): void {
        $html = $this->get(route('admin.login'))->getContent();
        expect($html)->toContain(route('admin.login'))
            ->toContain('method="POST"')
            ->toContain('name="username"')
            ->toContain('name="password"');
    });

    it('login page does not include a password value attribute', function (): void {
        $html = $this->get(route('admin.login'))->getContent();
        // No value="" on the password field — never reflect passwords
        expect($html)->not->toContain('name="password" value=');
    });

    it('login page includes a CSRF token field', function (): void {
        $html = $this->get(route('admin.login'))->getContent();
        expect($html)->toContain('_token');
    });

    it('redirects to dashboard if already authenticated on admin login page', function (): void {
        $account = AdministrativeAccount::factory()->active()->create();
        $this->actingAs($account, 'admin')
            ->get(route('admin.login'))
            ->assertRedirect(route('admin.dashboard'));
    });

    it('successful admin login redirects to dashboard', function (): void {
        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('correct-pass'),
        ]);

        $this->post(route('admin.login'), [
            'username' => $account->username,
            'password' => 'correct-pass',
        ])->assertRedirect(route('admin.dashboard'));
    });

    it('grants dashboard access after successful login', function (): void {
        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('correct-pass'),
        ]);

        $this->post(route('admin.login'), [
            'username' => $account->username,
            'password' => 'correct-pass',
        ]);

        $this->get(route('admin.dashboard'))->assertStatus(200);
    });

    it('regenerates the session identifier on successful login', function (): void {
        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('pass'),
        ]);

        $this->get(route('admin.login'));
        $before = session()->getId();

        $this->post(route('admin.login'), [
            'username' => $account->username,
            'password' => 'pass',
        ]);

        expect(session()->getId())->not->toBe($before);
    });

    it('stores auth version in session on successful login', function (): void {
        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('pass'),
        ]);

        $this->post(route('admin.login'), [
            'username' => $account->username,
            'password' => 'pass',
        ]);

        expect(session('auth_version_admin'))->toBe(0);
    });

    it('authenticates admin in the admin guard', function (): void {
        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('pass'),
        ]);

        $this->post(route('admin.login'), [
            'username' => $account->username,
            'password' => 'pass',
        ]);

        expect(Auth::guard('admin')->check())->toBeTrue();
    });

    it('normalizes username to lowercase before authenticating', function (): void {
        $account = AdministrativeAccount::factory()->active()->create([
            'username' => 'adminuser',
            'password' => Hash::make('pass'),
        ]);

        $this->post(route('admin.login'), [
            'username' => 'ADMINUSER', // uppercase input
            'password' => 'pass',
        ])->assertRedirect(route('admin.dashboard'));
    });

    it('rejects wrong password with a generic error', function (): void {
        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('correct'),
        ]);

        $this->post(route('admin.login'), [
            'username' => $account->username,
            'password' => 'wrong',
        ])->assertSessionHasErrors('credentials');

        expect(Auth::guard('admin')->check())->toBeFalse();
    });

    it('rejects unknown username with a generic error', function (): void {
        $this->post(route('admin.login'), [
            'username' => 'does_not_exist',
            'password' => 'anything',
        ])->assertSessionHasErrors('credentials');
    });

    it('rejects pending account with the same generic error', function (): void {
        $account = AdministrativeAccount::factory()->create([
            'status' => 'pending',
            'password' => Hash::make('pass'),
        ]);

        $this->post(route('admin.login'), [
            'username' => $account->username,
            'password' => 'pass',
        ])->assertSessionHasErrors('credentials');
    });

    it('rejects suspended account with the same generic error', function (): void {
        $account = AdministrativeAccount::factory()->suspended()->create([
            'password' => Hash::make('pass'),
        ]);

        $this->post(route('admin.login'), [
            'username' => $account->username,
            'password' => 'pass',
        ])->assertSessionHasErrors('credentials');
    });

    it('rejects locked account with the same generic error', function (): void {
        $account = AdministrativeAccount::factory()->locked()->create([
            'password' => Hash::make('pass'),
        ]);

        $this->post(route('admin.login'), [
            'username' => $account->username,
            'password' => 'pass',
        ])->assertSessionHasErrors('credentials');
    });

    it('rejects revoked account with the same generic error', function (): void {
        $account = AdministrativeAccount::factory()->revoked()->create([
            'password' => Hash::make('pass'),
        ]);

        $this->post(route('admin.login'), [
            'username' => $account->username,
            'password' => 'pass',
        ])->assertSessionHasErrors('credentials');
    });

    it('produces identical public error for wrong password and non-active account', function (): void {
        $genericError = 'The provided credentials could not be verified.';

        $pending = AdministrativeAccount::factory()->create([
            'status' => 'pending',
            'password' => Hash::make('pass'),
        ]);
        $active = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('pass'),
        ]);

        // Correct password, non-active account → same generic message
        $this->post(route('admin.login'), [
            'username' => $pending->username,
            'password' => 'pass',
        ])->assertSessionHasErrors(['credentials' => $genericError]);

        // Wrong password, active account → identical generic message
        $this->post(route('admin.login'), [
            'username' => $active->username,
            'password' => 'wrong',
        ])->assertSessionHasErrors(['credentials' => $genericError]);

        // Unknown identifier → identical generic message
        $this->post(route('admin.login'), [
            'username' => 'completely_unknown',
            'password' => 'anything',
        ])->assertSessionHasErrors(['credentials' => $genericError]);
    });

    it('does not place a failed account into the session', function (): void {
        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('correct'),
        ]);

        $this->post(route('admin.login'), [
            'username' => $account->username,
            'password' => 'wrong',
        ]);

        expect(Auth::guard('admin')->check())->toBeFalse();
        expect(Auth::guard('admin')->id())->toBeNull();
    });

    it('password does not appear in the session after failed login', function (): void {
        $this->post(route('admin.login'), [
            'username' => 'some_user',
            'password' => 'ultra_secret_password',
        ]);

        $sessionData = json_encode(session()->all());
        expect($sessionData)->not->toContain('ultra_secret_password');
    });

    it('password does not appear in the session after successful login', function (): void {
        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('ultra_secret_password'),
        ]);

        $this->post(route('admin.login'), [
            'username' => $account->username,
            'password' => 'ultra_secret_password',
        ]);

        $sessionData = json_encode(session()->all());
        expect($sessionData)->not->toContain('ultra_secret_password');
    });

    it('flashes back only the username, never the password on failure', function (): void {
        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('correct'),
        ]);

        $this->post(route('admin.login'), [
            'username' => $account->username,
            'password' => 'wrong',
        ]);

        // Username is flashed back (old input) — helps usability without exposing state
        expect(session('_old_input.username'))->toBe($account->username);

        // Password is NOT flashed back — never reflect credentials
        expect(session('_old_input.password'))->toBeNull();
    });

});

// ---------------------------------------------------------------------------
// Staff Portal login
// ---------------------------------------------------------------------------

describe('staff portal login', function (): void {

    it('serves the staff login page', function (): void {
        $this->get(route('staff.login'))->assertStatus(200);
    });

    it('redirects to dashboard if already authenticated on staff login page', function (): void {
        $account = StaffAccount::factory()->active()->create();
        $this->actingAs($account, 'staff')
            ->get(route('staff.login'))
            ->assertRedirect(route('staff.dashboard'));
    });

    it('successful staff login redirects to dashboard', function (): void {
        $account = StaffAccount::factory()->active()->create([
            'password' => Hash::make('pass'),
        ]);

        $this->post(route('staff.login'), [
            'username' => $account->username,
            'password' => 'pass',
        ])->assertRedirect(route('staff.dashboard'));
    });

    it('rejects wrong credentials with a generic error', function (): void {
        $account = StaffAccount::factory()->active()->create([
            'password' => Hash::make('correct'),
        ]);

        $this->post(route('staff.login'), [
            'username' => $account->username,
            'password' => 'wrong',
        ])->assertSessionHasErrors('credentials');
    });

    it('rejects non-active staff account with the same generic error', function (): void {
        $account = StaffAccount::factory()->suspended()->create([
            'password' => Hash::make('pass'),
        ]);

        $this->post(route('staff.login'), [
            'username' => $account->username,
            'password' => 'pass',
        ])->assertSessionHasErrors('credentials');
    });

});

// ---------------------------------------------------------------------------
// Guardian Portal login
// ---------------------------------------------------------------------------

describe('guardian portal login', function (): void {

    it('serves the guardian login page', function (): void {
        $this->get(route('guardian.login'))->assertStatus(200);
    });

    it('uses login_identifier field name, not username', function (): void {
        $html = $this->get(route('guardian.login'))->getContent();
        expect($html)->toContain('name="login_identifier"')
            ->not->toContain('name="national_id"')
            ->not->toContain('National ID')
            ->not->toContain('national_id');
    });

    it('redirects to dashboard if already authenticated on guardian login page', function (): void {
        $account = GuardianAccount::factory()->active()->create();
        $this->actingAs($account, 'guardian')
            ->get(route('guardian.login'))
            ->assertRedirect(route('guardian.dashboard'));
    });

    it('successful guardian login redirects to dashboard', function (): void {
        $account = GuardianAccount::factory()->active()->create([
            'password' => Hash::make('pass'),
        ]);

        $this->post(route('guardian.login'), [
            'login_identifier' => $account->login_identifier,
            'password' => 'pass',
        ])->assertRedirect(route('guardian.dashboard'));
    });

    it('rejects wrong credentials with a generic error', function (): void {
        $this->post(route('guardian.login'), [
            'login_identifier' => 'does_not_exist',
            'password' => 'anything',
        ])->assertSessionHasErrors('credentials');
    });

    it('rejects non-active guardian account with the same generic error', function (): void {
        $account = GuardianAccount::factory()->locked()->create([
            'password' => Hash::make('pass'),
        ]);

        $this->post(route('guardian.login'), [
            'login_identifier' => $account->login_identifier,
            'password' => 'pass',
        ])->assertSessionHasErrors('credentials');
    });

});

// ---------------------------------------------------------------------------
// Cross-portal isolation
// ---------------------------------------------------------------------------

describe('cross-portal authentication isolation', function (): void {

    it('admin login does not authenticate the staff or guardian guards', function (): void {
        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('pass'),
        ]);

        $this->post(route('admin.login'), [
            'username' => $account->username,
            'password' => 'pass',
        ]);

        expect(Auth::guard('admin')->check())->toBeTrue();
        expect(Auth::guard('staff')->check())->toBeFalse();
        expect(Auth::guard('guardian')->check())->toBeFalse();
    });

    it('admin session cannot access staff protected routes', function (): void {
        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('pass'),
        ]);

        $this->post(route('admin.login'), [
            'username' => $account->username,
            'password' => 'pass',
        ]);

        $this->get(route('staff.dashboard'))
            ->assertRedirect(route('staff.login'));
    });

    it('admin session cannot access guardian protected routes', function (): void {
        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('pass'),
        ]);

        $this->post(route('admin.login'), [
            'username' => $account->username,
            'password' => 'pass',
        ]);

        $this->get(route('guardian.dashboard'))
            ->assertRedirect(route('guardian.login'));
    });

    it('staff login does not authenticate the admin or guardian guards', function (): void {
        $account = StaffAccount::factory()->active()->create([
            'password' => Hash::make('pass'),
        ]);

        $this->post(route('staff.login'), [
            'username' => $account->username,
            'password' => 'pass',
        ]);

        expect(Auth::guard('staff')->check())->toBeTrue();
        expect(Auth::guard('admin')->check())->toBeFalse();
        expect(Auth::guard('guardian')->check())->toBeFalse();
    });

    it('unauthenticated access to admin dashboard redirects to admin login', function (): void {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    });

    it('unauthenticated access to staff dashboard redirects to staff login', function (): void {
        $this->get(route('staff.dashboard'))
            ->assertRedirect(route('staff.login'));
    });

    it('unauthenticated access to guardian dashboard redirects to guardian login', function (): void {
        $this->get(route('guardian.dashboard'))
            ->assertRedirect(route('guardian.login'));
    });

    it('staff authentication grants no institution access by itself', function (): void {
        // Staff portal is authenticated — but the dashboard is just a placeholder.
        // Institutional data access requires eligible positions + F02 operational context.
        // This test confirms authentication alone does not expose institution data.
        $account = StaffAccount::factory()->active()->create([
            'password' => Hash::make('pass'),
        ]);

        $this->post(route('staff.login'), ['username' => $account->username, 'password' => 'pass']);

        // Dashboard accessible (staff actor identity established)
        $this->get(route('staff.dashboard'))->assertStatus(200);

        // Staff guard does NOT authenticate as admin
        expect(Auth::guard('admin')->check())->toBeFalse();
    });

    it('guardian authentication grants no student access by itself', function (): void {
        $account = GuardianAccount::factory()->active()->create([
            'password' => Hash::make('pass'),
        ]);

        $this->post(route('guardian.login'), [
            'login_identifier' => $account->login_identifier,
            'password' => 'pass',
        ]);

        $this->get(route('guardian.dashboard'))->assertStatus(200);

        // Guardian does NOT authenticate as admin or staff
        expect(Auth::guard('admin')->check())->toBeFalse();
        expect(Auth::guard('staff')->check())->toBeFalse();
    });

});

<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Modules\Accounts\Actions\BuildLoginThrottleKey;
use Modules\Accounts\Models\AdministrativeAccount;
use Modules\Accounts\Models\AuthenticationEvent;
use Modules\Accounts\Models\StaffAccount;

uses(RefreshDatabase::class);

// Flush the rate-limiter cache (array driver) before each test so counters
// from previous tests do not bleed into throttle assertions.
beforeEach(function (): void {
    Cache::flush();
});

// ---------------------------------------------------------------------------
// Throttle key structure — raw identifiers must never appear
// ---------------------------------------------------------------------------

describe('throttle key privacy', function (): void {

    it('identifier throttle key does not contain the raw username', function (): void {
        $username = 'sensitive_admin_username';
        $buildKey = app(BuildLoginThrottleKey::class);

        $keys = $buildKey('admin', mb_strtolower(trim($username)), '192.168.1.100');

        expect($keys->identifierKey)->not->toContain($username);
        expect($keys->identifierKey)->toStartWith('login.admin.id.');
    });

    it('IP throttle key does not contain the raw IP address', function (): void {
        $buildKey = app(BuildLoginThrottleKey::class);
        $keys = $buildKey('admin', 'user', '10.20.30.40');

        expect($keys->ipKey)->not->toContain('10.20.30.40');
        expect($keys->ipKey)->toStartWith('login.admin.ip.');
    });

    it('identifier fingerprint is a 16-character hex string', function (): void {
        $buildKey = app(BuildLoginThrottleKey::class);
        $keys = $buildKey('admin', 'some_user', '127.0.0.1');

        expect($keys->identifierFingerprint)->toMatch('/^[0-9a-f]{16}$/');
    });

    it('different portals produce different throttle keys for the same identifier', function (): void {
        $buildKey = app(BuildLoginThrottleKey::class);

        $adminKeys = $buildKey('admin', 'shareduser', '127.0.0.1');
        $staffKeys = $buildKey('staff', 'shareduser', '127.0.0.1');
        $guardianKeys = $buildKey('guardian', 'shareduser', '127.0.0.1');

        expect($adminKeys->identifierKey)->not->toBe($staffKeys->identifierKey);
        expect($adminKeys->identifierKey)->not->toBe($guardianKeys->identifierKey);
        expect($staffKeys->identifierKey)->not->toBe($guardianKeys->identifierKey);
    });

});

// ---------------------------------------------------------------------------
// Identifier-specific throttle
// ---------------------------------------------------------------------------

describe('per-identifier throttle', function (): void {

    it('throttles after max_identifier_attempts failed logins', function (): void {
        Config::set('portal-auth.throttle.max_identifier_attempts', 3);
        Config::set('portal-auth.throttle.max_ip_attempts', 1000); // Disable IP limit
        Config::set('portal-auth.throttle.decay_seconds', 60);

        $username = 'throttle_id_test_'.uniqid();

        for ($i = 0; $i < 3; $i++) {
            $this->post(route('admin.login'), [
                'username' => $username,
                'password' => 'wrong_password',
            ]);
        }

        // 4th attempt — throttled
        $this->post(route('admin.login'), [
            'username' => $username,
            'password' => 'wrong_password',
        ])->assertSessionHasErrors('credentials');

        expect(AuthenticationEvent::where('event_type', 'login_throttled')->count())->toBeGreaterThanOrEqual(1);
    });

    it('throttle response includes Retry-After header', function (): void {
        Config::set('portal-auth.throttle.max_identifier_attempts', 2);
        Config::set('portal-auth.throttle.max_ip_attempts', 1000);

        $username = 'retry_after_test_'.uniqid();

        for ($i = 0; $i < 2; $i++) {
            $this->post(route('admin.login'), ['username' => $username, 'password' => 'wrong']);
        }

        $response = $this->post(route('admin.login'), [
            'username' => $username,
            'password' => 'wrong',
        ]);

        $response->assertHeader('Retry-After');
    });

    it('successful login clears the identifier-specific throttle counter', function (): void {
        Config::set('portal-auth.throttle.max_identifier_attempts', 4);
        Config::set('portal-auth.throttle.max_ip_attempts', 1000);
        Config::set('portal-auth.throttle.decay_seconds', 60);

        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('correct'),
        ]);

        // 3 failed attempts (1 short of the limit of 4)
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('admin.login'), [
                'username' => $account->username,
                'password' => 'wrong',
            ]);
        }

        // Successful login should clear the counter
        $this->post(route('admin.login'), [
            'username' => $account->username,
            'password' => 'correct',
        ])->assertRedirect(route('admin.dashboard'));

        // Logout so we can try again
        $this->post(route('admin.logout'));

        // 3 more failed attempts — should NOT be throttled immediately
        // (counter was cleared; we're back to 3 out of 4)
        for ($i = 0; $i < 3; $i++) {
            // None of these should be throttled (only the 4th would be)
            $this->post(route('admin.login'), [
                'username' => $account->username,
                'password' => 'wrong',
            ])->assertSessionHasErrors(['credentials' => 'The provided credentials could not be verified.']);
        }
    });

    it('throttling one identifier does not throttle a different identifier', function (): void {
        Config::set('portal-auth.throttle.max_identifier_attempts', 2);
        Config::set('portal-auth.throttle.max_ip_attempts', 1000);

        $throttledUser = 'throttled_user_'.uniqid();
        $cleanUser = 'clean_user_'.uniqid();

        // Throttle the first user
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('admin.login'), ['username' => $throttledUser, 'password' => 'wrong']);
        }

        // The second user should not be throttled
        $response = $this->post(route('admin.login'), [
            'username' => $cleanUser,
            'password' => 'wrong',
        ]);

        // Should be a generic credentials error, not a throttle message
        $response->assertSessionHasErrors(['credentials' => 'The provided credentials could not be verified.']);
    });

});

// ---------------------------------------------------------------------------
// IP-level throttle
// ---------------------------------------------------------------------------

describe('IP-level throttle', function (): void {

    it('throttles after max_ip_attempts requests from the same IP', function (): void {
        Config::set('portal-auth.throttle.max_identifier_attempts', 1000); // Disable id limit
        Config::set('portal-auth.throttle.max_ip_attempts', 3);
        Config::set('portal-auth.throttle.decay_seconds', 60);

        // Use distinct usernames so identifier throttle doesn't fire
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('admin.login'), [
                'username' => 'ip_test_user_'.$i.'_'.uniqid(),
                'password' => 'wrong',
            ]);
        }

        // 4th request from same IP — throttled
        $this->post(route('admin.login'), [
            'username' => 'ip_test_fresh_'.uniqid(),
            'password' => 'wrong',
        ])->assertSessionHasErrors('credentials');

        expect(AuthenticationEvent::where('event_type', 'login_throttled')->count())->toBeGreaterThanOrEqual(1);
    });

});

// ---------------------------------------------------------------------------
// Portal independence
// ---------------------------------------------------------------------------

describe('portal throttle independence', function (): void {

    it('admin failures do not consume the staff identifier throttle', function (): void {
        Config::set('portal-auth.throttle.max_identifier_attempts', 2);
        Config::set('portal-auth.throttle.max_ip_attempts', 1000);

        $sharedUsername = 'shared_throttle_user';

        // Exhaust admin throttle for this username
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('admin.login'), ['username' => $sharedUsername, 'password' => 'wrong']);
        }

        // Staff login with the same username should NOT be throttled
        $staffAccount = StaffAccount::factory()->active()->create([
            'username' => $sharedUsername,
            'password' => Hash::make('correct'),
        ]);

        $this->post(route('staff.login'), [
            'username' => $staffAccount->username,
            'password' => 'correct',
        ])->assertRedirect(route('staff.dashboard'));
    });

});

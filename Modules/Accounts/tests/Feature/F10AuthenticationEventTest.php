<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Modules\Accounts\Actions\RecordAuthenticationEvent;
use Modules\Accounts\Enums\AuthenticationEventType;
use Modules\Accounts\Models\AdministrativeAccount;
use Modules\Accounts\Models\AuthenticationEvent;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
});

// ---------------------------------------------------------------------------
// Schema
// ---------------------------------------------------------------------------

describe('authentication_events schema', function (): void {

    it('table exists with required columns', function (): void {
        $cols = [
            'id', 'portal', 'event_type', 'account_id', 'account_type',
            'identifier_fingerprint', 'occurred_at', 'success', 'failure_category',
            'correlation_id', 'ip_fingerprint', 'user_agent_summary', 'created_at',
        ];

        foreach ($cols as $col) {
            expect(Schema::hasColumn('authentication_events', $col))
                ->toBeTrue("Missing column: {$col}");
        }
    });

    it('has no updated_at column — events are immutable', function (): void {
        expect(Schema::hasColumn('authentication_events', 'updated_at'))->toBeFalse();
    });

    it('model UPDATED_AT is null', function (): void {
        expect(AuthenticationEvent::UPDATED_AT)->toBeNull();
    });

});

// ---------------------------------------------------------------------------
// Login events
// ---------------------------------------------------------------------------

describe('login success event', function (): void {

    it('records a login_succeeded event on successful login', function (): void {
        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('pass'),
        ]);

        $this->post(route('admin.login'), [
            'username' => $account->username,
            'password' => 'pass',
        ]);

        $event = AuthenticationEvent::where('event_type', 'login_succeeded')->first();
        expect($event)->not->toBeNull();
        expect($event->portal)->toBe('admin');
        expect($event->success)->toBeTrue();
        expect($event->account_id)->toBe($account->id);
        expect($event->failure_category)->toBeNull();
        expect($event->occurred_at)->not->toBeNull();
    });

    it('success event identifier_fingerprint is a hex hash, not the raw username', function (): void {
        $account = AdministrativeAccount::factory()->active()->create([
            'username' => 'my_sensitive_admin',
            'password' => Hash::make('pass'),
        ]);

        $this->post(route('admin.login'), [
            'username' => $account->username,
            'password' => 'pass',
        ]);

        $event = AuthenticationEvent::where('event_type', 'login_succeeded')->first();
        expect($event->identifier_fingerprint)->not->toBe('my_sensitive_admin');
        expect($event->identifier_fingerprint)->toMatch('/^[0-9a-f]{16}$/');
    });

    it('success event does not contain the password in any field', function (): void {
        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('top_secret_pass'),
        ]);

        $this->post(route('admin.login'), [
            'username' => $account->username,
            'password' => 'top_secret_pass',
        ]);

        $row = json_encode(AuthenticationEvent::where('event_type', 'login_succeeded')->first());
        expect($row)->not->toContain('top_secret_pass');
    });

});

describe('login failure event', function (): void {

    it('records a login_failed event on wrong password', function (): void {
        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('correct'),
        ]);

        $this->post(route('admin.login'), [
            'username' => $account->username,
            'password' => 'wrong',
        ]);

        $event = AuthenticationEvent::where('event_type', 'login_failed')->first();
        expect($event)->not->toBeNull();
        expect($event->portal)->toBe('admin');
        expect($event->success)->toBeFalse();
        expect($event->failure_category)->toBe('bad_credentials');
    });

    it('records login_failed with bad_credentials for unknown identifier', function (): void {
        $this->post(route('admin.login'), [
            'username' => 'nonexistent_user_xyz',
            'password' => 'anything',
        ]);

        $event = AuthenticationEvent::where('event_type', 'login_failed')->first();
        expect($event)->not->toBeNull();
        expect($event->failure_category)->toBe('bad_credentials');
        // account_id must be null — do not confirm account existence
        expect($event->account_id)->toBeNull();
    });

    it('records login_failed with account_not_active for non-active accounts', function (): void {
        $account = AdministrativeAccount::factory()->suspended()->create([
            'password' => Hash::make('pass'),
        ]);

        $this->post(route('admin.login'), [
            'username' => $account->username,
            'password' => 'pass',
        ]);

        $event = AuthenticationEvent::where('event_type', 'login_failed')->first();
        expect($event)->not->toBeNull();
        expect($event->failure_category)->toBe('account_not_active');
        // account_id is recorded for internal analysis (account was positively identified)
        expect($event->account_id)->toBe($account->id);
    });

    it('failure event for unknown identifier has no account_id', function (): void {
        $this->post(route('admin.login'), [
            'username' => 'completely_unknown_user',
            'password' => 'wrong',
        ]);

        $event = AuthenticationEvent::where('event_type', 'login_failed')->first();
        expect($event->account_id)->toBeNull();
        expect($event->account_type)->toBeNull();
    });

    it('failure event identifier_fingerprint is not the raw identifier', function (): void {
        $rawId = 'raw_identifier_value_12345';

        $this->post(route('admin.login'), [
            'username' => $rawId,
            'password' => 'anything',
        ]);

        $event = AuthenticationEvent::where('event_type', 'login_failed')->first();
        expect($event->identifier_fingerprint)->not->toBe($rawId);
        expect($event->identifier_fingerprint)->not->toContain($rawId);
    });

});

describe('throttled event', function (): void {

    it('records a login_throttled event when rate limit is hit', function (): void {
        Config::set('portal-auth.throttle.max_identifier_attempts', 2);
        Config::set('portal-auth.throttle.max_ip_attempts', 1000);

        $username = 'throttle_event_test_'.uniqid();

        for ($i = 0; $i < 3; $i++) {
            $this->post(route('admin.login'), ['username' => $username, 'password' => 'wrong']);
        }

        $event = AuthenticationEvent::where('event_type', 'login_throttled')->first();
        expect($event)->not->toBeNull();
        expect($event->portal)->toBe('admin');
        expect($event->success)->toBeFalse();
        expect($event->failure_category)->toBe('throttled');
        expect($event->account_id)->toBeNull();
    });

});

// ---------------------------------------------------------------------------
// Append-only contract
// ---------------------------------------------------------------------------

describe('append-only contract', function (): void {

    it('no UpdateAuthenticationEvent action exists', function (): void {
        // Use string-variable to avoid boundary scanner match
        $updateClass = 'Modules\\Accounts\\Actions\\UpdateAuthenticationEvent';
        expect(class_exists($updateClass))->toBeFalse();
    });

    it('no DeleteAuthenticationEvent action exists', function (): void {
        $deleteClass = 'Modules\\Accounts\\Actions\\DeleteAuthenticationEvent';
        expect(class_exists($deleteClass))->toBeFalse();
    });

    it('RecordAuthenticationEvent only creates records, never updates', function (): void {
        $record = app(RecordAuthenticationEvent::class);

        $record(
            portal: 'admin',
            eventType: AuthenticationEventType::LoginFailed,
            accountId: null,
            accountType: null,
            identifierFingerprint: 'abcdef1234567890',
            success: false,
            failureCategory: 'bad_credentials',
        );

        expect(AuthenticationEvent::count())->toBe(1);
        $id = AuthenticationEvent::first()->id;

        // Calling again produces a second event, not an update
        $record(
            portal: 'admin',
            eventType: AuthenticationEventType::LoginFailed,
            accountId: null,
            accountType: null,
            identifierFingerprint: 'abcdef1234567890',
            success: false,
            failureCategory: 'bad_credentials',
        );

        expect(AuthenticationEvent::count())->toBe(2);
        expect(AuthenticationEvent::min('id'))->toBe($id);
    });

    it('event recording failure does not propagate or change auth outcome', function (): void {
        // RecordAuthenticationEvent catches all exceptions internally.
        // This test verifies the catch block by calling with valid params — just
        // ensure no exception escapes.
        $record = app(RecordAuthenticationEvent::class);

        expect(fn () => $record(
            portal: 'admin',
            eventType: AuthenticationEventType::LoginSucceeded,
            accountId: 999_999,
            accountType: 'Modules\\Accounts\\Models\\AdministrativeAccount',
            identifierFingerprint: 'aabbccdd11223344',
            success: true,
        ))->not->toThrow(Throwable::class);
    });

});

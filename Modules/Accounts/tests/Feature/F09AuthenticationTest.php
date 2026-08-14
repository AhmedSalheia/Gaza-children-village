<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);
use Illuminate\Support\Facades\Hash;
use Modules\Accounts\Actions\ActivateAccount;
use Modules\Accounts\Actions\LockAccount;
use Modules\Accounts\Actions\RevokeAccount;
use Modules\Accounts\Actions\SuspendAccount;
use Modules\Accounts\Enums\AccountStatus;
use Modules\Accounts\Models\AdministrativeAccount;
use Modules\Accounts\Models\GuardianAccount;
use Modules\Accounts\Models\StaffAccount;

describe('F09 authentication — correct guard per account type', function (): void {

    it('administrative account authenticates via admin guard', function (): void {
        $account = AdministrativeAccount::factory()->active()->create(['password' => Hash::make('secret')]);

        $result = Auth::guard('admin')->attempt([
            'username' => $account->username,
            'password' => 'secret',
        ]);

        expect($result)->toBeTrue();
        expect(Auth::guard('admin')->user())->toBeInstanceOf(AdministrativeAccount::class);
        expect(Auth::guard('admin')->id())->toBe($account->id);
    });

    it('staff account authenticates via staff guard', function (): void {
        $account = StaffAccount::factory()->active()->create(['password' => Hash::make('secret')]);

        $result = Auth::guard('staff')->attempt([
            'username' => $account->username,
            'password' => 'secret',
        ]);

        expect($result)->toBeTrue();
        expect(Auth::guard('staff')->user())->toBeInstanceOf(StaffAccount::class);
    });

    it('guardian account authenticates via guardian guard', function (): void {
        $account = GuardianAccount::factory()->active()->create(['password' => Hash::make('secret')]);

        $result = Auth::guard('guardian')->attempt([
            'login_identifier' => $account->login_identifier,
            'password' => 'secret',
        ]);

        expect($result)->toBeTrue();
        expect(Auth::guard('guardian')->user())->toBeInstanceOf(GuardianAccount::class);
    });

});

describe('F09 authentication — cross-guard rejection', function (): void {

    it('administrative account cannot authenticate via staff guard', function (): void {
        $account = AdministrativeAccount::factory()->active()->create(['password' => Hash::make('secret')]);

        $result = Auth::guard('staff')->attempt([
            'username' => $account->username,
            'password' => 'secret',
        ]);

        expect($result)->toBeFalse();
        expect(Auth::guard('staff')->user())->toBeNull();
    });

    it('administrative account cannot authenticate via guardian guard', function (): void {
        $account = AdministrativeAccount::factory()->active()->create(['password' => Hash::make('secret')]);

        $result = Auth::guard('guardian')->attempt([
            'login_identifier' => $account->username,
            'password' => 'secret',
        ]);

        expect($result)->toBeFalse();
    });

    it('staff account cannot authenticate via admin guard', function (): void {
        $account = StaffAccount::factory()->active()->create(['password' => Hash::make('secret')]);

        $result = Auth::guard('admin')->attempt([
            'username' => $account->username,
            'password' => 'secret',
        ]);

        expect($result)->toBeFalse();
    });

    it('guardian account cannot authenticate via admin guard', function (): void {
        $account = GuardianAccount::factory()->active()->create(['password' => Hash::make('secret')]);

        $result = Auth::guard('admin')->attempt([
            'username' => $account->login_identifier,
            'password' => 'secret',
        ]);

        expect($result)->toBeFalse();
    });

    it('guardian account cannot authenticate via staff guard', function (): void {
        $account = GuardianAccount::factory()->active()->create(['password' => Hash::make('secret')]);

        $result = Auth::guard('staff')->attempt([
            'username' => $account->login_identifier,
            'password' => 'secret',
        ]);

        expect($result)->toBeFalse();
    });

});

describe('F09 authentication — lifecycle enforcement', function (): void {

    it('pending account cannot authenticate', function (): void {
        $account = AdministrativeAccount::factory()->create(['password' => Hash::make('secret')]); // pending by default

        $result = Auth::guard('admin')->attempt([
            'username' => $account->username,
            'password' => 'secret',
        ]);

        expect($result)->toBeFalse();
    });

    it('suspended account cannot authenticate', function (): void {
        $account = AdministrativeAccount::factory()->suspended()->create(['password' => Hash::make('secret')]);

        $result = Auth::guard('admin')->attempt([
            'username' => $account->username,
            'password' => 'secret',
        ]);

        expect($result)->toBeFalse();
    });

    it('locked account cannot authenticate', function (): void {
        $account = AdministrativeAccount::factory()->locked()->create(['password' => Hash::make('secret')]);

        $result = Auth::guard('admin')->attempt([
            'username' => $account->username,
            'password' => 'secret',
        ]);

        expect($result)->toBeFalse();
    });

    it('revoked account cannot authenticate', function (): void {
        $account = AdministrativeAccount::factory()->revoked()->create(['password' => Hash::make('secret')]);

        $result = Auth::guard('admin')->attempt([
            'username' => $account->username,
            'password' => 'secret',
        ]);

        expect($result)->toBeFalse();
    });

    it('wrong password fails even for active account', function (): void {
        $account = AdministrativeAccount::factory()->active()->create(['password' => Hash::make('correct')]);

        $result = Auth::guard('admin')->attempt([
            'username' => $account->username,
            'password' => 'wrong',
        ]);

        expect($result)->toBeFalse();
    });

    it('account becoming non-active is rejected on subsequent session requests', function (): void {
        $account = AdministrativeAccount::factory()->active()->create();

        // Simulate an existing session: provider resolves by ID (what happens on each request)
        $resolvedUser = Auth::guard('admin')->getProvider()->retrieveById($account->id);
        expect($resolvedUser)->toBeInstanceOf(AdministrativeAccount::class);

        // Now suspend the account (simulating admin action between requests)
        app(SuspendAccount::class)($account);

        // On the next request, retrieveById must return null for non-active accounts
        $resolvedAfterSuspend = Auth::guard('admin')->getProvider()->retrieveById($account->id);
        expect($resolvedAfterSuspend)->toBeNull();
    });

    it('account becoming locked is rejected on subsequent session requests', function (): void {
        $account = AdministrativeAccount::factory()->active()->create();

        $resolvedBefore = Auth::guard('admin')->getProvider()->retrieveById($account->id);
        expect($resolvedBefore)->not->toBeNull();

        app(LockAccount::class)($account);

        $resolvedAfter = Auth::guard('admin')->getProvider()->retrieveById($account->id);
        expect($resolvedAfter)->toBeNull();
    });

    it('account becoming revoked is rejected on subsequent session requests', function (): void {
        $account = AdministrativeAccount::factory()->active()->create();

        app(RevokeAccount::class)($account);

        $resolvedAfter = Auth::guard('admin')->getProvider()->retrieveById($account->id);
        expect($resolvedAfter)->toBeNull();
    });

});

describe('F09 lifecycle actions', function (): void {

    it('ActivateAccount sets status to Active and records timestamp', function (): void {
        $account = AdministrativeAccount::factory()->create(); // pending

        (new ActivateAccount)($account);
        $account->refresh();

        expect($account->status)->toBe(AccountStatus::Active);
        expect($account->activated_at)->not->toBeNull();
    });

    it('SuspendAccount sets status to Suspended and records timestamp', function (): void {
        $account = AdministrativeAccount::factory()->active()->create();

        app(SuspendAccount::class)($account);
        $account->refresh();

        expect($account->status)->toBe(AccountStatus::Suspended);
        expect($account->suspended_at)->not->toBeNull();
    });

    it('LockAccount sets status to Locked and records timestamp', function (): void {
        $account = AdministrativeAccount::factory()->active()->create();

        app(LockAccount::class)($account);
        $account->refresh();

        expect($account->status)->toBe(AccountStatus::Locked);
        expect($account->locked_at)->not->toBeNull();
    });

    it('RevokeAccount sets status to Revoked and records timestamp', function (): void {
        $account = AdministrativeAccount::factory()->active()->create();

        app(RevokeAccount::class)($account);
        $account->refresh();

        expect($account->status)->toBe(AccountStatus::Revoked);
        expect($account->revoked_at)->not->toBeNull();
    });

    it('lifecycle actions apply to staff accounts', function (): void {
        $account = StaffAccount::factory()->create();
        (new ActivateAccount)($account);
        expect($account->status)->toBe(AccountStatus::Active);
    });

    it('lifecycle actions apply to guardian accounts', function (): void {
        $account = GuardianAccount::factory()->create();
        (new ActivateAccount)($account);
        expect($account->status)->toBe(AccountStatus::Active);
    });

});

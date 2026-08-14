<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);
use Modules\Accounts\Enums\AccountStatus;
use Modules\Accounts\Models\AdministrativeAccount;
use Modules\Accounts\Models\GuardianAccount;
use Modules\Accounts\Models\StaffAccount;

describe('F09 account model behavior', function (): void {

    describe('password hashing and serialization', function (): void {

        it('hashes password on create — administrative account', function (): void {
            $account = AdministrativeAccount::factory()->create(['password' => 'plaintext_secret']);

            expect($account->password)->not->toBe('plaintext_secret');
            expect(Hash::check('plaintext_secret', $account->password))->toBeTrue();
        });

        it('hashes password on create — staff account', function (): void {
            $account = StaffAccount::factory()->create(['password' => 'plaintext_secret']);

            expect($account->password)->not->toBe('plaintext_secret');
            expect(Hash::check('plaintext_secret', $account->password))->toBeTrue();
        });

        it('hashes password on create — guardian account', function (): void {
            $account = GuardianAccount::factory()->create(['password' => 'plaintext_secret']);

            expect($account->password)->not->toBe('plaintext_secret');
            expect(Hash::check('plaintext_secret', $account->password))->toBeTrue();
        });

        it('hides password from toArray — administrative account', function (): void {
            $account = AdministrativeAccount::factory()->create();
            expect(array_key_exists('password', $account->toArray()))->toBeFalse();
        });

        it('hides password from toArray — staff account', function (): void {
            $account = StaffAccount::factory()->create();
            expect(array_key_exists('password', $account->toArray()))->toBeFalse();
        });

        it('hides password from toArray — guardian account', function (): void {
            $account = GuardianAccount::factory()->create();
            expect(array_key_exists('password', $account->toArray()))->toBeFalse();
        });

        it('hides password from json serialization — administrative account', function (): void {
            $account = AdministrativeAccount::factory()->create();
            $decoded = json_decode($account->toJson(), true);
            expect(array_key_exists('password', $decoded))->toBeFalse();
        });

        it('hides password from json serialization — staff account', function (): void {
            $account = StaffAccount::factory()->create();
            $decoded = json_decode($account->toJson(), true);
            expect(array_key_exists('password', $decoded))->toBeFalse();
        });

        it('hides password from json serialization — guardian account', function (): void {
            $account = GuardianAccount::factory()->create();
            $decoded = json_decode($account->toJson(), true);
            expect(array_key_exists('password', $decoded))->toBeFalse();
        });

        it('hides remember_token from serialization', function (): void {
            $account = AdministrativeAccount::factory()->create();
            expect(array_key_exists('remember_token', $account->toArray()))->toBeFalse();
        });

    });

    describe('login identifier normalization', function (): void {

        it('normalizes username to lowercase on create — administrative account', function (): void {
            $account = AdministrativeAccount::factory()->create(['username' => 'AdminUser']);
            expect($account->username)->toBe('adminuser');
            expect($account->fresh()->username)->toBe('adminuser');
        });

        it('normalizes username to lowercase on create — staff account', function (): void {
            $account = StaffAccount::factory()->create(['username' => 'StaffUser123']);
            expect($account->username)->toBe('staffuser123');
        });

        it('normalizes login_identifier to lowercase on create — guardian account', function (): void {
            $account = GuardianAccount::factory()->create(['login_identifier' => 'G-123ABC456']);
            expect($account->login_identifier)->toBe('g-123abc456');
        });

        it('trims whitespace from username on create', function (): void {
            $account = AdministrativeAccount::factory()->create(['username' => '  admin  ']);
            expect($account->username)->toBe('admin');
        });

    });

    describe('status enum casting', function (): void {

        it('casts status to AccountStatus enum', function (): void {
            $account = AdministrativeAccount::factory()->create(['status' => 'active']);
            expect($account->status)->toBeInstanceOf(AccountStatus::class);
            expect($account->status)->toBe(AccountStatus::Active);
        });

        it('casts all status values correctly', function (): void {
            foreach (AccountStatus::cases() as $case) {
                $account = AdministrativeAccount::factory()->create(['status' => $case->value]);
                $account->refresh();
                expect($account->status)->toBe($case);
            }
        });

    });

    describe('lifecycle timestamps', function (): void {

        it('activated_at is set by active factory state', function (): void {
            $account = AdministrativeAccount::factory()->active()->create();
            expect($account->activated_at)->not->toBeNull();
        });

        it('suspended_at is set by suspended factory state', function (): void {
            $account = AdministrativeAccount::factory()->suspended()->create();
            expect($account->suspended_at)->not->toBeNull();
        });

        it('locked_at is set by locked factory state', function (): void {
            $account = AdministrativeAccount::factory()->locked()->create();
            expect($account->locked_at)->not->toBeNull();
        });

        it('revoked_at is set by revoked factory state', function (): void {
            $account = AdministrativeAccount::factory()->revoked()->create();
            expect($account->revoked_at)->not->toBeNull();
        });

        it('lifecycle timestamps are null for pending accounts', function (): void {
            $account = AdministrativeAccount::factory()->create(); // pending by default
            expect($account->activated_at)->toBeNull();
            expect($account->suspended_at)->toBeNull();
            expect($account->locked_at)->toBeNull();
            expect($account->revoked_at)->toBeNull();
        });

    });

});

describe('AccountStatus lifecycle rules', function (): void {

    it('only Active status can authenticate', function (): void {
        expect(AccountStatus::Active->canAuthenticate())->toBeTrue();
    });

    it('Pending status cannot authenticate', function (): void {
        expect(AccountStatus::Pending->canAuthenticate())->toBeFalse();
    });

    it('Suspended status cannot authenticate', function (): void {
        expect(AccountStatus::Suspended->canAuthenticate())->toBeFalse();
    });

    it('Locked status cannot authenticate', function (): void {
        expect(AccountStatus::Locked->canAuthenticate())->toBeFalse();
    });

    it('Revoked status cannot authenticate', function (): void {
        expect(AccountStatus::Revoked->canAuthenticate())->toBeFalse();
    });

    it('all five statuses are defined', function (): void {
        $values = array_map(fn ($c) => $c->value, AccountStatus::cases());
        expect($values)->toContain('pending');
        expect($values)->toContain('active');
        expect($values)->toContain('suspended');
        expect($values)->toContain('locked');
        expect($values)->toContain('revoked');
    });

});

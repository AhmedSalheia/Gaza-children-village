<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Accounts\Actions\CompletePasswordSetup;
use Modules\Accounts\Actions\RequestAccountSetup;
use Modules\Accounts\Actions\RequestPasswordRecovery;
use Modules\Accounts\Contracts\ChallengeDelivery;
use Modules\Accounts\Data\PortalAuthConfig;
use Modules\Accounts\Enums\ChallengePurpose;
use Modules\Accounts\Enums\ChallengeValidationResult;
use Modules\Accounts\Models\AccountVerificationChallenge;
use Modules\Accounts\Models\AdministrativeAccount;
use Modules\Accounts\Models\AuthenticationEvent;
use Modules\Accounts\Models\GuardianAccount;
use Modules\Accounts\Models\StaffAccount;
use Modules\Accounts\Services\FakeChallengeDelivery;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Anti-enumeration: identical public responses
// ---------------------------------------------------------------------------

describe('anti-enumeration public responses', function (): void {

    it('request account setup returns void for existing identifier', function (): void {
        app()->instance(ChallengeDelivery::class, new FakeChallengeDelivery);
        $account = AdministrativeAccount::factory()->create(['status' => 'pending']);
        $result = app(RequestAccountSetup::class)(PortalAuthConfig::admin(), $account->username);
        expect($result)->toBeNull();
    });

    it('request account setup returns void for unknown identifier', function (): void {
        app()->instance(ChallengeDelivery::class, new FakeChallengeDelivery);
        $result = app(RequestAccountSetup::class)(PortalAuthConfig::admin(), 'completely_unknown_xyz');
        expect($result)->toBeNull();
    });

    it('request password recovery returns void for existing identifier', function (): void {
        app()->instance(ChallengeDelivery::class, new FakeChallengeDelivery);
        $account = AdministrativeAccount::factory()->active()->create();
        $result = app(RequestPasswordRecovery::class)(PortalAuthConfig::admin(), $account->username);
        expect($result)->toBeNull();
    });

    it('request password recovery returns void for unknown identifier', function (): void {
        app()->instance(ChallengeDelivery::class, new FakeChallengeDelivery);
        $result = app(RequestPasswordRecovery::class)(PortalAuthConfig::admin(), 'unknown_xyz');
        expect($result)->toBeNull();
    });

    it('request password recovery returns void for suspended account', function (): void {
        app()->instance(ChallengeDelivery::class, new FakeChallengeDelivery);
        $account = AdministrativeAccount::factory()->create(['status' => 'suspended']);
        $result = app(RequestPasswordRecovery::class)(PortalAuthConfig::admin(), $account->username);
        expect($result)->toBeNull();
    });

    it('request password recovery returns void for revoked account', function (): void {
        app()->instance(ChallengeDelivery::class, new FakeChallengeDelivery);
        $account = AdministrativeAccount::factory()->create(['status' => 'revoked']);
        $result = app(RequestPasswordRecovery::class)(PortalAuthConfig::admin(), $account->username);
        expect($result)->toBeNull();
    });

});

// ---------------------------------------------------------------------------
// Guardian self-service is disabled
// ---------------------------------------------------------------------------

describe('guardian self-service recovery', function (): void {

    it('request account setup silently no-ops for guardian portal', function (): void {
        $fake = new FakeChallengeDelivery;
        app()->instance(ChallengeDelivery::class, $fake);

        $guardian = GuardianAccount::factory()->create(['status' => 'pending']);
        app(RequestAccountSetup::class)(PortalAuthConfig::guardian(), $guardian->login_identifier);

        expect($fake->count())->toBe(0);
        expect(AccountVerificationChallenge::count())->toBe(0);
    });

    it('request password recovery silently no-ops for guardian portal', function (): void {
        $fake = new FakeChallengeDelivery;
        app()->instance(ChallengeDelivery::class, $fake);

        $guardian = GuardianAccount::factory()->active()->create();
        app(RequestPasswordRecovery::class)(PortalAuthConfig::guardian(), $guardian->login_identifier);

        expect($fake->count())->toBe(0);
        expect(AccountVerificationChallenge::count())->toBe(0);
    });

});

// ---------------------------------------------------------------------------
// Password reset completes correctly
// ---------------------------------------------------------------------------

describe('complete password setup', function (): void {

    it('valid token rotates the password', function (): void {
        $fake = new FakeChallengeDelivery;
        app()->instance(ChallengeDelivery::class, $fake);

        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('OldPassword1'),
        ]);
        $config = PortalAuthConfig::admin();

        app(RequestPasswordRecovery::class)($config, $account->username);
        $token = $fake->lastToken();

        $result = app(CompletePasswordSetup::class)(
            $account,
            $config,
            ChallengePurpose::PasswordReset,
            $token,
            'NewPassword2!',
        );

        expect($result)->toBe(ChallengeValidationResult::Valid);

        $account->refresh();
        expect(Hash::check('NewPassword2!', $account->password))->toBeTrue();
        expect(Hash::check('OldPassword1', $account->password))->toBeFalse();
    });

    it('password reset revokes existing sessions', function (): void {
        $fake = new FakeChallengeDelivery;
        app()->instance(ChallengeDelivery::class, $fake);

        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('OldPassword1'),
        ]);
        $config = PortalAuthConfig::admin();

        $account->refresh(); // ensure auth_version DB default is loaded
        $versionBefore = $account->auth_version;

        app(RequestPasswordRecovery::class)($config, $account->username);
        $token = $fake->lastToken();

        app(CompletePasswordSetup::class)(
            $account,
            $config,
            ChallengePurpose::PasswordReset,
            $token,
            'NewPassword2!',
        );

        $account->refresh();
        expect($account->auth_version)->toBeGreaterThan($versionBefore);
    });

    it('password reset revokes all remaining open challenges for the account', function (): void {
        $fake = new FakeChallengeDelivery;
        app()->instance(ChallengeDelivery::class, $fake);

        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('OldPassword1'),
        ]);
        $config = PortalAuthConfig::admin();

        // Issue two challenges (setup + reset)
        app(RequestAccountSetup::class)($config, $account->username);
        app(RequestPasswordRecovery::class)($config, $account->username);
        $resetToken = $fake->lastToken();

        expect(AccountVerificationChallenge::whereNull('revoked_at')->whereNull('consumed_at')->count())->toBeGreaterThan(0);

        app(CompletePasswordSetup::class)(
            $account,
            $config,
            ChallengePurpose::PasswordReset,
            $resetToken,
            'NewPassword2!',
        );

        // All challenges should now be consumed or revoked
        $open = AccountVerificationChallenge::whereNull('revoked_at')->whereNull('consumed_at')->count();
        expect($open)->toBe(0);
    });

    it('invalid token does not change the password', function (): void {
        $fake = new FakeChallengeDelivery;
        app()->instance(ChallengeDelivery::class, $fake);

        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('OldPassword1'),
        ]);
        $config = PortalAuthConfig::admin();

        app(RequestPasswordRecovery::class)($config, $account->username);

        $result = app(CompletePasswordSetup::class)(
            $account,
            $config,
            ChallengePurpose::PasswordReset,
            'completely_wrong_token',
            'NewPassword2!',
        );

        expect($result)->toBe(ChallengeValidationResult::InvalidToken);

        $account->refresh();
        expect(Hash::check('OldPassword1', $account->password))->toBeTrue();
    });

    it('password reset records a security event', function (): void {
        $fake = new FakeChallengeDelivery;
        app()->instance(ChallengeDelivery::class, $fake);

        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('OldPassword1'),
        ]);
        $config = PortalAuthConfig::admin();

        app(RequestPasswordRecovery::class)($config, $account->username);
        $token = $fake->lastToken();

        app(CompletePasswordSetup::class)(
            $account,
            $config,
            ChallengePurpose::PasswordReset,
            $token,
            'NewPassword2!',
        );

        $event = AuthenticationEvent::where('event_type', 'password_reset_completed')->first();
        expect($event)->not->toBeNull();
        expect($event->portal)->toBe('admin');
        expect($event->account_id)->toBe($account->id);
    });

    it('security event for password reset contains no secret payload', function (): void {
        $fake = new FakeChallengeDelivery;
        app()->instance(ChallengeDelivery::class, $fake);

        $account = AdministrativeAccount::factory()->active()->create([
            'password' => Hash::make('OldPassword1'),
        ]);
        $config = PortalAuthConfig::admin();

        app(RequestPasswordRecovery::class)($config, $account->username);
        $token = $fake->lastToken();

        app(CompletePasswordSetup::class)(
            $account,
            $config,
            ChallengePurpose::PasswordReset,
            $token,
            'NewPassword2!',
        );

        $event = AuthenticationEvent::where('event_type', 'password_reset_completed')->first();
        $json = $event->toJson();

        // The plaintext token must not appear in any event column
        expect($json)->not->toContain($token);
        expect($json)->not->toContain('NewPassword2!');
    });

});

// ---------------------------------------------------------------------------
// No plaintext tokens or passwords in events
// ---------------------------------------------------------------------------

describe('privacy: no secrets in events or challenges', function (): void {

    it('account_verification_challenges table contains no plaintext tokens', function (): void {
        $fake = new FakeChallengeDelivery;
        app()->instance(ChallengeDelivery::class, $fake);

        $account = AdministrativeAccount::factory()->active()->create();
        app(RequestPasswordRecovery::class)(PortalAuthConfig::admin(), $account->username);

        $token = $fake->lastToken();
        expect($token)->not->toBeNull();

        // The plaintext must not be stored in any column
        $row = DB::table('account_verification_challenges')->first();
        $rowJson = json_encode((array) $row);

        expect($rowJson)->not->toContain($token);
    });

    it('staff portal setup and recovery work independently', function (): void {
        $fake = new FakeChallengeDelivery;
        app()->instance(ChallengeDelivery::class, $fake);

        $staff = StaffAccount::factory()->active()->create([
            'password' => Hash::make('OldStaff1'),
        ]);
        $config = PortalAuthConfig::staff();

        app(RequestPasswordRecovery::class)($config, $staff->username);
        $token = $fake->lastToken();

        expect($token)->not->toBeNull();

        $result = app(CompletePasswordSetup::class)(
            $staff,
            $config,
            ChallengePurpose::PasswordReset,
            $token,
            'NewStaff2!',
        );

        expect($result)->toBe(ChallengeValidationResult::Valid);
    });

});

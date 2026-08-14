<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Accounts\Actions\IssueAccountChallenge;
use Modules\Accounts\Actions\LockAccount;
use Modules\Accounts\Actions\RevokeAccount;
use Modules\Accounts\Actions\RevokeAccountChallenges;
use Modules\Accounts\Actions\SuspendAccount;
use Modules\Accounts\Actions\ValidateChallenge;
use Modules\Accounts\Contracts\ChallengeDelivery;
use Modules\Accounts\Data\PortalAuthConfig;
use Modules\Accounts\Enums\ChallengePurpose;
use Modules\Accounts\Enums\ChallengeValidationResult;
use Modules\Accounts\Models\AccountVerificationChallenge;
use Modules\Accounts\Models\AdministrativeAccount;
use Modules\Accounts\Models\StaffAccount;
use Modules\Accounts\Services\FakeChallengeDelivery;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function adminAccountWithPassword(string $password = 'Secret123!'): AdministrativeAccount
{
    return AdministrativeAccount::factory()->active()->create([
        'password' => Hash::make($password),
    ]);
}

function fakeChallengeDelivery(): FakeChallengeDelivery
{
    $fake = new FakeChallengeDelivery;
    app()->instance(ChallengeDelivery::class, $fake);

    return $fake;
}

// ---------------------------------------------------------------------------
// Token is stored hashed
// ---------------------------------------------------------------------------

describe('challenge token security', function (): void {

    it('stores the token hash, not the plaintext', function (): void {
        $fake = fakeChallengeDelivery();
        $account = adminAccountWithPassword();
        $config = PortalAuthConfig::admin();

        app(IssueAccountChallenge::class)($account, $config, ChallengePurpose::InitialPasswordSetup);

        $plaintext = $fake->lastToken();
        expect($plaintext)->not->toBeNull();

        $challenge = AccountVerificationChallenge::first();
        expect($challenge)->not->toBeNull();

        // Plaintext must not appear in any stored column
        expect($challenge->token_hash)->not->toBe($plaintext);

        // The hash must be a SHA-256 hex string
        expect($challenge->token_hash)->toBe(hash('sha256', $plaintext));
    });

    it('each issued token is unique', function (): void {
        $fake = fakeChallengeDelivery();
        $config = PortalAuthConfig::admin();

        $a = adminAccountWithPassword();
        $b = adminAccountWithPassword();

        app(IssueAccountChallenge::class)($a, $config, ChallengePurpose::PasswordReset);
        app(IssueAccountChallenge::class)($b, $config, ChallengePurpose::PasswordReset);

        $tokens = collect($fake->deliveries())->pluck('token')->all();
        expect(array_unique($tokens))->toHaveCount(2);
    });

    it('token_hash is hidden from model serialization', function (): void {
        fakeChallengeDelivery();
        $account = adminAccountWithPassword();
        $config = PortalAuthConfig::admin();

        app(IssueAccountChallenge::class)($account, $config, ChallengePurpose::InitialPasswordSetup);

        $challenge = AccountVerificationChallenge::first();
        $json = $challenge->toJson();
        expect($json)->not->toContain('token_hash');
    });

});

// ---------------------------------------------------------------------------
// Portal / account / purpose binding
// ---------------------------------------------------------------------------

describe('challenge binding constraints', function (): void {

    it('challenge is bound to the correct portal', function (): void {
        fakeChallengeDelivery();
        $account = adminAccountWithPassword();

        app(IssueAccountChallenge::class)($account, PortalAuthConfig::admin(), ChallengePurpose::PasswordReset);

        $challenge = AccountVerificationChallenge::first();
        expect($challenge->portal)->toBe('admin');
    });

    it('challenge is bound to the correct account', function (): void {
        fakeChallengeDelivery();
        $account = adminAccountWithPassword();

        app(IssueAccountChallenge::class)($account, PortalAuthConfig::admin(), ChallengePurpose::PasswordReset);

        $challenge = AccountVerificationChallenge::first();
        expect($challenge->account_id)->toBe($account->id);
        expect($challenge->account_type)->toBe($account::class);
    });

    it('challenge is bound to the correct purpose', function (): void {
        fakeChallengeDelivery();
        $account = adminAccountWithPassword();

        app(IssueAccountChallenge::class)($account, PortalAuthConfig::admin(), ChallengePurpose::PasswordReset);

        $challenge = AccountVerificationChallenge::first();
        expect($challenge->purpose)->toBe(ChallengePurpose::PasswordReset);
    });

    it('token for one purpose does not validate for another purpose', function (): void {
        $fake = fakeChallengeDelivery();
        $account = adminAccountWithPassword();
        $config = PortalAuthConfig::admin();

        app(IssueAccountChallenge::class)($account, $config, ChallengePurpose::PasswordReset);
        $token = $fake->lastToken();

        $result = app(ValidateChallenge::class)(
            $account,
            $config,
            ChallengePurpose::InitialPasswordSetup, // wrong purpose
            $token,
        );

        expect($result)->toBe(ChallengeValidationResult::NotFound);
    });

    it('token for one portal does not validate for another portal', function (): void {
        $fake = fakeChallengeDelivery();
        $adminAccount = adminAccountWithPassword();
        $staffAccount = StaffAccount::factory()->active()->create();

        app(IssueAccountChallenge::class)(
            $adminAccount,
            PortalAuthConfig::admin(),
            ChallengePurpose::PasswordReset,
        );
        $token = $fake->lastToken();

        // Try to use the admin token against a staff account
        $result = app(ValidateChallenge::class)(
            $staffAccount,
            PortalAuthConfig::staff(),
            ChallengePurpose::PasswordReset,
            $token,
        );

        expect($result)->toBe(ChallengeValidationResult::NotFound);
    });

});

// ---------------------------------------------------------------------------
// Expiry
// ---------------------------------------------------------------------------

describe('challenge expiry', function (): void {

    it('challenge expires after the configured lifetime', function (): void {
        $fake = fakeChallengeDelivery();
        $account = adminAccountWithPassword();
        $config = PortalAuthConfig::admin();

        app(IssueAccountChallenge::class)($account, $config, ChallengePurpose::PasswordReset);
        $token = $fake->lastToken();

        // Travel past the lifetime
        $this->travel(31)->minutes();

        $result = app(ValidateChallenge::class)($account, $config, ChallengePurpose::PasswordReset, $token);

        expect($result)->toBe(ChallengeValidationResult::Expired);
    });

    it('challenge is valid just before expiry', function (): void {
        $fake = fakeChallengeDelivery();
        $account = adminAccountWithPassword();
        $config = PortalAuthConfig::admin();

        app(IssueAccountChallenge::class)($account, $config, ChallengePurpose::PasswordReset);
        $token = $fake->lastToken();

        $this->travel(29)->minutes();

        $result = app(ValidateChallenge::class)($account, $config, ChallengePurpose::PasswordReset, $token);

        expect($result)->toBe(ChallengeValidationResult::Valid);
    });

});

// ---------------------------------------------------------------------------
// Single-use
// ---------------------------------------------------------------------------

describe('challenge single-use', function (): void {

    it('a consumed challenge cannot be used again', function (): void {
        $fake = fakeChallengeDelivery();
        $account = adminAccountWithPassword();
        $config = PortalAuthConfig::admin();

        app(IssueAccountChallenge::class)($account, $config, ChallengePurpose::PasswordReset);
        $token = $fake->lastToken();

        $first = app(ValidateChallenge::class)($account, $config, ChallengePurpose::PasswordReset, $token);
        expect($first)->toBe(ChallengeValidationResult::Valid);

        $second = app(ValidateChallenge::class)($account, $config, ChallengePurpose::PasswordReset, $token);
        expect($second)->toBe(ChallengeValidationResult::AlreadyConsumed);
    });

});

// ---------------------------------------------------------------------------
// Attempt limiting
// ---------------------------------------------------------------------------

describe('attempt limiting', function (): void {

    it('challenge becomes exhausted after max attempts', function (): void {
        $fake = fakeChallengeDelivery();
        $account = adminAccountWithPassword();
        $config = PortalAuthConfig::admin();

        app(IssueAccountChallenge::class)($account, $config, ChallengePurpose::PasswordReset);
        $token = $fake->lastToken();

        $maxAttempts = (int) config('account-challenges.challenge.max_attempts', 5);

        $results = [];
        for ($i = 0; $i < $maxAttempts; $i++) {
            $results[] = app(ValidateChallenge::class)($account, $config, ChallengePurpose::PasswordReset, 'wrong_token');
        }

        // The last call should return Exhausted
        expect(end($results))->toBe(ChallengeValidationResult::Exhausted);

        // Correct token is also rejected after exhaustion
        $final = app(ValidateChallenge::class)($account, $config, ChallengePurpose::PasswordReset, $token);
        expect($final)->toBe(ChallengeValidationResult::Exhausted);
    });

    it('correct token succeeds before max attempts are reached', function (): void {
        $fake = fakeChallengeDelivery();
        $account = adminAccountWithPassword();
        $config = PortalAuthConfig::admin();

        app(IssueAccountChallenge::class)($account, $config, ChallengePurpose::PasswordReset);
        $token = $fake->lastToken();

        // One bad attempt
        app(ValidateChallenge::class)($account, $config, ChallengePurpose::PasswordReset, 'wrong');

        // Correct token still works
        $result = app(ValidateChallenge::class)($account, $config, ChallengePurpose::PasswordReset, $token);
        expect($result)->toBe(ChallengeValidationResult::Valid);
    });

});

// ---------------------------------------------------------------------------
// Revocation
// ---------------------------------------------------------------------------

describe('challenge revocation', function (): void {

    it('a revoked challenge cannot be used', function (): void {
        $fake = fakeChallengeDelivery();
        $account = adminAccountWithPassword();
        $config = PortalAuthConfig::admin();

        app(IssueAccountChallenge::class)($account, $config, ChallengePurpose::PasswordReset);
        $token = $fake->lastToken();

        app(RevokeAccountChallenges::class)($account, $config->portal);

        $result = app(ValidateChallenge::class)($account, $config, ChallengePurpose::PasswordReset, $token);
        expect($result)->toBe(ChallengeValidationResult::Revoked);
    });

    it('issuing a new challenge revokes the previous one for the same purpose', function (): void {
        $fake = fakeChallengeDelivery();
        $account = adminAccountWithPassword();
        $config = PortalAuthConfig::admin();

        app(IssueAccountChallenge::class)($account, $config, ChallengePurpose::PasswordReset);
        $firstToken = $fake->lastToken();

        app(IssueAccountChallenge::class)($account, $config, ChallengePurpose::PasswordReset);
        $secondToken = $fake->lastToken();

        // First token is now revoked
        $firstResult = app(ValidateChallenge::class)($account, $config, ChallengePurpose::PasswordReset, $firstToken);
        expect($firstResult)->toBe(ChallengeValidationResult::Revoked);

        // Second token is still valid
        $secondResult = app(ValidateChallenge::class)($account, $config, ChallengePurpose::PasswordReset, $secondToken);
        expect($secondResult)->toBe(ChallengeValidationResult::Valid);
    });

    it('account suspension revokes outstanding challenges', function (): void {
        $fake = fakeChallengeDelivery();
        $account = adminAccountWithPassword();
        $config = PortalAuthConfig::admin();

        app(IssueAccountChallenge::class)($account, $config, ChallengePurpose::PasswordReset);
        $token = $fake->lastToken();

        app(SuspendAccount::class)($account);

        $result = app(ValidateChallenge::class)($account, $config, ChallengePurpose::PasswordReset, $token);
        expect($result)->toBe(ChallengeValidationResult::Revoked);
    });

    it('account locking revokes outstanding challenges', function (): void {
        $fake = fakeChallengeDelivery();
        $account = adminAccountWithPassword();
        $config = PortalAuthConfig::admin();

        app(IssueAccountChallenge::class)($account, $config, ChallengePurpose::PasswordReset);
        $token = $fake->lastToken();

        app(LockAccount::class)($account);

        $result = app(ValidateChallenge::class)($account, $config, ChallengePurpose::PasswordReset, $token);
        expect($result)->toBe(ChallengeValidationResult::Revoked);
    });

    it('account revocation revokes outstanding challenges', function (): void {
        $fake = fakeChallengeDelivery();
        $account = adminAccountWithPassword();
        $config = PortalAuthConfig::admin();

        app(IssueAccountChallenge::class)($account, $config, ChallengePurpose::PasswordReset);
        $token = $fake->lastToken();

        app(RevokeAccount::class)($account);

        $result = app(ValidateChallenge::class)($account, $config, ChallengePurpose::PasswordReset, $token);
        expect($result)->toBe(ChallengeValidationResult::Revoked);
    });

});

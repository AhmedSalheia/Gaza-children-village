<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Modules\Accounts\Actions\RequestPasswordRecovery;
use Modules\Accounts\Contracts\ChallengeDelivery;
use Modules\Accounts\Data\PortalAuthConfig;
use Modules\Accounts\Enums\ChallengeValidationResult;
use Modules\Accounts\Models\AccountVerificationChallenge;
use Modules\Accounts\Models\GuardianAccount;
use Modules\Accounts\Services\FakeChallengeDelivery;
use Modules\Accounts\Services\NullChallengeDelivery;
use Modules\Accounts\Services\PasswordPolicy;

uses(RefreshDatabase::class);

describe('F11 architecture boundaries', function (): void {

    it('ChallengeDelivery contract is in the Accounts Contracts namespace', function (): void {
        expect(interface_exists('Modules\Accounts\Contracts\ChallengeDelivery'))->toBeTrue();
    });

    it('FakeChallengeDelivery implements ChallengeDelivery', function (): void {
        $fake = new FakeChallengeDelivery;
        expect($fake)->toBeInstanceOf(ChallengeDelivery::class);
    });

    it('NullChallengeDelivery implements ChallengeDelivery', function (): void {
        $null = new NullChallengeDelivery;
        expect($null)->toBeInstanceOf(ChallengeDelivery::class);
    });

    it('ChallengeDelivery binding resolves to NullChallengeDelivery by default', function (): void {
        // Reset to default (no test override)
        app()->forgetInstance(ChallengeDelivery::class);
        $resolved = app(ChallengeDelivery::class);
        expect($resolved)->toBeInstanceOf(NullChallengeDelivery::class);
    });

    it('PasswordPolicy is resolvable from the container', function (): void {
        $policy = app(PasswordPolicy::class);
        expect($policy)->toBeInstanceOf(PasswordPolicy::class);
    });

    it('ChallengeValidationResult enum has all required cases', function (): void {
        expect(ChallengeValidationResult::Valid)->toBeInstanceOf(ChallengeValidationResult::class);
        expect(ChallengeValidationResult::NotFound)->toBeInstanceOf(ChallengeValidationResult::class);
        expect(ChallengeValidationResult::Revoked)->toBeInstanceOf(ChallengeValidationResult::class);
        expect(ChallengeValidationResult::Expired)->toBeInstanceOf(ChallengeValidationResult::class);
        expect(ChallengeValidationResult::Exhausted)->toBeInstanceOf(ChallengeValidationResult::class);
        expect(ChallengeValidationResult::AlreadyConsumed)->toBeInstanceOf(ChallengeValidationResult::class);
    });

    it('no public recovery HTTP endpoints exist', function (): void {
        $routes = collect(Route::getRoutes())
            ->map(fn ($r) => $r->getName())
            ->filter()
            ->values();

        foreach (['admin.password', 'staff.password', 'guardian.password', 'password.request', 'password.reset'] as $name) {
            expect($routes->contains($name))->toBeFalse("Route '$name' must not exist in F11");
        }
    });

    it('guardian self-service recovery is not available', function (): void {
        $fake = new FakeChallengeDelivery;
        app()->instance(ChallengeDelivery::class, $fake);

        $guardian = GuardianAccount::factory()->active()->create();

        app(RequestPasswordRecovery::class)(
            PortalAuthConfig::guardian(),
            $guardian->login_identifier,
        );

        expect($fake->count())->toBe(0);
    });

    it('challenge table records exist only for admin and staff portals when guardian recovery is requested', function (): void {
        app()->instance(ChallengeDelivery::class, new FakeChallengeDelivery);
        $guardian = GuardianAccount::factory()->active()->create();

        app(RequestPasswordRecovery::class)(
            PortalAuthConfig::guardian(),
            $guardian->login_identifier,
        );

        expect(AccountVerificationChallenge::count())->toBe(0);
    });

});

<?php

declare(strict_types=1);

namespace Modules\Accounts\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Accounts\Contracts\ChallengeDelivery;
use Modules\Accounts\Data\PortalAuthConfig;
use Modules\Accounts\Enums\ChallengePurpose;
use SensitiveParameter;

/**
 * Test-only fake delivery implementation.
 *
 * Captures the plaintext token so that tests can assert the full challenge
 * flow without a real delivery channel. Never use outside of tests.
 *
 * Usage in tests:
 *
 *   $fake = new FakeChallengeDelivery();
 *   $this->app->instance(ChallengeDelivery::class, $fake);
 *
 *   // Trigger the action under test …
 *
 *   $token = $fake->lastToken();   // use to call CompletePasswordSetup
 *   expect($fake->deliveries())->toHaveCount(1);
 */
final class FakeChallengeDelivery implements ChallengeDelivery
{
    /** @var list<array{account_id: int|string, portal: string, purpose: string, token: string}> */
    private array $captured = [];

    public function deliver(
        Authenticatable $account,
        PortalAuthConfig $config,
        ChallengePurpose $purpose,
        #[SensitiveParameter] string $plaintextToken,
    ): void {
        $this->captured[] = [
            'account_id' => $account->getAuthIdentifier(),
            'portal' => $config->portal,
            'purpose' => $purpose->value,
            'token' => $plaintextToken,
        ];
    }

    /** Return all captured deliveries. */
    public function deliveries(): array
    {
        return $this->captured;
    }

    /** Return the plaintext token from the most recent delivery, or null. */
    public function lastToken(): ?string
    {
        if ($this->captured === []) {
            return null;
        }

        return end($this->captured)['token'];
    }

    /** Return the number of deliveries captured so far. */
    public function count(): int
    {
        return count($this->captured);
    }

    /** Reset captured deliveries (e.g. between test phases). */
    public function reset(): void
    {
        $this->captured = [];
    }
}

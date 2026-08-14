<?php

declare(strict_types=1);

namespace Modules\Accounts\Actions;

use Illuminate\Foundation\Auth\User;
use Modules\Accounts\Data\PortalAuthConfig;
use Modules\Accounts\Enums\AccountStatus;

/**
 * Lock an account because of a security decision, denying authentication.
 *
 * Sets status to Locked and records the lock timestamp.
 * Applies to all three portal account types.
 *
 * Automatic lockout thresholds are NOT implemented in F09.
 * Locking via this action is an explicit administrative decision only.
 *
 * Outstanding verification challenges are revoked immediately so that a
 * locked account cannot complete password setup/reset.
 */
final class LockAccount
{
    public function __construct(
        private readonly RevokeAccountChallenges $revokeAccountChallenges,
    ) {}

    public function __invoke(User $account): void
    {
        $account->status = AccountStatus::Locked->value;
        $account->locked_at = now();
        $account->save();

        try {
            $config = PortalAuthConfig::fromAccount($account);
            ($this->revokeAccountChallenges)($account, $config->portal);
        } catch (\Throwable) {
            // Challenge revocation is best-effort — do not fail the lock.
        }
    }
}

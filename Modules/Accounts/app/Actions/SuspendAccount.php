<?php

declare(strict_types=1);

namespace Modules\Accounts\Actions;

use Illuminate\Foundation\Auth\User;
use Modules\Accounts\Data\PortalAuthConfig;
use Modules\Accounts\Enums\AccountStatus;

/**
 * Suspend an account, temporarily denying authentication.
 *
 * Sets status to Suspended and records the suspension timestamp.
 * Applies to all three portal account types.
 *
 * Any existing session for the account will be rejected on the next request
 * because the lifecycle-aware provider returns null for non-active accounts.
 *
 * Outstanding verification challenges are revoked immediately so that a
 * suspended account cannot complete password setup/reset while suspended.
 */
final class SuspendAccount
{
    public function __construct(
        private readonly RevokeAccountChallenges $revokeAccountChallenges,
    ) {}

    public function __invoke(User $account): void
    {
        $account->status = AccountStatus::Suspended->value;
        $account->suspended_at = now();
        $account->save();

        try {
            $config = PortalAuthConfig::fromAccount($account);
            ($this->revokeAccountChallenges)($account, $config->portal);
        } catch (\Throwable) {
            // Challenge revocation is best-effort — do not fail the suspension.
        }
    }
}

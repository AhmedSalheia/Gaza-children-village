<?php

declare(strict_types=1);

namespace Modules\Accounts\Actions;

use Illuminate\Foundation\Auth\User;
use Modules\Accounts\Data\PortalAuthConfig;
use Modules\Accounts\Enums\AccountStatus;

/**
 * Permanently revoke an account.
 *
 * Sets status to Revoked and records the revocation timestamp.
 * Applies to all three portal account types.
 *
 * Revocation is permanent under normal operating rules. A future explicitly
 * approved recovery rule is required to restore access; no self-service
 * recovery path is implied by this action.
 *
 * Outstanding verification challenges are revoked immediately so that the
 * account cannot complete password setup/reset after revocation.
 */
final class RevokeAccount
{
    public function __construct(
        private readonly RevokeAccountChallenges $revokeAccountChallenges,
    ) {}

    public function __invoke(User $account): void
    {
        $account->status = AccountStatus::Revoked->value;
        $account->revoked_at = now();
        $account->save();

        try {
            $config = PortalAuthConfig::fromAccount($account);
            ($this->revokeAccountChallenges)($account, $config->portal);
        } catch (\Throwable) {
            // Challenge revocation is best-effort — do not fail the revocation.
        }
    }
}

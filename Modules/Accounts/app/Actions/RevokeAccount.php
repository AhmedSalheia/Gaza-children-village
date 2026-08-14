<?php

declare(strict_types=1);

namespace Modules\Accounts\Actions;

use Illuminate\Foundation\Auth\User;
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
 */
final class RevokeAccount
{
    public function __invoke(User $account): void
    {
        $account->status = AccountStatus::Revoked->value;
        $account->revoked_at = now();
        $account->save();
    }
}

<?php

declare(strict_types=1);

namespace Modules\Accounts\Actions;

use Illuminate\Foundation\Auth\User;
use Modules\Accounts\Enums\AccountStatus;

/**
 * Activate a pending account, permitting authentication.
 *
 * Sets status to Active and records the activation timestamp.
 * Applies to all three portal account types.
 */
final class ActivateAccount
{
    public function __invoke(User $account): void
    {
        $account->status = AccountStatus::Active->value;
        $account->activated_at = now();
        $account->save();
    }
}

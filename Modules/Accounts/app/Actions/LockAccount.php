<?php

declare(strict_types=1);

namespace Modules\Accounts\Actions;

use Illuminate\Foundation\Auth\User;
use Modules\Accounts\Enums\AccountStatus;

/**
 * Lock an account because of a security decision, denying authentication.
 *
 * Sets status to Locked and records the lock timestamp.
 * Applies to all three portal account types.
 *
 * Automatic lockout thresholds are NOT implemented in F09.
 * Locking via this action is an explicit administrative decision only.
 */
final class LockAccount
{
    public function __invoke(User $account): void
    {
        $account->status = AccountStatus::Locked->value;
        $account->locked_at = now();
        $account->save();
    }
}

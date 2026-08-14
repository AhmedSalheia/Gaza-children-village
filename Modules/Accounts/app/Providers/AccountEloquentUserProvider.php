<?php

declare(strict_types=1);

namespace Modules\Accounts\Providers;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Accounts\Enums\AccountStatus;

/**
 * Lifecycle-aware Eloquent provider for all three portal account types.
 *
 * Extends the standard EloquentUserProvider to enforce the central lifecycle rule:
 * only Active accounts may authenticate or retain an authenticated session.
 *
 * - retrieveById: called on every protected request after login to re-resolve the session
 *   user. Returns null for non-active accounts, causing the guard to treat the request
 *   as unauthenticated even if the session cookie is still valid.
 *
 * - validateCredentials: called during login attempt. Returns false for non-active accounts
 *   before password verification is attempted.
 */
final class AccountEloquentUserProvider extends EloquentUserProvider
{
    public function retrieveById(mixed $identifier): ?Authenticatable
    {
        $user = parent::retrieveById($identifier);

        if ($user === null) {
            return null;
        }

        /** @var AccountStatus $status */
        $status = $user->status;

        if (! $status->canAuthenticate()) {
            return null;
        }

        return $user;
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        /** @var AccountStatus $status */
        $status = $user->status;

        if (! $status->canAuthenticate()) {
            return false;
        }

        return parent::validateCredentials($user, $credentials);
    }
}

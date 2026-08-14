<?php

declare(strict_types=1);

namespace Modules\Accounts\Data;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Accounts\Models\AdministrativeAccount;
use Modules\Accounts\Models\GuardianAccount;
use Modules\Accounts\Models\StaffAccount;

/**
 * Immutable, portal-specific authentication configuration.
 *
 * Encapsulates all the values that differ between the three portals so that
 * AuthenticatePortalAccount, LogoutPortalAccount, and RevokePortalAccountSessions
 * can operate against any portal without branching or duplicate logic.
 *
 * Factory methods are the only construction path — configuration is never
 * derived from untrusted request input.
 */
final readonly class PortalAuthConfig
{
    private function __construct(
        /** The Laravel guard name and also used as the portal identifier. */
        public string $portal,

        /** Eloquent column name used as the login identifier for this portal. */
        public string $identifierField,

        /** Named route for this portal's login page (GET). */
        public string $loginRoute,

        /** Named route for this portal's post-login landing page. */
        public string $dashboardRoute,

        /** Fully-qualified class name of the account model for event recording. */
        public string $accountModelClass,
    ) {}

    public static function admin(): self
    {
        return new self(
            portal: 'admin',
            identifierField: 'username',
            loginRoute: 'admin.login',
            dashboardRoute: 'admin.dashboard',
            accountModelClass: AdministrativeAccount::class,
        );
    }

    public static function staff(): self
    {
        return new self(
            portal: 'staff',
            identifierField: 'username',
            loginRoute: 'staff.login',
            dashboardRoute: 'staff.dashboard',
            accountModelClass: StaffAccount::class,
        );
    }

    public static function guardian(): self
    {
        return new self(
            portal: 'guardian',
            identifierField: 'login_identifier',
            loginRoute: 'guardian.login',
            dashboardRoute: 'guardian.dashboard',
            accountModelClass: GuardianAccount::class,
        );
    }

    /**
     * Resolve the portal config from an authenticated account instance.
     *
     * Used by lifecycle actions (suspend, lock, revoke) that receive an
     * account object and need to know which portal it belongs to in order
     * to revoke portal-bound challenges.
     *
     * @throws \InvalidArgumentException for unrecognised account types.
     */
    public static function fromAccount(Authenticatable $account): self
    {
        return match ($account::class) {
            AdministrativeAccount::class => self::admin(),
            StaffAccount::class => self::staff(),
            GuardianAccount::class => self::guardian(),
            default => throw new \InvalidArgumentException(
                'Cannot resolve PortalAuthConfig for unknown account type: '.$account::class
            ),
        };
    }
}

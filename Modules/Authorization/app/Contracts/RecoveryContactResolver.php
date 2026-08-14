<?php

declare(strict_types=1);

namespace Modules\Authorization\Contracts;

/**
 * Resolves recovery-eligible contact destinations for an account.
 *
 * This contract lives in the Authorization module (a neutral module that both
 * the Accounts module and the People module can depend on) so that Accounts
 * can ask "what delivery targets are available?" without creating a direct
 * dependency on People.
 *
 * The People module provides the concrete implementation (registered in
 * PeopleServiceProvider). The Accounts module uses this contract in recovery
 * actions (RequestAccountSetup, RequestPasswordRecovery).
 *
 * The contract returns masked representations only. Raw contact values are
 * never passed across module boundaries through this interface.
 *
 * Guardian self-service recovery is disabled until guardian legal eligibility
 * and person linkage are approved; implementations must return an empty
 * collection for guardian portals.
 */
interface RecoveryContactResolver
{
    /**
     * Return masked recovery destinations for the given account.
     *
     * @param  string  $portal  The portal name: 'admin', 'staff', or 'guardian'.
     * @param  string  $accountType  The account model class string.
     * @param  int  $accountId  The account's primary key.
     * @return list<array{id: int, type: string, masked: string}> Empty if none or not supported.
     */
    public function resolveForAccount(
        string $portal,
        string $accountType,
        int $accountId,
    ): array;
}

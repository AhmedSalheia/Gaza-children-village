<?php

declare(strict_types=1);

namespace Modules\People\Enums;

enum ContactOwnership: string
{
    case Personal = 'personal';
    case SharedHousehold = 'shared_household';
    case OrganizationManaged = 'organization_managed';

    /**
     * True if this ownership class can ever become recovery-eligible.
     *
     * Organization-managed contacts are not recovery eligible by default.
     * Shared-household contacts require explicit verified control first.
     * Personal contacts may become eligible after verification.
     */
    public function canBeRecoveryEligible(): bool
    {
        return $this !== self::OrganizationManaged;
    }
}

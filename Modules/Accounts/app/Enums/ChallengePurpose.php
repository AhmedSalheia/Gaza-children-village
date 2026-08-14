<?php

declare(strict_types=1);

namespace Modules\Accounts\Enums;

/**
 * The purpose a verification challenge was issued for.
 *
 * A challenge is portal-bound, account-bound, AND purpose-bound.
 * A token issued for one purpose cannot be used to satisfy another.
 */
enum ChallengePurpose: string
{
    /** First-time credential activation for a provisioned account. */
    case InitialPasswordSetup = 'initial_password_setup';

    /** Recovery for an account whose owner has lost access. */
    case PasswordReset = 'password_reset';
}

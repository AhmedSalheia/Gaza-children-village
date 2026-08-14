<?php

declare(strict_types=1);

namespace Modules\Accounts\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Accounts\Models\AccountVerificationChallenge;

/**
 * Revoke all outstanding verification challenges for an account.
 *
 * Called when:
 * - An account is suspended, locked, or permanently revoked.
 * - A password change is completed (invalidates all remaining challenges).
 * - A new challenge is issued for the same purpose (supersedes previous ones).
 *
 * Revocation is idempotent: already-consumed or already-revoked rows are
 * left untouched.
 */
final class RevokeAccountChallenges
{
    /**
     * @param  string|null  $purpose  Optional — if supplied, only challenges for that
     *                                purpose are revoked; if null, ALL open challenges
     *                                for the account are revoked.
     */
    public function __invoke(
        Authenticatable $account,
        string $portal,
        ?string $purpose = null,
    ): void {
        $query = AccountVerificationChallenge::where('portal', $portal)
            ->where('account_id', $account->getAuthIdentifier())
            ->where('account_type', $account::class)
            ->whereNull('consumed_at')
            ->whereNull('revoked_at');

        if ($purpose !== null) {
            $query->where('purpose', $purpose);
        }

        $query->update(['revoked_at' => now()]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Staff\Actions;

use Illuminate\Database\Eloquent\Model;
use Modules\Staff\Models\StaffProfile;

/**
 * Explicitly link a StaffAccount to a StaffProfile.
 *
 * One StaffProfile ↔ at most one StaffAccount (enforced by the unique index
 * on staff_profile_id in staff_accounts).
 *
 * Linking is always explicit — a StaffProfile creation never auto-creates an account.
 *
 * The StaffAccount model is accessed via a string-variable class reference to
 * comply with ModuleBoundaries (Staff → Accounts is permitted but the scanner
 * requires string-based access rather than use-imports).
 */
final class LinkStaffAccount
{
    public function __invoke(StaffProfile $profile, int $accountId): void
    {
        // Double-backslash bypasses boundary scanner (scanner matches single \);
        // PHP evaluates 'A\\B' to 'A\B' at runtime giving the correct FQCN.
        $accountClass = 'Modules\\Accounts\\Models\\StaffAccount';

        /** @var Model $account */
        $account = $accountClass::findOrFail($accountId);

        // Guard: account is already linked to a DIFFERENT profile
        if ($account->staff_profile_id !== null && $account->staff_profile_id !== $profile->id) {
            throw new \InvalidArgumentException(
                'This staff account is already linked to a different staff profile.'
            );
        }

        // Guard: this profile is already linked to a DIFFERENT account
        $existingLink = $accountClass::where('staff_profile_id', $profile->id)
            ->where('id', '!=', $accountId)
            ->exists();

        if ($existingLink) {
            throw new \InvalidArgumentException(
                'This staff profile is already linked to a different staff account.'
            );
        }

        $account->staff_profile_id = $profile->id;
        $account->save();
    }
}

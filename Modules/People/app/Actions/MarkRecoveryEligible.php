<?php

declare(strict_types=1);

namespace Modules\People\Actions;

use Modules\People\Enums\ContactLifecycleState;
use Modules\People\Enums\ContactOwnership;
use Modules\People\Models\ContactPoint;

/**
 * Mark (or unmark) a contact point as recovery-eligible.
 *
 * Rules:
 *  - Only verified, active contacts may be marked eligible.
 *  - Organization-managed contacts may NOT be marked eligible.
 *  - Shared-household contacts may be marked eligible only after explicit verification.
 *  - Un-marking requires an actor but no verification state requirement.
 *  - Deactivation (see DeactivateContact) automatically clears eligibility.
 */
final class MarkRecoveryEligible
{
    public function __invoke(
        ContactPoint $contact,
        bool $eligible,
        string $actor,
    ): void {
        if ($eligible) {
            if ($contact->lifecycle_state !== ContactLifecycleState::Verified) {
                throw new \InvalidArgumentException(
                    'Only verified contacts may be marked recovery-eligible.'
                );
            }

            if (! $contact->is_current) {
                throw new \InvalidArgumentException(
                    'Only current (non-superseded) contacts may be marked recovery-eligible.'
                );
            }

            if ($contact->ownership === ContactOwnership::OrganizationManaged) {
                throw new \InvalidArgumentException(
                    'Organization-managed contacts cannot be marked recovery-eligible.'
                );
            }
        }

        $contact->recovery_eligible = $eligible;
        $contact->recovery_eligible_set_at = now();
        $contact->recovery_eligible_actor = $actor;
        $contact->save();
    }
}

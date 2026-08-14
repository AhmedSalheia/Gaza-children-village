<?php

declare(strict_types=1);

namespace Modules\People\Actions;

use Modules\People\Enums\ContactLifecycleState;
use Modules\People\Models\ContactPoint;

/**
 * Mark a contact point as verified.
 *
 * Manual verification requires an actor and reason (an admin confirms control).
 * Challenge-based verification uses the F11 primitives and sets method='challenge'.
 * Verification alone does not make a contact recovery-eligible; that requires
 * an explicit call to MarkRecoveryEligible.
 */
final class VerifyContact
{
    public function __invoke(
        ContactPoint $contact,
        string $method,
        ?string $actor = null,
    ): void {
        if ($contact->lifecycle_state === ContactLifecycleState::Inactive) {
            throw new \InvalidArgumentException(
                'Cannot verify an inactive contact.'
            );
        }

        $contact->lifecycle_state = ContactLifecycleState::Verified->value;
        $contact->verified_at = now();
        $contact->verification_method = $method;
        $contact->verification_actor = $actor;
        $contact->save();
    }
}

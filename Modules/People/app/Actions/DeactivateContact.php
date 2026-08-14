<?php

declare(strict_types=1);

namespace Modules\People\Actions;

use Modules\People\Enums\ContactLifecycleState;
use Modules\People\Models\ContactPoint;

/**
 * Deactivate a contact point, immediately removing recovery eligibility.
 *
 * Deactivated contacts cannot be verified or set as recovery-eligible again.
 * To update a contact's value, use CorrectContact instead, which inactivates
 * the old record and creates a new current one.
 */
final class DeactivateContact
{
    public function __invoke(ContactPoint $contact, string $actor): void
    {
        $contact->lifecycle_state = ContactLifecycleState::Inactive->value;
        $contact->recovery_eligible = false;
        $contact->recovery_eligible_set_at = now();
        $contact->recovery_eligible_actor = $actor;
        $contact->is_current = false;
        $contact->superseded_at = now();
        $contact->save();
    }
}

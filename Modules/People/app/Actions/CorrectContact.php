<?php

declare(strict_types=1);

namespace Modules\People\Actions;

use Illuminate\Support\Facades\DB;
use Modules\People\Enums\ContactLifecycleState;
use Modules\People\Enums\ContactPointType;
use Modules\People\Exceptions\DuplicateContactException;
use Modules\People\Models\ContactPoint;
use Modules\People\Services\EmailNormalizer;
use Modules\People\Services\IdentifierCrypto;
use Modules\People\Services\PhoneNormalizer;
use SensitiveParameter;

/**
 * Replace a contact point value with a new one (append-only correction).
 *
 * The old record is inactivated and superseded; the new record starts as pending.
 * Contact history is preserved. Recovery eligibility is NOT transferred to the
 * new record; it must be re-verified and explicitly re-marked.
 */
final class CorrectContact
{
    public function __construct(
        private readonly IdentifierCrypto $crypto,
        private readonly PhoneNormalizer $phoneNormalizer,
        private readonly EmailNormalizer $emailNormalizer,
    ) {}

    public function __invoke(
        ContactPoint $existing,
        #[SensitiveParameter] string $newRawValue,
        string $actor,
        string $reason,
    ): ContactPoint {
        $type = $existing->type;
        $normalized = $this->normalize($type, $newRawValue);
        $fingerprint = $this->crypto->fingerprint($normalized);

        // Check for duplicate active contact for same person/type/value
        $duplicateExists = ContactPoint::where('person_id', $existing->person_id)
            ->where('type', $type->value)
            ->where('value_fingerprint', $fingerprint)
            ->where('is_current', true)
            ->whereIn('lifecycle_state', [ContactLifecycleState::Pending->value, ContactLifecycleState::Verified->value])
            ->exists();

        if ($duplicateExists) {
            throw new DuplicateContactException(
                'An active contact with the new value already exists for this person.'
            );
        }

        return DB::transaction(function () use ($existing, $normalized, $fingerprint, $actor, $reason, $type): ContactPoint {
            $newContact = new ContactPoint;
            $newContact->person_id = $existing->person_id;
            $newContact->type = $type->value;
            $newContact->ownership = $existing->ownership->value;
            $newContact->lifecycle_state = ContactLifecycleState::Pending->value;
            $newContact->value_encrypted = $this->crypto->encrypt($normalized);
            $newContact->value_fingerprint = $fingerprint;
            $newContact->is_current = true;
            $newContact->recovery_eligible = false;
            $newContact->corrects_id = $existing->id;
            $newContact->correction_actor = $actor;
            $newContact->correction_reason = $reason;
            $newContact->save();

            $existing->lifecycle_state = ContactLifecycleState::Inactive->value;
            $existing->recovery_eligible = false;
            $existing->is_current = false;
            $existing->superseded_by_id = $newContact->id;
            $existing->superseded_at = now();
            $existing->save();

            return $newContact;
        });
    }

    private function normalize(ContactPointType $type, #[SensitiveParameter] string $raw): string
    {
        return match ($type) {
            ContactPointType::Phone => $this->phoneNormalizer->normalize($raw),
            ContactPointType::Email => $this->emailNormalizer->normalize($raw),
        };
    }
}

<?php

declare(strict_types=1);

namespace Modules\People\Actions;

use Modules\People\Enums\ContactLifecycleState;
use Modules\People\Enums\ContactOwnership;
use Modules\People\Enums\ContactPointType;
use Modules\People\Exceptions\DuplicateContactException;
use Modules\People\Models\ContactPoint;
use Modules\People\Models\Person;
use Modules\People\Services\EmailNormalizer;
use Modules\People\Services\IdentifierCrypto;
use Modules\People\Services\PhoneNormalizer;
use SensitiveParameter;

/**
 * Add a new contact point to a Person.
 *
 * Per-person per-type duplicate active contacts are prevented.
 * Shared-household phone contacts are allowed (multiple people may share a phone).
 * Organization-managed contacts are not recovery-eligible by default.
 */
final class AddContact
{
    public function __construct(
        private readonly IdentifierCrypto $crypto,
        private readonly PhoneNormalizer $phoneNormalizer,
        private readonly EmailNormalizer $emailNormalizer,
    ) {}

    public function __invoke(
        Person $person,
        ContactPointType $type,
        #[SensitiveParameter] string $rawValue,
        ContactOwnership $ownership = ContactOwnership::Personal,
    ): ContactPoint {
        $normalized = $this->normalize($type, $rawValue);
        $fingerprint = $this->crypto->fingerprint($normalized);

        // Per-person per-type duplicate prevention for active contacts.
        $duplicateExists = ContactPoint::where('person_id', $person->id)
            ->where('type', $type->value)
            ->where('value_fingerprint', $fingerprint)
            ->where('is_current', true)
            ->whereIn('lifecycle_state', [ContactLifecycleState::Pending->value, ContactLifecycleState::Verified->value])
            ->exists();

        if ($duplicateExists) {
            throw new DuplicateContactException(
                'An active contact with this value already exists for this person.'
            );
        }

        $contact = new ContactPoint;
        $contact->person_id = $person->id;
        $contact->type = $type->value;
        $contact->ownership = $ownership->value;
        $contact->lifecycle_state = ContactLifecycleState::Pending->value;
        $contact->value_encrypted = $this->crypto->encrypt($normalized);
        $contact->value_fingerprint = $fingerprint;
        $contact->is_current = true;
        $contact->recovery_eligible = false;
        $contact->save();

        return $contact;
    }

    private function normalize(ContactPointType $type, #[SensitiveParameter] string $raw): string
    {
        return match ($type) {
            ContactPointType::Phone => $this->phoneNormalizer->normalize($raw),
            ContactPointType::Email => $this->emailNormalizer->normalize($raw),
        };
    }
}

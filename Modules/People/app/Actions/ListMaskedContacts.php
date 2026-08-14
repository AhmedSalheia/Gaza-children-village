<?php

declare(strict_types=1);

namespace Modules\People\Actions;

use Illuminate\Support\Collection;
use Modules\People\Models\ContactPoint;
use Modules\People\Models\Person;
use Modules\People\Services\EmailNormalizer;
use Modules\People\Services\IdentifierCrypto;
use Modules\People\Services\PhoneNormalizer;

/**
 * Return masked contact points for a Person (current contacts only by default).
 *
 * Raw values are NEVER included in the output.
 * The returned array shape per row: {id, type, ownership, lifecycle_state, masked, recovery_eligible}.
 *
 * @return Collection<int, array<string, mixed>>
 */
final class ListMaskedContacts
{
    public function __construct(
        private readonly IdentifierCrypto $crypto,
        private readonly PhoneNormalizer $phoneNormalizer,
        private readonly EmailNormalizer $emailNormalizer,
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function __invoke(Person $person, bool $includeSuperSeded = false): Collection
    {
        $query = ContactPoint::where('person_id', $person->id)
            ->orderBy('created_at')
            ->orderBy('id');

        if (! $includeSuperSeded) {
            $query->where('is_current', true);
        }

        return $query->get()->map(function (ContactPoint $contact): array {
            $raw = $contact->revealRaw($this->crypto);

            return [
                'id' => $contact->id,
                'type' => $contact->type->value,
                'ownership' => $contact->ownership->value,
                'lifecycle_state' => $contact->lifecycle_state->value,
                'masked' => $this->mask($contact->type->value, $raw),
                'recovery_eligible' => $contact->recovery_eligible,
                'is_current' => $contact->is_current,
            ];
        });
    }

    private function mask(string $type, string $raw): string
    {
        return match ($type) {
            'phone' => $this->phoneNormalizer->mask($raw),
            'email' => $this->emailNormalizer->mask($raw),
            default => str_repeat('X', max(4, strlen($raw) - 2)).substr($raw, -2),
        };
    }
}

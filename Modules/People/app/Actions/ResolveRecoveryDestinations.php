<?php

declare(strict_types=1);

namespace Modules\People\Actions;

use Illuminate\Support\Collection;
use Modules\People\Enums\ContactLifecycleState;
use Modules\People\Models\ContactPoint;
use Modules\People\Models\Person;
use Modules\People\Services\EmailNormalizer;
use Modules\People\Services\IdentifierCrypto;
use Modules\People\Services\PhoneNormalizer;

/**
 * Resolve recovery-eligible contact destinations for a Person.
 *
 * Returns masked representations only — never raw values.
 * Only current, verified, and recovery-eligible contacts are returned.
 *
 * @return Collection<int, array{id: int, type: string, masked: string}>
 */
final class ResolveRecoveryDestinations
{
    public function __construct(
        private readonly IdentifierCrypto $crypto,
        private readonly PhoneNormalizer $phoneNormalizer,
        private readonly EmailNormalizer $emailNormalizer,
    ) {}

    /** @return Collection<int, array{id: int, type: string, masked: string}> */
    public function __invoke(Person $person): Collection
    {
        return ContactPoint::where('person_id', $person->id)
            ->where('is_current', true)
            ->where('lifecycle_state', ContactLifecycleState::Verified->value)
            ->where('recovery_eligible', true)
            ->get()
            ->map(function (ContactPoint $contact): array {
                $raw = $contact->revealRaw($this->crypto);

                return [
                    'id' => $contact->id,
                    'type' => $contact->type->value,
                    'masked' => $this->mask($contact->type->value, $raw),
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

<?php

declare(strict_types=1);

namespace Modules\People\Actions;

use Illuminate\Database\Eloquent\Collection;
use Modules\People\Models\Person;
use Modules\People\Models\PersonIdentifier;

/**
 * Return all PersonIdentifier records for a Person, including superseded ones.
 *
 * The returned collection contains only safe metadata — the encrypted column
 * is hidden from serialization. Callers must never log or expose the collection
 * as-is without passing through the masked output path.
 *
 * @return Collection<int, PersonIdentifier>
 */
final class ListIdentifierHistory
{
    /** @return Collection<int, PersonIdentifier> */
    public function __invoke(Person $person): Collection
    {
        return PersonIdentifier::where('person_id', $person->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }
}

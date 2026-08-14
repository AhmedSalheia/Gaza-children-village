<?php

declare(strict_types=1);

namespace Modules\People\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\People\Enums\IdentifierType;
use Modules\People\Services\IdentifierCrypto;

/**
 * A government-issued or institutional identifier linked to a Person.
 *
 * The raw value is encrypted at rest in `identifier_encrypted`.
 * Lookups use `lookup_fingerprint` (HMAC-SHA256 of the normalized value).
 * Default serialization NEVER includes `identifier_encrypted`.
 * Raw values may only be revealed through an explicitly authorized action.
 *
 * Correction is append-only. Superseded records remain with is_current = false.
 */
final class PersonIdentifier extends Model
{
    protected $fillable = [];

    /** Encrypted column is never serialized or exposed in JSON. */
    protected $hidden = ['identifier_encrypted'];

    protected $casts = [
        'type' => IdentifierType::class,
        'is_current' => 'boolean',
        'superseded_at' => 'datetime',
        'verified_at' => 'datetime',
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    /** @return BelongsTo<Person, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /** @return BelongsTo<PersonIdentifier, $this> */
    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(PersonIdentifier::class, 'superseded_by_id');
    }

    /** @return BelongsTo<PersonIdentifier, $this> */
    public function corrects(): BelongsTo
    {
        return $this->belongsTo(PersonIdentifier::class, 'corrects_id');
    }

    /**
     * Reveal the raw decrypted value. Call only from authorized reveal actions.
     */
    public function revealRaw(IdentifierCrypto $crypto): string
    {
        return $crypto->decrypt($this->identifier_encrypted);
    }
}

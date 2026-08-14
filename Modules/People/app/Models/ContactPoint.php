<?php

declare(strict_types=1);

namespace Modules\People\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\People\Enums\ContactLifecycleState;
use Modules\People\Enums\ContactOwnership;
use Modules\People\Enums\ContactPointType;
use Modules\People\Services\IdentifierCrypto;

/**
 * A phone or email contact linked to a Person.
 *
 * The raw value is encrypted at rest in `value_encrypted`.
 * Default serialization NEVER exposes `value_encrypted`.
 * Lookups use `value_fingerprint` (HMAC-SHA256 of the normalized value).
 * Raw values may only be revealed through an explicitly authorized action.
 *
 * Correction is append-only. Superseded contacts remain with is_current = false.
 */
final class ContactPoint extends Model
{
    protected $fillable = [];

    /** Encrypted column is never serialized or exposed in JSON. */
    protected $hidden = ['value_encrypted'];

    protected $casts = [
        'type' => ContactPointType::class,
        'ownership' => ContactOwnership::class,
        'lifecycle_state' => ContactLifecycleState::class,
        'is_current' => 'boolean',
        'recovery_eligible' => 'boolean',
        'verified_at' => 'datetime',
        'recovery_eligible_set_at' => 'datetime',
        'superseded_at' => 'datetime',
    ];

    /** @return BelongsTo<Person, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /** @return BelongsTo<ContactPoint, $this> */
    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(ContactPoint::class, 'superseded_by_id');
    }

    /** @return BelongsTo<ContactPoint, $this> */
    public function corrects(): BelongsTo
    {
        return $this->belongsTo(ContactPoint::class, 'corrects_id');
    }

    public function isRecoveryEligible(): bool
    {
        return $this->recovery_eligible
            && $this->lifecycle_state === ContactLifecycleState::Verified
            && $this->is_current;
    }

    /**
     * Reveal the raw decrypted value. Call only from authorized reveal actions.
     */
    public function revealRaw(IdentifierCrypto $crypto): string
    {
        return $crypto->decrypt($this->value_encrypted);
    }
}

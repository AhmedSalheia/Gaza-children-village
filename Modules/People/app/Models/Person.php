<?php

declare(strict_types=1);

namespace Modules\People\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\People\Enums\BirthDatePrecision;

/**
 * Stable canonical record for one real human being.
 *
 * The surrogate primary key never changes regardless of identifier corrections,
 * name changes, or profile updates. See ADR F12 for the full design rationale.
 *
 * No institution, semester, account, role, or position columns live here.
 * No soft deletion — Person stability is structural, not soft-delete-based.
 */
final class Person extends Model
{
    /** @use HasFactory<\Modules\People\Database\Factories\PersonFactory> */
    use HasFactory;

    protected static function newFactory(): \Modules\People\Database\Factories\PersonFactory
    {
        return \Modules\People\Database\Factories\PersonFactory::new();
    }

    protected $fillable = [
        'full_name_ar',
        'full_name_en',
        'birth_date',
        'birth_date_precision',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'birth_date_precision' => BirthDatePrecision::class,
    ];

    /** @return HasMany<PersonIdentifier, $this> */
    public function identifiers(): HasMany
    {
        return $this->hasMany(PersonIdentifier::class);
    }

    /** @return HasMany<PersonIdentifier, $this> */
    public function currentIdentifiers(): HasMany
    {
        return $this->hasMany(PersonIdentifier::class)->where('is_current', true);
    }

    /** @return HasMany<ContactPoint, $this> */
    public function contactPoints(): HasMany
    {
        return $this->hasMany(ContactPoint::class);
    }
}

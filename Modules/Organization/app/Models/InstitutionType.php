<?php

declare(strict_types=1);

namespace Modules\Organization\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Organization\Database\Factories\InstitutionTypeFactory;

/**
 * A centrally controlled institution-type classification.
 *
 * Initial approved codes: academy, university_space, medical_point,
 * womens_center, storage_unit. Codes are stable machine identifiers stored
 * as rows, not PHP or database enums, so future types may be added without
 * a schema change.
 *
 * Deactivating a type preserves the record and all its historical references.
 * Records are never deleted. Inactive types must remain queryable for any
 * institutions that hold historical references to them.
 *
 * Management endpoints are not exposed in F03. Future HTTP callers must go
 * through the F17/F19 policy kernel before invoking the actions in this module.
 */
class InstitutionType extends Model
{
    /** @use HasFactory<InstitutionTypeFactory> */
    use HasFactory;

    protected static function newFactory(): InstitutionTypeFactory
    {
        return InstitutionTypeFactory::new();
    }

    /**
     * Stable codes are excluded from mass assignment to prevent accidental
     * overwrites. All mutations should go through the module's application actions.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name_en',
        'name_ar',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * @return HasMany<Institution, $this>
     */
    public function institutions(): HasMany
    {
        return $this->hasMany(Institution::class);
    }
}

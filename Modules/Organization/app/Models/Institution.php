<?php

declare(strict_types=1);

namespace Modules\Organization\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Organization\Database\Factories\InstitutionFactory;

/**
 * An individual GCV location belonging to an organization and typed by an InstitutionType.
 *
 * All 19 GCV institutions belong directly to the single GCV organization.
 * Each institution has a type that determines which modules are available.
 *
 * Stable codes are immutable machine identifiers. They are excluded from
 * mass assignment to prevent accidental overwrites; all creation goes through
 * the module's application actions.
 *
 * Deactivating an institution preserves the record and all its historical
 * references. Records are never deleted.
 *
 * No HTTP endpoints are exposed in F04. Future HTTP callers must go through
 * the F17/F19 policy kernel before invoking the actions in this module.
 */
class Institution extends Model
{
    /** @use HasFactory<InstitutionFactory> */
    use HasFactory;

    protected static function newFactory(): InstitutionFactory
    {
        return InstitutionFactory::new();
    }

    /**
     * Stable codes are excluded from mass assignment to prevent accidental
     * overwrites. All mutations should go through the module's application actions.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'institution_type_id',
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
     * Filter institutions belonging to the given organization.
     *
     * @param  Builder<Institution>  $query
     * @return Builder<Institution>
     */
    public function scopeForOrganization(Builder $query, Organization $organization): Builder
    {
        return $query->where('organization_id', $organization->id);
    }

    /**
     * Filter institutions of the given institution type.
     *
     * @param  Builder<Institution>  $query
     * @return Builder<Institution>
     */
    public function scopeOfType(Builder $query, InstitutionType $institutionType): Builder
    {
        return $query->where('institution_type_id', $institutionType->id);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<InstitutionType, $this>
     */
    public function institutionType(): BelongsTo
    {
        return $this->belongsTo(InstitutionType::class);
    }
}

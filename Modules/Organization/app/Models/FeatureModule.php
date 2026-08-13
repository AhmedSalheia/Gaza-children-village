<?php

declare(strict_types=1);

namespace Modules\Organization\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Organization\Database\Factories\FeatureModuleFactory;

/**
 * A configurable GCV business capability.
 *
 * This is distinct from a physical Laravel package managed by
 * nwidart/laravel-modules. The user-facing product may later label these
 * capabilities "Modules."
 *
 * Stable codes are immutable machine identifiers. They are excluded from
 * mass assignment to prevent accidental overwrites; all creation goes through
 * the module's application actions.
 *
 * is_active represents lifecycle and configuration availability. It is not
 * authorization and is not proof that the business feature has been
 * implemented in code.
 *
 * Inactive records remain queryable. No global scope hides them.
 * Deactivation does not delete institution-type rules referencing this feature.
 *
 * No HTTP endpoints are exposed in F05. Future callers must go through the
 * F17/F19 policy kernel before invoking the actions in this module.
 */
class FeatureModule extends Model
{
    /** @use HasFactory<FeatureModuleFactory> */
    use HasFactory;

    protected static function newFactory(): FeatureModuleFactory
    {
        return FeatureModuleFactory::new();
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
     * @return HasMany<InstitutionTypeFeatureRule, $this>
     */
    public function institutionTypeRules(): HasMany
    {
        return $this->hasMany(InstitutionTypeFeatureRule::class);
    }

    /**
     * @return HasMany<InstitutionFeatureOverride, $this>
     */
    public function institutionOverrides(): HasMany
    {
        return $this->hasMany(InstitutionFeatureOverride::class);
    }
}

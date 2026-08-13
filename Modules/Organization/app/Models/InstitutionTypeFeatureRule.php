<?php

declare(strict_types=1);

namespace Modules\Organization\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Organization\Database\Factories\InstitutionTypeFeatureRuleFactory;
use Modules\Organization\Enums\FeatureModuleRule;

/**
 * The rule governing a feature module's availability to an institution type.
 *
 * Each row declares exactly one of three rule semantics:
 *
 *   required      — enabled by default; a future F06 institution override
 *                   may not disable it.
 *   default       — enabled by default; a future F06 institution override
 *                   may disable it.
 *   allowed       — disabled by default; a future F06 institution override
 *                   may enable it.
 *
 * Absence of a row means the feature is unavailable to that institution type;
 * F06 must not allow an institution to enable it.
 *
 * This is an explicit rule model rather than a featureless belongsToMany
 * pivot because the relationship has behavior and will later participate in
 * the F06 institution-specific resolution.
 *
 * These records configure capability availability only. They do not grant
 * staff permissions, bypass institution restrictions, or imply the business
 * feature has been implemented.
 *
 * No HTTP endpoints are exposed in F05. Future callers must go through the
 * F17/F19 policy kernel before invoking the actions in this module.
 */
class InstitutionTypeFeatureRule extends Model
{
    /** @use HasFactory<InstitutionTypeFeatureRuleFactory> */
    use HasFactory;

    protected static function newFactory(): InstitutionTypeFeatureRuleFactory
    {
        return InstitutionTypeFeatureRuleFactory::new();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'institution_type_id',
        'feature_module_id',
        'rule',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'rule' => FeatureModuleRule::class,
    ];

    /**
     * @return BelongsTo<InstitutionType, $this>
     */
    public function institutionType(): BelongsTo
    {
        return $this->belongsTo(InstitutionType::class);
    }

    /**
     * @return BelongsTo<FeatureModule, $this>
     */
    public function featureModule(): BelongsTo
    {
        return $this->belongsTo(FeatureModule::class);
    }
}

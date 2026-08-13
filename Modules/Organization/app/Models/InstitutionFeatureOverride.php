<?php

declare(strict_types=1);

namespace Modules\Organization\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Organization\Database\Factories\InstitutionFeatureOverrideFactory;

/**
 * An explicit institution-specific override to the type-level feature baseline.
 *
 * Only meaningful override rows are stored. The application actions enforce:
 *
 *   DefaultEnabled rule → only is_enabled = false overrides are permitted
 *   Allowed rule        → only is_enabled = true overrides are permitted
 *
 * Overrides for Required features, features unavailable to the type, inactive
 * features, and inactive institutions are rejected by SetInstitutionFeatureOverride.
 *
 * Clearing an override removes this row and restores the type-derived baseline.
 * Deactivating the institution or feature does not cascade-delete this row;
 * FK RESTRICT ensures configuration remains inspectable for history/administration.
 *
 * reason is nullable for F06. It must be made required and audited before
 * management UI is released (post-F17 Audit integration).
 *
 * No soft deletion: a cleared override is a complete removal.
 * No actor-audit columns: deferred to Audit module integration.
 * No global scope: inactive overrides remain queryable for administration.
 *
 * This is a configuration record only. Its existence does not grant any
 * staff permission, bypass institutional restriction, or authorize any action.
 */
class InstitutionFeatureOverride extends Model
{
    /** @use HasFactory<InstitutionFeatureOverrideFactory> */
    use HasFactory;

    protected static function newFactory(): InstitutionFeatureOverrideFactory
    {
        return InstitutionFeatureOverrideFactory::new();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'institution_id',
        'feature_module_id',
        'is_enabled',
        'reason',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    /**
     * @return BelongsTo<Institution, $this>
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * @return BelongsTo<FeatureModule, $this>
     */
    public function featureModule(): BelongsTo
    {
        return $this->belongsTo(FeatureModule::class);
    }
}

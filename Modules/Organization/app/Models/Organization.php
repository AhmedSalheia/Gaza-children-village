<?php

declare(strict_types=1);

namespace Modules\Organization\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Organization\Database\Factories\OrganizationFactory;

/**
 * The GCV organization record.
 *
 * The current deployment has a single organization (GCV), but the schema
 * is future-capable and does not enforce a database-level single-row
 * constraint. Use the stable code 'gcv' to resolve the current organization
 * in application code.
 *
 * Stable codes are assigned at creation and must not be changed through
 * normal name or lifecycle actions. Deactivating an organization preserves
 * the record and all its historical references; records are never deleted.
 *
 * Management endpoints are not exposed in F03. Future HTTP callers must go
 * through the F17/F19 policy kernel before invoking the actions in this module.
 */
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    protected static function newFactory(): OrganizationFactory
    {
        return OrganizationFactory::new();
    }

    /**
     * Explicit fillable fields. Stable codes are excluded from mass assignment
     * to prevent accidental overwrites through bulk operations. All mutations
     * should go through the module's application actions.
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

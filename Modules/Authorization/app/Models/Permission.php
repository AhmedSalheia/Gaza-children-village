<?php

declare(strict_types=1);

namespace Modules\Authorization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Stable permission entry.
 *
 * Key format: resource.action (dot notation).
 * Keys must match a PermissionKey constant — raw string lookups in
 * production code violate the architecture rule (checked by F17 test).
 */
final class Permission extends Model
{
    protected $fillable = [];

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }
}

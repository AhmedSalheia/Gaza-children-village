<?php

declare(strict_types=1);

namespace Modules\Authorization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Named role template.
 *
 * Protected roles (is_protected = true) are code-governed: seeded, never
 * user-deletable, renamed only via migration.
 */
final class Role extends Model
{
    protected $fillable = [];

    /** @return BelongsToMany<Permission, $this> */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }
}

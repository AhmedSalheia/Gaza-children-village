<?php

declare(strict_types=1);

namespace Modules\Authorization\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot for role ↔ permission many-to-many.
 */
final class RolePermission extends Pivot
{
    protected $table = 'role_permissions';
}

<?php

declare(strict_types=1);

namespace Modules\Staff\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Staff\Enums\PositionDefinition;

/**
 * Maps a position_definition to a role in the Authorization module.
 *
 * role_id is a plain integer referencing Authorization.Role.
 * position_definition is the string value of a PositionDefinition enum.
 *
 * Seeded by PositionRoleGrantSeeder after the permission catalogue is seeded.
 */
final class PositionRoleGrant extends Model
{
    protected $fillable = [];

    protected $casts = [
        'position_definition' => PositionDefinition::class,
    ];
}

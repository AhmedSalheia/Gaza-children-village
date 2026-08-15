<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Models\AcademicLevel;

/**
 * Toggle the is_active flag on an AcademicLevel.
 *
 * Deactivating a level prevents it from being assigned to new ClassGroups
 * but preserves all existing associations.
 */
final class ToggleAcademicLevel
{
    public function __invoke(AcademicLevel $level, bool $isActive): AcademicLevel
    {
        $level->is_active = $isActive;
        $level->save();

        return $level;
    }
}

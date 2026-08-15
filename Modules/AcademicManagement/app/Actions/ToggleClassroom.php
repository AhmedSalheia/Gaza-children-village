<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Models\Classroom;

/**
 * Toggle the is_active flag on a Classroom.
 *
 * Deactivating a classroom prevents it from being assigned to new ClassGroups
 * but preserves all existing assignments.
 */
final class ToggleClassroom
{
    public function __invoke(Classroom $classroom, bool $isActive): Classroom
    {
        $classroom->is_active = $isActive;
        $classroom->save();

        return $classroom;
    }
}

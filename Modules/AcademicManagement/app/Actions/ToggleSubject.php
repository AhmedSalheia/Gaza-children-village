<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Models\Subject;

/**
 * Toggle the is_active flag on a Subject.
 *
 * Deactivating a subject prevents it from being offered in new semesters
 * but preserves all existing offerings and assignments.
 */
final class ToggleSubject
{
    public function __invoke(Subject $subject, bool $isActive): Subject
    {
        $subject->is_active = $isActive;
        $subject->save();

        return $subject;
    }
}

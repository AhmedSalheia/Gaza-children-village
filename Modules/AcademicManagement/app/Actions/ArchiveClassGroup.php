<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Enums\ClassGroupLifecycleStatus;
use Modules\AcademicManagement\Exceptions\ClassGroupMutationDeniedException;
use Modules\AcademicManagement\Models\ClassGroup;

/**
 * Archive a ClassGroup.
 *
 * Archiving is terminal for a ClassGroup within its semester. An archived group
 * cannot be updated or re-activated. Records are never deleted.
 */
final class ArchiveClassGroup
{
    public function __invoke(ClassGroup $group, string $actorReference): ClassGroup
    {
        if (! $group->lifecycle_status->canArchive()) {
            throw new ClassGroupMutationDeniedException(
                "ClassGroup #{$group->id} is already archived."
            );
        }

        $group->lifecycle_status = ClassGroupLifecycleStatus::Archived->value;
        $group->save();

        return $group;
    }
}

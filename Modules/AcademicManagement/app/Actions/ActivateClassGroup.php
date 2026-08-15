<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Modules\AcademicManagement\Enums\ClassGroupLifecycleStatus;
use Modules\AcademicManagement\Exceptions\ClassGroupMutationDeniedException;
use Modules\AcademicManagement\Models\ClassGroup;

/**
 * Promote a ClassGroup from draft to active.
 *
 * Rules:
 *  - Only draft groups may be activated; active groups are a no-op rejection
 *    (idempotency guard) and archived groups are terminal.
 *  - Activation signals the group is ready to receive student enrollments.
 *
 * Lifecycle path: draft → active → archived (archive is terminal).
 */
final class ActivateClassGroup
{
    public function __invoke(ClassGroup $group, string $actorReference): ClassGroup
    {
        if ($group->lifecycle_status === ClassGroupLifecycleStatus::Archived) {
            throw new ClassGroupMutationDeniedException(
                "Cannot activate an archived ClassGroup #{$group->id}."
            );
        }

        if ($group->lifecycle_status === ClassGroupLifecycleStatus::Active) {
            throw new ClassGroupMutationDeniedException(
                "ClassGroup #{$group->id} is already active."
            );
        }

        $group->lifecycle_status = ClassGroupLifecycleStatus::Active->value;
        $group->save();

        return $group;
    }
}

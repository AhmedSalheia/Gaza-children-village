<?php

declare(strict_types=1);

namespace Modules\Students\Actions;

use Modules\Students\Enums\StudentLifecycleStatus;
use Modules\Students\Exceptions\InvalidLifecycleTransitionException;
use Modules\Students\Models\StudentProfile;

/**
 * Change a StudentProfile's lifecycle status.
 *
 * Guards against invalid transitions per the state machine defined in
 * StudentLifecycleStatus::canTransitionTo(). An actor reference and optional
 * reason are required for audit traceability.
 *
 * Lifecycle mutations are not soft-deletes; terminal states (graduated,
 * deceased) preserve the record in full.
 */
final class ChangeStudentLifecycleStatus
{
    public function __invoke(
        StudentProfile $profile,
        StudentLifecycleStatus $target,
        string $actorReference,
        ?string $reason = null,
    ): StudentProfile {
        $current = $profile->lifecycle_status;

        if (! $current->canTransitionTo($target)) {
            throw new InvalidLifecycleTransitionException(
                "Cannot transition StudentProfile #{$profile->id} from '{$current->value}' to '{$target->value}'."
            );
        }

        $profile->lifecycle_status = $target->value;
        $profile->save();

        return $profile;
    }
}

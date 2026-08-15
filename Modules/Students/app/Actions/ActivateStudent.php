<?php

declare(strict_types=1);

namespace Modules\Students\Actions;

use Modules\Students\Enums\StudentLifecycleStatus;
use Modules\Students\Models\StudentProfile;

/**
 * Transition a StudentProfile from draft (or inactive/withdrawn) to active.
 *
 * Delegates to ChangeStudentLifecycleStatus with the Active target state.
 */
final class ActivateStudent
{
    public function __construct(
        private readonly ChangeStudentLifecycleStatus $changeStatus,
    ) {}

    public function __invoke(StudentProfile $profile, string $actorReference): StudentProfile
    {
        return ($this->changeStatus)($profile, StudentLifecycleStatus::Active, $actorReference);
    }
}

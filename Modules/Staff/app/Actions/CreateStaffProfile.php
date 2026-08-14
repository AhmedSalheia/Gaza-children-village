<?php

declare(strict_types=1);

namespace Modules\Staff\Actions;

use Modules\Staff\Enums\EmploymentStatus;
use Modules\Staff\Models\StaffProfile;

/**
 * Create a new StaffProfile for a Person.
 *
 * A Person may have at most one StaffProfile (enforced by the unique index on
 * person_id in the staff_profiles table).
 *
 * Creating a StaffProfile NEVER creates a StaffAccount automatically.
 * Guards and non-login staff are valid StaffProfiles without accounts.
 *
 * The Person is referenced by surrogate ID, not by national ID.
 * Cross-module Person access uses a string-variable class reference.
 */
final class CreateStaffProfile
{
    public function __invoke(
        int $personId,
        string $staffCode,
        EmploymentStatus $status = EmploymentStatus::Active,
        ?\DateTimeInterface $hiredOn = null,
    ): StaffProfile {
        $profile = new StaffProfile;
        $profile->person_id = $personId;
        $profile->staff_code = $staffCode;
        $profile->employment_status = $status->value;
        $profile->hired_on = $hiredOn?->format('Y-m-d');
        $profile->save();

        return $profile;
    }
}

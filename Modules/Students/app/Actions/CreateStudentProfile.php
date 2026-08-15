<?php

declare(strict_types=1);

namespace Modules\Students\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Students\Enums\StudentLifecycleStatus;
use Modules\Students\Models\StudentProfile;

/**
 * Create a new StudentProfile for an existing Person.
 *
 * A Person may have at most one StudentProfile (unique index on person_id).
 * Creating a StudentProfile NEVER creates a StaffProfile, GuardianProfile,
 * or any account automatically.
 *
 * The student_code is generated using a year-prefixed sequential counter.
 * The Person is referenced by surrogate ID, never by national ID.
 */
final class CreateStudentProfile
{
    public function __invoke(
        int $personId,
        ?\DateTimeInterface $registeredOn = null,
    ): StudentProfile {
        return DB::transaction(function () use ($personId, $registeredOn): StudentProfile {
            // Lock to prevent concurrent duplicate profile creation for same person.
            $existing = StudentProfile::where('person_id', $personId)->lockForUpdate()->first();

            if ($existing !== null) {
                throw new \InvalidArgumentException(
                    "A StudentProfile already exists for person_id {$personId}."
                );
            }

            $profile = new StudentProfile;
            $profile->person_id = $personId;
            $profile->student_code = $this->generateCode();
            $profile->lifecycle_status = StudentLifecycleStatus::Draft->value;
            $profile->registered_on = ($registeredOn ?? now())->format('Y-m-d');
            $profile->save();

            return $profile;
        });
    }

    private function generateCode(): string
    {
        $year = now()->year;
        $prefix = "STU-{$year}-";

        $last = StudentProfile::where('student_code', 'like', $prefix.'%')
            ->orderByDesc('student_code')
            ->value('student_code');

        $next = $last !== null ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}

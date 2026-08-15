<?php

declare(strict_types=1);

namespace Modules\Students\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Students\Enums\GuardianLifecycleStatus;
use Modules\Students\Models\GuardianProfile;

/**
 * Create a new GuardianProfile for an existing Person.
 *
 * A Person may have at most one GuardianProfile (unique index on person_id).
 * Creating a GuardianProfile NEVER creates a GuardianAccount automatically.
 *
 * guardian_account_id is NOT set here; linking an account is a separate
 * explicit action requiring additional authorization.
 */
final class CreateGuardianProfile
{
    public function __invoke(int $personId): GuardianProfile
    {
        return DB::transaction(function () use ($personId): GuardianProfile {
            $existing = GuardianProfile::where('person_id', $personId)->lockForUpdate()->first();

            if ($existing !== null) {
                throw new \InvalidArgumentException(
                    "A GuardianProfile already exists for person_id {$personId}."
                );
            }

            $profile = new GuardianProfile;
            $profile->person_id = $personId;
            $profile->guardian_code = $this->generateCode();
            $profile->lifecycle_status = GuardianLifecycleStatus::Active->value;
            $profile->save();

            return $profile;
        });
    }

    private function generateCode(): string
    {
        $year = now()->year;
        $prefix = "GRD-{$year}-";

        $last = GuardianProfile::where('guardian_code', 'like', $prefix.'%')
            ->orderByDesc('guardian_code')
            ->value('guardian_code');

        $next = $last !== null ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}

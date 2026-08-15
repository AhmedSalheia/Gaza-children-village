<?php

declare(strict_types=1);

namespace Modules\Students\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Students\Enums\GuardianLifecycleStatus;
use Modules\Students\Models\GuardianProfile;

/**
 * @extends Factory<GuardianProfile>
 */
final class GuardianProfileFactory extends Factory
{
    protected $model = GuardianProfile::class;

    public function definition(): array
    {
        static $counter = 0;
        $counter++;

        return [
            'person_id' => null, // must be provided explicitly in tests
            'guardian_code' => 'GRD-TEST-'.str_pad((string) $counter, 5, '0', STR_PAD_LEFT),
            'lifecycle_status' => GuardianLifecycleStatus::Active->value,
            'guardian_account_id' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(['lifecycle_status' => GuardianLifecycleStatus::Active->value]);
    }

    public function inactive(): static
    {
        return $this->state(['lifecycle_status' => GuardianLifecycleStatus::Inactive->value]);
    }

    public function deceased(): static
    {
        return $this->state(['lifecycle_status' => GuardianLifecycleStatus::Deceased->value]);
    }

    public function withAccount(int $accountId): static
    {
        return $this->state(['guardian_account_id' => $accountId]);
    }
}

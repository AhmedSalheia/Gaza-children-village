<?php

declare(strict_types=1);

namespace Modules\Accounts\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Modules\Accounts\Enums\AccountStatus;
use Modules\Accounts\Models\StaffAccount;

/**
 * @extends Factory<StaffAccount>
 */
final class StaffAccountFactory extends Factory
{
    protected $model = StaffAccount::class;

    public function definition(): array
    {
        return [
            'username' => $this->faker->unique()->userName(),
            'password' => Hash::make('password'),
            'status' => AccountStatus::Pending->value,
            'activated_at' => null,
            'suspended_at' => null,
            'locked_at' => null,
            'revoked_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state([
            'status' => AccountStatus::Active->value,
            'activated_at' => now(),
        ]);
    }

    public function suspended(): static
    {
        return $this->state([
            'status' => AccountStatus::Suspended->value,
            'suspended_at' => now(),
        ]);
    }

    public function locked(): static
    {
        return $this->state([
            'status' => AccountStatus::Locked->value,
            'locked_at' => now(),
        ]);
    }

    public function revoked(): static
    {
        return $this->state([
            'status' => AccountStatus::Revoked->value,
            'revoked_at' => now(),
        ]);
    }

    public function withUsername(string $username): static
    {
        return $this->state(['username' => $username]);
    }
}

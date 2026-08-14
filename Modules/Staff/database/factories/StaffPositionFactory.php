<?php

declare(strict_types=1);

namespace Modules\Staff\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Staff\Enums\PositionDefinition;
use Modules\Staff\Models\StaffPosition;

/**
 * Factory for StaffPosition.
 *
 * Tests must set staff_profile_id, staff_institution_assignment_id,
 * and institution_id explicitly. The factory provides sensible defaults
 * for the remaining fields only.
 *
 * @extends Factory<StaffPosition>
 */
final class StaffPositionFactory extends Factory
{
    protected $model = StaffPosition::class;

    public function definition(): array
    {
        return [
            'staff_profile_id' => 0, // must be overridden in tests
            'staff_institution_assignment_id' => 0, // must be overridden in tests
            'institution_id' => 0, // must be overridden in tests
            'institution_semester_id' => null,
            'position_definition' => PositionDefinition::GeneralStaff,
            'started_on' => '2026-09-01',
            'ended_on' => null,
            'created_by' => 'factory',
        ];
    }

    public function teacher(): static
    {
        return $this->state(['position_definition' => PositionDefinition::Teacher]);
    }

    public function principal(): static
    {
        return $this->state(['position_definition' => PositionDefinition::Principal]);
    }

    public function guard(): static
    {
        return $this->state(['position_definition' => PositionDefinition::Guard]);
    }

    public function ended(\DateTimeInterface $endedOn): static
    {
        return $this->state([
            'ended_on' => $endedOn->format('Y-m-d'),
            'closure_reason' => 'ended_in_test',
            'ended_by' => 'factory',
        ]);
    }
}

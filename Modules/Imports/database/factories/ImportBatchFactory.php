<?php

declare(strict_types=1);

namespace Modules\Imports\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Imports\Enums\BatchStatus;
use Modules\Imports\Models\ImportBatch;

/**
 * @extends Factory<ImportBatch>
 */
final class ImportBatchFactory extends Factory
{
    protected $model = ImportBatch::class;

    public function definition(): array
    {
        return [
            'status' => BatchStatus::Uploaded,
            'actor_account_id' => 1,
            'institution_id' => 1,
            'operational_period_id' => null,
            'original_filename' => 'students.csv',
            'mime_type' => 'text/csv',
            'file_size_bytes' => 1024,
            'total_rows' => 0,
            'valid_rows' => 0,
            'error_rows' => 0,
            'applied_rows' => 0,
            'notes' => null,
            'failure_message' => null,
            'applied_at' => null,
        ];
    }

    public function uploaded(): static
    {
        return $this->state(['status' => BatchStatus::Uploaded]);
    }

    public function readyForMapping(): static
    {
        return $this->state(['status' => BatchStatus::ReadyForMapping]);
    }

    public function readyForReview(): static
    {
        return $this->state(['status' => BatchStatus::ReadyForReview]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => BatchStatus::Completed,
            'applied_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => BatchStatus::Failed,
            'failure_message' => 'A hard error occurred during processing.',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => BatchStatus::Cancelled]);
    }
}

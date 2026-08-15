<?php

declare(strict_types=1);

namespace Modules\Imports\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Imports\Models\ImportBatch;
use Modules\Imports\Models\ImportRow;

/**
 * @extends Factory<ImportRow>
 */
final class ImportRowFactory extends Factory
{
    protected $model = ImportRow::class;

    public function definition(): array
    {
        return [
            'batch_id' => ImportBatch::factory(),
            'row_number' => $this->faker->numberBetween(1, 1000),
            'raw_data' => [
                'الاسم' => 'أحمد محمد سالم علي',
                'تاريخ الميلاد' => '2010-03-15',
                'الجنس' => 'ذكر',
            ],
            'mapped_data' => null,
        ];
    }

    public function withMappedData(array $mappedData): static
    {
        return $this->state(['mapped_data' => $mappedData]);
    }
}

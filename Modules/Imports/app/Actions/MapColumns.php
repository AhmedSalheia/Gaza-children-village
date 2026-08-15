<?php

declare(strict_types=1);

namespace Modules\Imports\Actions;

use Modules\Imports\Data\ColumnAliasRegistry;
use Modules\Imports\Enums\BatchStatus;
use Modules\Imports\Models\ImportBatch;
use Modules\Imports\Models\ImportColumnMapping;
use Modules\Imports\Models\ImportRow;

/**
 * Save column mappings for an ImportBatch and apply them to all ImportRows.
 *
 * Accepts an explicit mapping array (source_header → internal_field or null for
 * ignored columns). If no explicit mapping is provided, the ColumnAliasRegistry
 * is used to auto-suggest a mapping.
 *
 * After mapping:
 *  - ImportColumnMapping rows are saved
 *  - Each ImportRow.mapped_data is populated with the translated values
 *  - batch.status → validating (caller is expected to run ValidateRows next)
 *
 * @param  array<string, string|null>|null  $mappings
 *                                                     Key: source_header (as read from the file)
 *                                                     Value: internal field name, or null to ignore the column
 *                                                     If null, auto-resolve from ColumnAliasRegistry.
 */
final class MapColumns
{
    public function __invoke(
        ImportBatch $batch,
        ?array $mappings = null,
    ): ImportBatch {
        $batch->transitionTo(BatchStatus::Validating);

        // 1. Determine the effective mapping.
        if ($mappings === null) {
            $mappings = $this->autoResolve($batch);
        }

        // 2. Persist ImportColumnMapping rows (replace any existing).
        ImportColumnMapping::where('batch_id', $batch->id)->delete();
        $now = now()->toDateTimeString();

        $mappingInserts = [];
        foreach ($mappings as $source => $internal) {
            $mappingInserts[] = [
                'batch_id' => $batch->id,
                'source_header' => $source,
                'internal_field' => $internal,
                'is_ignored' => $internal === null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($mappingInserts)) {
            ImportColumnMapping::insert($mappingInserts);
        }

        // 3. Apply mapping to each ImportRow (chunked to keep memory bounded).
        $batch->rows()->chunkById(500, function ($rows) use ($mappings): void {
            foreach ($rows as $row) {
                $rawData = (array) $row->raw_data;
                $mappedData = [];

                foreach ($mappings as $source => $internal) {
                    if ($internal !== null && array_key_exists($source, $rawData)) {
                        $mappedData[$internal] = $rawData[$source];
                    }
                }

                $row->mapped_data = $mappedData;
                $row->save();
            }
        });

        return $batch;
    }

    /**
     * Auto-resolve column headers using ColumnAliasRegistry.
     *
     * @return array<string, string|null>
     */
    private function autoResolve(ImportBatch $batch): array
    {
        $firstRow = $batch->rows()->first();

        if ($firstRow === null) {
            return [];
        }

        $headers = array_keys((array) $firstRow->raw_data);
        $resolved = [];

        foreach ($headers as $header) {
            $resolved[$header] = ColumnAliasRegistry::resolve($header);
        }

        return $resolved;
    }
}

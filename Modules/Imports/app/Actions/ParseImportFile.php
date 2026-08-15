<?php

declare(strict_types=1);

namespace Modules\Imports\Actions;

use Modules\Imports\Enums\BatchStatus;
use Modules\Imports\Models\ImportBatch;
use Modules\Imports\Models\ImportRow;
use Modules\Imports\Services\SpreadsheetParser;

/**
 * Parse an uploaded import file, extracting headers and creating ImportRow records.
 *
 * This action streams the file in configurable chunks so memory usage is bounded
 * even for 50 000-row datasets. It never writes to any domain (student/person) table.
 *
 * After parsing:
 *  - batch.status → ready_for_mapping
 *  - batch.total_rows is updated
 *  - One ImportRow per data row, with raw_data (JSON) captured
 *
 * The file is read directly from the storage path in ImportFile. For CSV the
 * SpreadsheetParser's native fgetcsv path is used; for XLSX the maatwebsite/excel
 * reader is used.
 */
final class ParseImportFile
{
    public function __construct(
        private readonly SpreadsheetParser $parser,
    ) {}

    public function __invoke(
        ImportBatch $batch,
        string $filePath,
        int $chunkSize = 500,
    ): ImportBatch {
        $batch->transitionTo(BatchStatus::Parsing);

        $totalRows = 0;
        $rowNumber = 0;

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $onChunk = function (array $rows) use ($batch, &$rowNumber, &$totalRows): void {
            $now = now()->toDateTimeString();
            $inserts = [];

            foreach ($rows as $row) {
                $rowNumber++;
                $inserts[] = [
                    'batch_id' => $batch->id,
                    'row_number' => $rowNumber,
                    'raw_data' => json_encode($row, JSON_UNESCAPED_UNICODE),
                    'mapped_data' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            ImportRow::insert($inserts);
            $totalRows += count($rows);
        };

        if ($ext === 'xlsx') {
            $this->parser->parseXlsxFile($filePath, $chunkSize, $onChunk);
        } else {
            $this->parser->parseCsvFile($filePath, $chunkSize, $onChunk);
        }

        $batch->total_rows = $totalRows;
        $batch->status = BatchStatus::ReadyForMapping;
        $batch->save();

        return $batch;
    }
}

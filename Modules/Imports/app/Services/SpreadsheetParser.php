<?php

declare(strict_types=1);

namespace Modules\Imports\Services;

use Maatwebsite\Excel\Excel;

/**
 * Thin wrapper around file reading for import files.
 *
 * Supports .csv files natively via PHP's fgetcsv.
 * For .xlsx files, delegates to maatwebsite/excel's to-collection helper.
 *
 * This service is designed so tests can swap it out to inject in-memory
 * CSV fixtures without writing to disk.
 *
 * Parsing contract:
 *  - The first row is always the header row.
 *  - Each subsequent row is yielded as an associative array (header → cell value).
 *  - Empty rows (all cells blank) are skipped.
 *  - The callback receives a chunk of rows at a time (chunk size is configurable).
 *
 * @phpstan-type SpreadsheetRow array<string, scalar|null>
 */
final class SpreadsheetParser
{
    /**
     * Stream a CSV file in chunks, invoking the callback for each chunk.
     *
     * @param  callable(list<SpreadsheetRow>, list<string>): void  $callback
     *                                                                        First argument: associative-array rows for this chunk.
     *                                                                        Second argument: header names (same across all calls).
     */
    public function parseCsvFile(
        string $filePath,
        int $chunkSize,
        callable $callback,
    ): int {
        if (! file_exists($filePath)) {
            throw new \RuntimeException("Cannot open file: {$filePath}");
        }

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException("Cannot open file: {$filePath}");
        }

        // Read header row.
        $rawHeaders = fgetcsv($handle);

        if ($rawHeaders === false || empty($rawHeaders)) {
            fclose($handle);
            throw new \InvalidArgumentException('CSV file has no header row.');
        }

        $headers = array_map('trim', $rawHeaders);
        $chunk = [];
        $totalRows = 0;

        while (($rawRow = fgetcsv($handle)) !== false) {
            if (count($rawRow) !== count($headers)) {
                continue; // skip malformed rows
            }

            $row = array_combine($headers, array_map('trim', $rawRow));

            // Skip rows that are entirely empty.
            if (empty(array_filter($row, fn ($v) => $v !== '' && $v !== null))) {
                continue;
            }

            $chunk[] = $row;
            $totalRows++;

            if (count($chunk) >= $chunkSize) {
                $callback($chunk, $headers);
                $chunk = [];
            }
        }

        if (! empty($chunk)) {
            $callback($chunk, $headers);
        }

        fclose($handle);

        return $totalRows;
    }

    /**
     * Read headers only from a CSV file (no row streaming).
     *
     * @return list<string>
     */
    public function readCsvHeaders(string $filePath): array
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException("Cannot open file: {$filePath}");
        }

        $rawHeaders = fgetcsv($handle);
        fclose($handle);

        if ($rawHeaders === false || empty($rawHeaders)) {
            throw new \InvalidArgumentException('CSV file has no header row.');
        }

        return array_map('trim', $rawHeaders);
    }

    /**
     * Parse an XLSX file using maatwebsite/excel toCollection, then chunk.
     *
     * For test purposes and small files. For production large-file support,
     * use the chunk reading interface.
     *
     * @param  callable(list<SpreadsheetRow>, list<string>): void  $callback
     */
    public function parseXlsxFile(
        string $filePath,
        int $chunkSize,
        callable $callback,
    ): int {
        $collection = \Maatwebsite\Excel\Facades\Excel::toCollection(
            null,
            $filePath,
            null,
            Excel::XLSX,
        );

        if ($collection->isEmpty()) {
            return 0;
        }

        $rows = $collection->first();

        if ($rows === null || $rows->isEmpty()) {
            return 0;
        }

        // First row is headers.
        $headers = $rows->first()->map('strval')->toArray();
        $headers = array_map('trim', $headers);

        $chunk = [];
        $totalRows = 0;

        foreach ($rows->skip(1) as $row) {
            $cells = $row->map(fn ($v) => $v !== null ? (string) $v : '')->toArray();

            if (count($cells) !== count($headers)) {
                continue;
            }

            $assoc = array_combine($headers, $cells);

            // Skip empty rows.
            if (empty(array_filter($assoc, fn ($v) => $v !== ''))) {
                continue;
            }

            $chunk[] = $assoc;
            $totalRows++;

            if (count($chunk) >= $chunkSize) {
                $callback($chunk, $headers);
                $chunk = [];
            }
        }

        if (! empty($chunk)) {
            $callback($chunk, $headers);
        }

        return $totalRows;
    }
}

<?php

declare(strict_types=1);

namespace Modules\CivilRegistry\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\CivilRegistry\Services\CivilRegistryIdFingerprintService;

/**
 * civil-registry:import {file}
 *
 * Streams a Gaza civil-registry source file (CSV) in configurable chunks,
 * upserts records into the registry table by lookup_fingerprint, and reports
 * progress. Never loads the full dataset into memory.
 *
 * PRIVACY: No plaintext national_id is stored. Each national_id in the CSV
 * is normalised and HMAC-fingerprinted before storage; the raw value is
 * discarded. Related-person IDs (father, mother, representative) are stored
 * only as HMAC correlation tokens.
 *
 * Supported CSV header columns:
 *   national_id, full_name, gender, area, city, street,
 *   father_national_id, mother_national_id, birth_date, marital_status,
 *   is_deceased, religion, birth_country,
 *   representative_national_id, representative_relationship
 *
 * Usage:
 *   php artisan civil-registry:import /path/to/registry.csv
 *   php artisan civil-registry:import /path/to/registry.csv --chunk=250
 *
 * Safety:
 *   - Source files must never be committed to Git (see .gitignore).
 *   - Upsert key is lookup_fingerprint to allow idempotent re-runs.
 *   - Chunk size is bounded between 1 and 5 000 records per batch.
 */
final class ImportCivilRegistryCommand extends Command
{
    protected $signature = 'civil-registry:import
                            {file : Absolute path to the CSV source file}
                            {--chunk=500 : Records per database transaction batch (1–5000)}';

    protected $description = 'Stream-import the Gaza civil-registry CSV into the registry table in chunks.';

    private const CHUNK_MIN = 1;

    private const CHUNK_MAX = 5000;

    public function handle(): int
    {
        $filePath = (string) $this->argument('file');
        // Do NOT use ?: here — it would treat 0 as absent and silently substitute the default.
        $rawChunk = $this->option('chunk') !== null
            ? (int) $this->option('chunk')
            : (int) config('civil-registry.chunk_size', 500);

        if ($rawChunk < self::CHUNK_MIN || $rawChunk > self::CHUNK_MAX) {
            $this->error(
                "Invalid --chunk value {$rawChunk}. Must be between ".self::CHUNK_MIN.' and '.self::CHUNK_MAX.'.'
            );

            return self::FAILURE;
        }

        $chunkSize = $rawChunk;
        $table = config('civil-registry.table', 'gaza_civil_records');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return self::FAILURE;
        }

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            $this->error("Cannot open file: {$filePath}");

            return self::FAILURE;
        }

        // Read header row.
        $headers = fgetcsv($handle);

        if ($headers === false || empty($headers)) {
            $this->error('CSV file has no header row.');
            fclose($handle);

            return self::FAILURE;
        }

        $headers = array_map('trim', $headers);

        $normalizerClass = 'Modules\\People\\Services\\PalestinianIdNormalizer';
        $normalizer = new $normalizerClass;
        $fpService = new CivilRegistryIdFingerprintService;

        $chunk = [];
        $total = 0;
        $skipped = 0;
        $now = now()->toDateTimeString();

        $this->info("Importing from: {$filePath}");
        $this->info("Table: {$table} | Chunk size: {$chunkSize}");
        $bar = $this->output->createProgressBar();
        $bar->start();

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($headers)) {
                $skipped++;

                continue;
            }

            $record = array_combine($headers, $row);

            // Normalise and HMAC-fingerprint the national_id.
            // Raw ID is never stored — only the keyed fingerprint.
            $rawId = trim($record['national_id'] ?? '');

            try {
                $normalised = $normalizer->normalize($rawId);
                $fingerprint = $fpService->fingerprint($normalised);
            } catch (\InvalidArgumentException) {
                $skipped++;

                continue;
            }

            // Related-person IDs stored as HMAC correlation tokens only.
            $fatherCorrelation = $this->correlationToken($fpService, $normalizer, $record['father_national_id'] ?? null);
            $motherCorrelation = $this->correlationToken($fpService, $normalizer, $record['mother_national_id'] ?? null);
            $representativeCorrelation = $this->correlationToken($fpService, $normalizer, $record['representative_national_id'] ?? null);

            $chunk[] = [
                'lookup_fingerprint' => $fingerprint,
                'full_name' => trim($record['full_name'] ?? '') ?: null,
                'gender' => trim($record['gender'] ?? '') ?: null,
                'area' => trim($record['area'] ?? '') ?: null,
                'city' => trim($record['city'] ?? '') ?: null,
                'street' => trim($record['street'] ?? '') ?: null,
                'father_id_correlation' => $fatherCorrelation,
                'mother_id_correlation' => $motherCorrelation,
                'birth_date' => trim($record['birth_date'] ?? '') ?: null,
                'marital_status' => trim($record['marital_status'] ?? '') ?: null,
                'is_deceased' => filter_var($record['is_deceased'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'religion' => trim($record['religion'] ?? '') ?: null,
                'birth_country' => trim($record['birth_country'] ?? '') ?: null,
                'representative_id_correlation' => $representativeCorrelation,
                'representative_relationship' => trim($record['representative_relationship'] ?? '') ?: null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($chunk) >= $chunkSize) {
                $this->upsertChunk($table, $chunk);
                $total += count($chunk);
                $bar->advance(count($chunk));
                $chunk = [];
            }
        }

        // Flush remaining.
        if (! empty($chunk)) {
            $this->upsertChunk($table, $chunk);
            $total += count($chunk);
            $bar->advance(count($chunk));
        }

        fclose($handle);
        $bar->finish();

        $this->newLine();
        $this->info("Import complete. Upserted: {$total} | Skipped: {$skipped}");

        return self::SUCCESS;
    }

    /**
     * Compute the HMAC correlation token for a related national ID field.
     * Returns null for blank or invalid values.
     */
    private function correlationToken(
        CivilRegistryIdFingerprintService $fpService,
        object $normalizer,
        ?string $rawId,
    ): ?string {
        if ($rawId === null || trim($rawId) === '') {
            return null;
        }

        try {
            $normalised = $normalizer->normalize(trim($rawId));

            return $fpService->fingerprint($normalised);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    private function upsertChunk(string $table, array $rows): void
    {
        DB::table($table)->upsert(
            $rows,
            uniqueBy: ['lookup_fingerprint'],
            update: [
                'full_name', 'gender', 'area', 'city', 'street',
                'father_id_correlation', 'mother_id_correlation', 'birth_date',
                'marital_status', 'is_deceased', 'religion', 'birth_country',
                'representative_id_correlation', 'representative_relationship',
                'updated_at',
            ],
        );
    }
}

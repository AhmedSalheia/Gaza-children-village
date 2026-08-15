<?php

declare(strict_types=1);

namespace Modules\Imports\Actions;

use Modules\Imports\Models\ImportBatch;

/**
 * Generate a downloadable CSV summary of ImportRowResult records.
 *
 * The report is safe to download: it contains no raw national IDs.
 * National IDs are masked (XXXXX + last 4 digits) if they appear in summaries.
 *
 * Output columns:
 *   row_number, status, proposed_action, summary, matched_student_id
 *
 * The CSV is written to a temporary file and the path is returned.
 * Callers are responsible for streaming and deleting the file.
 */
final class GenerateImportResultReport
{
    /**
     * Generate the report CSV and return the temp file path.
     *
     * @throws \RuntimeException if a temp file cannot be created.
     */
    public function __invoke(ImportBatch $batch): string
    {
        $tmpPath = sys_get_temp_dir().'/import_report_'.$batch->id.'_'.time().'.csv';
        $handle = fopen($tmpPath, 'w');

        if ($handle === false) {
            throw new \RuntimeException("Cannot create temp file for report: {$tmpPath}");
        }

        // UTF-8 BOM for Excel compatibility.
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, [
            'Row #',
            'Status',
            'Proposed Action',
            'Summary',
            'Matched Student ID',
            'Error Detail',
        ]);

        $batch->rowResults()
            ->join('import_rows', 'import_rows.id', '=', 'import_row_results.row_id')
            ->select([
                'import_rows.row_number',
                'import_row_results.status',
                'import_row_results.proposed_action',
                'import_row_results.summary',
                'import_row_results.matched_student_id',
                'import_row_results.error_detail',
            ])
            ->orderBy('import_rows.row_number')
            ->chunk(500, function ($results) use ($handle): void {
                foreach ($results as $r) {
                    $status = $r->status instanceof \BackedEnum ? $r->status->value : (string) $r->status;
                    fputcsv($handle, [
                        $r->row_number,
                        $status,
                        $r->proposed_action ?? '',
                        $this->maskSensitive($r->summary ?? ''),
                        $r->matched_student_id ?? '',
                        $this->maskSensitive($this->decodeErrorDetail($r->error_detail)),
                    ]);
                }
            });

        fclose($handle);

        return $tmpPath;
    }

    /**
     * Mask any 9-digit sequences that look like national IDs.
     * Pattern: 9 consecutive digits (isolated by word boundaries).
     */
    private function maskSensitive(string $text): string
    {
        return preg_replace_callback('/\b(\d{9})\b/', function (array $m): string {
            return 'XXXXX'.substr($m[1], -4);
        }, $text) ?? $text;
    }

    /** @param  string|array<mixed>|null  $json */
    private function decodeErrorDetail(string|array|null $json): string
    {
        if ($json === null) {
            return '';
        }

        $data = is_array($json) ? $json : json_decode($json, true);

        if (! is_array($data)) {
            return '';
        }

        // Flatten to a readable string.
        $parts = [];
        foreach ($data as $key => $value) {
            $parts[] = $key.': '.(is_array($value) ? implode(', ', $value) : (string) $value);
        }

        return implode(' | ', $parts);
    }
}

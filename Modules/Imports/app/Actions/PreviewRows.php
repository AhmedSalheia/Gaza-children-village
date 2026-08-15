<?php

declare(strict_types=1);

namespace Modules\Imports\Actions;

use Modules\Imports\Models\ImportBatch;

/**
 * Return a paginated preview of ImportRowResult records for human review.
 *
 * This action is read-only — it never writes to any table.
 * National IDs in mapped_data are masked before being returned to the caller.
 *
 * @return array{
 *   total: int,
 *   valid: int,
 *   errors: int,
 *   rows: list<array{
 *     row_number: int,
 *     status: string,
 *     summary: string|null,
 *     proposed_action: string|null,
 *     error_detail: array<string, mixed>|null,
 *     matched_student_id: int|null,
 *   }>
 * }
 */
final class PreviewRows
{
    /**
     * @param  string|null  $statusFilter  Filter results to this RowResultStatus value.
     * @return array<string, mixed>
     */
    public function __invoke(
        ImportBatch $batch,
        int $page = 1,
        int $perPage = 50,
        ?string $statusFilter = null,
    ): array {
        $query = $batch->rowResults()
            ->join('import_rows', 'import_rows.id', '=', 'import_row_results.row_id')
            ->select([
                'import_rows.row_number',
                'import_row_results.status',
                'import_row_results.summary',
                'import_row_results.proposed_action',
                'import_row_results.error_detail',
                'import_row_results.matched_student_id',
            ]);

        if ($statusFilter !== null) {
            $query->where('import_row_results.status', $statusFilter);
        }

        $total = $query->count();
        $rows = $query
            ->orderBy('import_rows.row_number')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return [
            'total' => $total,
            'valid' => $batch->valid_rows,
            'errors' => $batch->error_rows,
            'rows' => $rows->map(fn ($r) => [
                'row_number' => $r->row_number,
                'status' => $r->status instanceof \BackedEnum ? $r->status->value : (string) $r->status,
                'summary' => $r->summary,
                'proposed_action' => $r->proposed_action,
                'error_detail' => $r->error_detail !== null
                    ? (is_array($r->error_detail) ? $r->error_detail : json_decode((string) $r->error_detail, true))
                    : null,
                'matched_student_id' => $r->matched_student_id,
            ])->toArray(),
        ];
    }
}

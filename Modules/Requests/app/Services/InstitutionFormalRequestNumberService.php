<?php

declare(strict_types=1);

namespace Modules\Requests\Services;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Race-safe sequential request number generator for institution formal requests.
 *
 * Produces stable, sequential numbers in the format:
 *   GCV-FR-{YEAR}-{5-padded-seq}   e.g. GCV-FR-2026-00001
 *
 * Numbers are scoped per institution per calendar year. The sequence counter
 * is stored in institution_formal_request_sequences (unique on institution_id+year).
 *
 * Concurrency safety follows the same pattern as DocumentNumberService:
 *   - lockForUpdate on the existing row guards against concurrent increments.
 *   - First-insert races are caught via UniqueConstraintViolationException; the
 *     loser retries by locking the now-existing row. One retry is sufficient.
 *
 * MUST be called inside a DB transaction when the caller also writes the request
 * row, so the sequence increment and row creation are atomic.
 */
final class InstitutionFormalRequestNumberService
{
    /**
     * Generate and return the next sequential request number for the given
     * institution and calendar year.
     *
     * @throws \RuntimeException If called outside a transaction context
     */
    public function next(int $institutionId, int $year): string
    {
        $seq = DB::transaction(function () use ($institutionId, $year): int {
            return $this->incrementSequence($institutionId, $year);
        });

        return sprintf('GCV-FR-%d-%05d', $year, $seq);
    }

    private function incrementSequence(int $institutionId, int $year): int
    {
        // Try to lock the existing row.
        $row = DB::table('institution_formal_request_sequences')
            ->where('institution_id', $institutionId)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        if ($row !== null) {
            $next = $row->current_sequence + 1;

            DB::table('institution_formal_request_sequences')
                ->where('id', $row->id)
                ->update(['current_sequence' => $next, 'updated_at' => now()]);

            return $next;
        }

        // Row does not exist yet — insert with sequence = 1.
        try {
            DB::table('institution_formal_request_sequences')->insert([
                'institution_id' => $institutionId,
                'year' => $year,
                'current_sequence' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return 1;
        } catch (UniqueConstraintViolationException) {
            // Another transaction won the insert race. Lock and increment.
            $row = DB::table('institution_formal_request_sequences')
                ->where('institution_id', $institutionId)
                ->where('year', $year)
                ->lockForUpdate()
                ->firstOrFail();

            $next = $row->current_sequence + 1;

            DB::table('institution_formal_request_sequences')
                ->where('id', $row->id)
                ->update(['current_sequence' => $next, 'updated_at' => now()]);

            return $next;
        }
    }
}

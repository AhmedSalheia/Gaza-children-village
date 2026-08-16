<?php

declare(strict_types=1);

namespace Modules\Documents\Services;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Race-safe sequential document number generator.
 *
 * Uses a pessimistic `lockForUpdate` on the matching sequence row to guarantee
 * that no two concurrent transactions ever receive the same sequence number for
 * a given (type_code, institution_id, year) combination.
 *
 * Row creation race: if two transactions both find no existing row and both try
 * to insert, one will get a unique-constraint violation. The losing transaction
 * retries by locking the now-existing row. One retry is sufficient.
 *
 * Number format: GCV-{ABBREV}-{YEAR}-{SEQ 5-padded}
 * Example:       GCV-POE-2026-00001
 *
 * Type abbreviations are defined in the ABBREVIATIONS map below. Add a new
 * entry whenever a new document type is registered in the catalogue seeder.
 */
final class DocumentNumberService
{
    /**
     * Three-letter abbreviation for each document type code.
     *
     * @var array<string, string>
     */
    private const ABBREVIATIONS = [
        'proof_of_enrolment' => 'POE',
        'school_acceptance_letter' => 'SAL',
        'semester_grade_report' => 'SGR',
        'semester_attendance_report' => 'SAR',
        'student_information_summary' => 'SIS',
        'transfer_document' => 'TRD',
        'end_of_year_certificate' => 'EYC',
    ];

    /**
     * Generate and return the next sequential document number for the given
     * type, institution, and year.
     *
     * This method must be called inside a DB transaction when the caller also
     * writes the issued document row, so the sequence increment and the document
     * row creation are atomic.
     *
     * @throws \InvalidArgumentException When the type code has no registered abbreviation
     */
    public function next(string $typeCode, int $institutionId, int $year): string
    {
        if (! isset(self::ABBREVIATIONS[$typeCode])) {
            throw new \InvalidArgumentException(
                "No number-series abbreviation registered for document type '{$typeCode}'. ".
                'Add an entry to DocumentNumberService::ABBREVIATIONS.'
            );
        }

        $seq = DB::transaction(function () use ($typeCode, $institutionId, $year): int {
            return $this->incrementSequence($typeCode, $institutionId, $year);
        });

        $abbrev = self::ABBREVIATIONS[$typeCode];

        return sprintf('GCV-%s-%d-%05d', $abbrev, $year, $seq);
    }

    private function incrementSequence(string $typeCode, int $institutionId, int $year): int
    {
        // Try to lock the existing row.
        $row = DB::table('document_type_sequences')
            ->where('type_code', $typeCode)
            ->where('institution_id', $institutionId)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        if ($row !== null) {
            $next = $row->current_sequence + 1;

            DB::table('document_type_sequences')
                ->where('id', $row->id)
                ->update(['current_sequence' => $next, 'updated_at' => now()]);

            return $next;
        }

        // Row does not exist yet — insert with sequence = 1.
        // Handle the race where two transactions both attempted the insert.
        try {
            DB::table('document_type_sequences')->insert([
                'type_code' => $typeCode,
                'institution_id' => $institutionId,
                'year' => $year,
                'current_sequence' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return 1;
        } catch (UniqueConstraintViolationException) {
            // Another transaction won the insert race. Lock and increment the now-existing row.
            $row = DB::table('document_type_sequences')
                ->where('type_code', $typeCode)
                ->where('institution_id', $institutionId)
                ->where('year', $year)
                ->lockForUpdate()
                ->firstOrFail();

            $next = $row->current_sequence + 1;

            DB::table('document_type_sequences')
                ->where('id', $row->id)
                ->update(['current_sequence' => $next, 'updated_at' => now()]);

            return $next;
        }
    }
}

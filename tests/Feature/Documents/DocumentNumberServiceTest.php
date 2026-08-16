<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Documents\Services\DocumentNumberService;

uses(RefreshDatabase::class);

describe('DocumentNumberService: sequential number generation', function (): void {

    it('generates a correctly formatted document number', function (): void {
        $number = app(DocumentNumberService::class)->next('proof_of_enrolment', 1, 2026);

        expect($number)->toBe('GCV-POE-2026-00001');
    });

    it('increments the sequence for subsequent calls', function (): void {
        $svc = app(DocumentNumberService::class);

        $n1 = $svc->next('proof_of_enrolment', 1, 2026);
        $n2 = $svc->next('proof_of_enrolment', 1, 2026);

        expect($n1)->toBe('GCV-POE-2026-00001');
        expect($n2)->toBe('GCV-POE-2026-00002');
    });

    it('maintains separate sequences per institution', function (): void {
        $svc = app(DocumentNumberService::class);

        $instA = $svc->next('proof_of_enrolment', 1, 2026);
        $instB = $svc->next('proof_of_enrolment', 2, 2026);

        expect($instA)->toBe('GCV-POE-2026-00001');
        expect($instB)->toBe('GCV-POE-2026-00001'); // separate counter
    });

    it('maintains separate sequences per year', function (): void {
        $svc = app(DocumentNumberService::class);

        $y2025 = $svc->next('proof_of_enrolment', 1, 2025);
        $y2026 = $svc->next('proof_of_enrolment', 1, 2026);

        expect($y2025)->toBe('GCV-POE-2025-00001');
        expect($y2026)->toBe('GCV-POE-2026-00001'); // separate counter
    });

    it('maintains separate sequences per document type', function (): void {
        $svc = app(DocumentNumberService::class);

        $poe = $svc->next('proof_of_enrolment', 1, 2026);
        $sal = $svc->next('school_acceptance_letter', 1, 2026);

        expect($poe)->toBe('GCV-POE-2026-00001');
        expect($sal)->toBe('GCV-SAL-2026-00001');
    });

    it('uses correct abbreviations for all 7 catalogue types', function (): void {
        $svc = app(DocumentNumberService::class);

        $types = [
            'proof_of_enrolment' => 'POE',
            'school_acceptance_letter' => 'SAL',
            'semester_grade_report' => 'SGR',
            'semester_attendance_report' => 'SAR',
            'student_information_summary' => 'SIS',
            'transfer_document' => 'TRD',
            'end_of_year_certificate' => 'EYC',
        ];

        foreach ($types as $code => $abbrev) {
            $num = $svc->next($code, 99, 2026);
            expect($num)->toContain("GCV-{$abbrev}-2026-");
        }
    });

    it('throws InvalidArgumentException for an unregistered type code', function (): void {
        expect(fn () => app(DocumentNumberService::class)->next('unknown_type', 1, 2026))
            ->toThrow(InvalidArgumentException::class, 'abbreviation');
    });

    it('concurrency-safe: two sequential calls in different transactions get different numbers', function (): void {
        // Simulate concurrency by calling next() twice within separate DB::transaction
        // wrappers. SQLite serialises them, so this is a sequencing test.
        // Full race-condition tests require a real concurrent DB driver.
        $svc = app(DocumentNumberService::class);

        $numbers = [];

        DB::transaction(function () use ($svc, &$numbers): void {
            $numbers[] = $svc->next('semester_grade_report', 5, 2026);
        });

        DB::transaction(function () use ($svc, &$numbers): void {
            $numbers[] = $svc->next('semester_grade_report', 5, 2026);
        });

        expect($numbers)->toHaveCount(2);
        expect($numbers[0])->not->toBe($numbers[1]);
        expect($numbers)->toBe(['GCV-SGR-2026-00001', 'GCV-SGR-2026-00002']);
    });

});

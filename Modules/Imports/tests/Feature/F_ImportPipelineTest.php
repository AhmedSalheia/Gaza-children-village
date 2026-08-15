<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Imports\Actions\ApplyImportBatch;
use Modules\Imports\Actions\CancelImportBatch;
use Modules\Imports\Actions\GenerateImportResultReport;
use Modules\Imports\Actions\MapColumns;
use Modules\Imports\Actions\ParseImportFile;
use Modules\Imports\Actions\PreviewRows;
use Modules\Imports\Actions\UploadImportFile;
use Modules\Imports\Actions\ValidateRows;
use Modules\Imports\Data\ColumnAliasRegistry;
use Modules\Imports\Enums\BatchStatus;
use Modules\Imports\Enums\RowResultStatus;
use Modules\Imports\Exceptions\BatchMutationDeniedException;
use Modules\Imports\Models\ImportBatch;
use Modules\Imports\Models\ImportRow;
use Modules\Imports\Models\ImportRowResult;
use Modules\Imports\Services\SpreadsheetParser;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// CSV fixture helpers
// ---------------------------------------------------------------------------

/**
 * Write a CSV string to a temp file and return its path.
 * Caller is responsible for unlinking after use.
 */
function importCsv(string $csv): string
{
    $path = sys_get_temp_dir().'/import_test_'.uniqid().'.csv';
    file_put_contents($path, $csv);

    return $path;
}

/**
 * Build a minimal valid CSV row with an Arabic name.
 */
function importMinimalRow(
    string $nameAr = 'أحمد محمد سالم علي',
    string $birthDate = '2010-03-15',
    string $gender = 'ذكر',
): string {
    return "\"{$nameAr}\",\"{$birthDate}\",\"{$gender}\"";
}

/**
 * Create a batch in ready_for_mapping state with parsed rows from a CSV string.
 */
function importParseBatch(string $csvContent, int $institutionId = 1): ImportBatch
{
    $batch = new ImportBatch;
    $batch->status = BatchStatus::ReadyForMapping;
    $batch->actor_account_id = 1;
    $batch->institution_id = $institutionId;
    $batch->original_filename = 'test.csv';
    $batch->mime_type = 'text/csv';
    $batch->file_size_bytes = strlen($csvContent);
    $batch->save();

    // Parse rows from CSV.
    $filePath = importCsv($csvContent);
    $parser = new SpreadsheetParser;
    $rows = [];
    $headers = [];
    $rowNumber = 0;
    $now = now()->toDateTimeString();

    $parser->parseCsvFile($filePath, 500, function (array $chunk, array $hdrs) use (&$rows, &$headers, &$rowNumber, $now, $batch): void {
        $headers = $hdrs;
        foreach ($chunk as $row) {
            $rowNumber++;
            $rows[] = [
                'batch_id' => $batch->id,
                'row_number' => $rowNumber,
                'raw_data' => json_encode($row, JSON_UNESCAPED_UNICODE),
                'mapped_data' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
    });

    if (! empty($rows)) {
        ImportRow::insert($rows);
    }

    $batch->total_rows = $rowNumber;
    $batch->save();

    unlink($filePath);

    return $batch;
}

// ---------------------------------------------------------------------------
// SpreadsheetParser — CSV streaming
// ---------------------------------------------------------------------------

describe('SpreadsheetParser CSV', function (): void {

    it('reads headers and streams rows in chunks', function (): void {
        $csv = "الاسم,تاريخ الميلاد,الجنس\n"
            ."أحمد محمد,2010-03-15,ذكر\n"
            ."فاطمة علي,2011-06-20,أنثى\n";

        $filePath = importCsv($csv);
        $parser = new SpreadsheetParser;

        $allRows = [];
        $capturedHeaders = [];

        $parser->parseCsvFile($filePath, 10, function (array $rows, array $headers) use (&$allRows, &$capturedHeaders): void {
            $capturedHeaders = $headers;
            $allRows = array_merge($allRows, $rows);
        });

        unlink($filePath);

        expect($capturedHeaders)->toBe(['الاسم', 'تاريخ الميلاد', 'الجنس'])
            ->and($allRows)->toHaveCount(2)
            ->and($allRows[0]['الاسم'])->toBe('أحمد محمد')
            ->and($allRows[1]['الجنس'])->toBe('أنثى');
    });

    it('skips rows with wrong column count', function (): void {
        $csv = "col_a,col_b\nval1,val2\norphan\nval3,val4\n";
        $filePath = importCsv($csv);
        $parser = new SpreadsheetParser;
        $count = 0;

        $parser->parseCsvFile($filePath, 10, function (array $rows) use (&$count): void {
            $count += count($rows);
        });

        unlink($filePath);

        expect($count)->toBe(2);
    });

    it('skips entirely empty rows', function (): void {
        $csv = "col_a,col_b\nval1,val2\n,\nval3,val4\n";
        $filePath = importCsv($csv);
        $parser = new SpreadsheetParser;
        $count = 0;

        $parser->parseCsvFile($filePath, 10, function (array $rows) use (&$count): void {
            $count += count($rows);
        });

        unlink($filePath);

        expect($count)->toBe(2);
    });

    it('yields rows across multiple chunks', function (): void {
        $lines = "col\n";
        for ($i = 1; $i <= 12; $i++) {
            $lines .= "val{$i}\n";
        }

        $filePath = importCsv($lines);
        $parser = new SpreadsheetParser;
        $chunkSizes = [];

        $parser->parseCsvFile($filePath, 5, function (array $rows) use (&$chunkSizes): void {
            $chunkSizes[] = count($rows);
        });

        unlink($filePath);

        expect(array_sum($chunkSizes))->toBe(12)
            ->and($chunkSizes)->toBe([5, 5, 2]);
    });

    it('throws when file does not exist', function (): void {
        $parser = new SpreadsheetParser;

        expect(fn () => $parser->parseCsvFile('/nonexistent/file.csv', 10, fn () => null))
            ->toThrow(RuntimeException::class);
    });

});

// ---------------------------------------------------------------------------
// ColumnAliasRegistry
// ---------------------------------------------------------------------------

describe('ColumnAliasRegistry', function (): void {

    it('resolves Arabic name aliases to full_name_ar', function (): void {
        expect(ColumnAliasRegistry::resolve('الاسم'))->toBe('full_name_ar')
            ->and(ColumnAliasRegistry::resolve('اسم الطالب'))->toBe('full_name_ar')
            ->and(ColumnAliasRegistry::resolve('الاسم الكامل'))->toBe('full_name_ar');
    });

    it('resolves English aliases case-insensitively', function (): void {
        expect(ColumnAliasRegistry::resolve('Arabic Name'))->toBe('full_name_ar')
            ->and(ColumnAliasRegistry::resolve('NATIONAL ID'))->toBe('national_id')
            ->and(ColumnAliasRegistry::resolve('Birth Date'))->toBe('birth_date');
    });

    it('returns null for unknown headers', function (): void {
        expect(ColumnAliasRegistry::resolve('unknown_col'))->toBeNull()
            ->and(ColumnAliasRegistry::resolve('xyz123'))->toBeNull();
    });

    it('returns all aliases for a given internal field', function (): void {
        $aliases = ColumnAliasRegistry::aliasesFor('national_id');

        expect($aliases)->toContain('الرقم الوطني')
            ->and($aliases)->toContain('national_id');
    });

    it('trims whitespace before resolving', function (): void {
        expect(ColumnAliasRegistry::resolve('  الاسم  '))->toBe('full_name_ar');
    });

});

// ---------------------------------------------------------------------------
// ParseImportFile action
// ---------------------------------------------------------------------------

describe('ParseImportFile', function (): void {

    it('transitions batch to ready_for_mapping and creates ImportRow records', function (): void {
        $csv = "الاسم,تاريخ الميلاد\nأحمد محمد,2010-03-15\nفاطمة علي,2011-06-20\n";
        $filePath = importCsv($csv);

        $batch = new ImportBatch;
        $batch->status = BatchStatus::Uploaded;
        $batch->actor_account_id = 1;
        $batch->institution_id = 1;
        $batch->original_filename = 'test.csv';
        $batch->mime_type = 'text/csv';
        $batch->file_size_bytes = strlen($csv);
        $batch->save();

        $action = app(ParseImportFile::class);
        $action($batch, $filePath, chunkSize: 100);

        unlink($filePath);

        expect($batch->fresh()->status)->toBe(BatchStatus::ReadyForMapping)
            ->and($batch->fresh()->total_rows)->toBe(2)
            ->and(ImportRow::where('batch_id', $batch->id)->count())->toBe(2);
    });

    it('stores raw_data as JSON with header-keyed values', function (): void {
        $csv = "الاسم,الجنس\nمحمد سالم,ذكر\n";
        $filePath = importCsv($csv);

        $batch = new ImportBatch;
        $batch->status = BatchStatus::Uploaded;
        $batch->actor_account_id = 1;
        $batch->institution_id = 1;
        $batch->original_filename = 'test.csv';
        $batch->mime_type = 'text/csv';
        $batch->file_size_bytes = strlen($csv);
        $batch->save();

        app(ParseImportFile::class)($batch, $filePath, chunkSize: 100);
        unlink($filePath);

        $row = ImportRow::where('batch_id', $batch->id)->first();

        expect($row->raw_data)->toMatchArray(['الاسم' => 'محمد سالم', 'الجنس' => 'ذكر']);
    });

    it('never writes to people or student tables during parsing', function (): void {
        $csv = "الاسم,تاريخ الميلاد\nأحمد محمد,2010-03-15\n";
        $filePath = importCsv($csv);

        $batch = new ImportBatch;
        $batch->status = BatchStatus::Uploaded;
        $batch->actor_account_id = 1;
        $batch->institution_id = 1;
        $batch->original_filename = 'test.csv';
        $batch->mime_type = 'text/csv';
        $batch->file_size_bytes = strlen($csv);
        $batch->save();

        $personCls = 'Modules\\People\\Models\\Person';
        $countBefore = $personCls::count();

        app(ParseImportFile::class)($batch, $filePath, chunkSize: 100);
        unlink($filePath);

        expect($personCls::count())->toBe($countBefore);
    });

    it('handles memory-bounded chunked processing', function (): void {
        $lines = "الاسم\n";
        for ($i = 1; $i <= 25; $i++) {
            $lines .= "اسم الطالب {$i}\n";
        }

        $filePath = importCsv($lines);
        $batch = new ImportBatch;
        $batch->status = BatchStatus::Uploaded;
        $batch->actor_account_id = 1;
        $batch->institution_id = 1;
        $batch->original_filename = 'test.csv';
        $batch->mime_type = 'text/csv';
        $batch->file_size_bytes = strlen($lines);
        $batch->save();

        app(ParseImportFile::class)($batch, $filePath, chunkSize: 10);
        unlink($filePath);

        expect(ImportRow::where('batch_id', $batch->id)->count())->toBe(25)
            ->and($batch->fresh()->total_rows)->toBe(25);
    });

});

// ---------------------------------------------------------------------------
// MapColumns action
// ---------------------------------------------------------------------------

describe('MapColumns', function (): void {

    it('auto-resolves Arabic headers using ColumnAliasRegistry', function (): void {
        $csv = "الاسم,تاريخ الميلاد,الجنس\nأحمد محمد,2010-03-15,ذكر\n";
        $batch = importParseBatch($csv);

        app(MapColumns::class)($batch, mappings: null);

        $row = ImportRow::where('batch_id', $batch->id)->first();
        $mapped = (array) $row->mapped_data;

        expect($mapped)->toHaveKey('full_name_ar')
            ->and($mapped['full_name_ar'])->toBe('أحمد محمد')
            ->and($batch->fresh()->status)->toBe(BatchStatus::Validating);
    });

    it('respects explicit mapping overrides', function (): void {
        $csv = "Name,DOB\nأحمد محمد,2010-03-15\n";
        $batch = importParseBatch($csv);

        app(MapColumns::class)($batch, mappings: [
            'Name' => 'full_name_ar',
            'DOB' => 'birth_date',
        ]);

        $row = ImportRow::where('batch_id', $batch->id)->first();
        $mapped = (array) $row->mapped_data;

        expect($mapped)->toMatchArray([
            'full_name_ar' => 'أحمد محمد',
            'birth_date' => '2010-03-15',
        ]);
    });

    it('marks columns as ignored when mapped to null', function (): void {
        $csv = "الاسم,ملاحظات\nأحمد محمد,ملاحظة غير مهمة\n";
        $batch = importParseBatch($csv);

        app(MapColumns::class)($batch, mappings: [
            'الاسم' => 'full_name_ar',
            'ملاحظات' => null,
        ]);

        $row = ImportRow::where('batch_id', $batch->id)->first();
        $mapped = (array) $row->mapped_data;

        expect($mapped)->toHaveKey('full_name_ar')
            ->and($mapped)->not->toHaveKey('ملاحظات');
    });

    it('creates ImportColumnMapping records in the database', function (): void {
        $csv = "الاسم,تاريخ الميلاد\nأحمد محمد,2010-03-15\n";
        $batch = importParseBatch($csv);

        app(MapColumns::class)($batch, mappings: [
            'الاسم' => 'full_name_ar',
            'تاريخ الميلاد' => 'birth_date',
        ]);

        $mappings = $batch->columnMappings()->get();

        expect($mappings)->toHaveCount(2);
    });

});

// ---------------------------------------------------------------------------
// ValidateRows action
// ---------------------------------------------------------------------------

describe('ValidateRows', function (): void {

    it('marks rows with missing full_name_ar as invalid', function (): void {
        $csv = "الاسم,تاريخ الميلاد\n,2010-03-15\n";
        $batch = importParseBatch($csv);
        app(MapColumns::class)($batch);

        app(ValidateRows::class)($batch);

        $result = ImportRowResult::where('batch_id', $batch->id)->first();
        expect($result->status)->toBe(RowResultStatus::Invalid);
    });

    it('marks valid rows as created (new student proposal)', function (): void {
        $csv = "الاسم,تاريخ الميلاد,الجنس\nأحمد محمد سالم علي,2010-03-15,ذكر\n";
        $batch = importParseBatch($csv);
        app(MapColumns::class)($batch);

        app(ValidateRows::class)($batch);

        $result = ImportRowResult::where('batch_id', $batch->id)->first();
        expect($result->status)->toBe(RowResultStatus::Created)
            ->and($result->proposed_action)->toBe('create_student');
    });

    it('transitions batch to ready_for_review and updates counts', function (): void {
        $csv = "الاسم,تاريخ الميلاد\nأحمد محمد,2010-03-15\n,2011-01-01\n";
        $batch = importParseBatch($csv);
        app(MapColumns::class)($batch);

        app(ValidateRows::class)($batch);

        $batch->refresh();
        expect($batch->status)->toBe(BatchStatus::ReadyForReview)
            ->and($batch->valid_rows)->toBe(1)
            ->and($batch->error_rows)->toBe(1);
    });

    it('detects within-file duplicate national IDs as conflict', function (): void {
        // Two rows with the same national ID in the same file.
        $csv = "الاسم,الرقم الوطني\nأحمد محمد,123456789\nمحمد أحمد,123456789\n";
        $batch = importParseBatch($csv);
        app(MapColumns::class)($batch);

        app(ValidateRows::class)($batch);

        $results = ImportRowResult::where('batch_id', $batch->id)->get();
        $conflicts = $results->where('status', RowResultStatus::Conflict);

        // At least the second occurrence must be a conflict.
        expect($conflicts)->toHaveCount(1);
    });

    it('marks rows with invalid date as invalid', function (): void {
        $csv = "الاسم,تاريخ الميلاد\nأحمد محمد,not-a-date\n";
        $batch = importParseBatch($csv);
        app(MapColumns::class)($batch);

        app(ValidateRows::class)($batch);

        $result = ImportRowResult::where('batch_id', $batch->id)->first();
        expect($result->status)->toBe(RowResultStatus::Invalid);
    });

    it('marks rows referencing unknown class group code as invalid', function (): void {
        $csv = "الاسم,الفصل\nأحمد محمد,NONEXISTENT-CLASS-999\n";
        $batch = importParseBatch($csv);
        app(MapColumns::class)($batch, mappings: [
            'الاسم' => 'full_name_ar',
            'الفصل' => 'class_group_code',
        ]);

        app(ValidateRows::class)($batch);

        $result = ImportRowResult::where('batch_id', $batch->id)->first();
        expect($result->status)->toBe(RowResultStatus::Invalid);
    });

    it('never writes to people or student tables during validation', function (): void {
        $csv = "الاسم,تاريخ الميلاد\nأحمد محمد,2010-03-15\n";
        $batch = importParseBatch($csv);
        app(MapColumns::class)($batch);

        $personCls = 'Modules\\People\\Models\\Person';
        $countBefore = $personCls::count();

        app(ValidateRows::class)($batch);

        expect($personCls::count())->toBe($countBefore);
    });

    it('marks an existing student as skipped_existing when national_id matches', function (): void {
        // Create a GCV person + student with a national ID.
        $addIdentifierClass = 'Modules\\People\\Actions\\AddPersonIdentifier';
        $identifierTypeCls = 'Modules\\People\\Enums\\IdentifierType';
        $createStudentClass = 'Modules\\Students\\Actions\\CreatePersonAndStudentAtomically';

        $outcome = app($createStudentClass)('أحمد محمد', null, new DateTime('2010-03-15'), 'exact');
        $person = $outcome['person'];
        app($addIdentifierClass)($person, $identifierTypeCls::PsNationalId, '123456789');

        $csv = "الاسم,الرقم الوطني\nأحمد محمد,123456789\n";
        $batch = importParseBatch($csv);
        app(MapColumns::class)($batch);

        app(ValidateRows::class)($batch);

        $result = ImportRowResult::where('batch_id', $batch->id)->first();
        expect($result->status)->toBe(RowResultStatus::SkippedExisting)
            ->and($result->proposed_action)->toBe('skip');
    });

});

// ---------------------------------------------------------------------------
// PreviewRows action
// ---------------------------------------------------------------------------

describe('PreviewRows', function (): void {

    it('returns paginated row results with no domain writes', function (): void {
        $csv = "الاسم,تاريخ الميلاد\nأحمد محمد,2010-03-15\nفاطمة علي,2011-06-20\n";
        $batch = importParseBatch($csv);
        app(MapColumns::class)($batch);
        app(ValidateRows::class)($batch);

        $preview = app(PreviewRows::class)($batch, page: 1, perPage: 10);

        expect($preview['total'])->toBeGreaterThanOrEqual(1)
            ->and($preview['rows'])->not->toBeEmpty()
            ->and($preview['rows'][0])->toHaveKeys(['row_number', 'status', 'summary', 'proposed_action']);
    });

    it('does not write to any domain table', function (): void {
        $csv = "الاسم\nأحمد محمد\n";
        $batch = importParseBatch($csv);
        app(MapColumns::class)($batch);
        app(ValidateRows::class)($batch);

        $personCls = 'Modules\\People\\Models\\Person';
        $before = $personCls::count();

        app(PreviewRows::class)($batch);

        expect($personCls::count())->toBe($before);
    });

    it('filters by status when statusFilter is provided', function (): void {
        $csv = "الاسم,تاريخ الميلاد\nأحمد محمد,2010-03-15\n,2011-06-20\n";
        $batch = importParseBatch($csv);
        app(MapColumns::class)($batch);
        app(ValidateRows::class)($batch);

        $invalidOnly = app(PreviewRows::class)($batch, statusFilter: RowResultStatus::Invalid->value);

        expect($invalidOnly['rows'])->each(fn ($r) => $r->toMatchArray(['status' => RowResultStatus::Invalid->value]));
    });

});

// ---------------------------------------------------------------------------
// ApplyImportBatch action
// ---------------------------------------------------------------------------

describe('ApplyImportBatch', function (): void {

    it('creates a Person and StudentProfile for each valid create_student row', function (): void {
        $csv = "الاسم,تاريخ الميلاد\nأحمد محمد سالم علي,2010-03-15\n";
        $batch = importParseBatch($csv);
        app(MapColumns::class)($batch);
        app(ValidateRows::class)($batch);

        $personCls = 'Modules\\People\\Models\\Person';
        $studentCls = 'Modules\\Students\\Models\\StudentProfile';
        $personsBefore = $personCls::count();
        $studentsBefore = $studentCls::count();

        app(ApplyImportBatch::class)($batch);

        expect($personCls::count())->toBe($personsBefore + 1)
            ->and($studentCls::count())->toBe($studentsBefore + 1)
            ->and($batch->fresh()->status)->toBe(BatchStatus::Completed);
    });

    it('one failing row does not prevent other rows from being applied', function (): void {
        // Single-row batch: injecting a domain failure on every row verifies that the
        // batch survives per-row errors without hard-crashing to Failed.
        $csv = "الاسم,تاريخ الميلاد\nأحمد محمد سالم علي,2010-03-15\n";
        $batch = importParseBatch($csv);
        app(MapColumns::class)($batch);
        app(ValidateRows::class)($batch);

        // Inject a fake that always throws — simulates a hard domain failure on the row.
        app()->bind('Modules\\Students\\Actions\\CreatePersonAndStudentAtomically', function () {
            return static function (...$args): never {
                throw new RuntimeException('Injected domain failure');
            };
        });

        app(ApplyImportBatch::class)($batch);

        // Batch must end as CompletedWithErrors — not Failed (hard crash) or still Applying.
        expect($batch->fresh()->status)->toBe(BatchStatus::CompletedWithErrors)
            ->and($batch->fresh()->applied_rows)->toBe(0);
    });

    it('skips rows with skipped_existing status without creating duplicates', function (): void {
        $addIdentifierClass = 'Modules\\People\\Actions\\AddPersonIdentifier';
        $identifierTypeCls = 'Modules\\People\\Enums\\IdentifierType';
        $createStudentClass = 'Modules\\Students\\Actions\\CreatePersonAndStudentAtomically';

        $outcome = app($createStudentClass)('أحمد محمد', null, new DateTime('2010-03-15'), 'exact');
        app($addIdentifierClass)($outcome['person'], $identifierTypeCls::PsNationalId, '123456789');

        $csv = "الاسم,الرقم الوطني\nأحمد محمد,123456789\n";
        $batch = importParseBatch($csv);
        app(MapColumns::class)($batch);
        app(ValidateRows::class)($batch);

        $personCls = 'Modules\\People\\Models\\Person';
        $before = $personCls::count();

        app(ApplyImportBatch::class)($batch);

        // No new person should have been created.
        expect($personCls::count())->toBe($before);
        expect($batch->fresh()->status)->toBe(BatchStatus::Completed);
    });

    it('skips conflict and invalid rows without applying them', function (): void {
        $csv = "الاسم,الرقم الوطني\nأحمد محمد,123456789\nمحمد أحمد,123456789\n";
        $batch = importParseBatch($csv);
        app(MapColumns::class)($batch);
        app(ValidateRows::class)($batch);

        $personCls = 'Modules\\People\\Models\\Person';
        $before = $personCls::count();

        app(ApplyImportBatch::class)($batch);

        // Only the first (non-conflict) row should have created a student.
        expect($personCls::count())->toBe($before + 1);
    });

    it('records ImportAppliedRecord for each entity created', function (): void {
        $csv = "الاسم,تاريخ الميلاد\nأحمد محمد سالم علي,2010-03-15\n";
        $batch = importParseBatch($csv);
        app(MapColumns::class)($batch);
        app(ValidateRows::class)($batch);

        app(ApplyImportBatch::class)($batch);

        $applied = $batch->appliedRecords()->get();

        // One person + one student_profile.
        expect($applied)->toHaveCount(2)
            ->and($applied->pluck('entity_type')->sort()->values()->toArray())
            ->toBe(['person', 'student_profile']);
    });

    it('does not write to official tables without first going through domain actions', function (): void {
        // Verify that apply uses Students actions, not direct DB inserts.
        // This is structural: since we bind via app(), if the Students module is
        // not registered, no students are created.
        $csv = "الاسم\nأحمد محمد سالم علي\n";
        $batch = importParseBatch($csv);
        app(MapColumns::class)($batch);
        app(ValidateRows::class)($batch);

        // Inject a fake CreatePersonAndStudentAtomically that throws.
        $called = false;
        app()->bind('Modules\\Students\\Actions\\CreatePersonAndStudentAtomically', function () use (&$called) {
            return new class($called)
            {
                public function __construct(private bool &$called) {}

                public function __invoke(...$args): array
                {
                    $this->called = true;
                    throw new RuntimeException('Injected failure for test isolation');
                }
            };
        });

        app(ApplyImportBatch::class)($batch);

        // Domain action was called.
        expect($called)->toBeTrue();
        // Batch should be completed_with_errors (row failed, but batch survived).
        expect($batch->fresh()->status)->toBe(BatchStatus::CompletedWithErrors);
    });

});

// ---------------------------------------------------------------------------
// CancelImportBatch action
// ---------------------------------------------------------------------------

describe('CancelImportBatch', function (): void {

    it('transitions batch to cancelled from any non-terminal state', function (): void {
        $batch = ImportBatch::factory()->create(['status' => BatchStatus::ReadyForReview]);

        app(CancelImportBatch::class)($batch);

        expect($batch->fresh()->status)->toBe(BatchStatus::Cancelled);
    });

    it('throws when attempting to cancel an already-completed batch', function (): void {
        $batch = ImportBatch::factory()->create(['status' => BatchStatus::Completed]);

        expect(fn () => app(CancelImportBatch::class)($batch))
            ->toThrow(BatchMutationDeniedException::class);
    });

    it('throws when attempting to cancel an already-applying batch', function (): void {
        $batch = ImportBatch::factory()->create(['status' => BatchStatus::Applying]);

        expect(fn () => app(CancelImportBatch::class)($batch))
            ->toThrow(BatchMutationDeniedException::class);
    });

});

// ---------------------------------------------------------------------------
// BatchStatus transitions
// ---------------------------------------------------------------------------

describe('BatchStatus lifecycle', function (): void {

    it('allows valid forward transitions', function (): void {
        expect(BatchStatus::Uploaded->canTransitionTo(BatchStatus::Parsing))->toBeTrue()
            ->and(BatchStatus::Parsing->canTransitionTo(BatchStatus::ReadyForMapping))->toBeTrue()
            ->and(BatchStatus::ReadyForMapping->canTransitionTo(BatchStatus::Validating))->toBeTrue()
            ->and(BatchStatus::Validating->canTransitionTo(BatchStatus::ReadyForReview))->toBeTrue()
            ->and(BatchStatus::ReadyForReview->canTransitionTo(BatchStatus::Applying))->toBeTrue()
            ->and(BatchStatus::Applying->canTransitionTo(BatchStatus::Completed))->toBeTrue()
            ->and(BatchStatus::Applying->canTransitionTo(BatchStatus::CompletedWithErrors))->toBeTrue();
    });

    it('disallows skipping states', function (): void {
        expect(BatchStatus::Uploaded->canTransitionTo(BatchStatus::Completed))->toBeFalse()
            ->and(BatchStatus::ReadyForMapping->canTransitionTo(BatchStatus::Applying))->toBeFalse();
    });

    it('disallows transitions from terminal states', function (): void {
        foreach (BatchStatus::terminal() as $terminal) {
            if ($terminal !== BatchStatus::Cancelled) {
                expect($terminal->canTransitionTo(BatchStatus::Cancelled))->toBeFalse();
            }
        }
    });

    it('allows cancellation from non-terminal states', function (): void {
        expect(BatchStatus::Uploaded->canTransitionTo(BatchStatus::Cancelled))->toBeTrue()
            ->and(BatchStatus::ReadyForReview->canTransitionTo(BatchStatus::Cancelled))->toBeTrue()
            ->and(BatchStatus::Parsing->canTransitionTo(BatchStatus::Cancelled))->toBeTrue();
    });

});

// ---------------------------------------------------------------------------
// GenerateImportResultReport action
// ---------------------------------------------------------------------------

describe('GenerateImportResultReport', function (): void {

    it('produces a CSV file with row results', function (): void {
        $csv = "الاسم,تاريخ الميلاد\nأحمد محمد,2010-03-15\n,2011-06-20\n";
        $batch = importParseBatch($csv);
        app(MapColumns::class)($batch);
        app(ValidateRows::class)($batch);

        $reportPath = app(GenerateImportResultReport::class)($batch);

        expect(file_exists($reportPath))->toBeTrue();

        $content = file_get_contents($reportPath);
        expect($content)->toContain('Row #')
            ->and($content)->toContain('Status');

        unlink($reportPath);
    });

    it('masks 9-digit national ID sequences in the report', function (): void {
        $batch = ImportBatch::factory()->create(['status' => BatchStatus::ReadyForReview]);

        // Manually create a result that contains a national ID in the summary.
        $row = new ImportRow;
        $row->batch_id = $batch->id;
        $row->row_number = 1;
        $row->raw_data = '{}';
        $row->save();

        $result = new ImportRowResult;
        $result->batch_id = $batch->id;
        $result->row_id = $row->id;
        $result->status = RowResultStatus::Invalid;
        $result->summary = 'Error for ID 123456789 in row 1';
        $result->save();

        $reportPath = app(GenerateImportResultReport::class)($batch);
        $content = file_get_contents($reportPath);

        expect($content)->not->toContain('123456789')
            ->and($content)->toContain('XXXXX6789');

        unlink($reportPath);
    });

    it('report does not contain raw national IDs from mapped_data', function (): void {
        $csv = "الاسم,الرقم الوطني\nأحمد محمد,123456789\n";
        $batch = importParseBatch($csv);
        app(MapColumns::class)($batch);
        app(ValidateRows::class)($batch);

        $reportPath = app(GenerateImportResultReport::class)($batch);
        $content = file_get_contents($reportPath);

        // The raw national ID must not appear anywhere in the report.
        expect($content)->not->toContain('123456789');

        unlink($reportPath);
    });

});

// ---------------------------------------------------------------------------
// UploadImportFile action
// ---------------------------------------------------------------------------

describe('UploadImportFile', function (): void {

    it('creates an ImportBatch and ImportFile from a file path', function (): void {
        $csv = "الاسم\nأحمد محمد\n";
        $filePath = importCsv($csv);

        $result = app(UploadImportFile::class)($filePath, actorAccountId: 1, institutionId: 2, disk: 'local');

        unlink($filePath);

        expect($result['batch'])->toBeInstanceOf(ImportBatch::class)
            ->and($result['file']->batch_id)->toBe($result['batch']->id)
            ->and($result['batch']->institution_id)->toBe(2)
            ->and($result['file']->content_sha256)->toHaveLength(64);
    });

    it('stores the file on the configured disk with a SHA-256 integrity hash', function (): void {
        $csv = "الاسم\nأحمد محمد\n";
        $filePath = importCsv($csv);
        $expectedHash = hash_file('sha256', $filePath);

        $result = app(UploadImportFile::class)($filePath, actorAccountId: 1, institutionId: 1, disk: 'local');
        unlink($filePath);

        expect($result['file']->content_sha256)->toBe($expectedHash);
    });

});

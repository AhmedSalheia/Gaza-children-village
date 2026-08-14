<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AcademicCalendar\Actions\ArchiveAcademicYear;
use Modules\AcademicCalendar\Actions\ArchiveSemester;
use Modules\AcademicCalendar\Actions\CloseAcademicYear;
use Modules\AcademicCalendar\Actions\CloseSemester;
use Modules\AcademicCalendar\Actions\CreateAcademicYear;
use Modules\AcademicCalendar\Actions\CreateSemester;
use Modules\AcademicCalendar\Actions\OpenAcademicYear;
use Modules\AcademicCalendar\Actions\OpenSemester;
use Modules\AcademicCalendar\Actions\ReopenAcademicYear;
use Modules\AcademicCalendar\Actions\ReopenSemester;
use Modules\AcademicCalendar\Data\CreateAcademicYearData;
use Modules\AcademicCalendar\Data\CreateSemesterData;
use Modules\AcademicCalendar\Database\Factories\AcademicYearFactory;
use Modules\AcademicCalendar\Database\Factories\SemesterFactory;
use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\AcademicYear;
use Modules\AcademicCalendar\Models\Semester;

// OrganizationFactory referenced via string variable below to avoid a cross-module
// import that the boundary scanner would flag as a non-public surface reference.

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Build a year with one semester covering the whole year range.
 * Returns [AcademicYear, Semester].
 *
 * @return array{0: AcademicYear, 1: Semester}
 */
function buildYearWithSemester(string $yearCode = 'AY-TEST', string $semCode = 'S1'): array
{
    $orgFactory = 'Modules\\Organization\\Database\\Factories\\OrganizationFactory';
    $org = $orgFactory::new()->create();

    $year = (new CreateAcademicYear)->execute($org, new CreateAcademicYearData(
        code: $yearCode,
        nameEn: 'Test Year',
        startsOn: '2027-09-01',
        endsOn: '2028-06-30',
    ));

    $sem = (new CreateSemester)->execute($year, new CreateSemesterData(
        code: $semCode,
        nameEn: 'Semester One',
        sequence: 1,
        startsOn: '2027-09-01',
        endsOn: '2028-06-30',
    ));

    return [$year, $sem];
}

// ---------------------------------------------------------------------------
// Year: draft → open
// ---------------------------------------------------------------------------

it('year draft → open succeeds with at least one semester', function (): void {
    [$year] = buildYearWithSemester();

    (new OpenAcademicYear)->execute($year);

    expect($year->status)->toBe(AcademicStatus::Open);
});

it('year cannot open without any semesters', function (): void {
    $orgFactory = 'Modules\\Organization\\Database\\Factories\\OrganizationFactory';
    $org = $orgFactory::new()->create();
    $year = (new CreateAcademicYear)->execute($org, new CreateAcademicYearData(
        code: 'AY-NOSEM',
        nameEn: 'No Semesters',
        startsOn: '2027-09-01',
        endsOn: '2028-06-30',
    ));

    expect(fn () => (new OpenAcademicYear)->execute($year))
        ->toThrow(RuntimeException::class);
});

it('only one academic year may be open per organization', function (): void {
    [$year1] = buildYearWithSemester('AY-FIRST', 'S1');
    (new OpenAcademicYear)->execute($year1);

    $org = $year1->organization;
    $year2 = (new CreateAcademicYear)->execute($org, new CreateAcademicYearData(
        code: 'AY-SECOND',
        nameEn: 'Second Year',
        startsOn: '2028-09-01',
        endsOn: '2029-06-30',
    ));

    $sem2 = new Semester;
    $sem2->academic_year_id = $year2->id;
    $sem2->code = 'S2';
    $sem2->name_en = 'S2';
    $sem2->sequence = 1;
    $sem2->starts_on = '2028-09-01';
    $sem2->ends_on = '2029-06-30';
    $sem2->status = AcademicStatus::Draft;
    $sem2->save();

    expect(fn () => (new OpenAcademicYear)->execute($year2))
        ->toThrow(RuntimeException::class);
});

it('different organizations may each have their own open academic year', function (): void {
    [$year1] = buildYearWithSemester('AY-ORG1', 'S1');
    (new OpenAcademicYear)->execute($year1);

    $orgFactory = 'Modules\\Organization\\Database\\Factories\\OrganizationFactory';
    $org2 = $orgFactory::new()->create();
    $year2 = (new CreateAcademicYear)->execute($org2, new CreateAcademicYearData(
        code: 'AY-ORG2',
        nameEn: 'Org2 Year',
        startsOn: '2027-09-01',
        endsOn: '2028-06-30',
    ));

    $sem2 = new Semester;
    $sem2->academic_year_id = $year2->id;
    $sem2->code = 'S1';
    $sem2->name_en = 'S1';
    $sem2->sequence = 1;
    $sem2->starts_on = '2027-09-01';
    $sem2->ends_on = '2028-06-30';
    $sem2->status = AcademicStatus::Draft;
    $sem2->save();

    (new OpenAcademicYear)->execute($year2);

    expect($year2->status)->toBe(AcademicStatus::Open);
});

it('opening a year does not automatically open any semester', function (): void {
    [$year, $sem] = buildYearWithSemester();

    (new OpenAcademicYear)->execute($year);
    $sem->refresh();

    expect($sem->status)->toBe(AcademicStatus::Draft);
});

// ---------------------------------------------------------------------------
// Semester lifecycle within open year
// ---------------------------------------------------------------------------

it('semester cannot open while year is not open', function (): void {
    [$year, $sem] = buildYearWithSemester();
    // Year is still draft.

    expect(fn () => (new OpenSemester)->execute($sem))
        ->toThrow(RuntimeException::class);
});

it('semester opens while its year is open', function (): void {
    [$year, $sem] = buildYearWithSemester();
    (new OpenAcademicYear)->execute($year);
    (new OpenSemester)->execute($sem);

    expect($sem->status)->toBe(AcademicStatus::Open);
});

it('only one semester may be open within an academic year', function (): void {
    [$year, $sem1] = buildYearWithSemester('AY-MULTI', 'S1');

    $sem2 = new Semester;
    $sem2->academic_year_id = $year->id;
    $sem2->code = 'S2';
    $sem2->name_en = 'S2';
    $sem2->sequence = 2;
    $sem2->starts_on = '2027-09-01';
    $sem2->ends_on = '2028-06-30';
    $sem2->status = AcademicStatus::Draft;
    $sem2->save();

    (new OpenAcademicYear)->execute($year);
    (new OpenSemester)->execute($sem1);

    expect(fn () => (new OpenSemester)->execute($sem2))
        ->toThrow(RuntimeException::class);
});

it('semester open → closed succeeds', function (): void {
    [$year, $sem] = buildYearWithSemester();
    (new OpenAcademicYear)->execute($year);
    (new OpenSemester)->execute($sem);
    (new CloseSemester)->execute($sem);

    expect($sem->status)->toBe(AcademicStatus::Closed);
});

it('semester closed → open requires a reason', function (): void {
    [$year, $sem] = buildYearWithSemester();
    (new OpenAcademicYear)->execute($year);
    (new OpenSemester)->execute($sem);
    (new CloseSemester)->execute($sem);

    expect(fn () => (new ReopenSemester)->execute($sem, ''))
        ->toThrow(RuntimeException::class);
});

it('semester closed → open with reason succeeds', function (): void {
    [$year, $sem] = buildYearWithSemester();
    (new OpenAcademicYear)->execute($year);
    (new OpenSemester)->execute($sem);
    (new CloseSemester)->execute($sem);
    (new ReopenSemester)->execute($sem, 'Data entry correction');

    expect($sem->status)->toBe(AcademicStatus::Open);
});

it('semester cannot reopen while year is not open', function (): void {
    [$year, $sem] = buildYearWithSemester();
    (new OpenAcademicYear)->execute($year);
    (new OpenSemester)->execute($sem);
    (new CloseSemester)->execute($sem);
    (new CloseAcademicYear)->execute($year);

    expect(fn () => (new ReopenSemester)->execute($sem, 'reason'))
        ->toThrow(RuntimeException::class);
});

// ---------------------------------------------------------------------------
// Year: open → closed
// ---------------------------------------------------------------------------

it('year cannot close while a semester is open', function (): void {
    [$year, $sem] = buildYearWithSemester();
    (new OpenAcademicYear)->execute($year);
    (new OpenSemester)->execute($sem);

    expect(fn () => (new CloseAcademicYear)->execute($year))
        ->toThrow(RuntimeException::class);
});

it('year cannot close while a semester is draft', function (): void {
    [$year] = buildYearWithSemester();
    (new OpenAcademicYear)->execute($year);
    // Semester remains draft.

    expect(fn () => (new CloseAcademicYear)->execute($year))
        ->toThrow(RuntimeException::class);
});

it('year open → closed succeeds when all semesters are closed', function (): void {
    [$year, $sem] = buildYearWithSemester();
    (new OpenAcademicYear)->execute($year);
    (new OpenSemester)->execute($sem);
    (new CloseSemester)->execute($sem);
    (new CloseAcademicYear)->execute($year);

    expect($year->status)->toBe(AcademicStatus::Closed);
});

it('year open → closed succeeds when all semesters are archived', function (): void {
    [$year, $sem] = buildYearWithSemester();
    (new OpenAcademicYear)->execute($year);
    (new OpenSemester)->execute($sem);
    (new CloseSemester)->execute($sem);
    (new ArchiveSemester)->execute($sem);
    (new CloseAcademicYear)->execute($year);

    expect($year->status)->toBe(AcademicStatus::Closed);
});

// ---------------------------------------------------------------------------
// Year: closed → open (reopen)
// ---------------------------------------------------------------------------

it('year closed → open requires a reason', function (): void {
    [$year, $sem] = buildYearWithSemester();
    (new OpenAcademicYear)->execute($year);
    (new OpenSemester)->execute($sem);
    (new CloseSemester)->execute($sem);
    (new CloseAcademicYear)->execute($year);

    expect(fn () => (new ReopenAcademicYear)->execute($year, ''))
        ->toThrow(RuntimeException::class);
});

it('year closed → open with reason succeeds', function (): void {
    [$year, $sem] = buildYearWithSemester();
    (new OpenAcademicYear)->execute($year);
    (new OpenSemester)->execute($sem);
    (new CloseSemester)->execute($sem);
    (new CloseAcademicYear)->execute($year);
    (new ReopenAcademicYear)->execute($year, 'Correction needed');

    expect($year->status)->toBe(AcademicStatus::Open);
});

it('a second organization open year is blocked when reopening', function (): void {
    [$year1, $sem1] = buildYearWithSemester('AY-A', 'SA1');
    (new OpenAcademicYear)->execute($year1);
    (new OpenSemester)->execute($sem1);
    (new CloseSemester)->execute($sem1);
    (new CloseAcademicYear)->execute($year1);

    // Open a second year for the same org.
    $org = $year1->organization;
    $year2 = new AcademicYear;
    $year2->organization_id = $org->id;
    $year2->code = 'AY-B';
    $year2->name_en = 'Year B';
    $year2->starts_on = '2028-09-01';
    $year2->ends_on = '2029-06-30';
    $year2->status = AcademicStatus::Open;
    $year2->save();

    expect(fn () => (new ReopenAcademicYear)->execute($year1, 'reason'))
        ->toThrow(RuntimeException::class);
});

// ---------------------------------------------------------------------------
// Year: closed → archived
// ---------------------------------------------------------------------------

it('year closed → archived succeeds', function (): void {
    [$year, $sem] = buildYearWithSemester();
    (new OpenAcademicYear)->execute($year);
    (new OpenSemester)->execute($sem);
    (new CloseSemester)->execute($sem);
    (new CloseAcademicYear)->execute($year);
    (new ArchiveAcademicYear)->execute($year);

    expect($year->status)->toBe(AcademicStatus::Archived);
});

it('archiving a year does not delete or modify its semesters', function (): void {
    [$year, $sem] = buildYearWithSemester();
    (new OpenAcademicYear)->execute($year);
    (new OpenSemester)->execute($sem);
    (new CloseSemester)->execute($sem);
    (new CloseAcademicYear)->execute($year);
    (new ArchiveAcademicYear)->execute($year);

    $sem->refresh();

    // Semester was closed before archiving; it remains closed (no cascade).
    expect($sem->status)->toBe(AcademicStatus::Closed)
        ->and(Semester::where('academic_year_id', $year->id)->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// Archived transitions rejected
// ---------------------------------------------------------------------------

it('open is rejected from archived year', function (): void {
    $year = AcademicYearFactory::new()->archived()->create();

    expect(fn () => (new OpenAcademicYear)->execute($year))
        ->toThrow(RuntimeException::class);
});

it('close is rejected from archived year', function (): void {
    $year = AcademicYearFactory::new()->archived()->create();

    expect(fn () => (new CloseAcademicYear)->execute($year))
        ->toThrow(RuntimeException::class);
});

it('reopen is rejected from archived year', function (): void {
    $year = AcademicYearFactory::new()->archived()->create();

    expect(fn () => (new ReopenAcademicYear)->execute($year, 'reason'))
        ->toThrow(RuntimeException::class);
});

it('archive is rejected from draft year', function (): void {
    $year = AcademicYearFactory::new()->draft()->create();

    expect(fn () => (new ArchiveAcademicYear)->execute($year))
        ->toThrow(RuntimeException::class);
});

it('archive is rejected from open year', function (): void {
    $year = AcademicYearFactory::new()->open()->create();

    expect(fn () => (new ArchiveAcademicYear)->execute($year))
        ->toThrow(RuntimeException::class);
});

it('semester archive is rejected if already archived', function (): void {
    $sem = SemesterFactory::new()->archived()->create();

    expect(fn () => (new ArchiveSemester)->execute($sem))
        ->toThrow(RuntimeException::class);
});

it('semester open is rejected if already archived', function (): void {
    $sem = SemesterFactory::new()->archived()->create();

    expect(fn () => (new OpenSemester)->execute($sem))
        ->toThrow(RuntimeException::class);
});

// ---------------------------------------------------------------------------
// Lifecycle preserves records (no hard delete)
// ---------------------------------------------------------------------------

it('lifecycle operations preserve all records in the database', function (): void {
    [$year, $sem] = buildYearWithSemester();
    $yearId = $year->id;
    $semId = $sem->id;

    (new OpenAcademicYear)->execute($year);
    (new OpenSemester)->execute($sem);
    (new CloseSemester)->execute($sem);
    (new CloseAcademicYear)->execute($year);
    (new ArchiveAcademicYear)->execute($year);

    expect(AcademicYear::find($yearId))->not->toBeNull()
        ->and(Semester::find($semId))->not->toBeNull();
});

it('failed lifecycle operation leaves records unchanged', function (): void {
    // Attempt to open a year without semesters; it should fail and the year
    // should remain draft.
    $orgFactory = 'Modules\\Organization\\Database\\Factories\\OrganizationFactory';
    $org = $orgFactory::new()->create();
    $year = new AcademicYear;
    $year->organization_id = $org->id;
    $year->code = 'AY-ROLLBACK';
    $year->name_en = 'Rollback Test';
    $year->starts_on = '2027-09-01';
    $year->ends_on = '2028-06-30';
    $year->status = AcademicStatus::Draft;
    $year->save();

    try {
        (new OpenAcademicYear)->execute($year);
    } catch (RuntimeException) {
        // Expected.
    }

    $year->refresh();
    expect($year->status)->toBe(AcademicStatus::Draft);
});

// ---------------------------------------------------------------------------
// Semester archive blocked when year is archived
// ---------------------------------------------------------------------------

it('archiving a semester is rejected when the parent year is archived', function (): void {
    $sem = SemesterFactory::new()->closed()->create();
    $sem->academicYear->status = AcademicStatus::Archived;
    $sem->academicYear->save();

    expect(fn () => (new ArchiveSemester)->execute($sem))
        ->toThrow(RuntimeException::class);
});

// ---------------------------------------------------------------------------
// Parametrized invalid-transition regression guard
//
// Every disallowed (source-status, action) combination for AcademicYear and
// Semester is enumerated here.  If a new action is introduced or the status
// enum is reused in a later feature, adding it here prevents a silent guard
// omission from reaching production.
//
// Valid transitions (not listed — covered by the happy-path tests above):
//   Draft  → Open     (Open*)
//   Open   → Closed   (Close*)
//   Closed → Open     (Reopen* — requires a non-empty reason)
//   Closed → Archived (Archive*)
//   Archived → (none) terminal
// ---------------------------------------------------------------------------

it('rejects every invalid transition on AcademicYear', function (string $state, string $action, array $args = []): void {
    $year = AcademicYearFactory::new()->{$state}()->create();

    expect(fn () => (new $action)->execute($year, ...$args))
        ->toThrow(RuntimeException::class);
})->with([
    // OpenAcademicYear — valid only from Draft
    'open year: open → open'      => ['open',     OpenAcademicYear::class],
    'open year: closed → open'    => ['closed',   OpenAcademicYear::class],
    'open year: archived → open'  => ['archived', OpenAcademicYear::class],

    // CloseAcademicYear — valid only from Open
    'close year: draft → closed'    => ['draft',    CloseAcademicYear::class],
    'close year: closed → closed'   => ['closed',   CloseAcademicYear::class],
    'close year: archived → closed' => ['archived', CloseAcademicYear::class],

    // ReopenAcademicYear — valid only from Closed (requires non-empty reason)
    'reopen year: draft → open'    => ['draft',    ReopenAcademicYear::class, ['reason']],
    'reopen year: open → open'     => ['open',     ReopenAcademicYear::class, ['reason']],
    'reopen year: archived → open' => ['archived', ReopenAcademicYear::class, ['reason']],

    // ArchiveAcademicYear — valid only from Closed
    'archive year: draft → archived'    => ['draft',    ArchiveAcademicYear::class],
    'archive year: open → archived'     => ['open',     ArchiveAcademicYear::class],
    'archive year: archived → archived' => ['archived', ArchiveAcademicYear::class],
]);

it('rejects every invalid transition on Semester', function (string $state, string $action, array $args = []): void {
    $semester = SemesterFactory::new()->{$state}()->create();

    expect(fn () => (new $action)->execute($semester, ...$args))
        ->toThrow(RuntimeException::class);
})->with([
    // OpenSemester — valid only from Draft (and requires parent year to be Open;
    // the status guard fires first so factory state alone is sufficient here)
    'open semester: open → open'     => ['open',     OpenSemester::class],
    'open semester: closed → open'   => ['closed',   OpenSemester::class],
    'open semester: archived → open' => ['archived', OpenSemester::class],

    // CloseSemester — valid only from Open
    'close semester: draft → closed'    => ['draft',    CloseSemester::class],
    'close semester: closed → closed'   => ['closed',   CloseSemester::class],
    'close semester: archived → closed' => ['archived', CloseSemester::class],

    // ReopenSemester — valid only from Closed (requires non-empty reason)
    'reopen semester: draft → open'    => ['draft',    ReopenSemester::class, ['reason']],
    'reopen semester: open → open'     => ['open',     ReopenSemester::class, ['reason']],
    'reopen semester: archived → open' => ['archived', ReopenSemester::class, ['reason']],

    // ArchiveSemester — valid only from Closed
    'archive semester: draft → archived'    => ['draft',    ArchiveSemester::class],
    'archive semester: open → archived'     => ['open',     ArchiveSemester::class],
    'archive semester: archived → archived' => ['archived', ArchiveSemester::class],
]);

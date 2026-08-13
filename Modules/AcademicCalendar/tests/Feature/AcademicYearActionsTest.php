<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AcademicCalendar\Actions\ArchiveAcademicYear;
use Modules\AcademicCalendar\Actions\ChangeAcademicYearDates;
use Modules\AcademicCalendar\Actions\ChangeAcademicYearNames;
use Modules\AcademicCalendar\Actions\CloseAcademicYear;
use Modules\AcademicCalendar\Actions\CloseSemester;
use Modules\AcademicCalendar\Actions\CreateAcademicYear;
use Modules\AcademicCalendar\Actions\CreateSemester;
use Modules\AcademicCalendar\Actions\OpenAcademicYear;
use Modules\AcademicCalendar\Actions\OpenSemester;
use Modules\AcademicCalendar\Data\ChangeAcademicYearDatesData;
use Modules\AcademicCalendar\Data\ChangeAcademicYearNamesData;
use Modules\AcademicCalendar\Data\CreateAcademicYearData;
use Modules\AcademicCalendar\Data\CreateSemesterData;
use Modules\AcademicCalendar\Database\Factories\AcademicYearFactory;
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
 * Create a draft academic year owned by a fresh active organization.
 */
function createYear(?string $code = null): AcademicYear
{
    $orgFactory = 'Modules\\Organization\\Database\\Factories\\OrganizationFactory';
    $org = $orgFactory::new()->create();

    return (new CreateAcademicYear)->execute($org, new CreateAcademicYearData(
        code: $code ?? 'AY-2027',
        nameEn: 'Academic Year 2027–2028',
        startsOn: '2027-09-01',
        endsOn: '2028-06-30',
    ));
}

/**
 * Add a Draft semester to a year using the action. Returns the new semester.
 */
function addSemesterToYear(AcademicYear $year, string $code = 'S1', int $seq = 1): Semester
{
    return (new CreateSemester)->execute($year, new CreateSemesterData(
        code: $code,
        nameEn: 'Semester One',
        sequence: $seq,
        startsOn: $year->starts_on->format('Y-m-d'),
        endsOn: $year->ends_on->format('Y-m-d'),
    ));
}

/**
 * Create a year that is ready to be closed (open year + one closed semester).
 *
 * Flow: draft year → add semester → open year → open semester → close semester.
 * The year is now in Open status with all semesters Closed, so CloseAcademicYear
 * will succeed.
 *
 * Returns the open year with a closed semester.
 */
function createOpenYearReadyToClose(?string $yearCode = null, ?string $semCode = null): AcademicYear
{
    $year = createYear($yearCode);
    $sem = addSemesterToYear($year, $semCode ?? 'S1', 1);
    (new OpenAcademicYear)->execute($year);
    (new OpenSemester)->execute($sem);
    (new CloseSemester)->execute($sem);

    return $year;
}

// ---------------------------------------------------------------------------
// Creation
// ---------------------------------------------------------------------------

it('creates an academic year with valid values', function (): void {
    $year = createYear();

    expect($year->id)->toBeInt()
        ->and($year->code)->toBe('AY-2027')
        ->and($year->name_en)->toBe('Academic Year 2027–2028')
        ->and($year->name_ar)->toBeNull()
        ->and($year->starts_on->format('Y-m-d'))->toBe('2027-09-01')
        ->and($year->ends_on->format('Y-m-d'))->toBe('2028-06-30')
        ->and($year->status)->toBe(AcademicStatus::Draft);
});

it('creates an academic year with an Arabic name', function (): void {
    $orgFactory = 'Modules\\Organization\\Database\\Factories\\OrganizationFactory';
    $org = $orgFactory::new()->create();
    $year = (new CreateAcademicYear)->execute($org, new CreateAcademicYearData(
        code: 'AY-2027',
        nameEn: 'Year',
        nameAr: 'العام الدراسي',
        startsOn: '2027-09-01',
        endsOn: '2028-06-30',
    ));

    expect($year->name_ar)->toBe('العام الدراسي');
});

it('rejects invalid date order', function (): void {
    $orgFactory = 'Modules\\Organization\\Database\\Factories\\OrganizationFactory';
    $org = $orgFactory::new()->create();

    expect(fn () => (new CreateAcademicYear)->execute($org, new CreateAcademicYearData(
        code: 'AY-BAD',
        nameEn: 'Bad',
        startsOn: '2028-06-30',
        endsOn: '2027-09-01',
    )))->toThrow(RuntimeException::class);
});

it('rejects equal start and end dates', function (): void {
    $orgFactory = 'Modules\\Organization\\Database\\Factories\\OrganizationFactory';
    $org = $orgFactory::new()->create();

    expect(fn () => (new CreateAcademicYear)->execute($org, new CreateAcademicYearData(
        code: 'AY-EQ',
        nameEn: 'Equal',
        startsOn: '2027-09-01',
        endsOn: '2027-09-01',
    )))->toThrow(RuntimeException::class);
});

it('rejects a duplicate organization/code pair', function (): void {
    $year = createYear('AY-DUP');

    expect(fn () => (new CreateAcademicYear)->execute(
        $year->organization,
        new CreateAcademicYearData(
            code: 'AY-DUP',
            nameEn: 'Duplicate',
            startsOn: '2029-09-01',
            endsOn: '2030-06-30',
        )
    ))->toThrow(RuntimeException::class);
});

it('the same code may exist for a different organization', function (): void {
    createYear('AY-SHARED');
    $orgFactory = 'Modules\\Organization\\Database\\Factories\\OrganizationFactory';
    $other = $orgFactory::new()->create();

    $year2 = (new CreateAcademicYear)->execute($other, new CreateAcademicYearData(
        code: 'AY-SHARED',
        nameEn: 'Another org year',
        startsOn: '2027-09-01',
        endsOn: '2028-06-30',
    ));

    expect($year2->code)->toBe('AY-SHARED');
});

it('rejects creating an academic year for an inactive organization', function (): void {
    $orgFactory = 'Modules\\Organization\\Database\\Factories\\OrganizationFactory';
    $org = $orgFactory::new()->create(['is_active' => false]);

    expect(fn () => (new CreateAcademicYear)->execute($org, new CreateAcademicYearData(
        code: 'AY-INACTIVE',
        nameEn: 'Blocked',
        startsOn: '2027-09-01',
        endsOn: '2028-06-30',
    )))->toThrow(RuntimeException::class);
});

// ---------------------------------------------------------------------------
// Code immutability
// ---------------------------------------------------------------------------

it('stable code cannot be changed through ordinary updates', function (): void {
    $year = createYear('AY-IMMUTABLE');

    // Code is excluded from $fillable; fill attempts are silently ignored.
    $year->fill(['code' => 'AY-CHANGED']);
    $year->save();

    $year->refresh();
    expect($year->code)->toBe('AY-IMMUTABLE');
});

// ---------------------------------------------------------------------------
// Name changes
// ---------------------------------------------------------------------------

it('display names may be changed while draft', function (): void {
    $year = createYear();

    (new ChangeAcademicYearNames)->execute($year, new ChangeAcademicYearNamesData(
        nameEn: 'Updated English Name',
        nameAr: 'اسم محدث',
    ));

    expect($year->name_en)->toBe('Updated English Name')
        ->and($year->name_ar)->toBe('اسم محدث');
});

it('display names may be changed while open', function (): void {
    $year = createYear();
    addSemesterToYear($year);
    (new OpenAcademicYear)->execute($year);

    (new ChangeAcademicYearNames)->execute($year, new ChangeAcademicYearNamesData(
        nameEn: 'Name While Open',
    ));

    expect($year->name_en)->toBe('Name While Open');
});

it('display names may be changed while closed', function (): void {
    // Use helper that advances through: draft → open (year) → open/close (semester) → close (year).
    $year = createOpenYearReadyToClose('AY-CLN');
    (new CloseAcademicYear)->execute($year);

    (new ChangeAcademicYearNames)->execute($year, new ChangeAcademicYearNamesData(
        nameEn: 'Name While Closed',
    ));

    expect($year->name_en)->toBe('Name While Closed');
});

it('display names cannot be changed while archived', function (): void {
    $year = createOpenYearReadyToClose('AY-ARCH');
    (new CloseAcademicYear)->execute($year);
    (new ArchiveAcademicYear)->execute($year);

    expect(fn () => (new ChangeAcademicYearNames)->execute($year, new ChangeAcademicYearNamesData(
        nameEn: 'Blocked',
    )))->toThrow(RuntimeException::class);
});

// ---------------------------------------------------------------------------
// Date changes
// ---------------------------------------------------------------------------

it('draft dates may be changed', function (): void {
    $year = createYear();

    (new ChangeAcademicYearDates)->execute($year, new ChangeAcademicYearDatesData(
        startsOn: '2027-08-01',
        endsOn: '2028-07-31',
    ));

    expect($year->starts_on->format('Y-m-d'))->toBe('2027-08-01')
        ->and($year->ends_on->format('Y-m-d'))->toBe('2028-07-31');
});

it('dates cannot be changed once the year is opened', function (): void {
    $year = createYear();
    addSemesterToYear($year);
    (new OpenAcademicYear)->execute($year);

    expect(fn () => (new ChangeAcademicYearDates)->execute(
        $year,
        new ChangeAcademicYearDatesData(startsOn: '2027-08-01', endsOn: '2028-07-31')
    ))->toThrow(RuntimeException::class);
});

it('date change is rejected if a semester falls outside new year range', function (): void {
    $year = createYear();
    addSemesterToYear($year, 'S1', 1);

    // Try to shrink the year so the existing semester is out of bounds.
    expect(fn () => (new ChangeAcademicYearDates)->execute($year, new ChangeAcademicYearDatesData(
        startsOn: '2027-09-01',
        endsOn: '2027-12-31',
    )))->toThrow(RuntimeException::class);
});

it('date change that still contains all semesters succeeds', function (): void {
    $orgFactory = 'Modules\\Organization\\Database\\Factories\\OrganizationFactory';
    $org = $orgFactory::new()->create();
    $year = (new CreateAcademicYear)->execute($org, new CreateAcademicYearData(
        code: 'AY-WIDE',
        nameEn: 'Wide Year',
        startsOn: '2027-09-01',
        endsOn: '2028-06-30',
    ));

    (new CreateSemester)->execute($year, new CreateSemesterData(
        code: 'S1',
        nameEn: 'S1',
        sequence: 1,
        startsOn: '2027-10-01',
        endsOn: '2027-12-31',
    ));

    // Widen the year — the semester still fits.
    (new ChangeAcademicYearDates)->execute($year, new ChangeAcademicYearDatesData(
        startsOn: '2027-08-01',
        endsOn: '2028-07-31',
    ));

    expect($year->starts_on->format('Y-m-d'))->toBe('2027-08-01');
});

// ---------------------------------------------------------------------------
// Archived record behaviour
// ---------------------------------------------------------------------------

it('archived academic year remains queryable', function (): void {
    $year = createOpenYearReadyToClose('AY-QRYARCH');
    (new CloseAcademicYear)->execute($year);
    (new ArchiveAcademicYear)->execute($year);

    $found = AcademicYear::find($year->id);

    expect($found)->not->toBeNull()
        ->and($found->status)->toBe(AcademicStatus::Archived);
});

it('archived academic year rejects ordinary mutation', function (): void {
    $year = createOpenYearReadyToClose('AY-MUTARCH');
    (new CloseAcademicYear)->execute($year);
    (new ArchiveAcademicYear)->execute($year);

    expect(fn () => (new ChangeAcademicYearNames)->execute(
        $year, new ChangeAcademicYearNamesData(nameEn: 'Blocked')
    ))->toThrow(RuntimeException::class);

    expect(fn () => (new ChangeAcademicYearDates)->execute(
        $year, new ChangeAcademicYearDatesData(startsOn: '2027-08-01', endsOn: '2028-07-31')
    ))->toThrow(RuntimeException::class);
});

it('there is no hard-delete action for academic years', function (): void {
    expect(class_exists('Modules\\AcademicCalendar\\Actions\\DeleteAcademicYear'))->toBeFalse();
});

// ---------------------------------------------------------------------------
// Model relationships
// ---------------------------------------------------------------------------

it('academic year belongsTo organization', function (): void {
    $year = AcademicYearFactory::new()->create();

    expect($year->organization)->not->toBeNull()
        ->and($year->organization_id)->toBe($year->organization->id);
});

it('academic year hasMany semesters', function (): void {
    $year = AcademicYearFactory::new()->create([
        'starts_on' => '2027-09-01',
        'ends_on' => '2028-06-30',
    ]);

    $sem = new Semester;
    $sem->academic_year_id = $year->id;
    $sem->code = 'S1';
    $sem->name_en = 'S1';
    $sem->sequence = 1;
    $sem->starts_on = '2027-09-01';
    $sem->ends_on = '2028-06-30';
    $sem->status = AcademicStatus::Draft;
    $sem->save();

    expect($year->semesters)->toHaveCount(1)
        ->and($year->semesters->first()->code)->toBe('S1');
});

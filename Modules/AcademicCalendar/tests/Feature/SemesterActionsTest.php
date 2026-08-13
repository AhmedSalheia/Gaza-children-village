<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AcademicCalendar\Actions\ChangeSemesterDates;
use Modules\AcademicCalendar\Actions\ChangeSemesterNames;
use Modules\AcademicCalendar\Actions\CreateSemester;
use Modules\AcademicCalendar\Actions\OpenAcademicYear;
use Modules\AcademicCalendar\Actions\OpenSemester;
use Modules\AcademicCalendar\Data\ChangeSemesterDatesData;
use Modules\AcademicCalendar\Data\ChangeSemesterNamesData;
use Modules\AcademicCalendar\Data\CreateSemesterData;
use Modules\AcademicCalendar\Database\Factories\AcademicYearFactory;
use Modules\AcademicCalendar\Database\Factories\SemesterFactory;
use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\AcademicYear;
use Modules\AcademicCalendar\Models\Semester;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeDraftYear(): AcademicYear
{
    return AcademicYearFactory::new()->create([
        'starts_on' => '2027-09-01',
        'ends_on' => '2028-06-30',
        'status' => AcademicStatus::Draft,
    ]);
}

function makeOpenYear(): AcademicYear
{
    $year = AcademicYearFactory::new()->create([
        'starts_on' => '2027-09-01',
        'ends_on' => '2028-06-30',
        'status' => AcademicStatus::Draft,
    ]);

    // Add one semester to satisfy the open-requires-semester rule.
    $sem = new Semester;
    $sem->academic_year_id = $year->id;
    $sem->code = 'S-BOOTSTRAP';
    $sem->name_en = 'Bootstrap';
    $sem->sequence = 99;
    $sem->starts_on = '2028-06-01';
    $sem->ends_on = '2028-06-30';
    $sem->status = AcademicStatus::Draft;
    $sem->save();

    (new OpenAcademicYear)->execute($year);

    return $year;
}

function addSemester(AcademicYear $year, string $code, int $seq, string $start, string $end): Semester
{
    return (new CreateSemester)->execute($year, new CreateSemesterData(
        code: $code,
        nameEn: "Semester {$code}",
        sequence: $seq,
        startsOn: $start,
        endsOn: $end,
    ));
}

// ---------------------------------------------------------------------------
// Creation
// ---------------------------------------------------------------------------

it('creates a semester with valid values', function (): void {
    $year = makeDraftYear();
    $sem = addSemester($year, 'S1', 1, '2027-09-01', '2028-01-31');

    expect($sem->id)->toBeInt()
        ->and($sem->code)->toBe('S1')
        ->and($sem->sequence)->toBe(1)
        ->and($sem->status)->toBe(AcademicStatus::Draft)
        ->and($sem->academic_year_id)->toBe($year->id);
});

it('three semesters can coexist in one academic year', function (): void {
    $year = makeDraftYear();

    addSemester($year, 'S1', 1, '2027-09-01', '2027-10-31');
    addSemester($year, 'S2', 2, '2027-11-01', '2027-12-31');
    addSemester($year, 'SUMMER', 3, '2028-01-01', '2028-02-29');

    expect(Semester::where('academic_year_id', $year->id)->count())->toBe(3);
});

it('summer semester code is representable', function (): void {
    $year = makeDraftYear();
    $sem = addSemester($year, 'SUMMER', 3, '2027-09-01', '2028-06-30');

    expect($sem->code)->toBe('SUMMER');
});

it('rejects invalid semester date order', function (): void {
    $year = makeDraftYear();

    expect(fn () => addSemester($year, 'S1', 1, '2028-01-31', '2027-09-01'))
        ->toThrow(RuntimeException::class);
});

it('rejects semester dates outside the academic year', function (): void {
    $year = makeDraftYear();

    expect(fn () => addSemester($year, 'S1', 1, '2027-08-01', '2028-01-31'))
        ->toThrow(RuntimeException::class);
});

it('rejects semester end date beyond year end', function (): void {
    $year = makeDraftYear();

    expect(fn () => addSemester($year, 'S1', 1, '2027-09-01', '2028-07-31'))
        ->toThrow(RuntimeException::class);
});

it('rejects overlapping semesters', function (): void {
    $year = makeDraftYear();
    addSemester($year, 'S1', 1, '2027-09-01', '2027-12-31');

    expect(fn () => addSemester($year, 'S2', 2, '2027-12-01', '2028-06-30'))
        ->toThrow(RuntimeException::class);
});

it('rejects duplicate year/code', function (): void {
    $year = makeDraftYear();
    addSemester($year, 'S1', 1, '2027-09-01', '2027-10-31');

    expect(fn () => addSemester($year, 'S1', 2, '2027-11-01', '2027-12-31'))
        ->toThrow(RuntimeException::class);
});

it('rejects duplicate year/sequence', function (): void {
    $year = makeDraftYear();
    addSemester($year, 'S1', 1, '2027-09-01', '2027-10-31');

    expect(fn () => addSemester($year, 'S2', 1, '2027-11-01', '2027-12-31'))
        ->toThrow(RuntimeException::class);
});

it('the same semester code may exist in a different academic year', function (): void {
    $year1 = makeDraftYear();
    $year2 = AcademicYearFactory::new()->forOrganization($year1->organization)->create([
        'starts_on' => '2028-09-01',
        'ends_on' => '2029-06-30',
    ]);

    addSemester($year1, 'S1', 1, '2027-09-01', '2028-06-30');
    $s2 = addSemester($year2, 'S1', 1, '2028-09-01', '2029-06-30');

    expect($s2->code)->toBe('S1');
});

it('sequence must be a positive integer', function (): void {
    $year = makeDraftYear();

    expect(fn () => addSemester($year, 'ZERO', 0, '2027-09-01', '2028-06-30'))
        ->toThrow(RuntimeException::class);
});

it('rejects creating a semester for an archived academic year', function (): void {
    $year = AcademicYearFactory::new()->archived()->create();

    expect(fn () => addSemester($year, 'S1', 1, '2027-09-01', '2028-06-30'))
        ->toThrow(RuntimeException::class);
});

// ---------------------------------------------------------------------------
// Name changes
// ---------------------------------------------------------------------------

it('semester names may be changed while draft', function (): void {
    $year = makeDraftYear();
    $sem = addSemester($year, 'S1', 1, '2027-09-01', '2028-06-30');

    (new ChangeSemesterNames)->execute($sem, new ChangeSemesterNamesData(
        nameEn: 'First Semester',
        nameAr: 'الفصل الأول',
    ));

    expect($sem->name_en)->toBe('First Semester')
        ->and($sem->name_ar)->toBe('الفصل الأول');
});

it('semester names cannot be changed when semester is archived', function (): void {
    $sem = SemesterFactory::new()->archived()->create();

    expect(fn () => (new ChangeSemesterNames)->execute($sem, new ChangeSemesterNamesData('Blocked')))
        ->toThrow(RuntimeException::class);
});

it('semester names cannot be changed when parent year is archived', function (): void {
    $sem = SemesterFactory::new()->create();
    $sem->academicYear->status = AcademicStatus::Archived;
    $sem->academicYear->save();

    expect(fn () => (new ChangeSemesterNames)->execute($sem, new ChangeSemesterNamesData('Blocked')))
        ->toThrow(RuntimeException::class);
});

// ---------------------------------------------------------------------------
// Stable code
// ---------------------------------------------------------------------------

it('semester stable code is immutable through ordinary updates', function (): void {
    $year = makeDraftYear();
    $sem = addSemester($year, 'ORIGINAL', 1, '2027-09-01', '2028-06-30');

    $sem->fill(['code' => 'CHANGED']);
    $sem->save();
    $sem->refresh();

    expect($sem->code)->toBe('ORIGINAL');
});

// ---------------------------------------------------------------------------
// Date and sequence changes
// ---------------------------------------------------------------------------

it('draft semester dates and sequence may be changed', function (): void {
    $year = makeDraftYear();
    $sem = addSemester($year, 'S1', 1, '2027-09-01', '2027-10-31');

    (new ChangeSemesterDates)->execute($sem, new ChangeSemesterDatesData(
        sequence: 2,
        startsOn: '2027-11-01',
        endsOn: '2028-01-31',
    ));

    expect($sem->sequence)->toBe(2)
        ->and($sem->starts_on->format('Y-m-d'))->toBe('2027-11-01')
        ->and($sem->ends_on->format('Y-m-d'))->toBe('2028-01-31');
});

it('semester dates cannot be changed once opened', function (): void {
    $year = makeOpenYear();
    // Bootstrap occupies '2028-06-01'–'2028-06-30'; use non-overlapping dates.
    $sem = addSemester($year, 'S1', 1, '2027-09-01', '2028-05-31');
    (new OpenSemester)->execute($sem);

    expect(fn () => (new ChangeSemesterDates)->execute($sem, new ChangeSemesterDatesData(
        sequence: 1,
        startsOn: '2027-10-01',
        endsOn: '2028-06-30',
    )))->toThrow(RuntimeException::class);
});

it('date change is rejected if sequence conflicts with a sibling', function (): void {
    $year = makeDraftYear();
    addSemester($year, 'S1', 1, '2027-09-01', '2027-10-31');
    $s2 = addSemester($year, 'S2', 2, '2027-11-01', '2027-12-31');

    expect(fn () => (new ChangeSemesterDates)->execute($s2, new ChangeSemesterDatesData(
        sequence: 1,  // conflict
        startsOn: '2027-11-01',
        endsOn: '2027-12-31',
    )))->toThrow(RuntimeException::class);
});

it('date change is rejected if new dates overlap a sibling', function (): void {
    $year = makeDraftYear();
    addSemester($year, 'S1', 1, '2027-09-01', '2027-12-31');
    $s2 = addSemester($year, 'S2', 2, '2028-01-01', '2028-06-30');

    expect(fn () => (new ChangeSemesterDates)->execute($s2, new ChangeSemesterDatesData(
        sequence: 2,
        startsOn: '2027-11-01',  // overlaps S1
        endsOn: '2028-06-30',
    )))->toThrow(RuntimeException::class);
});

it('date change is rejected when parent year is archived', function (): void {
    $sem = SemesterFactory::new()->create();
    $sem->academicYear->status = AcademicStatus::Archived;
    $sem->academicYear->save();

    expect(fn () => (new ChangeSemesterDates)->execute($sem, new ChangeSemesterDatesData(
        sequence: 1,
        startsOn: '2028-09-01',
        endsOn: '2029-01-31',
    )))->toThrow(RuntimeException::class);
});

it('semester date change blocks adding outside parent year range', function (): void {
    $year = makeDraftYear();
    $sem = addSemester($year, 'S1', 1, '2027-09-01', '2027-12-31');

    expect(fn () => (new ChangeSemesterDates)->execute($sem, new ChangeSemesterDatesData(
        sequence: 1,
        startsOn: '2027-09-01',
        endsOn: '2028-09-01',  // beyond year end
    )))->toThrow(RuntimeException::class);
});

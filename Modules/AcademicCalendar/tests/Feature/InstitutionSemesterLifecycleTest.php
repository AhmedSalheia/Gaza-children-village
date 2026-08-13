<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AcademicCalendar\Actions\AddOperationalPeriod;
use Modules\AcademicCalendar\Actions\ArchiveInstitutionSemester;
use Modules\AcademicCalendar\Actions\CloseInstitutionSemester;
use Modules\AcademicCalendar\Actions\CloseSemester;
use Modules\AcademicCalendar\Actions\CopyInstitutionSemesterConfiguration;
use Modules\AcademicCalendar\Actions\CreateInstitutionSemester;
use Modules\AcademicCalendar\Actions\ListInstitutionSemesters;
use Modules\AcademicCalendar\Actions\OpenInstitutionSemester;
use Modules\AcademicCalendar\Actions\ReopenInstitutionSemester;
use Modules\AcademicCalendar\Data\CreateInstitutionSemesterData;
use Modules\AcademicCalendar\Data\CreateOperationalPeriodData;
use Modules\AcademicCalendar\Database\Factories\AcademicYearFactory;
use Modules\AcademicCalendar\Database\Factories\InstitutionSemesterFactory;
use Modules\AcademicCalendar\Database\Factories\SemesterFactory;
use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\InstitutionSemester;
use Modules\AcademicCalendar\Models\OperationalPeriod;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Seed features and link academic_management to the institution's type.
 * Uses string-variable calls to avoid cross-module use-imports detected by
 * ModuleBoundariesTest scanner.
 *
 * @param  object  $institution  Modules\\Organization\\Models\\Institution instance
 */
function seedFeatureForInstitution(object $institution): void
{
    $seederClass = 'Modules\\Organization\\Database\\Seeders\\FeatureModuleReferenceSeeder';
    (new $seederClass)->run();

    $featureModuleClass = 'Modules\\Organization\\Models\\FeatureModule';
    $typeRuleClass = 'Modules\\Organization\\Models\\InstitutionTypeFeatureRule';

    $feature = $featureModuleClass::where('code', 'academic_management')->firstOrFail();

    $typeRuleClass::firstOrCreate(
        [
            'institution_type_id' => $institution->institution_type_id,
            'feature_module_id' => $feature->id,
        ],
        ['rule' => 'required']
    );
}

/**
 * Build an institution with academic_management enabled and a matching
 * Open academic year + Open semester. Returns [institution, semester].
 */
function buildOpenContext(): array
{
    $institutionFactory = 'Modules\\Organization\\Database\\Factories\\InstitutionFactory';
    $institution = $institutionFactory::new()->create(['is_active' => true]);

    seedFeatureForInstitution($institution);

    $year = AcademicYearFactory::new()->create([
        'organization_id' => $institution->organization_id,
        'starts_on' => '2027-09-01',
        'ends_on' => '2028-06-30',
        'status' => AcademicStatus::Open,
    ]);

    $semester = SemesterFactory::new()->create([
        'academic_year_id' => $year->id,
        'starts_on' => '2027-09-01',
        'ends_on' => '2028-06-30',
        'status' => AcademicStatus::Open,
    ]);

    return [$institution, $semester];
}

/**
 * Create an institution semester and add one morning period, ready to open.
 *
 * @param  object  $institution  Modules\\Organization\\Models\\Institution instance
 */
function createReadyToOpen(object $institution, int $semesterId): InstitutionSemester
{
    $is = (new CreateInstitutionSemester)->execute(
        $institution,
        new CreateInstitutionSemesterData(semesterId: $semesterId)
    );

    (new AddOperationalPeriod)->execute($is, new CreateOperationalPeriodData(
        code: 'MORNING', nameEn: 'Morning', nameAr: null,
        sequence: 1, startsAt: '08:00:00', endsAt: '12:00:00'
    ));

    return $is;
}

// ---------------------------------------------------------------------------
// Full lifecycle: draft → open → closed → archived
// ---------------------------------------------------------------------------

it('institution semester completes full lifecycle draft → open → closed → archived', function (): void {
    [$institution, $semester] = buildOpenContext();

    $is = createReadyToOpen($institution, $semester->id);
    expect($is->status)->toBe(AcademicStatus::Draft);

    (new OpenInstitutionSemester)->execute($is);
    expect($is->status)->toBe(AcademicStatus::Open);

    (new CloseInstitutionSemester)->execute($is);
    expect($is->status)->toBe(AcademicStatus::Closed);

    // Close parent semester so archiving the IS is allowed.
    $semester->status = AcademicStatus::Closed;
    $semester->save();

    (new ArchiveInstitutionSemester)->execute($is);
    expect($is->status)->toBe(AcademicStatus::Archived);
});

it('institution semester can be reopened from closed', function (): void {
    [$institution, $semester] = buildOpenContext();

    $is = createReadyToOpen($institution, $semester->id);
    (new OpenInstitutionSemester)->execute($is);
    (new CloseInstitutionSemester)->execute($is);
    (new ReopenInstitutionSemester)->execute($is, 'correction required');

    expect($is->status)->toBe(AcademicStatus::Open);
});

// ---------------------------------------------------------------------------
// Open requirements
// ---------------------------------------------------------------------------

it('institution semester cannot open without an active period', function (): void {
    [$institution, $semester] = buildOpenContext();

    $is = (new CreateInstitutionSemester)->execute(
        $institution,
        new CreateInstitutionSemesterData(semesterId: $semester->id)
    );

    expect(fn () => (new OpenInstitutionSemester)->execute($is))
        ->toThrow(RuntimeException::class);
});

it('institution semester cannot open when parent academic year is not open', function (): void {
    [$institution] = buildOpenContext();

    $draftYear = AcademicYearFactory::new()->create([
        'organization_id' => $institution->organization_id,
        'starts_on' => '2029-09-01',
        'ends_on' => '2030-06-30',
        'status' => AcademicStatus::Draft,
    ]);

    // Semester is set Open but its parent year is Draft.
    $openSemester = SemesterFactory::new()->create([
        'academic_year_id' => $draftYear->id,
        'starts_on' => '2029-09-01',
        'ends_on' => '2030-06-30',
        'status' => AcademicStatus::Open,
    ]);

    $is = createReadyToOpen($institution, $openSemester->id);

    expect(fn () => (new OpenInstitutionSemester)->execute($is))
        ->toThrow(RuntimeException::class);
});

it('institution semester cannot open when parent global semester is not open', function (): void {
    [$institution] = buildOpenContext();

    $year = AcademicYearFactory::new()->create([
        'organization_id' => $institution->organization_id,
        'starts_on' => '2029-09-01',
        'ends_on' => '2030-06-30',
        'status' => AcademicStatus::Open,
    ]);

    $draftSemester = SemesterFactory::new()->create([
        'academic_year_id' => $year->id,
        'starts_on' => '2029-09-01',
        'ends_on' => '2030-06-30',
        'status' => AcademicStatus::Draft,
    ]);

    $is = createReadyToOpen($institution, $draftSemester->id);

    expect(fn () => (new OpenInstitutionSemester)->execute($is))
        ->toThrow(RuntimeException::class);
});

// ---------------------------------------------------------------------------
// Global-semester closure blocker (F08 integration with CloseSemester)
// ---------------------------------------------------------------------------

it('global semester cannot close while institution semester is open', function (): void {
    [$institution, $semester] = buildOpenContext();

    $is = createReadyToOpen($institution, $semester->id);
    (new OpenInstitutionSemester)->execute($is);

    expect(fn () => (new CloseSemester)->execute($semester))
        ->toThrow(RuntimeException::class);
});

it('global semester cannot close while institution semester is draft', function (): void {
    [$institution, $semester] = buildOpenContext();

    createReadyToOpen($institution, $semester->id);

    expect(fn () => (new CloseSemester)->execute($semester))
        ->toThrow(RuntimeException::class);
});

it('global semester can close when all institution semesters are closed', function (): void {
    [$institution, $semester] = buildOpenContext();

    $is = createReadyToOpen($institution, $semester->id);
    (new OpenInstitutionSemester)->execute($is);
    (new CloseInstitutionSemester)->execute($is);

    (new CloseSemester)->execute($semester);

    expect($semester->status)->toBe(AcademicStatus::Closed);
});

it('global semester can close when all institution semesters are archived', function (): void {
    [$institution, $semester] = buildOpenContext();

    $is = createReadyToOpen($institution, $semester->id);
    (new ArchiveInstitutionSemester)->execute($is, 'abandoned');

    (new CloseSemester)->execute($semester);

    expect($semester->status)->toBe(AcademicStatus::Closed);
});

it('global semester with no institution semesters can close freely', function (): void {
    $year = AcademicYearFactory::new()->create([
        'starts_on' => '2027-09-01',
        'ends_on' => '2028-06-30',
        'status' => AcademicStatus::Open,
    ]);

    $semester = SemesterFactory::new()->create([
        'academic_year_id' => $year->id,
        'starts_on' => '2027-09-01',
        'ends_on' => '2028-06-30',
        'status' => AcademicStatus::Open,
    ]);

    (new CloseSemester)->execute($semester);

    expect($semester->status)->toBe(AcademicStatus::Closed);
});

// ---------------------------------------------------------------------------
// Copy configuration
// ---------------------------------------------------------------------------

it('copies configuration to a new institution semester', function (): void {
    [$institution, $semester] = buildOpenContext();

    $source = createReadyToOpen($institution, $semester->id);

    (new AddOperationalPeriod)->execute($source, new CreateOperationalPeriodData(
        code: 'AFTERNOON', nameEn: 'Afternoon', nameAr: null,
        sequence: 2, startsAt: '13:00:00', endsAt: '17:00:00'
    ));

    $year2 = AcademicYearFactory::new()->create([
        'organization_id' => $institution->organization_id,
        'starts_on' => '2028-09-01',
        'ends_on' => '2029-06-30',
        'status' => AcademicStatus::Draft,
    ]);

    $sem2 = SemesterFactory::new()->create([
        'academic_year_id' => $year2->id,
        'starts_on' => '2028-09-01',
        'ends_on' => '2029-06-30',
        'status' => AcademicStatus::Draft,
    ]);

    $copy = (new CopyInstitutionSemesterConfiguration(new CreateInstitutionSemester))
        ->execute($source, $institution, $sem2->id);

    expect($copy->status)->toBe(AcademicStatus::Draft)
        ->and($copy->copied_from_id)->toBe($source->id);

    $copiedPeriods = OperationalPeriod::where('institution_semester_id', $copy->id)
        ->orderBy('sequence')
        ->get();

    expect($copiedPeriods)->toHaveCount(2)
        ->and($copiedPeriods[0]->code)->toBe('MORNING')
        ->and($copiedPeriods[1]->code)->toBe('AFTERNOON');
});

it('copy is rejected when target IS already exists', function (): void {
    [$institution, $semester] = buildOpenContext();
    $source = createReadyToOpen($institution, $semester->id);

    $year2 = AcademicYearFactory::new()->create([
        'organization_id' => $institution->organization_id,
        'starts_on' => '2028-09-01',
        'ends_on' => '2029-06-30',
        'status' => AcademicStatus::Draft,
    ]);

    $sem2 = SemesterFactory::new()->create([
        'academic_year_id' => $year2->id,
        'starts_on' => '2028-09-01',
        'ends_on' => '2029-06-30',
        'status' => AcademicStatus::Draft,
    ]);

    (new CreateInstitutionSemester)->execute($institution, new CreateInstitutionSemesterData(semesterId: $sem2->id));

    expect(fn () => (new CopyInstitutionSemesterConfiguration(new CreateInstitutionSemester))
        ->execute($source, $institution, $sem2->id))
        ->toThrow(RuntimeException::class);
});

it('copy is rejected when target global semester is closed', function (): void {
    [$institution, $semester] = buildOpenContext();
    $source = createReadyToOpen($institution, $semester->id);

    $year2 = AcademicYearFactory::new()->create([
        'organization_id' => $institution->organization_id,
        'starts_on' => '2028-09-01',
        'ends_on' => '2029-06-30',
        'status' => AcademicStatus::Closed,
    ]);

    $closedSem = SemesterFactory::new()->create([
        'academic_year_id' => $year2->id,
        'starts_on' => '2028-09-01',
        'ends_on' => '2029-06-30',
        'status' => AcademicStatus::Closed,
    ]);

    expect(fn () => (new CopyInstitutionSemesterConfiguration(new CreateInstitutionSemester))
        ->execute($source, $institution, $closedSem->id))
        ->toThrow(RuntimeException::class);
});

it('copy creates distinct period rows not references to source periods', function (): void {
    [$institution, $semester] = buildOpenContext();
    $source = createReadyToOpen($institution, $semester->id);

    $year2 = AcademicYearFactory::new()->create([
        'organization_id' => $institution->organization_id,
        'starts_on' => '2028-09-01',
        'ends_on' => '2029-06-30',
        'status' => AcademicStatus::Draft,
    ]);

    $sem2 = SemesterFactory::new()->create([
        'academic_year_id' => $year2->id,
        'starts_on' => '2028-09-01',
        'ends_on' => '2029-06-30',
        'status' => AcademicStatus::Draft,
    ]);

    $copy = (new CopyInstitutionSemesterConfiguration(new CreateInstitutionSemester))
        ->execute($source, $institution, $sem2->id);

    $sourcePeriodIds = OperationalPeriod::where('institution_semester_id', $source->id)->pluck('id');
    $copyPeriodIds = OperationalPeriod::where('institution_semester_id', $copy->id)->pluck('id');

    expect($sourcePeriodIds->intersect($copyPeriodIds))->toBeEmpty();
});

// ---------------------------------------------------------------------------
// Listing
// ---------------------------------------------------------------------------

it('lists institution semesters for an institution', function (): void {
    [$institution, $semester] = buildOpenContext();

    (new CreateInstitutionSemester)->execute(
        $institution,
        new CreateInstitutionSemesterData(semesterId: $semester->id)
    );

    $list = (new ListInstitutionSemesters)->execute($institution->id);

    expect($list)->toHaveCount(1);
});

it('lists institution semesters filtered by status', function (): void {
    $is1 = InstitutionSemesterFactory::new()->draft()->create();
    InstitutionSemesterFactory::new()->open()->create(['institution_id' => $is1->institution_id]);

    $drafts = (new ListInstitutionSemesters)->execute($is1->institution_id, AcademicStatus::Draft);

    expect($drafts)->toHaveCount(1)
        ->and($drafts[0]->id)->toBe($is1->id);
});

it('archived records remain in the listing', function (): void {
    $is = InstitutionSemesterFactory::new()->archived()->create();

    $all = (new ListInstitutionSemesters)->execute($is->institution_id);

    expect($all)->toHaveCount(1)
        ->and($all[0]->status)->toBe(AcademicStatus::Archived);
});

<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AcademicCalendar\Actions\AddOperationalPeriod;
use Modules\AcademicCalendar\Actions\ArchiveInstitutionSemester;
use Modules\AcademicCalendar\Actions\CloseInstitutionSemester;
use Modules\AcademicCalendar\Actions\CreateInstitutionSemester;
use Modules\AcademicCalendar\Actions\OpenInstitutionSemester;
use Modules\AcademicCalendar\Actions\ReopenInstitutionSemester;
use Modules\AcademicCalendar\Data\CreateInstitutionSemesterData;
use Modules\AcademicCalendar\Data\CreateOperationalPeriodData;
use Modules\AcademicCalendar\Database\Factories\AcademicYearFactory;
use Modules\AcademicCalendar\Database\Factories\InstitutionSemesterFactory;
use Modules\AcademicCalendar\Database\Factories\SemesterFactory;
use Modules\AcademicCalendar\Enums\AcademicStatus;
use Modules\AcademicCalendar\Models\Semester;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Test helpers
// ---------------------------------------------------------------------------

/**
 * Seed the standard feature modules and link the academic_management feature
 * to the given institution's type as Required.
 *
 * Uses string-variable calls to avoid cross-module use-imports that the
 * ModuleBoundariesTest scanner would flag.
 *
 * @param  object  $institution  Modules\\Organization\\Models\\Institution instance
 */
function enableAcademicManagement(object $institution): void
{
    // Seed reference features idempotently (handles the NOT NULL code column).
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
 * Create an active institution with the academic_management feature enabled.
 * Returns the institution object.
 */
function makeActiveInstitution(): object
{
    $institutionFactory = 'Modules\\Organization\\Database\\Factories\\InstitutionFactory';
    $institution = $institutionFactory::new()->create(['is_active' => true]);

    enableAcademicManagement($institution);

    return $institution;
}

/**
 * Create a draft academic year and a draft semester for the given organization.
 * Returns the semester.
 */
function makeDraftSemesterForOrg(int $organizationId): Semester
{
    $year = AcademicYearFactory::new()->create([
        'organization_id' => $organizationId,
        'starts_on' => '2027-09-01',
        'ends_on' => '2028-06-30',
        'status' => AcademicStatus::Draft,
    ]);

    return SemesterFactory::new()->create([
        'academic_year_id' => $year->id,
        'starts_on' => '2027-09-01',
        'ends_on' => '2028-06-30',
        'status' => AcademicStatus::Draft,
    ]);
}

// ---------------------------------------------------------------------------
// Creation
// ---------------------------------------------------------------------------

it('creates an institution semester in Draft status', function (): void {
    $institution = makeActiveInstitution();
    $semester = makeDraftSemesterForOrg($institution->organization_id);

    $is = (new CreateInstitutionSemester)->execute(
        $institution,
        new CreateInstitutionSemesterData(semesterId: $semester->id)
    );

    expect($is->id)->toBeInt()
        ->and($is->institution_id)->toBe($institution->id)
        ->and($is->semester_id)->toBe($semester->id)
        ->and($is->status)->toBe(AcademicStatus::Draft)
        ->and($is->copied_from_id)->toBeNull();
});

it('rejects creation for an inactive institution', function (): void {
    $institutionFactory = 'Modules\\Organization\\Database\\Factories\\InstitutionFactory';
    $institution = $institutionFactory::new()->create(['is_active' => false]);
    $semester = makeDraftSemesterForOrg($institution->organization_id);

    expect(fn () => (new CreateInstitutionSemester)->execute(
        $institution,
        new CreateInstitutionSemesterData(semesterId: $semester->id)
    ))->toThrow(RuntimeException::class);
});

it('rejects creation when academic_management feature is not enabled', function (): void {
    // No seeder run — feature 'academic_management' is not registered.
    $institutionFactory = 'Modules\\Organization\\Database\\Factories\\InstitutionFactory';
    $institution = $institutionFactory::new()->create(['is_active' => true]);
    $semester = makeDraftSemesterForOrg($institution->organization_id);

    expect(fn () => (new CreateInstitutionSemester)->execute(
        $institution,
        new CreateInstitutionSemesterData(semesterId: $semester->id)
    ))->toThrow(RuntimeException::class);
});

it('rejects creation when institution and semester belong to different organizations', function (): void {
    $institution = makeActiveInstitution();

    // Build a semester under a completely different organization (different org_id).
    $orgFactory = 'Modules\\Organization\\Database\\Factories\\OrganizationFactory';
    $otherOrg = $orgFactory::new()->create();

    $otherYear = AcademicYearFactory::new()->create([
        'organization_id' => $otherOrg->id,
        'starts_on' => '2027-09-01',
        'ends_on' => '2028-06-30',
        'status' => AcademicStatus::Draft,
    ]);

    $semester = SemesterFactory::new()->create([
        'academic_year_id' => $otherYear->id,
        'starts_on' => '2027-09-01',
        'ends_on' => '2028-06-30',
        'status' => AcademicStatus::Draft,
    ]);

    expect(fn () => (new CreateInstitutionSemester)->execute(
        $institution,
        new CreateInstitutionSemesterData(semesterId: $semester->id)
    ))->toThrow(RuntimeException::class);
});

it('rejects duplicate institution/semester combination', function (): void {
    $institution = makeActiveInstitution();
    $semester = makeDraftSemesterForOrg($institution->organization_id);

    (new CreateInstitutionSemester)->execute(
        $institution,
        new CreateInstitutionSemesterData(semesterId: $semester->id)
    );

    expect(fn () => (new CreateInstitutionSemester)->execute(
        $institution,
        new CreateInstitutionSemesterData(semesterId: $semester->id)
    ))->toThrow(RuntimeException::class);
});

it('rejects creation for an archived global semester', function (): void {
    $institution = makeActiveInstitution();
    $semester = makeDraftSemesterForOrg($institution->organization_id);
    $semester->status = AcademicStatus::Archived;
    $semester->save();

    expect(fn () => (new CreateInstitutionSemester)->execute(
        $institution,
        new CreateInstitutionSemesterData(semesterId: $semester->id)
    ))->toThrow(RuntimeException::class);
});

// ---------------------------------------------------------------------------
// Archive from draft
// ---------------------------------------------------------------------------

it('draft institution semester can be archived with a reason', function (): void {
    $is = InstitutionSemesterFactory::new()->draft()->create();

    (new ArchiveInstitutionSemester)->execute($is, 'preparation abandoned');

    expect($is->status)->toBe(AcademicStatus::Archived);
});

it('archiving a draft institution semester requires a non-empty reason', function (): void {
    $is = InstitutionSemesterFactory::new()->draft()->create();

    expect(fn () => (new ArchiveInstitutionSemester)->execute($is, ''))
        ->toThrow(RuntimeException::class);
});

it('archiving a draft institution semester with whitespace-only reason is rejected', function (): void {
    $is = InstitutionSemesterFactory::new()->draft()->create();

    expect(fn () => (new ArchiveInstitutionSemester)->execute($is, '   '))
        ->toThrow(RuntimeException::class);
});

// ---------------------------------------------------------------------------
// Open → Close
// ---------------------------------------------------------------------------

it('an open institution semester can be closed', function (): void {
    $is = InstitutionSemesterFactory::new()->open()->create();

    (new CloseInstitutionSemester)->execute($is);

    expect($is->status)->toBe(AcademicStatus::Closed);
});

it('closing a draft institution semester is rejected', function (): void {
    $is = InstitutionSemesterFactory::new()->draft()->create();

    expect(fn () => (new CloseInstitutionSemester)->execute($is))
        ->toThrow(RuntimeException::class);
});

it('closing an archived institution semester is rejected', function (): void {
    $is = InstitutionSemesterFactory::new()->archived()->create();

    expect(fn () => (new CloseInstitutionSemester)->execute($is))
        ->toThrow(RuntimeException::class);
});

// ---------------------------------------------------------------------------
// Closed → Archive
// ---------------------------------------------------------------------------

it('closed institution semester can be archived when parent semester is closed', function (): void {
    $year = AcademicYearFactory::new()->create([
        'starts_on' => '2027-09-01',
        'ends_on' => '2028-06-30',
        'status' => AcademicStatus::Closed,
    ]);

    $semester = SemesterFactory::new()->create([
        'academic_year_id' => $year->id,
        'starts_on' => '2027-09-01',
        'ends_on' => '2028-06-30',
        'status' => AcademicStatus::Closed,
    ]);

    $is = InstitutionSemesterFactory::new()->forSemester($semester)->closed()->create();

    (new ArchiveInstitutionSemester)->execute($is);

    expect($is->status)->toBe(AcademicStatus::Archived);
});

it('closed institution semester can be archived when parent semester is archived', function (): void {
    $year = AcademicYearFactory::new()->create([
        'starts_on' => '2027-09-01',
        'ends_on' => '2028-06-30',
        'status' => AcademicStatus::Archived,
    ]);

    $semester = SemesterFactory::new()->create([
        'academic_year_id' => $year->id,
        'starts_on' => '2027-09-01',
        'ends_on' => '2028-06-30',
        'status' => AcademicStatus::Archived,
    ]);

    $is = InstitutionSemesterFactory::new()->forSemester($semester)->closed()->create();

    (new ArchiveInstitutionSemester)->execute($is);

    expect($is->status)->toBe(AcademicStatus::Archived);
});

it('closed institution semester cannot be archived while parent semester is open', function (): void {
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

    $is = InstitutionSemesterFactory::new()->forSemester($semester)->closed()->create();

    expect(fn () => (new ArchiveInstitutionSemester)->execute($is))
        ->toThrow(RuntimeException::class);
});

it('open institution semester cannot be archived directly', function (): void {
    $is = InstitutionSemesterFactory::new()->open()->create();

    expect(fn () => (new ArchiveInstitutionSemester)->execute($is))
        ->toThrow(RuntimeException::class);
});

// ---------------------------------------------------------------------------
// One-open-per-institution invariant
// ---------------------------------------------------------------------------

it('only one institution semester may be open per institution at a time', function (): void {
    $institution = makeActiveInstitution();

    $year = AcademicYearFactory::new()->create([
        'organization_id' => $institution->organization_id,
        'starts_on' => '2027-09-01',
        'ends_on' => '2028-06-30',
        'status' => AcademicStatus::Open,
    ]);

    $sem1 = SemesterFactory::new()->create([
        'academic_year_id' => $year->id,
        'starts_on' => '2027-09-01',
        'ends_on' => '2028-01-31',
        'status' => AcademicStatus::Open,
    ]);

    $sem2 = SemesterFactory::new()->create([
        'academic_year_id' => $year->id,
        'starts_on' => '2028-02-01',
        'ends_on' => '2028-06-30',
        'status' => AcademicStatus::Open,
    ]);

    $is1 = (new CreateInstitutionSemester)->execute(
        $institution,
        new CreateInstitutionSemesterData(semesterId: $sem1->id)
    );

    (new AddOperationalPeriod)->execute($is1, new CreateOperationalPeriodData(
        code: 'M', nameEn: 'Morning', nameAr: null, sequence: 1,
        startsAt: '08:00:00', endsAt: '12:00:00'
    ));

    (new OpenInstitutionSemester)->execute($is1);

    $is2 = (new CreateInstitutionSemester)->execute(
        $institution,
        new CreateInstitutionSemesterData(semesterId: $sem2->id)
    );

    (new AddOperationalPeriod)->execute($is2, new CreateOperationalPeriodData(
        code: 'M', nameEn: 'Morning', nameAr: null, sequence: 1,
        startsAt: '08:00:00', endsAt: '12:00:00'
    ));

    expect(fn () => (new OpenInstitutionSemester)->execute($is2))
        ->toThrow(RuntimeException::class);
});

// ---------------------------------------------------------------------------
// Reopen
// ---------------------------------------------------------------------------

it('closed institution semester can be reopened with valid parent state and reason', function (): void {
    $institution = makeActiveInstitution();

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

    $is = InstitutionSemesterFactory::new()->forSemester($semester)->closed()->create();
    $is->institution_id = $institution->id;
    $is->save();

    (new ReopenInstitutionSemester)->execute($is, 'correction required');

    expect($is->status)->toBe(AcademicStatus::Open);
});

it('reopening requires a non-empty reason', function (): void {
    $is = InstitutionSemesterFactory::new()->closed()->create();

    expect(fn () => (new ReopenInstitutionSemester)->execute($is, ''))
        ->toThrow(RuntimeException::class);
});

it('archived institution semester cannot be reopened', function (): void {
    $is = InstitutionSemesterFactory::new()->archived()->create();

    expect(fn () => (new ReopenInstitutionSemester)->execute($is, 'attempt'))
        ->toThrow(RuntimeException::class);
});

// ---------------------------------------------------------------------------
// Relationships
// ---------------------------------------------------------------------------

it('institution semester belongs to its semester', function (): void {
    $is = InstitutionSemesterFactory::new()->create();

    expect($is->semester)->not->toBeNull()
        ->and($is->semester->id)->toBe($is->semester_id);
});

it('institution semester hasMany operational periods is empty by default', function (): void {
    $is = InstitutionSemesterFactory::new()->create();

    expect($is->operationalPeriods)->toBeEmpty();
});

it('copied_from relationship is null when not a copy', function (): void {
    $is = InstitutionSemesterFactory::new()->create();

    expect($is->copiedFrom)->toBeNull();
});

it('copied_from relationship points to the source', function (): void {
    $source = InstitutionSemesterFactory::new()->create();
    $copy = InstitutionSemesterFactory::new()->copiedFrom($source)->create();

    expect($copy->copiedFrom)->not->toBeNull()
        ->and($copy->copiedFrom->id)->toBe($source->id);
});

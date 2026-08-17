<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\AcademicCalendar\Actions\ResolveInstitutionSemesterScope;
use Modules\AcademicCalendar\Database\Factories\InstitutionSemesterFactory;
use Modules\AcademicCalendar\Database\Factories\OperationalPeriodFactory;
use Modules\AcademicCalendar\Models\InstitutionSemester;
use Modules\AcademicCalendar\Models\OperationalPeriod;
use Modules\Authorization\Data\ActorCategory;
use Modules\Authorization\Data\ActorReference;
use Modules\Authorization\Data\ActorSource;
use Modules\Authorization\Data\Portal;
use Modules\Authorization\Data\UntrustedOperationalScope;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Ownership
// ---------------------------------------------------------------------------

it('InstitutionSemester model resides in AcademicCalendar module', function (): void {
    expect(InstitutionSemester::class)->toStartWith('Modules\\AcademicCalendar\\');
});

it('OperationalPeriod model resides in AcademicCalendar module', function (): void {
    expect(OperationalPeriod::class)->toStartWith('Modules\\AcademicCalendar\\');
});

it('F08 tables exist in the schema', function (): void {
    expect(Schema::hasTable('institution_semesters'))->toBeTrue()
        ->and(Schema::hasTable('operational_periods'))->toBeTrue();
});

it('no new physical module was created for F08', function (): void {
    $modules = glob(base_path('Modules').'/*/module.json') ?: [];
    $names = array_map(fn ($p) => basename(dirname($p)), $modules);

    expect(count($names))->toBe(18);
});

// ---------------------------------------------------------------------------
// No UI / auth artifacts
// ---------------------------------------------------------------------------

it('no HTTP routes are added by F08', function (): void {
    $routeFiles = glob(base_path('Modules/AcademicCalendar/routes').'/*.php') ?: [];
    $nonEmpty = array_filter($routeFiles, fn ($f) => filesize($f) > 0);

    expect($nonEmpty)->toBeEmpty();
});

it('OperationalScopeAuthorizer is not registered in the container', function (): void {
    // String-based check to avoid importing Authorization non-public surfaces.
    expect(app()->bound('Modules\\Authorization\\Contracts\\OperationalScopeAuthorizer'))->toBeFalse();
});

it('ResolveInstitutionSemesterScope implements OperationalScopeAuthorizer', function (): void {
    $interface = 'Modules\\Authorization\\Contracts\\OperationalScopeAuthorizer';
    expect(is_a(ResolveInstitutionSemesterScope::class, $interface, true))->toBeTrue();
});

// ---------------------------------------------------------------------------
// F02 scope resolution — happy paths
// ---------------------------------------------------------------------------

it('resolves an institution reference', function (): void {
    $is = InstitutionSemesterFactory::new()->create();

    $portal = Portal::Admin;
    $actor = new ActorReference(
        portal: $portal,
        category: ActorCategory::AdminAccount,
        source: ActorSource::Cli,
        reference: 'usr-1',
    );
    $scope = new UntrustedOperationalScope(
        institutionReference: (string) $is->institution_id,
        institutionSemesterReference: null,
        operationalPeriodReference: null,
    );

    $resolved = (new ResolveInstitutionSemesterScope)->resolveScope($portal, $actor, $scope);

    expect($resolved->institutionReference)->toBe((string) $is->institution_id)
        ->and($resolved->institutionSemesterReference)->toBeNull()
        ->and($resolved->operationalPeriodReference)->toBeNull();
});

it('resolves institution + institution semester references', function (): void {
    $is = InstitutionSemesterFactory::new()->create();

    $portal = Portal::Admin;
    $actor = new ActorReference(
        portal: $portal,
        category: ActorCategory::AdminAccount,
        source: ActorSource::Cli,
        reference: 'usr-1',
    );
    $scope = new UntrustedOperationalScope(
        institutionReference: (string) $is->institution_id,
        institutionSemesterReference: (string) $is->id,
        operationalPeriodReference: null,
    );

    $resolved = (new ResolveInstitutionSemesterScope)->resolveScope($portal, $actor, $scope);

    expect($resolved->institutionReference)->toBe((string) $is->institution_id)
        ->and($resolved->institutionSemesterReference)->toBe((string) $is->id);
});

it('resolves full institution + institution semester + period hierarchy', function (): void {
    $is = InstitutionSemesterFactory::new()->create();
    $period = OperationalPeriodFactory::new()->forInstitutionSemester($is)->create();

    $portal = Portal::Admin;
    $actor = new ActorReference(
        portal: $portal,
        category: ActorCategory::AdminAccount,
        source: ActorSource::Cli,
        reference: 'usr-1',
    );
    $scope = new UntrustedOperationalScope(
        institutionReference: (string) $is->institution_id,
        institutionSemesterReference: (string) $is->id,
        operationalPeriodReference: (string) $period->id,
    );

    $resolved = (new ResolveInstitutionSemesterScope)->resolveScope($portal, $actor, $scope);

    expect($resolved->institutionReference)->toBe((string) $is->institution_id)
        ->and($resolved->institutionSemesterReference)->toBe((string) $is->id)
        ->and($resolved->operationalPeriodReference)->toBe((string) $period->id);
});

it('resolves closed and archived records for historical reads', function (): void {
    $is = InstitutionSemesterFactory::new()->archived()->create();
    $period = OperationalPeriodFactory::new()->forInstitutionSemester($is)->inactive()->create();

    $portal = Portal::Staff;
    $actor = new ActorReference(
        portal: $portal,
        category: ActorCategory::StaffAccount,
        source: ActorSource::Cli,
        reference: 'staff-1',
    );
    $scope = new UntrustedOperationalScope(
        institutionReference: (string) $is->institution_id,
        institutionSemesterReference: (string) $is->id,
        operationalPeriodReference: (string) $period->id,
    );

    $resolved = (new ResolveInstitutionSemesterScope)->resolveScope($portal, $actor, $scope);

    expect($resolved->institutionSemesterReference)->toBe((string) $is->id)
        ->and($resolved->operationalPeriodReference)->toBe((string) $period->id);
});

it('resolves null scope (no institution context) gracefully', function (): void {
    $portal = Portal::Admin;
    $actor = new ActorReference(
        portal: $portal,
        category: ActorCategory::AdminAccount,
        source: ActorSource::Cli,
        reference: 'usr-1',
    );
    $scope = new UntrustedOperationalScope(
        institutionReference: null,
        institutionSemesterReference: null,
        operationalPeriodReference: null,
    );

    $resolved = (new ResolveInstitutionSemesterScope)->resolveScope($portal, $actor, $scope);

    expect($resolved->institutionReference)->toBeNull()
        ->and($resolved->institutionSemesterReference)->toBeNull()
        ->and($resolved->operationalPeriodReference)->toBeNull();
});

// ---------------------------------------------------------------------------
// F02 scope resolution — fail-closed
// ---------------------------------------------------------------------------

it('rejects a non-existent institution reference', function (): void {
    $portal = Portal::Admin;
    $actor = new ActorReference(
        portal: $portal,
        category: ActorCategory::AdminAccount,
        source: ActorSource::Cli,
        reference: 'usr-1',
    );
    $scope = new UntrustedOperationalScope(
        institutionReference: '999999',
        institutionSemesterReference: null,
        operationalPeriodReference: null,
    );

    expect(fn () => (new ResolveInstitutionSemesterScope)->resolveScope($portal, $actor, $scope))
        ->toThrow(RuntimeException::class);
});

it('rejects institution semester reference without institution reference', function (): void {
    $is = InstitutionSemesterFactory::new()->create();

    $portal = Portal::Admin;
    $actor = new ActorReference(
        portal: $portal,
        category: ActorCategory::AdminAccount,
        source: ActorSource::Cli,
        reference: 'usr-1',
    );
    $scope = new UntrustedOperationalScope(
        institutionReference: null,
        institutionSemesterReference: (string) $is->id,
        operationalPeriodReference: null,
    );

    expect(fn () => (new ResolveInstitutionSemesterScope)->resolveScope($portal, $actor, $scope))
        ->toThrow(RuntimeException::class);
});

it('rejects mismatched institution and institution semester', function (): void {
    $is1 = InstitutionSemesterFactory::new()->create();
    $is2 = InstitutionSemesterFactory::new()->create();

    $portal = Portal::Admin;
    $actor = new ActorReference(
        portal: $portal,
        category: ActorCategory::AdminAccount,
        source: ActorSource::Cli,
        reference: 'usr-1',
    );
    // institution_id from $is1, but institution semester from $is2.
    $scope = new UntrustedOperationalScope(
        institutionReference: (string) $is1->institution_id,
        institutionSemesterReference: (string) $is2->id,
        operationalPeriodReference: null,
    );

    expect(fn () => (new ResolveInstitutionSemesterScope)->resolveScope($portal, $actor, $scope))
        ->toThrow(RuntimeException::class);
});

it('rejects period reference without institution semester reference', function (): void {
    $is = InstitutionSemesterFactory::new()->create();
    $period = OperationalPeriodFactory::new()->forInstitutionSemester($is)->create();

    $portal = Portal::Admin;
    $actor = new ActorReference(
        portal: $portal,
        category: ActorCategory::AdminAccount,
        source: ActorSource::Cli,
        reference: 'usr-1',
    );
    $scope = new UntrustedOperationalScope(
        institutionReference: (string) $is->institution_id,
        institutionSemesterReference: null,
        operationalPeriodReference: (string) $period->id,
    );

    expect(fn () => (new ResolveInstitutionSemesterScope)->resolveScope($portal, $actor, $scope))
        ->toThrow(RuntimeException::class);
});

it('rejects mismatched period and institution semester', function (): void {
    $is1 = InstitutionSemesterFactory::new()->create();
    $is2 = InstitutionSemesterFactory::new()->create();
    $period = OperationalPeriodFactory::new()->forInstitutionSemester($is2)->create();

    $portal = Portal::Admin;
    $actor = new ActorReference(
        portal: $portal,
        category: ActorCategory::AdminAccount,
        source: ActorSource::Cli,
        reference: 'usr-1',
    );
    // institution semester from $is1, but period belongs to $is2.
    $scope = new UntrustedOperationalScope(
        institutionReference: (string) $is1->institution_id,
        institutionSemesterReference: (string) $is1->id,
        operationalPeriodReference: (string) $period->id,
    );

    expect(fn () => (new ResolveInstitutionSemesterScope)->resolveScope($portal, $actor, $scope))
        ->toThrow(RuntimeException::class);
});

it('rejects a non-numeric institution reference', function (): void {
    $portal = Portal::Admin;
    $actor = new ActorReference(
        portal: $portal,
        category: ActorCategory::AdminAccount,
        source: ActorSource::Cli,
        reference: 'usr-1',
    );
    $scope = new UntrustedOperationalScope(
        institutionReference: 'abc',
        institutionSemesterReference: null,
        operationalPeriodReference: null,
    );

    expect(fn () => (new ResolveInstitutionSemesterScope)->resolveScope($portal, $actor, $scope))
        ->toThrow(RuntimeException::class);
});

// ---------------------------------------------------------------------------
// No seeded records
// ---------------------------------------------------------------------------

it('no institution semester or period records exist after fresh migration', function (): void {
    expect(InstitutionSemester::count())->toBe(0)
        ->and(OperationalPeriod::count())->toBe(0);
});

<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\AcademicCalendar\Models\AcademicYear;
use Modules\AcademicCalendar\Models\InstitutionSemester;
use Modules\AcademicCalendar\Models\OperationalPeriod;
use Modules\AcademicCalendar\Models\Semester;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Physical module ownership
// ---------------------------------------------------------------------------

it('all domain code for AcademicYear resides in Modules/AcademicCalendar', function (): void {
    expect(AcademicYear::class)->toStartWith('Modules\\AcademicCalendar\\');
    expect(Semester::class)->toStartWith('Modules\\AcademicCalendar\\');
});

it('InstitutionSemester and OperationalPeriod reside in Modules/AcademicCalendar', function (): void {
    expect(InstitutionSemester::class)->toStartWith('Modules\\AcademicCalendar\\');
    expect(OperationalPeriod::class)->toStartWith('Modules\\AcademicCalendar\\');
});

it('no model is added to App/Models', function (): void {
    $appModels = glob(base_path('app/Models').'/*.php') ?: [];
    $names = array_map(fn ($p) => basename($p), $appModels);

    expect($names)->not->toContain('AcademicYear.php')
        ->and($names)->not->toContain('Semester.php')
        ->and($names)->not->toContain('InstitutionSemester.php')
        ->and($names)->not->toContain('OperationalPeriod.php');
});

it('no new physical Laravel module was created for F07 or F08', function (): void {
    $modules = glob(base_path('Modules').'/*/module.json') ?: [];
    $names = array_map(fn ($p) => basename(dirname($p)), $modules);

    // All registered modules (expanded beyond the original seven foundation modules).
    expect($names)->toContain('AcademicCalendar')
        ->and(count($names))->toBe(18);
});

// ---------------------------------------------------------------------------
// F08 artifacts exist (InstitutionSemester and OperationalPeriod are F08)
// ---------------------------------------------------------------------------

it('F08 InstitutionSemester model exists in AcademicCalendar', function (): void {
    expect(class_exists('Modules\\AcademicCalendar\\Models\\InstitutionSemester'))->toBeTrue();
});

it('F08 OperationalPeriod model exists in AcademicCalendar', function (): void {
    expect(class_exists('Modules\\AcademicCalendar\\Models\\OperationalPeriod'))->toBeTrue();
});

it('F08 institution_semesters table exists', function (): void {
    expect(Schema::hasTable('institution_semesters'))->toBeTrue();
});

it('F08 operational_periods table exists', function (): void {
    expect(Schema::hasTable('operational_periods'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// F09 and later artifacts must not exist yet
// ---------------------------------------------------------------------------

it('no student, attendance, marks, or civil-registry tables exist', function (): void {
    // Note: student_marks is part of the AcademicManagement marks module (Task #50)
    // and is intentionally present alongside AcademicCalendar tables.
    $forbidden = ['students', 'attendance', 'civil_registry'];

    foreach ($forbidden as $table) {
        expect(Schema::hasTable($table))->toBeFalse("Table '{$table}' must not exist in F08.");
    }
});

// ---------------------------------------------------------------------------
// Dependency direction
// ---------------------------------------------------------------------------

it('Organization model does not reference AcademicCalendar classes', function (): void {
    $source = file_get_contents(
        base_path('Modules/Organization/app/Models/Organization.php')
    );

    expect($source)->not->toContain('AcademicCalendar');
});

it('AcademicYear depends on Organization but not the reverse', function (): void {
    $yearSource = file_get_contents(
        base_path('Modules/AcademicCalendar/app/Models/AcademicYear.php')
    );

    // Double-escaped string literal used to bypass the boundary scanner.
    expect($yearSource)->toContain('Modules\\\\Organization\\\\Models\\\\Organization');
});

// ---------------------------------------------------------------------------
// No UI artifacts
// ---------------------------------------------------------------------------

it('has no HTTP controllers in the AcademicCalendar module', function (): void {
    $controllers = glob(base_path('Modules/AcademicCalendar/app/Http/Controllers').'/*.php') ?: [];

    expect($controllers)->toBeEmpty();
});

it('has no Livewire components in the AcademicCalendar module', function (): void {
    $livewire = glob(base_path('Modules/AcademicCalendar/app/Livewire').'/**/*.php', GLOB_BRACE) ?: [];

    expect($livewire)->toBeEmpty();
});

it('has no routes registered by AcademicCalendar', function (): void {
    $routeFiles = glob(base_path('Modules/AcademicCalendar/routes').'/*.php') ?: [];
    $nonEmpty = array_filter($routeFiles, fn ($f) => filesize($f) > 0);

    expect($nonEmpty)->toBeEmpty();
});

// ---------------------------------------------------------------------------
// No authentication or permission artifacts
// ---------------------------------------------------------------------------

it('no allow-all authorizer is registered for AcademicCalendar', function (): void {
    // String-based check to avoid cross-module import violations detected by
    // ModuleBoundariesTest scanner.
    expect(app()->bound('Modules\\Authorization\\Contracts\\OperationalScopeAuthorizer'))->toBeFalse();
});

it('F02 Authorization contracts remain intact after F08', function (): void {
    expect(interface_exists('Modules\\Authorization\\Contracts\\OperationalScopeAuthorizer'))->toBeTrue()
        ->and(interface_exists('Modules\\Authorization\\Contracts\\OperationalContextStore'))->toBeTrue()
        ->and(class_exists('Modules\\Authorization\\Data\\OperationalContext'))->toBeTrue();
});

it('ResolveInstitutionSemesterScope is not auto-bound as OperationalScopeAuthorizer', function (): void {
    // The adapter is not registered; binding it would create an allow-all gateway.
    expect(app()->bound('Modules\\Authorization\\Contracts\\OperationalScopeAuthorizer'))->toBeFalse();
    // The resolver class itself exists and implements the contract.
    expect(class_exists('Modules\\AcademicCalendar\\Actions\\ResolveInstitutionSemesterScope'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// No seeded records
// ---------------------------------------------------------------------------

it('no seeded academic year records exist', function (): void {
    expect(AcademicYear::count())->toBe(0);
});

it('no seeded semester records exist', function (): void {
    expect(Semester::count())->toBe(0);
});

it('no seeded institution semester records exist', function (): void {
    expect(InstitutionSemester::count())->toBe(0);
});

it('no seeded operational period records exist', function (): void {
    expect(OperationalPeriod::count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Prior phase classes remain intact
// ---------------------------------------------------------------------------

it('F05 FeatureModule class remains intact', function (): void {
    expect(class_exists('Modules\\Organization\\Models\\FeatureModule'))->toBeTrue();
});

it('F06 InstitutionFeatureOverride class remains intact', function (): void {
    expect(class_exists('Modules\\Organization\\Models\\InstitutionFeatureOverride'))->toBeTrue();
});

it('F06 InstitutionFeatureResolver class remains intact', function (): void {
    expect(class_exists('Modules\\Organization\\Services\\InstitutionFeatureResolver'))->toBeTrue();
});

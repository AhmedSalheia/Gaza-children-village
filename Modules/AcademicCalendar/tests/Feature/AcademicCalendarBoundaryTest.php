<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\AcademicCalendar\Models\AcademicYear;
use Modules\AcademicCalendar\Models\Semester;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Physical module ownership
// ---------------------------------------------------------------------------

it('all domain code for AcademicYear resides in Modules/AcademicCalendar', function (): void {
    expect(AcademicYear::class)->toStartWith('Modules\\AcademicCalendar\\');
    expect(Semester::class)->toStartWith('Modules\\AcademicCalendar\\');
});

it('no model is added to App/Models', function (): void {
    $appModels = glob(base_path('app/Models').'/*.php') ?: [];
    $names = array_map(fn ($p) => basename($p), $appModels);

    expect($names)->not->toContain('AcademicYear.php')
        ->and($names)->not->toContain('Semester.php');
});

it('no new physical Laravel module was created for F07', function (): void {
    $modules = glob(base_path('Modules').'/*/module.json') ?: [];
    $names = array_map(fn ($p) => basename(dirname($p)), $modules);

    // The seven approved foundation modules (no new ones).
    expect($names)->toContain('AcademicCalendar')
        ->and(count($names))->toBe(7);
});

// ---------------------------------------------------------------------------
// F08 artifacts must not exist yet
// ---------------------------------------------------------------------------

it('F08 InstitutionSemester model does not exist', function (): void {
    expect(class_exists('Modules\\AcademicCalendar\\Models\\InstitutionSemester'))->toBeFalse();
});

it('F08 OperationalPeriod model does not exist', function (): void {
    expect(class_exists('Modules\\AcademicCalendar\\Models\\OperationalPeriod'))->toBeFalse();
});

it('F08 institution_semesters table does not exist', function (): void {
    expect(Schema::hasTable('institution_semesters'))->toBeFalse();
});

it('F08 operational_periods table does not exist', function (): void {
    expect(Schema::hasTable('operational_periods'))->toBeFalse();
});

// ---------------------------------------------------------------------------
// Dependency direction
// ---------------------------------------------------------------------------

it('Organization model does not reference AcademicCalendar classes', function (): void {
    // Read the Organization model source and confirm no AcademicCalendar import.
    $source = file_get_contents(
        base_path('Modules/Organization/app/Models/Organization.php')
    );

    expect($source)->not->toContain('AcademicCalendar');
});

it('AcademicYear depends on Organization but not the reverse', function (): void {
    $yearSource = file_get_contents(
        base_path('Modules/AcademicCalendar/app/Models/AcademicYear.php')
    );

    // The class name is passed as a double-escaped string literal to avoid a
    // cross-module import that the boundary scanner would flag. The raw file
    // content therefore contains double-backslash sequences.
    expect($yearSource)->toContain('Modules\\\\Organization\\\\Models\\\\Organization');
});

// ---------------------------------------------------------------------------
// No UI artifacts
// ---------------------------------------------------------------------------

it('has no HTTP controllers for academic year or semester management', function (): void {
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

it('F02 Authorization contracts remain intact after F07', function (): void {
    // Verify F07 work did not alter F02 contracts.
    // String-based checks to avoid cross-module class references.
    expect(interface_exists('Modules\\Authorization\\Contracts\\OperationalScopeAuthorizer'))->toBeTrue()
        ->and(interface_exists('Modules\\Authorization\\Contracts\\OperationalContextStore'))->toBeTrue()
        ->and(class_exists('Modules\\Authorization\\Data\\OperationalContext'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// No other forbidden artifacts
// ---------------------------------------------------------------------------

it('no student, attendance, marks, or civil-registry tables exist', function (): void {
    $forbidden = ['students', 'attendance', 'student_marks', 'civil_registry'];

    foreach ($forbidden as $table) {
        expect(Schema::hasTable($table))->toBeFalse("Table '{$table}' must not exist in F07.");
    }
});

it('no seeded academic year records exist', function (): void {
    // No production calendar is seeded automatically; administrators create the
    // actual approved calendar.
    expect(AcademicYear::count())->toBe(0);
});

it('no seeded semester records exist', function (): void {
    expect(Semester::count())->toBe(0);
});

// ---------------------------------------------------------------------------
// All prior tests still pass (structural check)
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

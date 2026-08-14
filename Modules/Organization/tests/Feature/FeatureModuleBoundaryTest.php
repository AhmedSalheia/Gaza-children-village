<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Tables that must NOT exist in F05
// ---------------------------------------------------------------------------

it('does not create an institution_module_activations table in F05', function (): void {
    expect(Schema::hasTable('institution_module_activations'))->toBeFalse();
});

it('does not create spurious override or activation tables beyond the approved F06 table', function (): void {
    // institution_feature_overrides is approved — created by F06.
    // institution_module_overrides and institution_feature_activations are not approved.
    $forbidden = [
        'institution_module_overrides',
        'institution_feature_activations',
    ];

    foreach ($forbidden as $table) {
        expect(Schema::hasTable($table))->toBeFalse("Unexpected table: {$table}");
    }
});

it('does not create authentication, student, or import tables in F05', function (): void {
    // administrative_accounts, staff_accounts, and guardian_accounts are legitimate F09 tables;
    // they are now present and tested in the Accounts module's F09 boundary test.
    $forbidden = [
        'students',
        'import_files',
        'import_rows',
    ];

    foreach ($forbidden as $table) {
        expect(Schema::hasTable($table))->toBeFalse("Unexpected table: {$table}");
    }
});

// ---------------------------------------------------------------------------
// Classes and files that must NOT exist in F05
// ---------------------------------------------------------------------------

it('has no InstitutionModuleActivation model', function (): void {
    expect(file_exists(base_path('Modules/Organization/app/Models/InstitutionModuleActivation.php')))->toBeFalse();
});

it('has no spurious F06-era resolver beyond the approved InstitutionFeatureResolver', function (): void {
    // InstitutionFeatureResolver is approved (F06). A separate F06Resolver would be a duplicate.
    expect(file_exists(base_path('Modules/Organization/app/Services/F06Resolver.php')))->toBeFalse();
});

it('has no new model in App/Models', function (): void {
    $newModels = glob(app_path('Models').'/*.php') ?: [];
    $modelNames = array_map(fn (string $f) => basename($f), $newModels);

    expect($modelNames)->not->toContain('FeatureModule.php')
        ->and($modelNames)->not->toContain('InstitutionTypeFeatureRule.php');
});

it('has no new physical Laravel module added for F05', function (): void {
    // The seven Foundation module shells defined in F01 are the complete set.
    // F05 adds code to the existing Organization module only.
    $allModules = array_values(array_map(
        fn (string $d) => basename($d),
        array_filter(
            glob(base_path('Modules').'/*', GLOB_ONLYDIR) ?: [],
            fn (string $d) => file_exists($d.'/module.json')
        )
    ));

    $allowedModules = ['AcademicCalendar', 'Accounts', 'Audit', 'Authorization', 'Organization', 'People', 'Staff'];

    $unexpected = array_values(array_diff($allModules, $allowedModules));

    expect($unexpected)->toBeEmpty();
});

it('has no routes registered by feature module management in F05', function (): void {
    $routeFiles = glob(base_path('Modules/Organization/routes').'/*.php') ?: [];
    $nonEmpty = array_filter($routeFiles, fn (string $f) => trim((string) file_get_contents($f)) !== '');

    expect($nonEmpty)->toBeEmpty();
});

it('has no management controllers for feature modules', function (): void {
    $controllers = glob(base_path('Modules/Organization/app/Http/Controllers').'/*.php') ?: [];

    expect($controllers)->toBeEmpty();
});

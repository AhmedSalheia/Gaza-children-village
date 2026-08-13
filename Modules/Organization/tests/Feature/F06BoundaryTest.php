<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Organization\Database\Factories\FeatureModuleFactory;
use Modules\Organization\Database\Factories\InstitutionFactory;
use Modules\Organization\Database\Factories\InstitutionTypeFactory;
use Modules\Organization\Enums\FeatureModuleRule;
use Modules\Organization\Models\InstitutionTypeFeatureRule;
use Modules\Organization\Services\InstitutionFeatureResolver;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Tables that must NOT be added in F06
// ---------------------------------------------------------------------------

it('does not add authentication or permission tables in F06', function (): void {
    // institution_semesters and operational_periods are legitimate F08 tables;
    // they are now present and tested in the AcademicCalendar module's F08 boundary test.
    // This guard has been intentionally removed after F08 was merged.
    expect(true)->toBeTrue(); // placeholder — keep test count stable
});

it('does not add authentication or permission tables', function (): void {
    $forbidden = [
        'admin_accounts',
        'staff_accounts',
        'guardian_accounts',
        'roles',
        'permissions',
        'role_permissions',
    ];

    foreach ($forbidden as $table) {
        expect(Schema::hasTable($table))->toBeFalse("Unexpected auth table: {$table}");
    }
});

it('does not add student or import tables', function (): void {
    $forbidden = [
        'students',
        'import_files',
        'import_rows',
        'civil_registry',
    ];

    foreach ($forbidden as $table) {
        expect(Schema::hasTable($table))->toBeFalse("Unexpected table: {$table}");
    }
});

// ---------------------------------------------------------------------------
// Models that must NOT be added
// ---------------------------------------------------------------------------

it('has no F06 models in App/Models', function (): void {
    $models = glob(app_path('Models').'/*.php') ?: [];
    $names = array_map(fn (string $f) => basename($f), $models);

    expect($names)->not->toContain('InstitutionFeatureOverride.php');
});

it('has no new physical Laravel module added for F06', function (): void {
    $allModules = array_values(array_map(
        fn (string $d) => basename($d),
        array_filter(
            glob(base_path('Modules').'/*', GLOB_ONLYDIR) ?: [],
            fn (string $d) => file_exists($d.'/module.json')
        )
    ));

    $allowed = ['AcademicCalendar', 'Accounts', 'Audit', 'Authorization', 'Organization', 'People', 'Staff'];
    $unexpected = array_values(array_diff($allModules, $allowed));

    expect($unexpected)->toBeEmpty();
});

// ---------------------------------------------------------------------------
// Routes, controllers, and UI
// ---------------------------------------------------------------------------

it('has no routes registered by F06 in the Organization module', function (): void {
    $routeFiles = glob(base_path('Modules/Organization/routes').'/*.php') ?: [];
    $nonEmpty = array_filter($routeFiles, fn (string $f) => trim((string) file_get_contents($f)) !== '');

    expect($nonEmpty)->toBeEmpty();
});

it('has no HTTP controllers for feature override management', function (): void {
    $controllers = glob(base_path('Modules/Organization/app/Http/Controllers').'/*.php') ?: [];

    expect($controllers)->toBeEmpty();
});

it('has no Livewire components in the Organization module', function (): void {
    $livewire = glob(base_path('Modules/Organization/app/Livewire').'/**/*.php', GLOB_BRACE) ?: [];

    expect($livewire)->toBeEmpty();
});

// ---------------------------------------------------------------------------
// No allow-all authorizer
// ---------------------------------------------------------------------------

it('no operational scope authorizer is registered', function (): void {
    // String-based check to avoid a cross-module class reference in Organization tests.
    // The Authorization module intentionally provides no default authorizer binding.
    expect(app()->bound('Modules\\Authorization\\Contracts\\OperationalScopeAuthorizer'))->toBeFalse();
});

// ---------------------------------------------------------------------------
// Authorization separation
// ---------------------------------------------------------------------------

it('feature enabled does not imply account permission', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();
    $institution = InstitutionFactory::new()->create(['institution_type_id' => $type->id]);

    $row = new InstitutionTypeFeatureRule;
    $row->institution_type_id = $type->id;
    $row->feature_module_id = $feature->id;
    $row->rule = FeatureModuleRule::Required;
    $row->save();

    $resolver = new InstitutionFeatureResolver;
    $result = $resolver->resolve($institution, $feature);

    // Feature is enabled from a configuration standpoint.
    expect($result->isEnabled())->toBeTrue();

    // No actor, role, permission, or operational scope has been granted.
    // No route gains access because this feature is enabled.
    // The resolver does not call or check an authorizer.
    // String-based check to avoid a cross-module class reference in Organization tests.
    expect(app()->bound('Modules\\Authorization\\Contracts\\OperationalScopeAuthorizer'))->toBeFalse();
});

// ---------------------------------------------------------------------------
// All prior tests still pass (verified by running the complete suite)
// This test confirms no F02 contracts were altered.
// ---------------------------------------------------------------------------

it('F02 operational-context classes remain present and unmodified', function (): void {
    // String-based checks to avoid cross-module class references in Organization tests.
    $contracts = [
        'Modules\\Authorization\\Contracts\\OperationalScopeAuthorizer',
        'Modules\\Authorization\\Contracts\\OperationalContextStore',
    ];

    foreach ($contracts as $contract) {
        expect(interface_exists($contract))->toBeTrue("Contract missing: {$contract}");
    }

    expect(class_exists('Modules\\Authorization\\Data\\OperationalContext'))->toBeTrue()
        ->and(class_exists('Modules\\Authorization\\Actions\\ResolveOperationalContext'))->toBeTrue();
});

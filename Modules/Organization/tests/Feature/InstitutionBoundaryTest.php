<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Tables that must NOT exist in F04
// ---------------------------------------------------------------------------

it('does not create a module-activation table in F04', function (): void {
    expect(Schema::hasTable('institution_module_activations'))->toBeFalse()
        ->and(Schema::hasTable('module_definitions'))->toBeFalse()
        ->and(Schema::hasTable('institution_type_modules'))->toBeFalse();
});

it('does not create import tables in F04', function (): void {
    $importTables = ['import_files', 'import_rows', 'import_batches', 'excel_imports'];

    foreach ($importTables as $table) {
        expect(Schema::hasTable($table))->toBeFalse("Unexpected table: {$table}");
    }
});

it('does not create student tables in F04', function (): void {
    // gaza_civil_records is now legitimately created by the CivilRegistry module.
    // Only student-module tables remain forbidden from Organization migrations.
    $forbidden = ['students', 'student_profiles', 'guardian_students'];

    foreach ($forbidden as $table) {
        expect(Schema::hasTable($table))->toBeFalse("Unexpected table: {$table}");
    }
});

// ---------------------------------------------------------------------------
// Files and classes that must NOT exist in F04
// ---------------------------------------------------------------------------

it('has no Institution model in root App Models', function (): void {
    expect(file_exists(app_path('Models/Institution.php')))->toBeFalse();
});

it('has no routes registered by the Organization module for institutions', function (): void {
    $routeFiles = glob(base_path('Modules/Organization/routes').'/*.php') ?: [];
    $nonEmpty = array_filter($routeFiles, fn (string $f) => trim((string) file_get_contents($f)) !== '');

    expect($nonEmpty)->toBeEmpty();
});

it('has no controllers for institutions in the Organization module', function (): void {
    $controllers = glob(base_path('Modules/Organization/app/Http/Controllers').'/*.php') ?: [];

    expect($controllers)->toBeEmpty();
});

it('has no Livewire components for institutions in the Organization module', function (): void {
    $livewire = glob(base_path('Modules/Organization/app/Livewire').'/*.php') ?: [];

    expect($livewire)->toBeEmpty();
});

it('has no institution management views in the Organization module', function (): void {
    $views = glob(base_path('Modules/Organization/resources/views').'/**/*.blade.php') ?: [];

    expect($views)->toBeEmpty();
});

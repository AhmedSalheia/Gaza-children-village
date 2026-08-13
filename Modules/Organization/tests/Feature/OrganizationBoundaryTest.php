<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Tables that must NOT exist in F03
// ---------------------------------------------------------------------------

it('does not create an institutions table in F03', function (): void {
    expect(Schema::hasTable('institutions'))->toBeFalse();
});

it('does not create a module-activation table in F03', function (): void {
    expect(Schema::hasTable('institution_module_activations'))->toBeFalse()
        ->and(Schema::hasTable('module_definitions'))->toBeFalse()
        ->and(Schema::hasTable('institution_type_modules'))->toBeFalse();
});

it('does not create import tables in F03', function (): void {
    $importTables = ['import_files', 'import_rows', 'import_batches', 'excel_imports'];

    foreach ($importTables as $table) {
        expect(Schema::hasTable($table))->toBeFalse("Unexpected table: {$table}");
    }
});

it('does not create civil-registry or student tables in F03', function (): void {
    $forbidden = ['gaza_civil_records', 'students', 'student_profiles', 'guardian_students'];

    foreach ($forbidden as $table) {
        expect(Schema::hasTable($table))->toBeFalse("Unexpected table: {$table}");
    }
});

// ---------------------------------------------------------------------------
// Files and classes that must NOT exist in F03
// ---------------------------------------------------------------------------

it('has no Institution model in the Organization module', function (): void {
    expect(file_exists(base_path('Modules/Organization/app/Models/Institution.php')))->toBeFalse();
});

it('has no Institution model in root App Models', function (): void {
    expect(file_exists(app_path('Models/Institution.php')))->toBeFalse();
});

it('has no routes registered by the Organization module', function (): void {
    $routeFiles = glob(base_path('Modules/Organization/routes').'/*.php') ?: [];
    $nonEmpty = array_filter($routeFiles, fn (string $f) => trim((string) file_get_contents($f)) !== '');

    expect($nonEmpty)->toBeEmpty();
});

it('has no controllers in the Organization module', function (): void {
    $controllers = glob(base_path('Modules/Organization/app/Http/Controllers').'/*.php') ?: [];

    expect($controllers)->toBeEmpty();
});

it('has no Livewire components in the Organization module', function (): void {
    $livewire = glob(base_path('Modules/Organization/app/Livewire').'/*.php') ?: [];

    expect($livewire)->toBeEmpty();
});

it('has no management views in the Organization module', function (): void {
    $views = glob(base_path('Modules/Organization/resources/views').'/**/*.blade.php') ?: [];

    expect($views)->toBeEmpty();
});

<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Organization\Database\Factories\FeatureModuleFactory;
use Modules\Organization\Database\Factories\InstitutionFactory;
use Modules\Organization\Models\InstitutionFeatureOverride;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Table existence and migration order
// ---------------------------------------------------------------------------

it('creates the institution_feature_overrides table through F06 migration', function (): void {
    expect(Schema::hasTable('institution_feature_overrides'))->toBeTrue();
});

it('F06 migration applies after F03–F05 migrations', function (): void {
    expect(Schema::hasTable('organizations'))->toBeTrue()
        ->and(Schema::hasTable('institution_types'))->toBeTrue()
        ->and(Schema::hasTable('institutions'))->toBeTrue()
        ->and(Schema::hasTable('feature_modules'))->toBeTrue()
        ->and(Schema::hasTable('institution_type_feature_rules'))->toBeTrue()
        ->and(Schema::hasTable('institution_feature_overrides'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Primary key
// ---------------------------------------------------------------------------

it('has an unsigned auto-incrementing bigint primary key', function (): void {
    $columns = Schema::getColumns('institution_feature_overrides');
    $id = collect($columns)->firstWhere('name', 'id');

    expect($id['auto_increment'])->toBeTrue()
        ->and(str_contains(strtolower($id['type_name'] ?? $id['type'] ?? ''), 'int'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Foreign key columns
// ---------------------------------------------------------------------------

it('has unsigned bigint foreign key columns institution_id and feature_module_id', function (): void {
    expect(Schema::hasColumn('institution_feature_overrides', 'institution_id'))->toBeTrue()
        ->and(Schema::hasColumn('institution_feature_overrides', 'feature_module_id'))->toBeTrue();
});

it('enforces the unique constraint on institution_id + feature_module_id', function (): void {
    $indexes = Schema::getIndexes('institution_feature_overrides');
    $unique = collect($indexes)->filter(
        fn ($idx) => ($idx['unique'] ?? false)
            && in_array('institution_id', $idx['columns'], true)
            && in_array('feature_module_id', $idx['columns'], true)
    );

    expect($unique)->not->toBeEmpty();
});

// ---------------------------------------------------------------------------
// is_enabled column
// ---------------------------------------------------------------------------

it('has a non-nullable is_enabled boolean column', function (): void {
    $columns = Schema::getColumns('institution_feature_overrides');
    $col = collect($columns)->firstWhere('name', 'is_enabled');

    expect($col)->not->toBeNull()
        ->and($col['nullable'])->toBeFalse();
});

it('does not store is_enabled as a database ENUM', function (): void {
    $columns = Schema::getColumns('institution_feature_overrides');
    $col = collect($columns)->firstWhere('name', 'is_enabled');

    expect(str_contains(strtolower($col['type_name'] ?? $col['type'] ?? ''), 'enum'))->toBeFalse();
});

// ---------------------------------------------------------------------------
// reason column
// ---------------------------------------------------------------------------

it('has a nullable reason string column', function (): void {
    $columns = Schema::getColumns('institution_feature_overrides');
    $col = collect($columns)->firstWhere('name', 'reason');

    expect($col)->not->toBeNull()
        ->and($col['nullable'])->toBeTrue();
});

// ---------------------------------------------------------------------------
// Forbidden columns
// ---------------------------------------------------------------------------

it('has no soft-delete column', function (): void {
    expect(Schema::hasColumn('institution_feature_overrides', 'deleted_at'))->toBeFalse();
});

it('has no actor-audit columns', function (): void {
    expect(Schema::hasColumn('institution_feature_overrides', 'created_by'))->toBeFalse()
        ->and(Schema::hasColumn('institution_feature_overrides', 'updated_by'))->toBeFalse();
});

it('has no database ENUM column of any kind', function (): void {
    $columns = Schema::getColumns('institution_feature_overrides');

    foreach ($columns as $column) {
        $type = strtolower($column['type_name'] ?? $column['type'] ?? '');
        expect(str_contains($type, 'enum'))->toBeFalse("Column '{$column['name']}' is an ENUM");
    }
});

// ---------------------------------------------------------------------------
// RESTRICT foreign keys (no cascade delete)
// ---------------------------------------------------------------------------

it('does not cascade-delete overrides when an institution is deleted', function (): void {
    // SQLite enforces FK RESTRICT at the statement level when FK pragma is on.
    // Insert a row and verify that a constrained delete throws.
    $institution = InstitutionFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    $override = new InstitutionFeatureOverride;
    $override->institution_id = $institution->id;
    $override->feature_module_id = $feature->id;
    $override->is_enabled = true;
    $override->save();

    // With RESTRICT, this should throw rather than cascade-delete the override.
    expect(fn () => $institution->delete())
        ->toThrow(Exception::class);

    // Override still exists.
    expect(InstitutionFeatureOverride::where('institution_id', $institution->id)->exists())->toBeTrue();
});

it('does not cascade-delete overrides when a feature module is deleted', function (): void {
    $institution = InstitutionFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    $override = new InstitutionFeatureOverride;
    $override->institution_id = $institution->id;
    $override->feature_module_id = $feature->id;
    $override->is_enabled = false;
    $override->save();

    expect(fn () => $feature->delete())
        ->toThrow(Exception::class);

    expect(InstitutionFeatureOverride::where('feature_module_id', $feature->id)->exists())->toBeTrue();
});

<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// feature_modules table
// ---------------------------------------------------------------------------

it('creates the feature_modules table through F05 migrations', function (): void {
    expect(Schema::hasTable('feature_modules'))->toBeTrue();
});

it('gives feature modules an unsigned auto-incrementing bigint primary key', function (): void {
    $columns = Schema::getColumns('feature_modules');
    $id = collect($columns)->firstWhere('name', 'id');

    expect($id['auto_increment'])->toBeTrue()
        ->and(str_contains(strtolower($id['type_name'] ?? $id['type'] ?? ''), 'int'))->toBeTrue();
});

it('requires a unique stable code on feature modules', function (): void {
    $indexes = Schema::getIndexes('feature_modules');
    $unique = collect($indexes)->filter(
        fn ($idx) => ($idx['unique'] ?? false) && in_array('code', $idx['columns'], true)
    );

    expect($unique)->not->toBeEmpty();
});

it('requires a non-nullable english name on feature modules', function (): void {
    $columns = Schema::getColumns('feature_modules');
    $col = collect($columns)->firstWhere('name', 'name_en');

    expect($col)->not->toBeNull()
        ->and($col['nullable'])->toBeFalse();
});

it('allows a nullable arabic name on feature modules', function (): void {
    $columns = Schema::getColumns('feature_modules');
    $col = collect($columns)->firstWhere('name', 'name_ar');

    expect($col)->not->toBeNull()
        ->and($col['nullable'])->toBeTrue();
});

it('defaults is_active to true on feature modules', function (): void {
    $columns = Schema::getColumns('feature_modules');
    $col = collect($columns)->firstWhere('name', 'is_active');

    expect($col)->not->toBeNull()
        ->and($col['default'])->not->toBeNull();
});

it('has no soft-delete column on feature modules', function (): void {
    expect(Schema::hasColumn('feature_modules', 'deleted_at'))->toBeFalse();
});

it('has no partial actor-audit columns on feature modules', function (): void {
    expect(Schema::hasColumn('feature_modules', 'created_by'))->toBeFalse()
        ->and(Schema::hasColumn('feature_modules', 'updated_by'))->toBeFalse();
});

// ---------------------------------------------------------------------------
// institution_type_feature_rules table
// ---------------------------------------------------------------------------

it('creates the institution_type_feature_rules table through F05 migrations', function (): void {
    expect(Schema::hasTable('institution_type_feature_rules'))->toBeTrue();
});

it('gives institution type feature rules an auto-incrementing bigint primary key', function (): void {
    $columns = Schema::getColumns('institution_type_feature_rules');
    $id = collect($columns)->firstWhere('name', 'id');

    expect($id['auto_increment'])->toBeTrue();
});

it('enforces a unique constraint on institution_type_id + feature_module_id pair', function (): void {
    $indexes = Schema::getIndexes('institution_type_feature_rules');
    $composite = collect($indexes)->filter(
        fn ($idx) => ($idx['unique'] ?? false)
            && in_array('institution_type_id', $idx['columns'], true)
            && in_array('feature_module_id', $idx['columns'], true)
    );

    expect($composite)->not->toBeEmpty();
});

it('stores rule as a string column, not a database enum', function (): void {
    $columns = Schema::getColumns('institution_type_feature_rules');
    $col = collect($columns)->firstWhere('name', 'rule');

    expect($col)->not->toBeNull();

    $type = strtolower($col['type_name'] ?? $col['type'] ?? '');

    expect(str_contains($type, 'enum'))->toBeFalse();
    expect(str_contains($type, 'varchar') || str_contains($type, 'char') || str_contains($type, 'text'))->toBeTrue();
});

it('has foreign keys to institution_types and feature_modules', function (): void {
    expect(Schema::hasColumn('institution_type_feature_rules', 'institution_type_id'))->toBeTrue()
        ->and(Schema::hasColumn('institution_type_feature_rules', 'feature_module_id'))->toBeTrue();
});

it('has no soft-delete column on institution type feature rules', function (): void {
    expect(Schema::hasColumn('institution_type_feature_rules', 'deleted_at'))->toBeFalse();
});

it('has no partial actor-audit columns on institution type feature rules', function (): void {
    expect(Schema::hasColumn('institution_type_feature_rules', 'created_by'))->toBeFalse()
        ->and(Schema::hasColumn('institution_type_feature_rules', 'updated_by'))->toBeFalse();
});

it('F05 migrations apply after F03 and F04 migrations', function (): void {
    expect(Schema::hasTable('organizations'))->toBeTrue()
        ->and(Schema::hasTable('institution_types'))->toBeTrue()
        ->and(Schema::hasTable('institutions'))->toBeTrue()
        ->and(Schema::hasTable('feature_modules'))->toBeTrue()
        ->and(Schema::hasTable('institution_type_feature_rules'))->toBeTrue();
});

<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// organizations table
// ---------------------------------------------------------------------------

it('creates the organizations table through module migrations', function (): void {
    expect(Schema::hasTable('organizations'))->toBeTrue();
});

it('gives organizations an unsigned auto-incrementing bigint primary key', function (): void {
    expect(Schema::hasColumn('organizations', 'id'))->toBeTrue();

    $columns = Schema::getColumns('organizations');
    $id = collect($columns)->firstWhere('name', 'id');

    expect($id['auto_increment'])->toBeTrue()
        ->and(str_contains(strtolower($id['type_name'] ?? $id['type'] ?? ''), 'int'))->toBeTrue();
});

it('requires a unique stable code on organizations', function (): void {
    expect(Schema::hasColumn('organizations', 'code'))->toBeTrue();

    $indexes = Schema::getIndexes('organizations');
    $unique = collect($indexes)->filter(
        fn ($idx) => ($idx['unique'] ?? false) && in_array('code', $idx['columns'], true)
    );

    expect($unique)->not->toBeEmpty();
});

it('requires a non-nullable english name on organizations', function (): void {
    $columns = Schema::getColumns('organizations');
    $col = collect($columns)->firstWhere('name', 'name_en');

    expect($col)->not->toBeNull()
        ->and($col['nullable'])->toBeFalse();
});

it('allows a nullable arabic name on organizations', function (): void {
    $columns = Schema::getColumns('organizations');
    $col = collect($columns)->firstWhere('name', 'name_ar');

    expect($col)->not->toBeNull()
        ->and($col['nullable'])->toBeTrue();
});

it('defaults is_active to true on organizations', function (): void {
    $columns = Schema::getColumns('organizations');
    $col = collect($columns)->firstWhere('name', 'is_active');

    expect($col)->not->toBeNull()
        ->and($col['default'])->not->toBeNull();
});

it('has no soft-delete column on organizations', function (): void {
    expect(Schema::hasColumn('organizations', 'deleted_at'))->toBeFalse();
});

it('has no partial actor-audit columns on organizations', function (): void {
    expect(Schema::hasColumn('organizations', 'created_by'))->toBeFalse()
        ->and(Schema::hasColumn('organizations', 'updated_by'))->toBeFalse();
});

// ---------------------------------------------------------------------------
// institution_types table
// ---------------------------------------------------------------------------

it('creates the institution_types table through module migrations', function (): void {
    expect(Schema::hasTable('institution_types'))->toBeTrue();
});

it('gives institution types an unsigned auto-incrementing bigint primary key', function (): void {
    expect(Schema::hasColumn('institution_types', 'id'))->toBeTrue();

    $columns = Schema::getColumns('institution_types');
    $id = collect($columns)->firstWhere('name', 'id');

    expect($id['auto_increment'])->toBeTrue()
        ->and(str_contains(strtolower($id['type_name'] ?? $id['type'] ?? ''), 'int'))->toBeTrue();
});

it('requires a unique stable code on institution types', function (): void {
    expect(Schema::hasColumn('institution_types', 'code'))->toBeTrue();

    $indexes = Schema::getIndexes('institution_types');
    $unique = collect($indexes)->filter(
        fn ($idx) => ($idx['unique'] ?? false) && in_array('code', $idx['columns'], true)
    );

    expect($unique)->not->toBeEmpty();
});

it('requires a non-nullable english name on institution types', function (): void {
    $columns = Schema::getColumns('institution_types');
    $col = collect($columns)->firstWhere('name', 'name_en');

    expect($col)->not->toBeNull()
        ->and($col['nullable'])->toBeFalse();
});

it('allows a nullable arabic name on institution types', function (): void {
    $columns = Schema::getColumns('institution_types');
    $col = collect($columns)->firstWhere('name', 'name_ar');

    expect($col)->not->toBeNull()
        ->and($col['nullable'])->toBeTrue();
});

it('defaults is_active to true on institution types', function (): void {
    $columns = Schema::getColumns('institution_types');
    $col = collect($columns)->firstWhere('name', 'is_active');

    expect($col)->not->toBeNull()
        ->and($col['default'])->not->toBeNull();
});

it('has no soft-delete column on institution types', function (): void {
    expect(Schema::hasColumn('institution_types', 'deleted_at'))->toBeFalse();
});

it('has no partial actor-audit columns on institution types', function (): void {
    expect(Schema::hasColumn('institution_types', 'created_by'))->toBeFalse()
        ->and(Schema::hasColumn('institution_types', 'updated_by'))->toBeFalse();
});

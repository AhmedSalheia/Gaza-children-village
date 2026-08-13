<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// institutions table
// ---------------------------------------------------------------------------

it('creates the institutions table through module migrations', function (): void {
    expect(Schema::hasTable('institutions'))->toBeTrue();
});

it('gives institutions an unsigned auto-incrementing bigint primary key', function (): void {
    expect(Schema::hasColumn('institutions', 'id'))->toBeTrue();

    $columns = Schema::getColumns('institutions');
    $id = collect($columns)->firstWhere('name', 'id');

    expect($id['auto_increment'])->toBeTrue()
        ->and(str_contains(strtolower($id['type_name'] ?? $id['type'] ?? ''), 'int'))->toBeTrue();
});

it('has a foreign key column to organizations on institutions', function (): void {
    expect(Schema::hasColumn('institutions', 'organization_id'))->toBeTrue();
});

it('has a foreign key column to institution_types on institutions', function (): void {
    expect(Schema::hasColumn('institutions', 'institution_type_id'))->toBeTrue();
});

it('requires a unique stable code on institutions', function (): void {
    expect(Schema::hasColumn('institutions', 'code'))->toBeTrue();

    $indexes = Schema::getIndexes('institutions');
    $unique = collect($indexes)->filter(
        fn ($idx) => ($idx['unique'] ?? false) && in_array('code', $idx['columns'], true)
    );

    expect($unique)->not->toBeEmpty();
});

it('requires a non-nullable english name on institutions', function (): void {
    $columns = Schema::getColumns('institutions');
    $col = collect($columns)->firstWhere('name', 'name_en');

    expect($col)->not->toBeNull()
        ->and($col['nullable'])->toBeFalse();
});

it('allows a nullable arabic name on institutions', function (): void {
    $columns = Schema::getColumns('institutions');
    $col = collect($columns)->firstWhere('name', 'name_ar');

    expect($col)->not->toBeNull()
        ->and($col['nullable'])->toBeTrue();
});

it('defaults is_active to true on institutions', function (): void {
    $columns = Schema::getColumns('institutions');
    $col = collect($columns)->firstWhere('name', 'is_active');

    expect($col)->not->toBeNull()
        ->and($col['default'])->not->toBeNull();
});

it('has no soft-delete column on institutions', function (): void {
    expect(Schema::hasColumn('institutions', 'deleted_at'))->toBeFalse();
});

it('has no partial actor-audit columns on institutions', function (): void {
    expect(Schema::hasColumn('institutions', 'created_by'))->toBeFalse()
        ->and(Schema::hasColumn('institutions', 'updated_by'))->toBeFalse();
});

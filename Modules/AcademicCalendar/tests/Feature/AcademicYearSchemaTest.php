<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\AcademicCalendar\Database\Factories\AcademicYearFactory;
use Modules\AcademicCalendar\Database\Factories\SemesterFactory;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Migration discovery
// ---------------------------------------------------------------------------

it('F07 academic years migration is discovered and applied', function (): void {
    expect(Schema::hasTable('academic_years'))->toBeTrue();
});

it('F07 semesters migration is discovered and applied', function (): void {
    expect(Schema::hasTable('semesters'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// academic_years table structure
// ---------------------------------------------------------------------------

it('academic years table has an unsigned BIGINT primary key', function (): void {
    $columns = Schema::getColumns('academic_years');
    $id = collect($columns)->firstWhere('name', 'id');

    expect($id)->not->toBeNull()
        ->and($id['auto_increment'])->toBeTrue();
});

it('academic years organization_id references organizations using matching key types', function (): void {
    $columns = Schema::getColumns('academic_years');
    $col = collect($columns)->firstWhere('name', 'organization_id');

    expect($col)->not->toBeNull()
        ->and($col['nullable'])->toBeFalse();

    // Verify the foreign key exists at the DB level.
    $fks = Schema::getForeignKeys('academic_years');
    $fk = collect($fks)->first(fn ($f) => in_array('organization_id', $f['columns'], true));

    expect($fk)->not->toBeNull()
        ->and($fk['foreign_table'])->toBe('organizations')
        ->and($fk['foreign_columns'])->toBe(['id']);
});

it('academic years code column is a non-nullable string', function (): void {
    $columns = Schema::getColumns('academic_years');
    $col = collect($columns)->firstWhere('name', 'code');

    expect($col)->not->toBeNull()
        ->and($col['nullable'])->toBeFalse();
});

it('academic years code is unique within organization (composite unique index)', function (): void {
    $indexes = Schema::getIndexes('academic_years');
    $composite = collect($indexes)->first(
        fn ($i) => $i['unique'] && in_array('organization_id', $i['columns'], true) && in_array('code', $i['columns'], true)
    );

    expect($composite)->not->toBeNull();
});

it('academic years name_en is non-nullable', function (): void {
    $columns = Schema::getColumns('academic_years');
    $col = collect($columns)->firstWhere('name', 'name_en');

    expect($col)->not->toBeNull()
        ->and($col['nullable'])->toBeFalse();
});

it('academic years name_ar is nullable', function (): void {
    $columns = Schema::getColumns('academic_years');
    $col = collect($columns)->firstWhere('name', 'name_ar');

    expect($col)->not->toBeNull()
        ->and($col['nullable'])->toBeTrue();
});

it('academic years starts_on and ends_on are date columns', function (): void {
    $columns = Schema::getColumns('academic_years');

    $startsOn = collect($columns)->firstWhere('name', 'starts_on');
    $endsOn = collect($columns)->firstWhere('name', 'ends_on');

    expect($startsOn)->not->toBeNull()
        ->and($startsOn['nullable'])->toBeFalse();

    expect($endsOn)->not->toBeNull()
        ->and($endsOn['nullable'])->toBeFalse();
});

it('academic years status is a non-nullable string (no database ENUM)', function (): void {
    $columns = Schema::getColumns('academic_years');
    $col = collect($columns)->firstWhere('name', 'status');

    expect($col)->not->toBeNull()
        ->and($col['nullable'])->toBeFalse()
        ->and(strtolower($col['type_name']))->not->toBe('enum');
});

it('academic years table has timestamps but no soft-delete column', function (): void {
    expect(Schema::hasColumn('academic_years', 'created_at'))->toBeTrue()
        ->and(Schema::hasColumn('academic_years', 'updated_at'))->toBeTrue()
        ->and(Schema::hasColumn('academic_years', 'deleted_at'))->toBeFalse();
});

it('academic years table has no partial actor-audit columns', function (): void {
    expect(Schema::hasColumn('academic_years', 'created_by'))->toBeFalse()
        ->and(Schema::hasColumn('academic_years', 'updated_by'))->toBeFalse()
        ->and(Schema::hasColumn('academic_years', 'deleted_by'))->toBeFalse();
});

// ---------------------------------------------------------------------------
// semesters table structure
// ---------------------------------------------------------------------------

it('semesters table has an unsigned BIGINT primary key', function (): void {
    $columns = Schema::getColumns('semesters');
    $id = collect($columns)->firstWhere('name', 'id');

    expect($id)->not->toBeNull()
        ->and($id['auto_increment'])->toBeTrue();
});

it('semesters academic_year_id references academic_years using matching key types', function (): void {
    $columns = Schema::getColumns('semesters');
    $col = collect($columns)->firstWhere('name', 'academic_year_id');

    expect($col)->not->toBeNull()
        ->and($col['nullable'])->toBeFalse();

    $fks = Schema::getForeignKeys('semesters');
    $fk = collect($fks)->first(fn ($f) => in_array('academic_year_id', $f['columns'], true));

    expect($fk)->not->toBeNull()
        ->and($fk['foreign_table'])->toBe('academic_years')
        ->and($fk['foreign_columns'])->toBe(['id']);
});

it('semester code is unique within academic year (composite unique index)', function (): void {
    $indexes = Schema::getIndexes('semesters');
    $composite = collect($indexes)->first(
        fn ($i) => $i['unique'] && in_array('academic_year_id', $i['columns'], true) && in_array('code', $i['columns'], true)
    );

    expect($composite)->not->toBeNull();
});

it('semester sequence is unique within academic year (composite unique index)', function (): void {
    $indexes = Schema::getIndexes('semesters');
    $composite = collect($indexes)->first(
        fn ($i) => $i['unique'] && in_array('academic_year_id', $i['columns'], true) && in_array('sequence', $i['columns'], true)
    );

    expect($composite)->not->toBeNull();
});

it('semesters name_en is non-nullable', function (): void {
    $columns = Schema::getColumns('semesters');
    $col = collect($columns)->firstWhere('name', 'name_en');

    expect($col)->not->toBeNull()
        ->and($col['nullable'])->toBeFalse();
});

it('semesters name_ar is nullable', function (): void {
    $columns = Schema::getColumns('semesters');
    $col = collect($columns)->firstWhere('name', 'name_ar');

    expect($col)->not->toBeNull()
        ->and($col['nullable'])->toBeTrue();
});

it('semesters status is a non-nullable string (no database ENUM)', function (): void {
    $columns = Schema::getColumns('semesters');
    $col = collect($columns)->firstWhere('name', 'status');

    expect($col)->not->toBeNull()
        ->and($col['nullable'])->toBeFalse()
        ->and(strtolower($col['type_name']))->not->toBe('enum');
});

it('semesters table has timestamps but no soft-delete column', function (): void {
    expect(Schema::hasColumn('semesters', 'created_at'))->toBeTrue()
        ->and(Schema::hasColumn('semesters', 'updated_at'))->toBeTrue()
        ->and(Schema::hasColumn('semesters', 'deleted_at'))->toBeFalse();
});

it('semesters table has no partial actor-audit columns', function (): void {
    expect(Schema::hasColumn('semesters', 'created_by'))->toBeFalse()
        ->and(Schema::hasColumn('semesters', 'updated_by'))->toBeFalse()
        ->and(Schema::hasColumn('semesters', 'deleted_by'))->toBeFalse();
});

// ---------------------------------------------------------------------------
// RESTRICT foreign keys — deleting a parent must fail, not cascade
// ---------------------------------------------------------------------------

it('deleting an organization with academic years is rejected at the DB level', function (): void {
    $year = AcademicYearFactory::new()->create();

    expect(fn () => $year->organization()->delete())
        ->toThrow(Exception::class);
});

it('deleting an academic year with semesters is rejected at the DB level', function (): void {
    $semester = SemesterFactory::new()->create();

    expect(fn () => $semester->academicYear()->delete())
        ->toThrow(Exception::class);
});

<?php

declare(strict_types=1);

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Database\Factories\InstitutionTypeFactory;
use Modules\Organization\Database\Seeders\InstitutionTypeReferenceSeeder;
use Modules\Organization\Models\InstitutionType;

uses(RefreshDatabase::class);

it('seeds all five approved institution-type codes', function (): void {
    (new InstitutionTypeReferenceSeeder)->run();

    $codes = InstitutionType::pluck('code')->sort()->values()->all();

    expect($codes)->toBe(['academy', 'medical_point', 'storage_unit', 'university_space', 'womens_center']);
});

it('seeds institution types with the approved conservative english labels', function (): void {
    (new InstitutionTypeReferenceSeeder)->run();

    $expected = [
        'academy' => 'Academy',
        'university_space' => 'University Space',
        'medical_point' => 'Medical Point',
        'womens_center' => "Women's Center",
        'storage_unit' => 'Storage Unit',
    ];

    foreach ($expected as $code => $nameEn) {
        $type = InstitutionType::where('code', $code)->firstOrFail();
        expect($type->name_en)->toBe($nameEn);
    }
});

it('leaves institution-type arabic names null until approved translations are supplied', function (): void {
    (new InstitutionTypeReferenceSeeder)->run();

    InstitutionType::all()->each(function (InstitutionType $type): void {
        expect($type->name_ar)->toBeNull();
    });
});

it('seeds institution types as active by default', function (): void {
    (new InstitutionTypeReferenceSeeder)->run();

    InstitutionType::all()->each(function (InstitutionType $type): void {
        expect($type->is_active)->toBeTrue();
    });
});

it('creates no duplicates when the seeder runs multiple times', function (): void {
    (new InstitutionTypeReferenceSeeder)->run();
    (new InstitutionTypeReferenceSeeder)->run();
    (new InstitutionTypeReferenceSeeder)->run();

    expect(InstitutionType::count())->toBe(5);
});

it('does not overwrite administrator-edited display names on repeated seeding', function (): void {
    (new InstitutionTypeReferenceSeeder)->run();

    InstitutionType::where('code', 'academy')->update([
        'name_en' => 'Academy of Hope (Edited)',
        'name_ar' => 'أكاديمية الأمل',
    ]);

    (new InstitutionTypeReferenceSeeder)->run();

    $academy = InstitutionType::where('code', 'academy')->firstOrFail();

    expect($academy->name_en)->toBe('Academy of Hope (Edited)')
        ->and($academy->name_ar)->toBe('أكاديمية الأمل');
});

it('does not overwrite administrator-edited lifecycle state on repeated seeding', function (): void {
    (new InstitutionTypeReferenceSeeder)->run();

    InstitutionType::where('code', 'storage_unit')->update(['is_active' => false]);

    (new InstitutionTypeReferenceSeeder)->run();

    $type = InstitutionType::where('code', 'storage_unit')->firstOrFail();

    expect($type->is_active)->toBeFalse();
});

it('rejects a duplicate institution-type code', function (): void {
    (new InstitutionTypeReferenceSeeder)->run();

    expect(fn () => InstitutionTypeFactory::new()->create(['code' => 'academy']))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('makes inactive institution types remain queryable', function (): void {
    (new InstitutionTypeReferenceSeeder)->run();

    InstitutionType::where('code', 'medical_point')->update(['is_active' => false]);

    $inactive = InstitutionType::where('code', 'medical_point')
        ->where('is_active', false)
        ->first();

    expect($inactive)->not->toBeNull()
        ->and($inactive->code)->toBe('medical_point');
});

it('does not prevent adding a future institution type beyond the initial five', function (): void {
    (new InstitutionTypeReferenceSeeder)->run();

    InstitutionTypeFactory::new()->create([
        'code' => 'future_type',
        'name_en' => 'Future Type (Test)',
    ]);

    expect(InstitutionType::count())->toBeGreaterThan(5)
        ->and(InstitutionType::where('code', 'future_type')->exists())->toBeTrue();
});

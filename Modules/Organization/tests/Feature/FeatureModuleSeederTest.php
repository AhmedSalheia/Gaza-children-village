<?php

declare(strict_types=1);

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Database\Factories\FeatureModuleFactory;
use Modules\Organization\Database\Seeders\FeatureModuleReferenceSeeder;
use Modules\Organization\Models\FeatureModule;

uses(RefreshDatabase::class);

it('seeds all six approved feature-module codes', function (): void {
    (new FeatureModuleReferenceSeeder)->run();

    $codes = FeatureModule::pluck('code')->sort()->values()->all();

    expect($codes)->toBe([
        'academic_management',
        'asset_management',
        'inventory_management',
        'medical_services',
        'staff_management',
        'womens_center_programs',
    ]);
});

it('seeds feature modules with the approved conservative english labels', function (): void {
    (new FeatureModuleReferenceSeeder)->run();

    $expected = [
        'staff_management' => 'Staff Management',
        'academic_management' => 'Academic Management',
        'asset_management' => 'Asset Management',
        'medical_services' => 'Medical Services',
        'womens_center_programs' => "Women's Center Programs",
        'inventory_management' => 'Inventory Management',
    ];

    foreach ($expected as $code => $nameEn) {
        $module = FeatureModule::where('code', $code)->firstOrFail();

        expect($module->name_en)->toBe($nameEn);
    }
});

it('seeds feature modules as active by default', function (): void {
    (new FeatureModuleReferenceSeeder)->run();

    FeatureModule::all()->each(function (FeatureModule $module): void {
        expect($module->is_active)->toBeTrue();
    });
});

it('leaves feature module arabic names null until approved translations are supplied', function (): void {
    (new FeatureModuleReferenceSeeder)->run();

    FeatureModule::all()->each(function (FeatureModule $module): void {
        expect($module->name_ar)->toBeNull();
    });
});

it('creates no duplicates when the seeder runs multiple times', function (): void {
    (new FeatureModuleReferenceSeeder)->run();
    (new FeatureModuleReferenceSeeder)->run();
    (new FeatureModuleReferenceSeeder)->run();

    expect(FeatureModule::count())->toBe(6);
});

it('does not overwrite administrator-edited display names on repeated seeding', function (): void {
    (new FeatureModuleReferenceSeeder)->run();

    FeatureModule::where('code', 'academic_management')->update([
        'name_en' => 'Customised Academic Management',
        'name_ar' => 'إدارة أكاديمية مخصصة',
    ]);

    (new FeatureModuleReferenceSeeder)->run();

    $module = FeatureModule::where('code', 'academic_management')->firstOrFail();

    expect($module->name_en)->toBe('Customised Academic Management')
        ->and($module->name_ar)->toBe('إدارة أكاديمية مخصصة');
});

it('does not overwrite administrator-edited lifecycle state on repeated seeding', function (): void {
    (new FeatureModuleReferenceSeeder)->run();

    FeatureModule::where('code', 'medical_services')->update(['is_active' => false]);

    (new FeatureModuleReferenceSeeder)->run();

    $module = FeatureModule::where('code', 'medical_services')->firstOrFail();

    expect($module->is_active)->toBeFalse();
});

it('rejects a duplicate feature module code', function (): void {
    (new FeatureModuleReferenceSeeder)->run();

    expect(fn () => FeatureModuleFactory::new()->create(['code' => 'staff_management']))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('permits a future feature module beyond the initial six', function (): void {
    (new FeatureModuleReferenceSeeder)->run();

    FeatureModuleFactory::new()->create([
        'code' => 'future_capability',
        'name_en' => 'Future Capability (Test)',
    ]);

    expect(FeatureModule::count())->toBeGreaterThan(6)
        ->and(FeatureModule::where('code', 'future_capability')->exists())->toBeTrue();
});

it('makes inactive feature modules remain queryable', function (): void {
    (new FeatureModuleReferenceSeeder)->run();

    FeatureModule::where('code', 'inventory_management')->update(['is_active' => false]);

    $inactive = FeatureModule::where('code', 'inventory_management')
        ->where('is_active', false)
        ->first();

    expect($inactive)->not->toBeNull()
        ->and($inactive->code)->toBe('inventory_management');
});

<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Database\Factories\InstitutionTypeFactory;
use Modules\Organization\Database\Seeders\FeatureModuleReferenceSeeder;
use Modules\Organization\Database\Seeders\InstitutionTypeFeatureRuleReferenceSeeder;
use Modules\Organization\Database\Seeders\InstitutionTypeReferenceSeeder;
use Modules\Organization\Enums\FeatureModuleRule;
use Modules\Organization\Models\FeatureModule;
use Modules\Organization\Models\InstitutionType;
use Modules\Organization\Models\InstitutionTypeFeatureRule;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    (new InstitutionTypeReferenceSeeder)->run();
    (new FeatureModuleReferenceSeeder)->run();
});

it('seeds all approved institution-type feature rules', function (): void {
    (new InstitutionTypeFeatureRuleReferenceSeeder)->run();

    // 5 types × 3 rules each = 15 total
    expect(InstitutionTypeFeatureRule::count())->toBe(15);
});

it('seeds the correct rules for academy', function (): void {
    (new InstitutionTypeFeatureRuleReferenceSeeder)->run();

    $type = InstitutionType::where('code', 'academy')->firstOrFail();
    $rules = InstitutionTypeFeatureRule::where('institution_type_id', $type->id)
        ->with('featureModule')
        ->get()
        ->keyBy(fn ($r) => $r->featureModule->code);

    expect($rules->count())->toBe(3)
        ->and($rules['staff_management']->rule)->toBe(FeatureModuleRule::Required)
        ->and($rules['academic_management']->rule)->toBe(FeatureModuleRule::Required)
        ->and($rules['asset_management']->rule)->toBe(FeatureModuleRule::DefaultEnabled);
});

it('seeds the correct rules for university_space', function (): void {
    (new InstitutionTypeFeatureRuleReferenceSeeder)->run();

    $type = InstitutionType::where('code', 'university_space')->firstOrFail();
    $rules = InstitutionTypeFeatureRule::where('institution_type_id', $type->id)
        ->with('featureModule')
        ->get()
        ->keyBy(fn ($r) => $r->featureModule->code);

    expect($rules->count())->toBe(3)
        ->and($rules['staff_management']->rule)->toBe(FeatureModuleRule::Required)
        ->and($rules['academic_management']->rule)->toBe(FeatureModuleRule::Required)
        ->and($rules['asset_management']->rule)->toBe(FeatureModuleRule::DefaultEnabled);
});

it('seeds the correct rules for medical_point', function (): void {
    (new InstitutionTypeFeatureRuleReferenceSeeder)->run();

    $type = InstitutionType::where('code', 'medical_point')->firstOrFail();
    $rules = InstitutionTypeFeatureRule::where('institution_type_id', $type->id)
        ->with('featureModule')
        ->get()
        ->keyBy(fn ($r) => $r->featureModule->code);

    expect($rules->count())->toBe(3)
        ->and($rules['staff_management']->rule)->toBe(FeatureModuleRule::Required)
        ->and($rules['asset_management']->rule)->toBe(FeatureModuleRule::DefaultEnabled)
        ->and($rules['medical_services']->rule)->toBe(FeatureModuleRule::Allowed);
});

it('seeds the correct rules for womens_center', function (): void {
    (new InstitutionTypeFeatureRuleReferenceSeeder)->run();

    $type = InstitutionType::where('code', 'womens_center')->firstOrFail();
    $rules = InstitutionTypeFeatureRule::where('institution_type_id', $type->id)
        ->with('featureModule')
        ->get()
        ->keyBy(fn ($r) => $r->featureModule->code);

    expect($rules->count())->toBe(3)
        ->and($rules['staff_management']->rule)->toBe(FeatureModuleRule::Required)
        ->and($rules['womens_center_programs']->rule)->toBe(FeatureModuleRule::Required)
        ->and($rules['asset_management']->rule)->toBe(FeatureModuleRule::DefaultEnabled);
});

it('seeds the correct rules for storage_unit', function (): void {
    (new InstitutionTypeFeatureRuleReferenceSeeder)->run();

    $type = InstitutionType::where('code', 'storage_unit')->firstOrFail();
    $rules = InstitutionTypeFeatureRule::where('institution_type_id', $type->id)
        ->with('featureModule')
        ->get()
        ->keyBy(fn ($r) => $r->featureModule->code);

    expect($rules->count())->toBe(3)
        ->and($rules['staff_management']->rule)->toBe(FeatureModuleRule::Required)
        ->and($rules['inventory_management']->rule)->toBe(FeatureModuleRule::Required)
        ->and($rules['asset_management']->rule)->toBe(FeatureModuleRule::DefaultEnabled);
});

it('creates no duplicates when the seeder runs multiple times', function (): void {
    (new InstitutionTypeFeatureRuleReferenceSeeder)->run();
    (new InstitutionTypeFeatureRuleReferenceSeeder)->run();
    (new InstitutionTypeFeatureRuleReferenceSeeder)->run();

    expect(InstitutionTypeFeatureRule::count())->toBe(15);
});

it('does not silently overwrite an administrator-changed rule on repeated seeding', function (): void {
    (new InstitutionTypeFeatureRuleReferenceSeeder)->run();

    $type = InstitutionType::where('code', 'medical_point')->firstOrFail();

    InstitutionTypeFeatureRule::where('institution_type_id', $type->id)
        ->whereHas('featureModule', fn ($q) => $q->where('code', 'medical_services'))
        ->update(['rule' => FeatureModuleRule::Required->value]);

    (new InstitutionTypeFeatureRuleReferenceSeeder)->run();

    $rule = InstitutionTypeFeatureRule::where('institution_type_id', $type->id)
        ->whereHas('featureModule', fn ($q) => $q->where('code', 'medical_services'))
        ->firstOrFail();

    expect($rule->rule)->toBe(FeatureModuleRule::Required);
});

it('uses stable codes for rule lookup during seeding, not display names', function (): void {
    // Corrupt display names; seeder must still match by code
    InstitutionType::query()->update(['name_en' => 'CORRUPTED']);
    FeatureModule::query()->update(['name_en' => 'CORRUPTED']);

    (new InstitutionTypeFeatureRuleReferenceSeeder)->run();

    expect(InstitutionTypeFeatureRule::count())->toBe(15);
});

it('a future additional institution type remains technically representable', function (): void {
    (new InstitutionTypeFeatureRuleReferenceSeeder)->run();

    $newType = InstitutionTypeFactory::new()->create([
        'code' => 'future_type',
        'name_en' => 'Future Type (Test)',
    ]);

    $feature = FeatureModule::where('code', 'staff_management')->firstOrFail();

    $rule = new InstitutionTypeFeatureRule;
    $rule->institution_type_id = $newType->id;
    $rule->feature_module_id = $feature->id;
    $rule->rule = FeatureModuleRule::Required;
    $rule->save();

    expect(InstitutionTypeFeatureRule::where('institution_type_id', $newType->id)->count())->toBe(1);
});

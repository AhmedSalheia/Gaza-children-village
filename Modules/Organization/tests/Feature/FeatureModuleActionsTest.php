<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Actions\ActivateFeatureModule;
use Modules\Organization\Actions\ChangeFeatureModuleName;
use Modules\Organization\Actions\CreateFeatureModule;
use Modules\Organization\Actions\DeactivateFeatureModule;
use Modules\Organization\Data\ChangeFeatureModuleNameData;
use Modules\Organization\Data\CreateFeatureModuleData;
use Modules\Organization\Database\Factories\FeatureModuleFactory;
use Modules\Organization\Database\Factories\InstitutionTypeFactory;
use Modules\Organization\Enums\FeatureModuleRule;
use Modules\Organization\Models\FeatureModule;
use Modules\Organization\Models\InstitutionTypeFeatureRule;

uses(RefreshDatabase::class);

it('creates a feature module with a stable code and english name', function (): void {
    $module = (new CreateFeatureModule)->execute(new CreateFeatureModuleData(
        code: 'test_feature',
        nameEn: 'Test Feature',
    ));

    expect($module)->toBeInstanceOf(FeatureModule::class)
        ->and($module->exists)->toBeTrue()
        ->and($module->code)->toBe('test_feature')
        ->and($module->name_en)->toBe('Test Feature')
        ->and($module->name_ar)->toBeNull()
        ->and($module->is_active)->toBeTrue();
});

it('creates a feature module with an optional arabic name', function (): void {
    $module = (new CreateFeatureModule)->execute(new CreateFeatureModuleData(
        code: 'test_feature_ar',
        nameEn: 'Test Feature',
        nameAr: 'ميزة اختبارية',
    ));

    expect($module->name_ar)->toBe('ميزة اختبارية');
});

it('rejects a duplicate feature module code', function (): void {
    (new CreateFeatureModule)->execute(new CreateFeatureModuleData(
        code: 'dup_feature',
        nameEn: 'First Feature',
    ));

    expect(fn () => (new CreateFeatureModule)->execute(new CreateFeatureModuleData(
        code: 'dup_feature',
        nameEn: 'Second Feature',
    )))->toThrow(RuntimeException::class);
});

it('changes a feature module english name without touching the stable code', function (): void {
    $module = FeatureModuleFactory::new()->create(['code' => 'immutable-code']);
    $originalCode = $module->code;

    $updated = (new ChangeFeatureModuleName)->execute(
        $module,
        new ChangeFeatureModuleNameData(nameEn: 'Updated Name')
    );

    expect($updated->code)->toBe($originalCode)
        ->and($updated->name_en)->toBe('Updated Name')
        ->and($updated->name_ar)->toBeNull();
});

it('changes a feature module arabic name', function (): void {
    $module = FeatureModuleFactory::new()->create();

    $updated = (new ChangeFeatureModuleName)->execute(
        $module,
        new ChangeFeatureModuleNameData(nameEn: 'Name', nameAr: 'اسم')
    );

    expect($updated->name_ar)->toBe('اسم');
});

it('display name changes do not affect institution-type rule relationships', function (): void {
    $module = FeatureModuleFactory::new()->create();
    $type = InstitutionTypeFactory::new()->create();

    $rule = new InstitutionTypeFeatureRule;
    $rule->institution_type_id = $type->id;
    $rule->feature_module_id = $module->id;
    $rule->rule = FeatureModuleRule::Required;
    $rule->save();

    (new ChangeFeatureModuleName)->execute(
        $module,
        new ChangeFeatureModuleNameData(nameEn: 'Renamed Feature')
    );

    expect(InstitutionTypeFeatureRule::where('feature_module_id', $module->id)->count())->toBe(1);
});

it('activates an inactive feature module', function (): void {
    $module = FeatureModuleFactory::new()->inactive()->create();

    $activated = (new ActivateFeatureModule)->execute($module);

    expect($activated->is_active)->toBeTrue()
        ->and(FeatureModule::find($module->id)?->is_active)->toBeTrue();
});

it('deactivates an active feature module without deleting it', function (): void {
    $module = FeatureModuleFactory::new()->create();

    $deactivated = (new DeactivateFeatureModule)->execute($module);

    expect($deactivated->is_active)->toBeFalse()
        ->and(FeatureModule::find($module->id))->not->toBeNull()
        ->and(FeatureModule::find($module->id)?->is_active)->toBeFalse();
});

it('deactivation does not delete existing institution-type rules', function (): void {
    $module = FeatureModuleFactory::new()->create();
    $type = InstitutionTypeFactory::new()->create();

    $rule = new InstitutionTypeFeatureRule;
    $rule->institution_type_id = $type->id;
    $rule->feature_module_id = $module->id;
    $rule->rule = FeatureModuleRule::Required;
    $rule->save();

    (new DeactivateFeatureModule)->execute($module);

    expect(InstitutionTypeFeatureRule::where('feature_module_id', $module->id)->count())->toBe(1);
});

it('inactive feature modules remain queryable', function (): void {
    $module = FeatureModuleFactory::new()->inactive()->create(['code' => 'inactive-feature']);

    $found = FeatureModule::where('code', 'inactive-feature')->where('is_active', false)->first();

    expect($found)->not->toBeNull()
        ->and($found->code)->toBe('inactive-feature');
});

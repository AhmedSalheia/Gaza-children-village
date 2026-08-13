<?php

declare(strict_types=1);

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Actions\AssignInstitutionTypeRule;
use Modules\Organization\Actions\DeactivateFeatureModule;
use Modules\Organization\Actions\RemoveInstitutionTypeRule;
use Modules\Organization\Data\AssignInstitutionTypeRuleData;
use Modules\Organization\Database\Factories\FeatureModuleFactory;
use Modules\Organization\Database\Factories\InstitutionTypeFactory;
use Modules\Organization\Enums\FeatureModuleRule;
use Modules\Organization\Models\InstitutionTypeFeatureRule;

uses(RefreshDatabase::class);

it('assigns a required rule to an institution type', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    $rule = (new AssignInstitutionTypeRule)->execute(
        $type,
        $feature,
        new AssignInstitutionTypeRuleData(rule: FeatureModuleRule::Required)
    );

    expect($rule->institution_type_id)->toBe($type->id)
        ->and($rule->feature_module_id)->toBe($feature->id)
        ->and($rule->rule)->toBe(FeatureModuleRule::Required);
});

it('assigns a default-enabled rule to an institution type', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    $rule = (new AssignInstitutionTypeRule)->execute(
        $type,
        $feature,
        new AssignInstitutionTypeRuleData(rule: FeatureModuleRule::DefaultEnabled)
    );

    expect($rule->rule)->toBe(FeatureModuleRule::DefaultEnabled);
});

it('assigns an allowed rule to an institution type', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    $rule = (new AssignInstitutionTypeRule)->execute(
        $type,
        $feature,
        new AssignInstitutionTypeRuleData(rule: FeatureModuleRule::Allowed)
    );

    expect($rule->rule)->toBe(FeatureModuleRule::Allowed);
});

it('replaces an existing rule when assigning to the same type/feature pair', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    (new AssignInstitutionTypeRule)->execute($type, $feature, new AssignInstitutionTypeRuleData(rule: FeatureModuleRule::Allowed));
    (new AssignInstitutionTypeRule)->execute($type, $feature, new AssignInstitutionTypeRuleData(rule: FeatureModuleRule::Required));

    expect(InstitutionTypeFeatureRule::where('institution_type_id', $type->id)
        ->where('feature_module_id', $feature->id)
        ->count())->toBe(1)
        ->and(InstitutionTypeFeatureRule::where('institution_type_id', $type->id)
            ->where('feature_module_id', $feature->id)
            ->firstOrFail()->rule)->toBe(FeatureModuleRule::Required);
});

it('rejects assigning a rule to an inactive feature module', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->inactive()->create();

    expect(fn () => (new AssignInstitutionTypeRule)->execute(
        $type,
        $feature,
        new AssignInstitutionTypeRuleData(rule: FeatureModuleRule::Required)
    ))->toThrow(RuntimeException::class);
});

it('rejects a duplicate type/feature pair at the database level', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    $rule1 = new InstitutionTypeFeatureRule;
    $rule1->institution_type_id = $type->id;
    $rule1->feature_module_id = $feature->id;
    $rule1->rule = FeatureModuleRule::Required;
    $rule1->save();

    $rule2 = new InstitutionTypeFeatureRule;
    $rule2->institution_type_id = $type->id;
    $rule2->feature_module_id = $feature->id;
    $rule2->rule = FeatureModuleRule::Allowed;

    expect(fn () => $rule2->save())
        ->toThrow(UniqueConstraintViolationException::class);
});

it('removes an existing rule making the feature unavailable to the type', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    (new AssignInstitutionTypeRule)->execute($type, $feature, new AssignInstitutionTypeRuleData(rule: FeatureModuleRule::Required));

    (new RemoveInstitutionTypeRule)->execute($type, $feature);

    expect(InstitutionTypeFeatureRule::where('institution_type_id', $type->id)
        ->where('feature_module_id', $feature->id)
        ->exists())->toBeFalse();
});

it('removing a non-existent rule is a no-op', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    expect(fn () => (new RemoveInstitutionTypeRule)->execute($type, $feature))->not->toThrow(Exception::class);

    expect(InstitutionTypeFeatureRule::count())->toBe(0);
});

it('inactive feature rules remain historically inspectable after deactivation', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    (new AssignInstitutionTypeRule)->execute($type, $feature, new AssignInstitutionTypeRuleData(rule: FeatureModuleRule::Required));
    (new DeactivateFeatureModule)->execute($feature);

    $rule = InstitutionTypeFeatureRule::where('institution_type_id', $type->id)
        ->where('feature_module_id', $feature->id)
        ->firstOrFail();

    expect($rule->rule)->toBe(FeatureModuleRule::Required)
        ->and($rule->featureModule->is_active)->toBeFalse();
});

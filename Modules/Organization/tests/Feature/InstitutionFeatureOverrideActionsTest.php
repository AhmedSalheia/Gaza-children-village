<?php

declare(strict_types=1);

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Actions\ClearInstitutionFeatureOverride;
use Modules\Organization\Actions\SetInstitutionFeatureOverride;
use Modules\Organization\Data\SetInstitutionFeatureOverrideData;
use Modules\Organization\Database\Factories\FeatureModuleFactory;
use Modules\Organization\Database\Factories\InstitutionFactory;
use Modules\Organization\Database\Factories\InstitutionTypeFactory;
use Modules\Organization\Database\Factories\InstitutionTypeFeatureRuleFactory;
use Modules\Organization\Database\Seeders\FeatureModuleReferenceSeeder;
use Modules\Organization\Database\Seeders\InstitutionTypeFeatureRuleReferenceSeeder;
use Modules\Organization\Enums\FeatureModuleRule;
use Modules\Organization\Models\FeatureModule;
use Modules\Organization\Models\InstitutionFeatureOverride;
use Modules\Organization\Models\InstitutionTypeFeatureRule;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeInstitutionWithRule(FeatureModuleRule $rule): array
{
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();
    $institution = InstitutionFactory::new()->create(['institution_type_id' => $type->id]);

    $typeRule = new InstitutionTypeFeatureRule;
    $typeRule->institution_type_id = $type->id;
    $typeRule->feature_module_id = $feature->id;
    $typeRule->rule = $rule;
    $typeRule->save();

    return [$institution, $feature];
}

// ---------------------------------------------------------------------------
// Setting a meaningful override
// ---------------------------------------------------------------------------

it('sets a disable override for a DefaultEnabled feature', function (): void {
    [$institution, $feature] = makeInstitutionWithRule(FeatureModuleRule::DefaultEnabled);

    $override = (new SetInstitutionFeatureOverride)->execute(
        $institution,
        $feature,
        new SetInstitutionFeatureOverrideData(isEnabled: false, reason: 'Not needed at this location')
    );

    expect($override->institution_id)->toBe($institution->id)
        ->and($override->feature_module_id)->toBe($feature->id)
        ->and($override->is_enabled)->toBeFalse()
        ->and($override->reason)->toBe('Not needed at this location');
});

it('sets an enable override for an Allowed feature', function (): void {
    [$institution, $feature] = makeInstitutionWithRule(FeatureModuleRule::Allowed);

    $override = (new SetInstitutionFeatureOverride)->execute(
        $institution,
        $feature,
        new SetInstitutionFeatureOverrideData(isEnabled: true)
    );

    expect($override->is_enabled)->toBeTrue()
        ->and($override->reason)->toBeNull();
});

it('replaces an existing override for the same institution/feature pair', function (): void {
    [$institution, $feature] = makeInstitutionWithRule(FeatureModuleRule::Allowed);

    (new SetInstitutionFeatureOverride)->execute($institution, $feature, new SetInstitutionFeatureOverrideData(isEnabled: true));
    (new SetInstitutionFeatureOverride)->execute($institution, $feature, new SetInstitutionFeatureOverrideData(isEnabled: true, reason: 'Updated reason'));

    expect(InstitutionFeatureOverride::where('institution_id', $institution->id)
        ->where('feature_module_id', $feature->id)
        ->count())->toBe(1)
        ->and(InstitutionFeatureOverride::where('institution_id', $institution->id)
            ->where('feature_module_id', $feature->id)
            ->firstOrFail()->reason)->toBe('Updated reason');
});

// ---------------------------------------------------------------------------
// Rejection cases
// ---------------------------------------------------------------------------

it('rejects an override for a Required feature', function (): void {
    [$institution, $feature] = makeInstitutionWithRule(FeatureModuleRule::Required);

    expect(fn () => (new SetInstitutionFeatureOverride)->execute(
        $institution,
        $feature,
        new SetInstitutionFeatureOverrideData(isEnabled: false)
    ))->toThrow(RuntimeException::class);
});

it('rejects a redundant enable override for a DefaultEnabled feature', function (): void {
    [$institution, $feature] = makeInstitutionWithRule(FeatureModuleRule::DefaultEnabled);

    expect(fn () => (new SetInstitutionFeatureOverride)->execute(
        $institution,
        $feature,
        new SetInstitutionFeatureOverrideData(isEnabled: true)  // already enabled by default
    ))->toThrow(RuntimeException::class);
});

it('rejects a redundant disable override for an Allowed feature', function (): void {
    [$institution, $feature] = makeInstitutionWithRule(FeatureModuleRule::Allowed);

    expect(fn () => (new SetInstitutionFeatureOverride)->execute(
        $institution,
        $feature,
        new SetInstitutionFeatureOverrideData(isEnabled: false)  // already disabled by default
    ))->toThrow(RuntimeException::class);
});

it('rejects an override for an inactive institution', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();
    $institution = InstitutionFactory::new()->create([
        'institution_type_id' => $type->id,
        'is_active' => false,
    ]);
    InstitutionTypeFeatureRuleFactory::new()
        ->forType($type)
        ->allowed()
        ->create(['feature_module_id' => $feature->id]);

    expect(fn () => (new SetInstitutionFeatureOverride)->execute(
        $institution,
        $feature,
        new SetInstitutionFeatureOverrideData(isEnabled: true)
    ))->toThrow(RuntimeException::class);
});

it('rejects an override for an inactive feature', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->inactive()->create();
    $institution = InstitutionFactory::new()->create(['institution_type_id' => $type->id]);
    InstitutionTypeFeatureRuleFactory::new()
        ->forType($type)
        ->allowed()
        ->create(['feature_module_id' => $feature->id]);

    expect(fn () => (new SetInstitutionFeatureOverride)->execute(
        $institution,
        $feature,
        new SetInstitutionFeatureOverrideData(isEnabled: true)
    ))->toThrow(RuntimeException::class);
});

it('rejects an override for an unavailable feature (no type rule)', function (): void {
    $institution = InstitutionFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();
    // No InstitutionTypeFeatureRule created.

    expect(fn () => (new SetInstitutionFeatureOverride)->execute(
        $institution,
        $feature,
        new SetInstitutionFeatureOverrideData(isEnabled: true)
    ))->toThrow(RuntimeException::class);
});

it('rejected override attempts leave the database unchanged', function (): void {
    [$institution, $feature] = makeInstitutionWithRule(FeatureModuleRule::Required);

    try {
        (new SetInstitutionFeatureOverride)->execute($institution, $feature, new SetInstitutionFeatureOverrideData(isEnabled: false));
    } catch (RuntimeException) {
        // expected
    }

    expect(InstitutionFeatureOverride::count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Clearing overrides
// ---------------------------------------------------------------------------

it('clears an existing disable override restoring type-default behavior', function (): void {
    [$institution, $feature] = makeInstitutionWithRule(FeatureModuleRule::DefaultEnabled);

    (new SetInstitutionFeatureOverride)->execute($institution, $feature, new SetInstitutionFeatureOverrideData(isEnabled: false));

    (new ClearInstitutionFeatureOverride)->execute($institution, $feature);

    expect(InstitutionFeatureOverride::where('institution_id', $institution->id)
        ->where('feature_module_id', $feature->id)
        ->exists())->toBeFalse();
});

it('clears an existing enable override restoring allowed-but-disabled behavior', function (): void {
    [$institution, $feature] = makeInstitutionWithRule(FeatureModuleRule::Allowed);

    (new SetInstitutionFeatureOverride)->execute($institution, $feature, new SetInstitutionFeatureOverrideData(isEnabled: true));

    (new ClearInstitutionFeatureOverride)->execute($institution, $feature);

    expect(InstitutionFeatureOverride::where('institution_id', $institution->id)
        ->where('feature_module_id', $feature->id)
        ->exists())->toBeFalse();
});

it('clearing a non-existent override is a no-op', function (): void {
    $institution = InstitutionFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    expect(fn () => (new ClearInstitutionFeatureOverride)->execute($institution, $feature))
        ->not->toThrow(Exception::class);

    expect(InstitutionFeatureOverride::count())->toBe(0);
});

it('clearing an override does not modify the institution-type rule', function (): void {
    [$institution, $feature] = makeInstitutionWithRule(FeatureModuleRule::Allowed);

    (new SetInstitutionFeatureOverride)->execute($institution, $feature, new SetInstitutionFeatureOverrideData(isEnabled: true));
    (new ClearInstitutionFeatureOverride)->execute($institution, $feature);

    $rule = InstitutionTypeFeatureRule::where('institution_type_id', $institution->institution_type_id)
        ->where('feature_module_id', $feature->id)
        ->firstOrFail();

    expect($rule->rule)->toBe(FeatureModuleRule::Allowed);
});

it('clearing an override does not delete the feature module', function (): void {
    [$institution, $feature] = makeInstitutionWithRule(FeatureModuleRule::Allowed);

    (new SetInstitutionFeatureOverride)->execute($institution, $feature, new SetInstitutionFeatureOverrideData(isEnabled: true));
    (new ClearInstitutionFeatureOverride)->execute($institution, $feature);

    expect(FeatureModule::find($feature->id))->not->toBeNull();
});

// ---------------------------------------------------------------------------
// Seeder idempotency
// ---------------------------------------------------------------------------

it('repeated seeder runs do not delete or modify existing overrides', function (): void {
    [$institution, $feature] = makeInstitutionWithRule(FeatureModuleRule::Allowed);

    (new SetInstitutionFeatureOverride)->execute($institution, $feature, new SetInstitutionFeatureOverrideData(isEnabled: true, reason: 'Pre-existing override'));

    // Run F05 seeders (which include the feature rule seeder) and confirm override survives.
    (new FeatureModuleReferenceSeeder)->run();
    (new InstitutionTypeFeatureRuleReferenceSeeder)->run();

    $surviving = InstitutionFeatureOverride::where('institution_id', $institution->id)
        ->where('feature_module_id', $feature->id)
        ->first();

    expect($surviving)->not->toBeNull()
        ->and($surviving->reason)->toBe('Pre-existing override');
});

// ---------------------------------------------------------------------------
// DB-level uniqueness
// ---------------------------------------------------------------------------

it('rejects a duplicate institution/feature override at the database level', function (): void {
    $institution = InstitutionFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    $o1 = new InstitutionFeatureOverride;
    $o1->institution_id = $institution->id;
    $o1->feature_module_id = $feature->id;
    $o1->is_enabled = true;
    $o1->save();

    $o2 = new InstitutionFeatureOverride;
    $o2->institution_id = $institution->id;
    $o2->feature_module_id = $feature->id;
    $o2->is_enabled = false;

    expect(fn () => $o2->save())
        ->toThrow(UniqueConstraintViolationException::class);
});

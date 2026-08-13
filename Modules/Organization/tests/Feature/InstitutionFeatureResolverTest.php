<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Actions\ClearInstitutionFeatureOverride;
use Modules\Organization\Actions\SetInstitutionFeatureOverride;
use Modules\Organization\Data\SetInstitutionFeatureOverrideData;
use Modules\Organization\Database\Factories\FeatureModuleFactory;
use Modules\Organization\Database\Factories\InstitutionFactory;
use Modules\Organization\Database\Factories\InstitutionTypeFactory;
use Modules\Organization\Enums\FeatureModuleRule;
use Modules\Organization\Enums\ResolutionSource;
use Modules\Organization\Models\Institution;
use Modules\Organization\Models\InstitutionTypeFeatureRule;
use Modules\Organization\Services\InstitutionFeatureResolver;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->resolver = new InstitutionFeatureResolver;
    $this->set = new SetInstitutionFeatureOverride;
    $this->clear = new ClearInstitutionFeatureOverride;
});

// ---------------------------------------------------------------------------
// Shared helper
// ---------------------------------------------------------------------------

function makeScenario(FeatureModuleRule $rule): array
{
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();
    $institution = InstitutionFactory::new()->create(['institution_type_id' => $type->id]);

    $row = new InstitutionTypeFeatureRule;
    $row->institution_type_id = $type->id;
    $row->feature_module_id = $feature->id;
    $row->rule = $rule;
    $row->save();

    return [$institution, $feature];
}

// ---------------------------------------------------------------------------
// required rule
// ---------------------------------------------------------------------------

it('required rule resolves enabled with source=required', function (): void {
    [$institution, $feature] = makeScenario(FeatureModuleRule::Required);

    $result = $this->resolver->resolve($institution, $feature);

    expect($result->isEnabled())->toBeTrue()
        ->and($result->source())->toBe(ResolutionSource::Required)
        ->and($result->reasonKey())->toBe('required')
        ->and($result->isAvailable())->toBeTrue()
        ->and($result->canBeDisabled())->toBeFalse()
        ->and($result->canBeEnabled())->toBeFalse()
        ->and($result->hasOverride())->toBeFalse();
});

// ---------------------------------------------------------------------------
// default rule
// ---------------------------------------------------------------------------

it('DefaultEnabled rule resolves enabled with source=type_default when no override', function (): void {
    [$institution, $feature] = makeScenario(FeatureModuleRule::DefaultEnabled);

    $result = $this->resolver->resolve($institution, $feature);

    expect($result->isEnabled())->toBeTrue()
        ->and($result->source())->toBe(ResolutionSource::TypeDefault)
        ->and($result->canBeDisabled())->toBeTrue()
        ->and($result->canBeEnabled())->toBeFalse()
        ->and($result->hasOverride())->toBeFalse();
});

it('DefaultEnabled + disable override resolves disabled with source=institution_override', function (): void {
    [$institution, $feature] = makeScenario(FeatureModuleRule::DefaultEnabled);

    ($this->set)->execute($institution, $feature, new SetInstitutionFeatureOverrideData(isEnabled: false));

    $result = $this->resolver->resolve($institution, $feature);

    expect($result->isEnabled())->toBeFalse()
        ->and($result->source())->toBe(ResolutionSource::InstitutionOverride)
        ->and($result->hasOverride())->toBeTrue()
        ->and($result->isAvailable())->toBeTrue();
});

it('clearing a disable override on DefaultEnabled restores enabled', function (): void {
    [$institution, $feature] = makeScenario(FeatureModuleRule::DefaultEnabled);

    ($this->set)->execute($institution, $feature, new SetInstitutionFeatureOverrideData(isEnabled: false));
    ($this->clear)->execute($institution, $feature);

    $result = $this->resolver->resolve($institution, $feature);

    expect($result->isEnabled())->toBeTrue()
        ->and($result->source())->toBe(ResolutionSource::TypeDefault)
        ->and($result->hasOverride())->toBeFalse();
});

// ---------------------------------------------------------------------------
// allowed rule
// ---------------------------------------------------------------------------

it('Allowed rule resolves disabled with source=allowed_but_disabled when no override', function (): void {
    [$institution, $feature] = makeScenario(FeatureModuleRule::Allowed);

    $result = $this->resolver->resolve($institution, $feature);

    expect($result->isEnabled())->toBeFalse()
        ->and($result->source())->toBe(ResolutionSource::AllowedButDisabled)
        ->and($result->canBeEnabled())->toBeTrue()
        ->and($result->canBeDisabled())->toBeFalse()
        ->and($result->hasOverride())->toBeFalse()
        ->and($result->isAvailable())->toBeTrue();
});

it('Allowed + enable override resolves enabled with source=institution_override', function (): void {
    [$institution, $feature] = makeScenario(FeatureModuleRule::Allowed);

    ($this->set)->execute($institution, $feature, new SetInstitutionFeatureOverrideData(isEnabled: true));

    $result = $this->resolver->resolve($institution, $feature);

    expect($result->isEnabled())->toBeTrue()
        ->and($result->source())->toBe(ResolutionSource::InstitutionOverride)
        ->and($result->hasOverride())->toBeTrue();
});

it('clearing an enable override on Allowed restores disabled', function (): void {
    [$institution, $feature] = makeScenario(FeatureModuleRule::Allowed);

    ($this->set)->execute($institution, $feature, new SetInstitutionFeatureOverrideData(isEnabled: true));
    ($this->clear)->execute($institution, $feature);

    $result = $this->resolver->resolve($institution, $feature);

    expect($result->isEnabled())->toBeFalse()
        ->and($result->source())->toBe(ResolutionSource::AllowedButDisabled)
        ->and($result->hasOverride())->toBeFalse();
});

// ---------------------------------------------------------------------------
// No rule (unavailable)
// ---------------------------------------------------------------------------

it('no type rule resolves disabled and unavailable', function (): void {
    $institution = InstitutionFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();
    // No InstitutionTypeFeatureRule row.

    $result = $this->resolver->resolve($institution, $feature);

    expect($result->isEnabled())->toBeFalse()
        ->and($result->isAvailable())->toBeFalse()
        ->and($result->source())->toBe(ResolutionSource::Unavailable)
        ->and($result->canBeEnabled())->toBeFalse()
        ->and($result->canBeDisabled())->toBeFalse();
});

// ---------------------------------------------------------------------------
// Inactive feature
// ---------------------------------------------------------------------------

it('inactive feature resolves disabled with source=feature_inactive', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->inactive()->create();
    $institution = InstitutionFactory::new()->create(['institution_type_id' => $type->id]);

    $row = new InstitutionTypeFeatureRule;
    $row->institution_type_id = $type->id;
    $row->feature_module_id = $feature->id;
    $row->rule = FeatureModuleRule::Required;
    $row->save();

    $result = $this->resolver->resolve($institution, $feature);

    expect($result->isEnabled())->toBeFalse()
        ->and($result->isAvailable())->toBeFalse()
        ->and($result->source())->toBe(ResolutionSource::FeatureInactive);
});

// ---------------------------------------------------------------------------
// Inactive institution
// ---------------------------------------------------------------------------

it('inactive institution resolves operationally disabled with source=institution_inactive', function (): void {
    [$institution, $feature] = makeScenario(FeatureModuleRule::Required);

    $institution->is_active = false;
    $institution->save();

    // Load without global scope to get inactive institution
    $inactive = Institution::withoutGlobalScopes()->find($institution->id);

    $result = $this->resolver->resolve($inactive, $feature);

    expect($result->isEnabled())->toBeFalse()
        ->and($result->isAvailable())->toBeFalse()
        ->and($result->source())->toBe(ResolutionSource::InstitutionInactive);
});

it('inactive institution type does not erase resolution for existing institutions', function (): void {
    [$institution, $feature] = makeScenario(FeatureModuleRule::Required);

    // Deactivate the type
    $institution->institutionType->is_active = false;
    $institution->institutionType->save();

    // Institution still resolves against its type's rules
    $result = $this->resolver->resolve($institution, $feature);

    expect($result->isEnabled())->toBeTrue()
        ->and($result->source())->toBe(ResolutionSource::Required);
});

// ---------------------------------------------------------------------------
// Display name / translation independence
// ---------------------------------------------------------------------------

it('display name changes do not affect resolution', function (): void {
    [$institution, $feature] = makeScenario(FeatureModuleRule::Required);

    $feature->name_en = 'Completely Different Name';
    $feature->name_ar = 'اسم مختلف تماماً';
    $feature->save();

    $result = $this->resolver->resolve($institution, $feature);

    expect($result->isEnabled())->toBeTrue()
        ->and($result->source())->toBe(ResolutionSource::Required);
});

it('Arabic and English names do not affect resolution', function (): void {
    [$institution, $feature] = makeScenario(FeatureModuleRule::Allowed);

    $institution->name_ar = 'مؤسسة أخرى';
    $institution->save();

    $result = $this->resolver->resolve($institution, $feature);

    expect($result->isEnabled())->toBeFalse()
        ->and($result->source())->toBe(ResolutionSource::AllowedButDisabled);
});

// ---------------------------------------------------------------------------
// resolveByCode
// ---------------------------------------------------------------------------

it('resolveByCode resolves using a stable feature code', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create(['code' => 'explicit_test_code']);
    $institution = InstitutionFactory::new()->create(['institution_type_id' => $type->id]);

    $row = new InstitutionTypeFeatureRule;
    $row->institution_type_id = $type->id;
    $row->feature_module_id = $feature->id;
    $row->rule = FeatureModuleRule::Required;
    $row->save();

    $result = $this->resolver->resolveByCode($institution, 'explicit_test_code');

    expect($result->isEnabled())->toBeTrue()
        ->and($result->feature->code)->toBe('explicit_test_code');
});

it('feature availability is not authorization', function (): void {
    [$institution, $feature] = makeScenario(FeatureModuleRule::Required);

    $result = $this->resolver->resolve($institution, $feature);

    // isEnabled returns true for configuration availability only.
    // No actor, permission, or operational-scope check has been performed.
    expect($result->isEnabled())->toBeTrue();

    // There is no authorizer registered, no actor assigned, no permission granted.
    // The resolver deliberately accepts trusted domain inputs and makes no auth claim.
    // String-based check avoids a cross-module class reference in Organization tests.
    expect(app()->bound('Modules\\Authorization\\Contracts\\OperationalScopeAuthorizer'))->toBeFalse();
});

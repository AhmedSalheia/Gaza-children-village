<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Actions\AssignInstitutionTypeRule;
use Modules\Organization\Data\AssignInstitutionTypeRuleData;
use Modules\Organization\Database\Factories\FeatureModuleFactory;
use Modules\Organization\Database\Factories\InstitutionTypeFactory;
use Modules\Organization\Enums\FeatureModuleRule;
use Modules\Organization\Services\InstitutionTypeRuleInterpreter;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->interpreter = new InstitutionTypeRuleInterpreter;
    $this->assign = new AssignInstitutionTypeRule;
});

// ---------------------------------------------------------------------------
// required rule
// ---------------------------------------------------------------------------

it('required: baseline is enabled', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    ($this->assign)->execute($type, $feature, new AssignInstitutionTypeRuleData(rule: FeatureModuleRule::Required));

    expect($this->interpreter->isBaselineEnabled($type, $feature))->toBeTrue();
});

it('required: cannot be disabled by an institution override', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    ($this->assign)->execute($type, $feature, new AssignInstitutionTypeRuleData(rule: FeatureModuleRule::Required));

    expect($this->interpreter->canBeDisabled($type, $feature))->toBeFalse();
});

it('required: cannot be enabled (it is already enabled)', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    ($this->assign)->execute($type, $feature, new AssignInstitutionTypeRuleData(rule: FeatureModuleRule::Required));

    expect($this->interpreter->canBeEnabled($type, $feature))->toBeFalse();
});

it('required: is not unavailable', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    ($this->assign)->execute($type, $feature, new AssignInstitutionTypeRuleData(rule: FeatureModuleRule::Required));

    expect($this->interpreter->isUnavailable($type, $feature))->toBeFalse();
});

// ---------------------------------------------------------------------------
// default (enabled by default, potentially disableable) rule
// ---------------------------------------------------------------------------

it('default-enabled: baseline is enabled', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    ($this->assign)->execute($type, $feature, new AssignInstitutionTypeRuleData(rule: FeatureModuleRule::DefaultEnabled));

    expect($this->interpreter->isBaselineEnabled($type, $feature))->toBeTrue();
});

it('default-enabled: may be disabled by an institution override', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    ($this->assign)->execute($type, $feature, new AssignInstitutionTypeRuleData(rule: FeatureModuleRule::DefaultEnabled));

    expect($this->interpreter->canBeDisabled($type, $feature))->toBeTrue();
});

it('default-enabled: cannot be enabled (it is already enabled)', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    ($this->assign)->execute($type, $feature, new AssignInstitutionTypeRuleData(rule: FeatureModuleRule::DefaultEnabled));

    expect($this->interpreter->canBeEnabled($type, $feature))->toBeFalse();
});

// ---------------------------------------------------------------------------
// allowed rule
// ---------------------------------------------------------------------------

it('allowed: baseline is disabled', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    ($this->assign)->execute($type, $feature, new AssignInstitutionTypeRuleData(rule: FeatureModuleRule::Allowed));

    expect($this->interpreter->isBaselineEnabled($type, $feature))->toBeFalse();
});

it('allowed: may be enabled by an institution override', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    ($this->assign)->execute($type, $feature, new AssignInstitutionTypeRuleData(rule: FeatureModuleRule::Allowed));

    expect($this->interpreter->canBeEnabled($type, $feature))->toBeTrue();
});

it('allowed: cannot be disabled (it is already disabled)', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    ($this->assign)->execute($type, $feature, new AssignInstitutionTypeRuleData(rule: FeatureModuleRule::Allowed));

    expect($this->interpreter->canBeDisabled($type, $feature))->toBeFalse();
});

it('allowed: is not unavailable', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    ($this->assign)->execute($type, $feature, new AssignInstitutionTypeRuleData(rule: FeatureModuleRule::Allowed));

    expect($this->interpreter->isUnavailable($type, $feature))->toBeFalse();
});

// ---------------------------------------------------------------------------
// no rule (unavailable)
// ---------------------------------------------------------------------------

it('no rule: baseline is disabled', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    expect($this->interpreter->isBaselineEnabled($type, $feature))->toBeFalse();
});

it('no rule: cannot be enabled by an institution override', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    expect($this->interpreter->canBeEnabled($type, $feature))->toBeFalse();
});

it('no rule: cannot be disabled', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    expect($this->interpreter->canBeDisabled($type, $feature))->toBeFalse();
});

it('no rule: reports the feature as unavailable', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    expect($this->interpreter->isUnavailable($type, $feature))->toBeTrue();
});

it('ruleFor returns null when no rule exists', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    expect($this->interpreter->ruleFor($type, $feature))->toBeNull();
});

it('interpretation does not use institution-specific overrides', function (): void {
    // The interpreter operates purely on the InstitutionTypeFeatureRule rows;
    // there must be no InstitutionModuleActivation or F06 resolver table.
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    ($this->assign)->execute($type, $feature, new AssignInstitutionTypeRuleData(rule: FeatureModuleRule::Allowed));

    // Baseline result for 'allowed' is always disabled at type level regardless
    // of any hypothetical institution preference.
    expect($this->interpreter->isBaselineEnabled($type, $feature))->toBeFalse();
});

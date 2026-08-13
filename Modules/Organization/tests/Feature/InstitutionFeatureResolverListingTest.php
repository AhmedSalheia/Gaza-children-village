<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Organization\Actions\SetInstitutionFeatureOverride;
use Modules\Organization\Data\SetInstitutionFeatureOverrideData;
use Modules\Organization\Database\Factories\FeatureModuleFactory;
use Modules\Organization\Database\Factories\InstitutionFactory;
use Modules\Organization\Database\Factories\InstitutionTypeFactory;
use Modules\Organization\Enums\FeatureModuleRule;
use Modules\Organization\Enums\ResolutionSource;
use Modules\Organization\Models\InstitutionTypeFeatureRule;
use Modules\Organization\Services\InstitutionFeatureResolver;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->resolver = new InstitutionFeatureResolver;
    $this->set = new SetInstitutionFeatureOverride;
});

// ---------------------------------------------------------------------------
// enabledFor
// ---------------------------------------------------------------------------

it('enabledFor returns only effectively enabled active features', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $institution = InstitutionFactory::new()->create(['institution_type_id' => $type->id]);

    $required = FeatureModuleFactory::new()->create(['code' => 'feat_required']);
    $default = FeatureModuleFactory::new()->create(['code' => 'feat_default']);
    $allowed = FeatureModuleFactory::new()->create(['code' => 'feat_allowed']);
    $noRule = FeatureModuleFactory::new()->create(['code' => 'feat_no_rule']);

    foreach ([
        [$required, FeatureModuleRule::Required],
        [$default, FeatureModuleRule::DefaultEnabled],
        [$allowed, FeatureModuleRule::Allowed],
    ] as [$feat, $rule]) {
        $row = new InstitutionTypeFeatureRule;
        $row->institution_type_id = $type->id;
        $row->feature_module_id = $feat->id;
        $row->rule = $rule;
        $row->save();
    }

    $enabled = $this->resolver->enabledFor($institution)->pluck('code')->sort()->values()->all();

    expect($enabled)->toBe(['feat_default', 'feat_required']);
});

it('enabledFor excludes features disabled by institution override', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $institution = InstitutionFactory::new()->create(['institution_type_id' => $type->id]);

    $default = FeatureModuleFactory::new()->create();

    $row = new InstitutionTypeFeatureRule;
    $row->institution_type_id = $type->id;
    $row->feature_module_id = $default->id;
    $row->rule = FeatureModuleRule::DefaultEnabled;
    $row->save();

    ($this->set)->execute($institution, $default, new SetInstitutionFeatureOverrideData(isEnabled: false));

    $enabled = $this->resolver->enabledFor($institution);

    expect($enabled->where('id', $default->id)->isEmpty())->toBeTrue();
});

it('enabledFor includes Allowed features enabled by institution override', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $institution = InstitutionFactory::new()->create(['institution_type_id' => $type->id]);

    $allowed = FeatureModuleFactory::new()->create(['code' => 'feat_allowed_enabled']);

    $row = new InstitutionTypeFeatureRule;
    $row->institution_type_id = $type->id;
    $row->feature_module_id = $allowed->id;
    $row->rule = FeatureModuleRule::Allowed;
    $row->save();

    ($this->set)->execute($institution, $allowed, new SetInstitutionFeatureOverrideData(isEnabled: true));

    $codes = $this->resolver->enabledFor($institution)->pluck('code')->all();

    expect($codes)->toContain('feat_allowed_enabled');
});

it('enabledFor excludes inactive features even when they have a type rule', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $institution = InstitutionFactory::new()->create(['institution_type_id' => $type->id]);

    $inactive = FeatureModuleFactory::new()->inactive()->create();

    $row = new InstitutionTypeFeatureRule;
    $row->institution_type_id = $type->id;
    $row->feature_module_id = $inactive->id;
    $row->rule = FeatureModuleRule::Required;
    $row->save();

    $enabled = $this->resolver->enabledFor($institution);

    expect($enabled->where('id', $inactive->id)->isEmpty())->toBeTrue();
});

// ---------------------------------------------------------------------------
// resolveAll
// ---------------------------------------------------------------------------

it('resolveAll returns correct sources for all features', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $institution = InstitutionFactory::new()->create(['institution_type_id' => $type->id]);

    $required = FeatureModuleFactory::new()->create();
    $default = FeatureModuleFactory::new()->create();
    $allowed = FeatureModuleFactory::new()->create();

    foreach ([
        [$required, FeatureModuleRule::Required],
        [$default, FeatureModuleRule::DefaultEnabled],
        [$allowed, FeatureModuleRule::Allowed],
    ] as [$feat, $rule]) {
        $row = new InstitutionTypeFeatureRule;
        $row->institution_type_id = $type->id;
        $row->feature_module_id = $feat->id;
        $row->rule = $rule;
        $row->save();
    }

    $results = $this->resolver->resolveAll($institution)->keyBy(fn ($r) => $r->feature->id);

    expect($results->get($required->id)->source())->toBe(ResolutionSource::Required)
        ->and($results->get($default->id)->source())->toBe(ResolutionSource::TypeDefault)
        ->and($results->get($allowed->id)->source())->toBe(ResolutionSource::AllowedButDisabled);
});

it('resolveAll returns all features including disabled and unavailable', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $institution = InstitutionFactory::new()->create(['institution_type_id' => $type->id]);

    $feat1 = FeatureModuleFactory::new()->create();
    $feat2 = FeatureModuleFactory::new()->create();  // no rule

    $row = new InstitutionTypeFeatureRule;
    $row->institution_type_id = $type->id;
    $row->feature_module_id = $feat1->id;
    $row->rule = FeatureModuleRule::Required;
    $row->save();

    $results = $this->resolver->resolveAll($institution);

    expect($results)->toHaveCount(2);

    $unavailable = $results->firstWhere(fn ($r) => $r->feature->id === $feat2->id);
    expect($unavailable->source())->toBe(ResolutionSource::Unavailable);
});

// ---------------------------------------------------------------------------
// N+1 query prevention
// ---------------------------------------------------------------------------

it('resolveAll uses a bounded number of queries regardless of feature count', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $institution = InstitutionFactory::new()->create(['institution_type_id' => $type->id]);

    // Create 5 feature modules with type rules
    collect(range(1, 5))->each(function (int $i) use ($type): void {
        $feat = FeatureModuleFactory::new()->create();
        $row = new InstitutionTypeFeatureRule;
        $row->institution_type_id = $type->id;
        $row->feature_module_id = $feat->id;
        $row->rule = FeatureModuleRule::Required;
        $row->save();
    });

    DB::enableQueryLog();
    $this->resolver->resolveAll($institution);
    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();
    DB::flushQueryLog();

    // 3 queries: all features, type rules for institution's type, institution overrides.
    // Must not scale with the number of features.
    expect($queryCount)->toBeLessThanOrEqual(3);
});

it('enabledFor uses a bounded number of queries', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $institution = InstitutionFactory::new()->create(['institution_type_id' => $type->id]);

    collect(range(1, 5))->each(function (int $i) use ($type): void {
        $feat = FeatureModuleFactory::new()->create();
        $row = new InstitutionTypeFeatureRule;
        $row->institution_type_id = $type->id;
        $row->feature_module_id = $feat->id;
        $row->rule = FeatureModuleRule::DefaultEnabled;
        $row->save();
    });

    DB::enableQueryLog();
    $this->resolver->enabledFor($institution);
    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();
    DB::flushQueryLog();

    expect($queryCount)->toBeLessThanOrEqual(3);
});

// ---------------------------------------------------------------------------
// Multi-institution and multi-type scenarios
// ---------------------------------------------------------------------------

it('two institutions of the same type may resolve differently through permitted overrides', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();
    $instA = InstitutionFactory::new()->create(['institution_type_id' => $type->id]);
    $instB = InstitutionFactory::new()->create(['institution_type_id' => $type->id]);

    $row = new InstitutionTypeFeatureRule;
    $row->institution_type_id = $type->id;
    $row->feature_module_id = $feature->id;
    $row->rule = FeatureModuleRule::DefaultEnabled;
    $row->save();

    // Only institution A disables the feature.
    ($this->set)->execute($instA, $feature, new SetInstitutionFeatureOverrideData(isEnabled: false));

    $resultA = $this->resolver->resolve($instA, $feature);
    $resultB = $this->resolver->resolve($instB, $feature);

    expect($resultA->isEnabled())->toBeFalse()
        ->and($resultA->source())->toBe(ResolutionSource::InstitutionOverride)
        ->and($resultB->isEnabled())->toBeTrue()
        ->and($resultB->source())->toBe(ResolutionSource::TypeDefault);
});

it('institutions of different types resolve from their own rules', function (): void {
    $typeA = InstitutionTypeFactory::new()->create();
    $typeB = InstitutionTypeFactory::new()->create();
    $feature = FeatureModuleFactory::new()->create();

    $instA = InstitutionFactory::new()->create(['institution_type_id' => $typeA->id]);
    $instB = InstitutionFactory::new()->create(['institution_type_id' => $typeB->id]);

    // Type A has a Required rule; Type B has no rule.
    $row = new InstitutionTypeFeatureRule;
    $row->institution_type_id = $typeA->id;
    $row->feature_module_id = $feature->id;
    $row->rule = FeatureModuleRule::Required;
    $row->save();

    $resultA = $this->resolver->resolve($instA, $feature);
    $resultB = $this->resolver->resolve($instB, $feature);

    expect($resultA->isEnabled())->toBeTrue()
        ->and($resultA->source())->toBe(ResolutionSource::Required)
        ->and($resultB->isEnabled())->toBeFalse()
        ->and($resultB->source())->toBe(ResolutionSource::Unavailable);
});

it('a future additional feature definition remains representable', function (): void {
    $type = InstitutionTypeFactory::new()->create();
    $institution = InstitutionFactory::new()->create(['institution_type_id' => $type->id]);

    $existing = FeatureModuleFactory::new()->create();
    $future = FeatureModuleFactory::new()->create(['code' => 'future_feature_test']);

    $row = new InstitutionTypeFeatureRule;
    $row->institution_type_id = $type->id;
    $row->feature_module_id = $existing->id;
    $row->rule = FeatureModuleRule::Required;
    $row->save();

    // future feature has no type rule yet
    $results = $this->resolver->resolveAll($institution);

    $futureResult = $results->firstWhere(fn ($r) => $r->feature->code === 'future_feature_test');

    expect($futureResult)->not->toBeNull()
        ->and($futureResult->source())->toBe(ResolutionSource::Unavailable);

    // Once a type rule is added, it resolves correctly.
    $newRow = new InstitutionTypeFeatureRule;
    $newRow->institution_type_id = $type->id;
    $newRow->feature_module_id = $future->id;
    $newRow->rule = FeatureModuleRule::Allowed;
    $newRow->save();

    $updatedResult = $this->resolver->resolve($institution, $future);
    expect($updatedResult->source())->toBe(ResolutionSource::AllowedButDisabled);
});

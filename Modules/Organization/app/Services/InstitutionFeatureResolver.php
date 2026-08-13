<?php

declare(strict_types=1);

namespace Modules\Organization\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Modules\Organization\Data\FeatureResolutionResult;
use Modules\Organization\Enums\FeatureModuleRule;
use Modules\Organization\Enums\ResolutionSource;
use Modules\Organization\Models\FeatureModule;
use Modules\Organization\Models\Institution;
use Modules\Organization\Models\InstitutionFeatureOverride;
use Modules\Organization\Models\InstitutionTypeFeatureRule;

/**
 * Resolves effective feature availability for an institution.
 *
 * Combines the institution-type baseline rule from InstitutionTypeFeatureRule
 * with any explicit institution-specific override from InstitutionFeatureOverride
 * to produce an immutable FeatureResolutionResult.
 *
 * Resolution priority:
 *
 *   1. Institution inactive → InstitutionInactive (operationally disabled).
 *   2. Feature inactive     → FeatureInactive (disabled; config inspectable).
 *   3. No type rule         → Unavailable (cannot enable via override).
 *   4. Required rule        → Required (always enabled; cannot disable).
 *   5. DefaultEnabled rule  → TypeDefault if no override; InstitutionOverride if
 *                             an is_enabled=false row exists.
 *   6. Allowed rule         → AllowedButDisabled if no override; InstitutionOverride
 *                             if an is_enabled=true row exists.
 *
 * Query discipline:
 *
 *   resolve()      — 2 queries (type rule + override).
 *   resolveAll()   — 3 queries total regardless of feature count (features,
 *                    type rules for institution's type, institution overrides).
 *   enabledFor()   — delegates to resolveAll(); 3 queries.
 *   resolveByCode()— 1 extra query (feature by code) + 2 for resolve() = 3.
 *
 *   Never resolves by translated display name. Never reads from request context,
 *   session, or authentication state. Accepts only trusted domain model inputs.
 *
 * Authorization boundary:
 *
 *   This resolver is a pure query service. It does not check actor permissions,
 *   institution assignment, operational scope, or role records. isEnabled()
 *   answers a configuration availability question only. Both effective
 *   availability AND a separately authorized actor with appropriate permissions,
 *   institution assignment, semester scope, and record-state clearance are
 *   required for any operation.
 *
 * Note on inactive institutions:
 *
 *   Institution::withoutGlobalScopes() must be used by callers that need to
 *   resolve configuration for an inactive institution (e.g. admin panels).
 *   The resolver itself does not bypass the ActiveInstitutionScope; it reads
 *   the is_active attribute on the passed model instance.
 */
final readonly class InstitutionFeatureResolver
{
    /**
     * Resolve one feature for one institution.
     *
     * @param  Institution  $institution  May be active or inactive; caller controls scope.
     */
    public function resolve(Institution $institution, FeatureModule $feature): FeatureResolutionResult
    {
        $typeRuleRecord = InstitutionTypeFeatureRule::where('institution_type_id', $institution->institution_type_id)
            ->where('feature_module_id', $feature->id)
            ->first();

        $override = InstitutionFeatureOverride::where('institution_id', $institution->id)
            ->where('feature_module_id', $feature->id)
            ->first();

        return $this->resolveOne($institution, $feature, $typeRuleRecord?->rule, $override);
    }

    /**
     * Resolve one feature for one institution by stable feature code.
     *
     * This is an explicit, separately-named method to avoid ambiguous mixed
     * arguments that accept models, numeric IDs, and codes through one method.
     *
     * @throws ModelNotFoundException when no feature with this code exists.
     */
    public function resolveByCode(Institution $institution, string $featureCode): FeatureResolutionResult
    {
        $feature = FeatureModule::where('code', $featureCode)->firstOrFail();

        return $this->resolve($institution, $feature);
    }

    /**
     * Return only the effectively enabled, active feature definitions for an institution.
     *
     * Uses 3 queries regardless of feature count. Returns FeatureModule models.
     *
     * @return Collection<int, FeatureModule>
     */
    public function enabledFor(Institution $institution): Collection
    {
        return $this->resolveAll($institution)
            ->filter(fn (FeatureResolutionResult $r) => $r->isEnabled())
            ->map(fn (FeatureResolutionResult $r) => $r->feature)
            ->values();
    }

    /**
     * Resolve all known feature modules for an institution, including disabled
     * and unavailable features.
     *
     * Use this when a configuration UI needs the complete picture. Use
     * enabledFor() when only enabled features are needed.
     *
     * Uses 3 queries regardless of feature count.
     *
     * @return Collection<int, FeatureResolutionResult>
     */
    public function resolveAll(Institution $institution): Collection
    {
        $typeRulesByFeatureId = InstitutionTypeFeatureRule::where('institution_type_id', $institution->institution_type_id)
            ->get()
            ->keyBy('feature_module_id');

        $overridesByFeatureId = InstitutionFeatureOverride::where('institution_id', $institution->id)
            ->get()
            ->keyBy('feature_module_id');

        $features = FeatureModule::all();

        return $features->map(fn (FeatureModule $feature) => $this->resolveOne(
            $institution,
            $feature,
            $typeRulesByFeatureId->get($feature->id)?->rule,
            $overridesByFeatureId->get($feature->id),
        ));
    }

    /**
     * Core resolution logic for a single institution/feature pair.
     *
     * Accepts pre-loaded rule and override values to enable N+1-free bulk resolution.
     */
    private function resolveOne(
        Institution $institution,
        FeatureModule $feature,
        ?FeatureModuleRule $typeRule,
        ?InstitutionFeatureOverride $override,
    ): FeatureResolutionResult {
        if (! $institution->is_active) {
            return new FeatureResolutionResult(
                institution: $institution,
                feature: $feature,
                source: ResolutionSource::InstitutionInactive,
                effectivelyEnabled: false,
                effectivelyAvailable: false,
                institutionCanEnable: false,
                institutionCanDisable: false,
                overrideExists: $override !== null,
            );
        }

        if (! $feature->is_active) {
            return new FeatureResolutionResult(
                institution: $institution,
                feature: $feature,
                source: ResolutionSource::FeatureInactive,
                effectivelyEnabled: false,
                effectivelyAvailable: false,
                institutionCanEnable: false,
                institutionCanDisable: false,
                overrideExists: $override !== null,
            );
        }

        if ($typeRule === null) {
            return new FeatureResolutionResult(
                institution: $institution,
                feature: $feature,
                source: ResolutionSource::Unavailable,
                effectivelyEnabled: false,
                effectivelyAvailable: false,
                institutionCanEnable: false,
                institutionCanDisable: false,
                overrideExists: false,
            );
        }

        return match ($typeRule) {
            FeatureModuleRule::Required => new FeatureResolutionResult(
                institution: $institution,
                feature: $feature,
                source: ResolutionSource::Required,
                effectivelyEnabled: true,
                effectivelyAvailable: true,
                institutionCanEnable: false,
                institutionCanDisable: false,
                overrideExists: false,
            ),

            FeatureModuleRule::DefaultEnabled => $override === null
                ? new FeatureResolutionResult(
                    institution: $institution,
                    feature: $feature,
                    source: ResolutionSource::TypeDefault,
                    effectivelyEnabled: true,
                    effectivelyAvailable: true,
                    institutionCanEnable: false,
                    institutionCanDisable: true,
                    overrideExists: false,
                )
                : new FeatureResolutionResult(
                    institution: $institution,
                    feature: $feature,
                    source: ResolutionSource::InstitutionOverride,
                    effectivelyEnabled: false,  // only is_enabled=false rows are valid for DefaultEnabled
                    effectivelyAvailable: true,
                    institutionCanEnable: false,
                    institutionCanDisable: true,
                    overrideExists: true,
                ),

            FeatureModuleRule::Allowed => $override === null
                ? new FeatureResolutionResult(
                    institution: $institution,
                    feature: $feature,
                    source: ResolutionSource::AllowedButDisabled,
                    effectivelyEnabled: false,
                    effectivelyAvailable: true,
                    institutionCanEnable: true,
                    institutionCanDisable: false,
                    overrideExists: false,
                )
                : new FeatureResolutionResult(
                    institution: $institution,
                    feature: $feature,
                    source: ResolutionSource::InstitutionOverride,
                    effectivelyEnabled: true,   // only is_enabled=true rows are valid for Allowed
                    effectivelyAvailable: true,
                    institutionCanEnable: true,
                    institutionCanDisable: false,
                    overrideExists: true,
                ),
        };
    }
}

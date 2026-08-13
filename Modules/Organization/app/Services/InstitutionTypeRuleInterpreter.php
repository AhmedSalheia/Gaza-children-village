<?php

declare(strict_types=1);

namespace Modules\Organization\Services;

use Modules\Organization\Enums\FeatureModuleRule;
use Modules\Organization\Models\FeatureModule;
use Modules\Organization\Models\InstitutionType;
use Modules\Organization\Models\InstitutionTypeFeatureRule;

/**
 * Pure baseline rule interpreter for institution-type feature availability.
 *
 * Answers questions about the type-level rule ONLY. This interpreter does not
 * apply institution-specific overrides (F06), does not check authorization,
 * and does not confirm the business feature has been implemented in code.
 *
 * Effective institution-level resolution — incorporating per-institution
 * activation overrides — belongs to F06 and is not implemented here.
 *
 * IMPORTANT: A positive result from this interpreter does not:
 * - Grant any staff permission.
 * - Bypass institution or operational-context restrictions.
 * - Confirm the feature module is actually deployed in application code.
 */
final readonly class InstitutionTypeRuleInterpreter
{
    /**
     * Returns the type-level rule for the given institution type and feature,
     * or null when no rule exists (the feature is unavailable to this type).
     */
    public function ruleFor(InstitutionType $type, FeatureModule $feature): ?FeatureModuleRule
    {
        $record = InstitutionTypeFeatureRule::where('institution_type_id', $type->id)
            ->where('feature_module_id', $feature->id)
            ->first();

        return $record?->rule;
    }

    /**
     * Whether the feature is baseline-enabled for this institution type.
     *
     * True for Required and DefaultEnabled rules; false for Allowed and
     * when no rule exists.
     */
    public function isBaselineEnabled(InstitutionType $type, FeatureModule $feature): bool
    {
        return $this->ruleFor($type, $feature)?->isBaselineEnabled() ?? false;
    }

    /**
     * Whether a future F06 institution-specific override may disable
     * a baseline-enabled feature for this type.
     *
     * True only for DefaultEnabled. Required features cannot be disabled.
     * Returns false when the feature is unavailable (no rule).
     */
    public function canBeDisabled(InstitutionType $type, FeatureModule $feature): bool
    {
        return $this->ruleFor($type, $feature)?->canBeDisabled() ?? false;
    }

    /**
     * Whether a future F06 institution-specific override may enable
     * a baseline-disabled feature for this type.
     *
     * True only for Allowed. Returns false for Required, DefaultEnabled,
     * and unavailable (no-rule) features.
     */
    public function canBeEnabled(InstitutionType $type, FeatureModule $feature): bool
    {
        return $this->ruleFor($type, $feature)?->canBeEnabled() ?? false;
    }

    /**
     * Whether the feature is completely unavailable to this institution type.
     *
     * True when no rule exists. F06 must not allow an institution to enable
     * a feature that is unavailable at the type level.
     */
    public function isUnavailable(InstitutionType $type, FeatureModule $feature): bool
    {
        return $this->ruleFor($type, $feature) === null;
    }
}

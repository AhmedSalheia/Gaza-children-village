<?php

declare(strict_types=1);

namespace Modules\Organization\Data;

use Modules\Organization\Enums\ResolutionSource;
use Modules\Organization\Models\FeatureModule;
use Modules\Organization\Models\Institution;

/**
 * Immutable resolution result for one institution/feature pair.
 *
 * Returned by InstitutionFeatureResolver. Carries the full context of
 * why a feature is in its effective state, whether the institution can
 * change it, and whether a current override row exists.
 *
 * This is not an authorization result. isEnabled() answers a configuration
 * availability question only. Both effective availability AND a separately
 * authorized actor with appropriate permissions, institution assignment,
 * semester scope, and record-state clearance are required for any operation.
 *
 * Usage:
 *
 *   $result = $resolver->resolve($institution, $feature);
 *   $result->isEnabled();
 *   $result->source();           // ResolutionSource enum case
 *   $result->reasonKey();        // stable string for logs and UI
 *   $result->canBeDisabled();    // institution may create a disable override
 *   $result->canBeEnabled();     // institution may create an enable override
 *   $result->hasOverride();      // explicit override row currently exists
 */
final readonly class FeatureResolutionResult
{
    public function __construct(
        public Institution $institution,
        public FeatureModule $feature,
        public ResolutionSource $source,
        private bool $effectivelyEnabled,
        private bool $effectivelyAvailable,
        private bool $institutionCanEnable,
        private bool $institutionCanDisable,
        private bool $overrideExists,
    ) {}

    /**
     * Whether the feature is effectively enabled for this institution.
     *
     * False when the institution or feature is inactive, when no type rule
     * exists, or when an institution override has disabled a default feature.
     */
    public function isEnabled(): bool
    {
        return $this->effectivelyEnabled;
    }

    /**
     * Whether the feature is available to this institution type at all.
     *
     * False when no type rule exists, when the feature is inactive, or when
     * the institution is inactive. True for required, default, and allowed
     * rules regardless of the current enabled state.
     */
    public function isAvailable(): bool
    {
        return $this->effectivelyAvailable;
    }

    /**
     * Whether the institution may create an override that enables this feature.
     *
     * True only for Allowed-rule features without a current enable override.
     * Does not imply the actor is authorized to perform the action.
     */
    public function canBeEnabled(): bool
    {
        return $this->institutionCanEnable;
    }

    /**
     * Whether the institution may create an override that disables this feature.
     *
     * True only for DefaultEnabled-rule features without a current disable override.
     * Does not imply the actor is authorized to perform the action.
     */
    public function canBeDisabled(): bool
    {
        return $this->institutionCanDisable;
    }

    /**
     * Whether an explicit institution-specific override row currently exists.
     */
    public function hasOverride(): bool
    {
        return $this->overrideExists;
    }

    /**
     * The resolution source that determined the effective state.
     */
    public function source(): ResolutionSource
    {
        return $this->source;
    }

    /**
     * A stable string key suitable for log messages and future UI labels.
     *
     * Equal to the ResolutionSource enum's backed string value.
     * This value is stable; changing it is a breaking change for log queries.
     */
    public function reasonKey(): string
    {
        return $this->source->value;
    }
}

<?php

declare(strict_types=1);

namespace Modules\Organization\Enums;

/**
 * The three possible rule semantics for an institution-type/feature-module pair.
 *
 * Persisted as a bounded string column (never a database ENUM).
 * PHP-level validation is the primary enforcement boundary; all writes
 * go through application actions that accept only this enum.
 *
 * Semantics:
 *
 *   Required      — enabled by default; a future F06 institution override
 *                   may NOT disable it.
 *
 *   DefaultEnabled — enabled by default; a future F06 institution override
 *                   may disable it.
 *
 *   Allowed       — disabled by default; a future F06 institution override
 *                   may enable it.
 *
 * Absence of any rule means the feature is unavailable to that institution
 * type; F06 must not allow an institution to enable it.
 *
 * Note: "default" is a PHP reserved keyword and cannot be used as an enum
 * case name. The stored database value is still 'default' so that it matches
 * the canonical terminology used in planning documents and UI labels.
 */
enum FeatureModuleRule: string
{
    case Required = 'required';
    case DefaultEnabled = 'default';
    case Allowed = 'allowed';

    /**
     * Whether a feature with this rule is baseline-enabled for its type.
     *
     * Returns true for Required and DefaultEnabled; false for Allowed.
     * Use this to seed and resolve the baseline activation state before
     * any F06 institution-specific override is applied.
     */
    public function isBaselineEnabled(): bool
    {
        return match ($this) {
            self::Required, self::DefaultEnabled => true,
            self::Allowed => false,
        };
    }

    /**
     * Whether a future F06 institution-specific override may disable
     * a baseline-enabled feature.
     *
     * Returns true only for DefaultEnabled. Required features cannot be
     * disabled by an institution override.
     */
    public function canBeDisabled(): bool
    {
        return $this === self::DefaultEnabled;
    }

    /**
     * Whether a future F06 institution-specific override may enable
     * a baseline-disabled feature.
     *
     * Returns true only for Allowed. Unavailable (no-rule) features
     * cannot be enabled by an institution override.
     */
    public function canBeEnabled(): bool
    {
        return $this === self::Allowed;
    }
}

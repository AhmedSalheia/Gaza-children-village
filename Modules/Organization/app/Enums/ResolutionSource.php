<?php

declare(strict_types=1);

namespace Modules\Organization\Enums;

/**
 * The source that determined the effective feature resolution for an institution.
 *
 * Persisted only as string values in logs and result value objects.
 * Never stored as a database ENUM.
 *
 * These values are stable identifiers suitable for log messages,
 * future configuration UI labels, and internal routing. Changing
 * a case value is a breaking change for log queries and UI consumers;
 * do so only with an explicit migration plan.
 *
 * Source semantics:
 *
 *   required            — type rule is Required; always enabled; institution
 *                         cannot override.
 *
 *   type_default        — type rule is DefaultEnabled; enabled without an
 *                         institution override.
 *
 *   institution_override — an explicit institution row altered the baseline;
 *                         enabled for Allowed-rule features, disabled for
 *                         DefaultEnabled-rule features.
 *
 *   allowed_but_disabled — type rule is Allowed; disabled without an
 *                         institution override.
 *
 *   unavailable          — no type rule exists for this type/feature pair;
 *                         the institution cannot enable it.
 *
 *   feature_inactive     — the FeatureModule is inactive; configuration
 *                         remains inspectable but effective state is disabled.
 *
 *   institution_inactive — the institution is inactive; configuration
 *                         remains inspectable for administration/history
 *                         but operational effective state is disabled.
 */
enum ResolutionSource: string
{
    case Required = 'required';
    case TypeDefault = 'type_default';
    case InstitutionOverride = 'institution_override';
    case AllowedButDisabled = 'allowed_but_disabled';
    case Unavailable = 'unavailable';
    case FeatureInactive = 'feature_inactive';
    case InstitutionInactive = 'institution_inactive';
}

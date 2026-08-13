<?php

declare(strict_types=1);

namespace Modules\Organization\Data;

use Modules\Organization\Enums\FeatureModuleRule;

/**
 * Command data for assigning or changing an institution-type feature rule.
 *
 * This creates or replaces the rule for the given institution type and
 * feature module pair. Pass through the AssignInstitutionTypeRule action;
 * do not call Eloquent directly for rule management.
 */
final readonly class AssignInstitutionTypeRuleData
{
    public function __construct(
        public readonly FeatureModuleRule $rule,
    ) {}
}

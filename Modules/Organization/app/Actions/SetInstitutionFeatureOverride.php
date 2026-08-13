<?php

declare(strict_types=1);

namespace Modules\Organization\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Organization\Data\SetInstitutionFeatureOverrideData;
use Modules\Organization\Enums\FeatureModuleRule;
use Modules\Organization\Models\FeatureModule;
use Modules\Organization\Models\Institution;
use Modules\Organization\Models\InstitutionFeatureOverride;
use Modules\Organization\Models\InstitutionTypeFeatureRule;
use RuntimeException;

/**
 * Create or update an institution-specific feature override.
 *
 * Only meaningful overrides are permitted:
 *
 *   DefaultEnabled rule → is_enabled must be false (disable an on-by-default feature)
 *   Allowed rule        → is_enabled must be true  (enable an off-by-default feature)
 *
 * Rejected cases:
 *   - Inactive institution: configuration must not be mutated for inactive institutions.
 *   - Inactive feature: no new or changed overrides are allowed.
 *   - No type rule (unavailable): the institution cannot enable this feature.
 *   - Required rule: required features cannot be disabled by any override.
 *   - Redundant DefaultEnabled-enable override: feature is already enabled; store nothing.
 *   - Redundant Allowed-disable override: feature is already disabled; store nothing.
 *
 * Concurrent override changes:
 *
 *   The mutation runs in a DB transaction. SQLite (used in tests) acquires a
 *   database-level write lock for the transaction duration, providing
 *   sufficient isolation for the test environment. Production (MySQL/MariaDB)
 *   should add application-level advisory locks or row-level locking at the
 *   service boundary when concurrent administrator edits become a concern;
 *   SELECT … FOR UPDATE is not used here to preserve SQLite test compatibility.
 *
 * Authorization boundary:
 *
 *   This action does not check actor permissions, institution assignment, or
 *   operational scope. All callers must pass through the F17/F19 policy kernel
 *   before invoking this action.
 */
final readonly class SetInstitutionFeatureOverride
{
    public function execute(
        Institution $institution,
        FeatureModule $feature,
        SetInstitutionFeatureOverrideData $data,
    ): InstitutionFeatureOverride {
        if (! $institution->is_active) {
            throw new RuntimeException(
                "Cannot set override for inactive institution '{$institution->code}'."
            );
        }

        if (! $feature->is_active) {
            throw new RuntimeException(
                "Cannot set override for inactive feature '{$feature->code}'."
            );
        }

        $typeRuleRecord = InstitutionTypeFeatureRule::where('institution_type_id', $institution->institution_type_id)
            ->where('feature_module_id', $feature->id)
            ->first();

        if ($typeRuleRecord === null) {
            throw new RuntimeException(
                "Feature '{$feature->code}' is unavailable to this institution's type; override rejected."
            );
        }

        $typeRule = $typeRuleRecord->rule;

        if ($typeRule === FeatureModuleRule::Required) {
            throw new RuntimeException(
                "Cannot override required feature '{$feature->code}'; required features cannot be disabled."
            );
        }

        if ($typeRule === FeatureModuleRule::DefaultEnabled && $data->isEnabled) {
            throw new RuntimeException(
                "Redundant override rejected: feature '{$feature->code}' is already enabled by default for this institution type."
            );
        }

        if ($typeRule === FeatureModuleRule::Allowed && ! $data->isEnabled) {
            throw new RuntimeException(
                "Redundant override rejected: feature '{$feature->code}' is already disabled by default for this institution type."
            );
        }

        return DB::transaction(function () use ($institution, $feature, $data): InstitutionFeatureOverride {
            $override = InstitutionFeatureOverride::where('institution_id', $institution->id)
                ->where('feature_module_id', $feature->id)
                ->first();

            if ($override === null) {
                $override = new InstitutionFeatureOverride;
                $override->institution_id = $institution->id;
                $override->feature_module_id = $feature->id;
            }

            $override->is_enabled = $data->isEnabled;
            $override->reason = $data->reason;
            $override->save();

            return $override;
        });
    }
}

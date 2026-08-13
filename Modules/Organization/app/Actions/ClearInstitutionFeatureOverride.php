<?php

declare(strict_types=1);

namespace Modules\Organization\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Organization\Models\FeatureModule;
use Modules\Organization\Models\Institution;
use Modules\Organization\Models\InstitutionFeatureOverride;

/**
 * Remove an institution-specific feature override.
 *
 * Clearing an override restores the type-derived baseline for the feature:
 *
 *   DefaultEnabled rule → feature reverts to enabled
 *   Allowed rule        → feature reverts to disabled
 *
 * This action does NOT modify the institution-type rule or the FeatureModule.
 * It removes only the explicit InstitutionFeatureOverride row.
 *
 * If no override exists, this action is a no-op (consistent with the
 * RemoveInstitutionTypeRule convention used elsewhere in this module).
 *
 * The mutation runs in a DB transaction to maintain consistency with the
 * rest of the override action suite.
 *
 * Authorization boundary:
 *
 *   This action does not check actor permissions, institution assignment, or
 *   operational scope. All callers must pass through the F17/F19 policy kernel
 *   before invoking this action.
 */
final readonly class ClearInstitutionFeatureOverride
{
    public function execute(Institution $institution, FeatureModule $feature): void
    {
        DB::transaction(function () use ($institution, $feature): void {
            InstitutionFeatureOverride::where('institution_id', $institution->id)
                ->where('feature_module_id', $feature->id)
                ->delete();
        });
    }
}

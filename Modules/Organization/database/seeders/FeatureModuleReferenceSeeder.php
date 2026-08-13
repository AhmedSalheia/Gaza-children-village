<?php

declare(strict_types=1);

namespace Modules\Organization\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Organization\Models\FeatureModule;

/**
 * Idempotent seeder for the approved foundation feature-module definitions.
 *
 * Safe to run multiple times. Creates missing records; preserves
 * administrator-edited display names and lifecycle state on subsequent runs.
 *
 * Stable codes are set via direct property assignment (not mass assignment)
 * to stay consistent with the module's mass-assignment strategy, which
 * excludes codes from $fillable to prevent bulk overwrites.
 *
 * Approved stable codes:
 *   - staff_management
 *   - academic_management
 *   - asset_management
 *   - medical_services
 *   - womens_center_programs
 *   - inventory_management
 *
 * Arabic names are intentionally left null until official approved
 * translations are supplied.
 *
 * is_active represents lifecycle and configuration availability, not
 * authorization and not proof the business feature has been implemented.
 *
 * Note: this seeder must not create a reusable web authorization bypass.
 * It is for reference data initialization only.
 */
class FeatureModuleReferenceSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    private const MODULES = [
        'staff_management' => 'Staff Management',
        'academic_management' => 'Academic Management',
        'asset_management' => 'Asset Management',
        'medical_services' => 'Medical Services',
        'womens_center_programs' => "Women's Center Programs",
        'inventory_management' => 'Inventory Management',
    ];

    public function run(): void
    {
        foreach (self::MODULES as $code => $nameEn) {
            if (FeatureModule::where('code', $code)->exists()) {
                continue;
            }

            $module = new FeatureModule;
            $module->code = $code;
            $module->name_en = $nameEn;
            $module->name_ar = null;
            $module->is_active = true;
            $module->save();
        }
    }
}

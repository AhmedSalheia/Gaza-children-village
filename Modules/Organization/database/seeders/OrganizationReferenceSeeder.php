<?php

declare(strict_types=1);

namespace Modules\Organization\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Organization\Models\Organization;

/**
 * Idempotent seeder for the GCV organization reference record.
 *
 * Safe to run multiple times. Creates the record if it does not exist.
 * Preserves administrator-edited display names and lifecycle state on
 * subsequent runs — it will never overwrite existing values.
 *
 * Stable codes are set via direct property assignment (not mass assignment)
 * to stay consistent with the module's mass-assignment strategy, which
 * excludes codes from $fillable to prevent bulk overwrites.
 *
 * The Arabic name is the stakeholder-approved value 'قرية أطفال غزة'.
 *
 * Although GCV is currently the only organization, the schema does not
 * enforce a single-row constraint. Future organizations may be added without
 * a schema change.
 *
 * Note: this seeder must not create a reusable web authorization bypass.
 * It is for reference data initialization only.
 */
class OrganizationReferenceSeeder extends Seeder
{
    public function run(): void
    {
        if (Organization::where('code', 'gcv')->exists()) {
            return;
        }

        $organization = new Organization;
        $organization->code = 'gcv';
        $organization->name_en = 'Gaza Children Village';
        $organization->name_ar = 'قرية أطفال غزة';
        $organization->is_active = true;
        $organization->save();
    }
}

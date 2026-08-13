<?php

declare(strict_types=1);

namespace Modules\Organization\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Organization\Models\InstitutionType;

/**
 * Idempotent seeder for the approved foundation institution-type codes.
 *
 * Safe to run multiple times. Creates missing records; preserves
 * administrator-edited display names and lifecycle state on subsequent runs.
 *
 * Stable codes are set via direct property assignment (not mass assignment)
 * to stay consistent with the module's mass-assignment strategy, which
 * excludes codes from $fillable to prevent bulk overwrites.
 *
 * Approved stable codes:
 *   - academy
 *   - university_space
 *   - medical_point
 *   - womens_center
 *   - storage_unit
 *
 * Arabic names are intentionally left null until official approved
 * translations are supplied.
 *
 * Future types may be added without a schema change because codes are
 * stored as rows, not as PHP or database enums.
 *
 * Note: this seeder must not create a reusable web authorization bypass.
 * It is for reference data initialization only.
 */
class InstitutionTypeReferenceSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    private const TYPES = [
        'academy' => 'Academy',
        'university_space' => 'University Space',
        'medical_point' => 'Medical Point',
        'womens_center' => "Women's Center",
        'storage_unit' => 'Storage Unit',
    ];

    public function run(): void
    {
        foreach (self::TYPES as $code => $nameEn) {
            if (InstitutionType::where('code', $code)->exists()) {
                continue;
            }

            $type = new InstitutionType;
            $type->code = $code;
            $type->name_en = $nameEn;
            $type->name_ar = null;
            $type->is_active = true;
            $type->save();
        }
    }
}

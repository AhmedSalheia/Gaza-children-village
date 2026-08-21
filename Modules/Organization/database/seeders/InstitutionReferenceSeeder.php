<?php

declare(strict_types=1);

namespace Modules\Organization\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Organization\Models\Institution;
use Modules\Organization\Models\InstitutionType;
use Modules\Organization\Models\Organization;

/**
 * Idempotent seeder for known GCV institutions.
 *
 * Safe to run multiple times. Creates missing records; preserves
 * administrator-edited display names and lifecycle state on subsequent runs.
 *
 * Stable codes are set via direct property assignment (not mass assignment)
 * to stay consistent with the module's mass-assignment strategy, which
 * excludes codes from $fillable to prevent bulk overwrites.
 *
 * Requires the GCV organization and all institution-type rows to exist.
 * Run OrganizationReferenceSeeder and InstitutionTypeReferenceSeeder first,
 * or call this seeder via DatabaseSeeder which should order them correctly.
 *
 * Approved stable codes follow the pattern: {type_code}_{sequence}
 * e.g. academy_1 … academy_8, university_space_1 … university_space_2
 *
 * Arabic names are intentionally left null until official approved
 * translations are supplied.
 *
 * Note: this seeder must not create a reusable web authorization bypass.
 * It is for reference data initialization only.
 */
class InstitutionReferenceSeeder extends Seeder
{
    /**
     * Known GCV institutions indexed by stable code.
     * Each entry: [name_en, institution_type_code]
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const INSTITUTIONS = [
        // Schools / Academies of Hope (8)
        'academy_1' => ['Academy of Hope 1','أكاديمية الأمل 1', 'academy'],
        'academy_2' => ['Academy of Hope 2','أكاديمية الأمل 2', 'academy'],
        'academy_3' => ['Academy of Hope 3','أكاديمية الأمل 3', 'academy'],
        'academy_4' => ['Academy of Hope 4','أكاديمية الأمل 4', 'academy'],
        'academy_5' => ['Academy of Hope 5','أكاديمية الأمل 5', 'academy'],
        'academy_6' => ['Academy of Hope 6','أكاديمية الأمل 6', 'academy'],
        'academy_7' => ['Academy of Hope 7','أكاديمية الأمل 7', 'academy'],
        'academy_8' => ['Academy of Hope 8','أكاديمية الأمل 8', 'academy'],
        // University spaces (2)
        'university_space_1' => ['University Space 1','المساحة الجامعية 1', 'university_space'],
        'university_space_2' => ['University Space 2','المساحة الجامعية 2', 'university_space'],
        // Medical points (2)
        'medical_point_1' => ['Medical Point 1','النقطة الطبية 1', 'medical_point'],
        'medical_point_2' => ['Medical Point 2','النقطة الطبية 2', 'medical_point'],
        // Women's centers (2)
        'womens_center_1' => ["Women's Center 1",'مركز نسائي 1', 'womens_center'],
        'womens_center_2' => ["Women's Center 2",'مركز نسائي 2', 'womens_center'],
        // Storage units (5)
        'storage_unit_1' => ['Storage Unit 1','مخزن 1', 'storage_unit'],
        'storage_unit_2' => ['Storage Unit 2','مخزن 2', 'storage_unit'],
        'storage_unit_3' => ['Storage Unit 3','مخزن 3', 'storage_unit'],
        'storage_unit_4' => ['Storage Unit 4','مخزن 4', 'storage_unit'],
        'storage_unit_5' => ['Storage Unit 5','مخزن 5', 'storage_unit'],
    ];

    public function run(): void
    {
        $organization = Organization::where('code', 'gcv')->first();

        if ($organization === null) {
            return;
        }

        foreach (self::INSTITUTIONS as $code => [$nameEn, $nameAr, $typeCode]) {
            if (Institution::withoutGlobalScopes()->where('code', $code)->exists()) {
                continue;
            }

            $type = InstitutionType::where('code', $typeCode)->first();

            if ($type === null) {
                continue;
            }

            $institution = new Institution;
            $institution->code = $code;
            $institution->organization_id = $organization->id;
            $institution->institution_type_id = $type->id;
            $institution->name_en = $nameEn;
            $institution->name_ar = $nameAr;
            $institution->is_active = true;
            $institution->save();
        }
    }
}

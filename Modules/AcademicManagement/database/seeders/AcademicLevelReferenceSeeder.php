<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AcademicManagement\Models\AcademicLevel;

/**
 * Seeds the canonical GCV academic level catalogue.
 *
 * Idempotent: uses check-then-create with direct property assignment because
 * 'code' is excluded from $fillable (see F03 seeder pattern in project memory).
 *
 * Levels: KG1, KG2, then Grade1–Grade12 in sequence order.
 */
final class AcademicLevelReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['code' => 'KG1',    'name_ar' => 'حضانة أولى',         'name_en' => 'Kindergarten 1', 'sequence' => 1],
            ['code' => 'KG2',    'name_ar' => 'حضانة ثانية',        'name_en' => 'Kindergarten 2', 'sequence' => 2],
            ['code' => 'GRADE1', 'name_ar' => 'الصف الأول',         'name_en' => 'Grade 1',        'sequence' => 3],
            ['code' => 'GRADE2', 'name_ar' => 'الصف الثاني',        'name_en' => 'Grade 2',        'sequence' => 4],
            ['code' => 'GRADE3', 'name_ar' => 'الصف الثالث',        'name_en' => 'Grade 3',        'sequence' => 5],
            ['code' => 'GRADE4', 'name_ar' => 'الصف الرابع',        'name_en' => 'Grade 4',        'sequence' => 6],
            ['code' => 'GRADE5', 'name_ar' => 'الصف الخامس',        'name_en' => 'Grade 5',        'sequence' => 7],
            ['code' => 'GRADE6', 'name_ar' => 'الصف السادس',        'name_en' => 'Grade 6',        'sequence' => 8],
            ['code' => 'GRADE7', 'name_ar' => 'الصف السابع',        'name_en' => 'Grade 7',        'sequence' => 9],
            ['code' => 'GRADE8', 'name_ar' => 'الصف الثامن',        'name_en' => 'Grade 8',        'sequence' => 10],
            ['code' => 'GRADE9', 'name_ar' => 'الصف التاسع',        'name_en' => 'Grade 9',        'sequence' => 11],
            ['code' => 'GRADE10', 'name_ar' => 'الصف العاشر',        'name_en' => 'Grade 10',       'sequence' => 12],
            ['code' => 'GRADE11', 'name_ar' => 'الصف الحادي عشر',   'name_en' => 'Grade 11',       'sequence' => 13],
            ['code' => 'GRADE12', 'name_ar' => 'الصف الثاني عشر',   'name_en' => 'Grade 12',       'sequence' => 14],
        ];

        foreach ($levels as $data) {
            if (AcademicLevel::where('code', $data['code'])->exists()) {
                continue;
            }

            $level = new AcademicLevel;
            $level->code = $data['code'];
            $level->name_ar = $data['name_ar'];
            $level->name_en = $data['name_en'];
            $level->sequence = $data['sequence'];
            $level->is_active = true;
            $level->save();
        }
    }
}

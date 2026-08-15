<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the GCV canonical subject catalogue.
 *
 * Idempotent: check-then-create by code.
 */
final class DemoSubjectReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            ['code' => 'ARABIC',    'name_en' => 'Arabic Language',      'name_ar' => 'اللغة العربية'],
            ['code' => 'MATH',      'name_en' => 'Mathematics',           'name_ar' => 'الرياضيات'],
            ['code' => 'ENGLISH',   'name_en' => 'English Language',      'name_ar' => 'اللغة الإنجليزية'],
            ['code' => 'SCIENCE',   'name_en' => 'Science',               'name_ar' => 'العلوم'],
            ['code' => 'ISLAMIC',   'name_en' => 'Islamic Studies',       'name_ar' => 'التربية الإسلامية'],
            ['code' => 'SOCIAL',    'name_en' => 'Social Studies',        'name_ar' => 'التربية الاجتماعية'],
            ['code' => 'ART',       'name_en' => 'Art and Craft',         'name_ar' => 'التربية الفنية'],
            ['code' => 'PE',        'name_en' => 'Physical Education',    'name_ar' => 'التربية البدنية'],
        ];

        foreach ($subjects as $subject) {
            if (DB::table('subjects')->where('code', $subject['code'])->exists()) {
                continue;
            }

            DB::table('subjects')->insert([
                'code' => $subject['code'],
                'name_en' => $subject['name_en'],
                'name_ar' => $subject['name_ar'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

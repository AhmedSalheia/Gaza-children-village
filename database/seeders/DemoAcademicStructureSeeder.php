<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds classrooms, class groups, and subject offerings for demo academies.
 *
 * Operates on Academy 1 (academy_1) open semester only.
 * Creates 6 classrooms and class groups covering KG1, KG2, Grade1–Grade4.
 * Seeds all 8 subjects as institution offerings.
 *
 * Idempotent: check-then-create by code/composite key.
 */
final class DemoAcademicStructureSeeder extends Seeder
{
    public function run(): void
    {
        $inst1Id = DB::table('institutions')->where('code', 'academy_1')->value('id');

        if ($inst1Id === null) {
            $this->command?->warn('DemoAcademicStructureSeeder: academy_1 not found. Skipping.');

            return;
        }

        // Find open semester for academy_1
        $instSemId = DB::table('institution_semesters')
            ->where('institution_id', $inst1Id)
            ->where('status', 'open')
            ->value('id');

        if ($instSemId === null) {
            $this->command?->warn('DemoAcademicStructureSeeder: No open institution semester for academy_1. Skipping.');

            return;
        }

        // Get academic levels
        $levelIds = DB::table('academic_levels')
            ->whereIn('code', ['KG1', 'KG2', 'GRADE1', 'GRADE2', 'GRADE3', 'GRADE4'])
            ->pluck('id', 'code');

        // -----------------------------------------------------------
        // Classrooms
        // -----------------------------------------------------------
        $classroomCodes = [
            'CR-A1' => ['name_en' => 'Classroom A1', 'name_ar' => 'الغرفة أ1', 'capacity' => 30],
            'CR-A2' => ['name_en' => 'Classroom A2', 'name_ar' => 'الغرفة أ2', 'capacity' => 30],
            'CR-B1' => ['name_en' => 'Classroom B1', 'name_ar' => 'الغرفة ب1', 'capacity' => 28],
            'CR-B2' => ['name_en' => 'Classroom B2', 'name_ar' => 'الغرفة ب2', 'capacity' => 28],
            'CR-C1' => ['name_en' => 'Classroom C1', 'name_ar' => 'الغرفة ج1', 'capacity' => 32],
            'CR-C2' => ['name_en' => 'Classroom C2', 'name_ar' => 'الغرفة ج2', 'capacity' => 32],
        ];

        $classroomIds = [];

        foreach ($classroomCodes as $code => $data) {
            $existing = DB::table('classrooms')
                ->where('institution_id', $inst1Id)
                ->where('code', $code)
                ->first();

            if ($existing) {
                $classroomIds[$code] = $existing->id;

                continue;
            }

            $classroomIds[$code] = DB::table('classrooms')->insertGetId([
                'institution_id' => $inst1Id,
                'code' => $code,
                'name_en' => $data['name_en'],
                'name_ar' => $data['name_ar'],
                'capacity' => $data['capacity'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // -----------------------------------------------------------
        // Class Groups — open semester (morning shift)
        // -----------------------------------------------------------
        $morningOpPeriodId = DB::table('operational_periods')
            ->where('institution_semester_id', $instSemId)
            ->where('code', 'OP-MORN')
            ->value('id');

        if ($morningOpPeriodId === null) {
            $this->command?->warn('DemoAcademicStructureSeeder: Morning operational period not found. Skipping class groups.');

            return;
        }

        $classGroups = [
            [
                'code' => 'CG-KG1-A',
                'name_ar' => 'مجموعة الحضانة الأولى أ',
                'academic_level_code' => 'KG1',
                'classroom_code' => 'CR-A1',
                'lifecycle_status' => 'active',
            ],
            [
                'code' => 'CG-KG2-A',
                'name_ar' => 'مجموعة الحضانة الثانية أ',
                'academic_level_code' => 'KG2',
                'classroom_code' => 'CR-A2',
                'lifecycle_status' => 'active',
            ],
            [
                'code' => 'CG-G1-A',
                'name_ar' => 'مجموعة الصف الأول أ',
                'academic_level_code' => 'GRADE1',
                'classroom_code' => 'CR-B1',
                'lifecycle_status' => 'active',
            ],
            [
                'code' => 'CG-G2-A',
                'name_ar' => 'مجموعة الصف الثاني أ',
                'academic_level_code' => 'GRADE2',
                'classroom_code' => 'CR-B2',
                'lifecycle_status' => 'active',
            ],
            [
                'code' => 'CG-G3-A',
                'name_ar' => 'مجموعة الصف الثالث أ',
                'academic_level_code' => 'GRADE3',
                'classroom_code' => 'CR-C1',
                'lifecycle_status' => 'active',
            ],
            [
                'code' => 'CG-G4-A',
                'name_ar' => 'مجموعة الصف الرابع أ',
                'academic_level_code' => 'GRADE4',
                'classroom_code' => 'CR-C2',
                'lifecycle_status' => 'draft',
            ],
        ];

        foreach ($classGroups as $cg) {
            $exists = DB::table('class_groups')
                ->where('institution_semester_id', $instSemId)
                ->where('code', $cg['code'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('class_groups')->insert([
                'institution_semester_id' => $instSemId,
                'operational_period_id' => $morningOpPeriodId,
                'academic_level_id' => $levelIds[$cg['academic_level_code']] ?? null,
                'classroom_id' => $classroomIds[$cg['classroom_code']] ?? null,
                'code' => $cg['code'],
                'name_ar' => $cg['name_ar'],
                'name_en' => null,
                'capacity' => 30,
                'lifecycle_status' => $cg['lifecycle_status'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // -----------------------------------------------------------
        // Subject Offerings for open semester
        // -----------------------------------------------------------
        $subjectIds = DB::table('subjects')->pluck('id');

        foreach ($subjectIds as $subjectId) {
            $exists = DB::table('institution_subject_offerings')
                ->where('institution_semester_id', $instSemId)
                ->where('subject_id', $subjectId)
                ->exists();

            if (! $exists) {
                DB::table('institution_subject_offerings')->insert([
                    'institution_semester_id' => $instSemId,
                    'subject_id' => $subjectId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}

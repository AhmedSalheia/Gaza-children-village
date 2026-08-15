<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds synthetic staff profiles and their institution assignments,
 * positions, and position-role grants for Academy 1.
 *
 * Staff created:
 *  - 1 principal
 *  - 1 secretary (with operational period restriction)
 *  - 1 period-restricted secretary
 *  - 2 teachers
 *  - 1 counselor
 *
 * Idempotent: checks staff_code before inserting.
 */
final class DemoStaffSeeder extends Seeder
{
    public function run(): void
    {
        $inst1Id = DB::table('institutions')->where('code', 'academy_1')->value('id');

        if ($inst1Id === null) {
            $this->command->warn('DemoStaffSeeder: academy_1 not found. Skipping.');

            return;
        }

        $instSemId = DB::table('institution_semesters')
            ->where('institution_id', $inst1Id)
            ->where('status', 'open')
            ->value('id');

        $staff = [
            [
                'code' => 'STAFF-001',
                'full_name_ar' => 'سامي محمد العتيبي',
                'full_name_en' => 'Sami Mohammad Al-Otaibi',
                'sex' => 'male',
                'birth_date' => '1975-03-18',
                'position' => 'principal',
                'hired_on' => '2022-09-01',
            ],
            [
                'code' => 'STAFF-002',
                'full_name_ar' => 'نهاد عبد الرحمن الزبن',
                'full_name_en' => 'Nihad Abd Al-Rahman Al-Zubn',
                'sex' => 'female',
                'birth_date' => '1982-07-12',
                'position' => 'secretary',
                'hired_on' => '2023-01-15',
            ],
            [
                'code' => 'STAFF-003',
                'full_name_ar' => 'وليد حسن البطش',
                'full_name_en' => 'Walid Hassan Al-Batsh',
                'sex' => 'male',
                'birth_date' => '1979-11-28',
                'position' => 'secretary',
                'hired_on' => '2023-06-01',
            ],
            [
                'code' => 'STAFF-004',
                'full_name_ar' => 'أيمن رامي الجمال',
                'full_name_en' => 'Ayman Rami Al-Jamal',
                'sex' => 'male',
                'birth_date' => '1985-04-22',
                'position' => 'teacher',
                'hired_on' => '2024-09-01',
            ],
            [
                'code' => 'STAFF-005',
                'full_name_ar' => 'تغريد ماجد سليم',
                'full_name_en' => 'Taghrid Majid Salim',
                'sex' => 'female',
                'birth_date' => '1990-08-07',
                'position' => 'teacher',
                'hired_on' => '2024-09-01',
            ],
            [
                'code' => 'STAFF-006',
                'full_name_ar' => 'إيناس فارس عوض',
                'full_name_en' => 'Inas Faris Awad',
                'sex' => 'female',
                'birth_date' => '1988-02-14',
                'position' => 'counselor',
                'hired_on' => '2023-09-01',
            ],
        ];

        foreach ($staff as $s) {
            if (DB::table('staff_profiles')->where('staff_code', $s['code'])->exists()) {
                continue;
            }

            $personId = DB::table('people')->insertGetId([
                'full_name_ar' => $s['full_name_ar'],
                'full_name_en' => $s['full_name_en'],
                'birth_date' => $s['birth_date'],
                'birth_date_precision' => 'exact',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $staffProfileId = DB::table('staff_profiles')->insertGetId([
                'person_id' => $personId,
                'staff_code' => $s['code'],
                'employment_status' => 'active',
                'hired_on' => $s['hired_on'],
                'ended_on' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inst1Id === null) {
                continue;
            }

            $assignId = DB::table('staff_institution_assignments')->insertGetId([
                'staff_profile_id' => $staffProfileId,
                'institution_id' => $inst1Id,
                'started_on' => $s['hired_on'],
                'ended_on' => null,
                'closure_reason' => null,
                'source_actor' => 'seeder',
                'source_context' => 'Demo data seeding',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('staff_positions')->insert([
                'staff_profile_id' => $staffProfileId,
                'staff_institution_assignment_id' => $assignId,
                'institution_id' => $inst1Id,
                'institution_semester_id' => $instSemId,
                'position_definition' => $s['position'],
                'started_on' => $s['hired_on'],
                'ended_on' => null,
                'created_by' => 'seeder',
                'ended_by' => null,
                'closure_reason' => null,
                'closure_source' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Ensure position-role grants exist for all position definitions used
        $this->seedPositionRoleGrants();
    }

    private function seedPositionRoleGrants(): void
    {
        $grants = [
            'principal' => 'principal',
            'deputy_principal' => 'deputy_principal',
            'secretary' => 'secretary',
            'teacher' => 'teacher',
            'counselor' => 'counselor',
        ];

        foreach ($grants as $positionDef => $roleCode) {
            $roleId = DB::table('roles')->where('code', $roleCode)->value('id');

            if ($roleId === null) {
                continue;
            }

            $exists = DB::table('position_role_grants')
                ->where('position_definition', $positionDef)
                ->where('role_id', $roleId)
                ->exists();

            if (! $exists) {
                DB::table('position_role_grants')->insert([
                    'position_definition' => $positionDef,
                    'role_id' => $roleId,
                    'granted_by' => 'seeder',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}

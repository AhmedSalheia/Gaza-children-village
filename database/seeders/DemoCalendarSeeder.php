<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds a complete synthetic academic calendar for two GCV academies.
 *
 * Academy 1 (academy_1): current open semester + three operational periods.
 * Academy 2 (academy_2): one historical (closed) semester for historical data.
 *
 * Idempotent: checks before inserting.
 */
final class DemoCalendarSeeder extends Seeder
{
    public function run(): void
    {
        $orgId = DB::table('organizations')->where('code', 'gcv')->value('id');

        if ($orgId === null) {
            $this->command?->warn('DemoCalendarSeeder: GCV organization not found. Skipping.');

            return;
        }

        // -----------------------------------------------------------
        // Academic Years
        // -----------------------------------------------------------
        $year2024Id = $this->upsertAcademicYear($orgId, [
            'code' => 'AY-2024-2025',
            'name_en' => 'Academic Year 2024–2025',
            'name_ar' => 'العام الدراسي 2024–2025',
            'starts_on' => '2024-09-01',
            'ends_on' => '2025-06-30',
            'status' => 'archived',
        ]);

        $year2025Id = $this->upsertAcademicYear($orgId, [
            'code' => 'AY-2025-2026',
            'name_en' => 'Academic Year 2025–2026',
            'name_ar' => 'العام الدراسي 2025–2026',
            'starts_on' => '2025-09-01',
            'ends_on' => '2026-06-30',
            'status' => 'open',
        ]);

        // -----------------------------------------------------------
        // Semesters
        // -----------------------------------------------------------
        $sem2024S1Id = $this->upsertSemester($year2024Id, [
            'code' => '2024-S1',
            'name_en' => 'Semester 1 2024/25',
            'name_ar' => 'الفصل الأول 2024/25',
            'sequence' => 1,
            'starts_on' => '2024-09-01',
            'ends_on' => '2025-01-31',
            'status' => 'archived',
        ]);

        $sem2025S1Id = $this->upsertSemester($year2025Id, [
            'code' => '2025-S1',
            'name_en' => 'Semester 1 2025/26',
            'name_ar' => 'الفصل الأول 2025/26',
            'sequence' => 1,
            'starts_on' => '2025-09-01',
            'ends_on' => '2026-01-31',
            'status' => 'open',
        ]);

        // -----------------------------------------------------------
        // Institution Semesters
        // -----------------------------------------------------------
        $inst1Id = DB::table('institutions')->where('code', 'academy_1')->value('id');
        $inst2Id = DB::table('institutions')->where('code', 'academy_2')->value('id');

        if ($inst1Id === null || $inst2Id === null) {
            $this->command?->warn('DemoCalendarSeeder: Institutions academy_1/academy_2 not found. Skipping.');

            return;
        }

        // Academy 1: historical (archived) semester
        $instSem1HistId = $this->upsertInstitutionSemester($inst1Id, $sem2024S1Id, 'archived');
        // Academy 1: current open semester
        $instSem1OpenId = $this->upsertInstitutionSemester($inst1Id, $sem2025S1Id, 'open');
        // Academy 2: historical semester only
        $instSem2HistId = $this->upsertInstitutionSemester($inst2Id, $sem2024S1Id, 'archived');

        // -----------------------------------------------------------
        // Operational Periods for open semester (Academy 1)
        // -----------------------------------------------------------
        $this->upsertOperationalPeriod($instSem1OpenId, [
            'code' => 'OP-MORN',
            'name_en' => 'Morning Shift',
            'name_ar' => 'الفترة الصباحية',
            'sequence' => 1,
            'starts_at' => '07:30:00',
            'ends_at' => '12:00:00',
            'is_active' => true,
        ]);

        $this->upsertOperationalPeriod($instSem1OpenId, [
            'code' => 'OP-AFT',
            'name_en' => 'Afternoon Shift',
            'name_ar' => 'الفترة المسائية',
            'sequence' => 2,
            'starts_at' => '12:30:00',
            'ends_at' => '17:00:00',
            'is_active' => true,
        ]);

        $this->upsertOperationalPeriod($instSem1OpenId, [
            'code' => 'OP-EVE',
            'name_en' => 'Evening Program',
            'name_ar' => 'البرنامج المسائي',
            'sequence' => 3,
            'starts_at' => '17:30:00',
            'ends_at' => '21:00:00',
            'is_active' => false,
        ]);

        // Operational Periods for historical semester (Academy 1)
        $this->upsertOperationalPeriod($instSem1HistId, [
            'code' => 'OP-MORN',
            'name_en' => 'Morning Shift',
            'name_ar' => 'الفترة الصباحية',
            'sequence' => 1,
            'starts_at' => '07:30:00',
            'ends_at' => '12:00:00',
            'is_active' => false,
        ]);

        $this->upsertOperationalPeriod($instSem1HistId, [
            'code' => 'OP-AFT',
            'name_en' => 'Afternoon Shift',
            'name_ar' => 'الفترة المسائية',
            'sequence' => 2,
            'starts_at' => '12:30:00',
            'ends_at' => '17:00:00',
            'is_active' => false,
        ]);

        $this->upsertOperationalPeriod($instSem1HistId, [
            'code' => 'OP-EVE',
            'name_en' => 'Evening Program',
            'name_ar' => 'البرنامج المسائي',
            'sequence' => 3,
            'starts_at' => '17:30:00',
            'ends_at' => '21:00:00',
            'is_active' => false,
        ]);

        // Operational Periods for Academy 2 historical semester
        $this->upsertOperationalPeriod($instSem2HistId, [
            'code' => 'OP-MORN',
            'name_en' => 'Morning Shift',
            'name_ar' => 'الفترة الصباحية',
            'sequence' => 1,
            'starts_at' => '07:30:00',
            'ends_at' => '12:00:00',
            'is_active' => false,
        ]);

        $this->upsertOperationalPeriod($instSem2HistId, [
            'code' => 'OP-AFT',
            'name_en' => 'Afternoon Shift',
            'name_ar' => 'الفترة المسائية',
            'sequence' => 2,
            'starts_at' => '12:30:00',
            'ends_at' => '17:00:00',
            'is_active' => false,
        ]);

        $this->upsertOperationalPeriod($instSem2HistId, [
            'code' => 'OP-EVE',
            'name_en' => 'Evening Program',
            'name_ar' => 'البرنامج المسائي',
            'sequence' => 3,
            'starts_at' => '17:30:00',
            'ends_at' => '21:00:00',
            'is_active' => false,
        ]);
    }

    private function upsertAcademicYear(int $orgId, array $data): int
    {
        $existing = DB::table('academic_years')->where('code', $data['code'])->first();

        if ($existing) {
            return $existing->id;
        }

        return DB::table('academic_years')->insertGetId([
            'organization_id' => $orgId,
            'code' => $data['code'],
            'name_en' => $data['name_en'],
            'name_ar' => $data['name_ar'],
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'],
            'status' => $data['status'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function upsertSemester(int $yearId, array $data): int
    {
        $existing = DB::table('semesters')
            ->where('academic_year_id', $yearId)
            ->where('code', $data['code'])
            ->first();

        if ($existing) {
            return $existing->id;
        }

        return DB::table('semesters')->insertGetId([
            'academic_year_id' => $yearId,
            'code' => $data['code'],
            'name_en' => $data['name_en'],
            'name_ar' => $data['name_ar'],
            'sequence' => $data['sequence'],
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'],
            'status' => $data['status'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function upsertInstitutionSemester(int $instId, int $semId, string $status): int
    {
        $existing = DB::table('institution_semesters')
            ->where('institution_id', $instId)
            ->where('semester_id', $semId)
            ->first();

        if ($existing) {
            return $existing->id;
        }

        return DB::table('institution_semesters')->insertGetId([
            'institution_id' => $instId,
            'semester_id' => $semId,
            'status' => $status,
            'copied_from_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function upsertOperationalPeriod(int $instSemId, array $data): void
    {
        $exists = DB::table('operational_periods')
            ->where('institution_semester_id', $instSemId)
            ->where('code', $data['code'])
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('operational_periods')->insert([
            'institution_semester_id' => $instSemId,
            'code' => $data['code'],
            'name_en' => $data['name_en'],
            'name_ar' => $data['name_ar'],
            'sequence' => $data['sequence'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'is_active' => $data['is_active'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

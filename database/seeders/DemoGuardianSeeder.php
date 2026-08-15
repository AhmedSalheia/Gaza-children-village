<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds guardian profiles and their relationships to students.
 *
 * Covers the required relationship permutations:
 * - Student with no guardian (STU-027 — draft, stays ungrouped)
 * - Student with a single guardian (STU-001)
 * - Student with multiple guardians (STU-002: mother + father)
 * - Guardian with multiple students (shared guardian for STU-003 & STU-004 — siblings)
 * - Sibling relationship between students (STU-003 & STU-004)
 *
 * Idempotent: checks guardian_code before inserting.
 */
final class DemoGuardianSeeder extends Seeder
{
    public function run(): void
    {
        // -----------------------------------------------------------
        // Guardians
        // -----------------------------------------------------------
        $guardianData = [
            [
                'code' => 'GRD-001',
                'full_name_ar' => 'محمد علي حسن',
                'full_name_en' => 'Mohammad Ali Hassan',
                'sex' => 'male',
                'birth_date' => '1980-05-14',
                'students' => [
                    ['code' => 'STU-001', 'type' => 'father', 'priority' => 1, 'emergency' => true, 'portal_eligible' => true],
                ],
            ],
            [
                'code' => 'GRD-002',
                'full_name_ar' => 'سعاد أحمد المصري',
                'full_name_en' => 'Suad Ahmad Al-Masri',
                'sex' => 'female',
                'birth_date' => '1985-09-22',
                'students' => [
                    ['code' => 'STU-002', 'type' => 'mother', 'priority' => 1, 'emergency' => true, 'portal_eligible' => true],
                ],
            ],
            [
                'code' => 'GRD-003',
                'full_name_ar' => 'عبد الله كمال المطيري',
                'full_name_en' => 'Abdullah Kamal Al-Mutairi',
                'sex' => 'male',
                'birth_date' => '1982-03-08',
                'students' => [
                    ['code' => 'STU-002', 'type' => 'father', 'priority' => 2, 'emergency' => false, 'portal_eligible' => false],
                ],
            ],
            [
                'code' => 'GRD-004',
                'full_name_ar' => 'خديجة يوسف إبراهيم',
                'full_name_en' => 'Khadija Yousuf Ibrahim',
                'sex' => 'female',
                'birth_date' => '1978-11-30',
                'students' => [
                    // Shared guardian for STU-003 & STU-004 — siblings
                    ['code' => 'STU-003', 'type' => 'mother', 'priority' => 1, 'emergency' => true, 'portal_eligible' => true],
                    ['code' => 'STU-004', 'type' => 'mother', 'priority' => 1, 'emergency' => true, 'portal_eligible' => false],
                ],
            ],
            [
                'code' => 'GRD-005',
                'full_name_ar' => 'رانيا سالم القيسي',
                'full_name_en' => 'Rania Salem Al-Qaisi',
                'sex' => 'female',
                'birth_date' => '1988-07-17',
                'students' => [
                    ['code' => 'STU-005', 'type' => 'legal_guardian', 'priority' => 1, 'emergency' => true, 'portal_eligible' => true],
                ],
            ],
            [
                'code' => 'GRD-006',
                'full_name_ar' => 'أحمد سامي القاسم',
                'full_name_en' => 'Ahmad Sami Al-Qasim',
                'sex' => 'male',
                'birth_date' => '1975-01-28',
                'students' => [
                    ['code' => 'STU-006', 'type' => 'father', 'priority' => 1, 'emergency' => true, 'portal_eligible' => true],
                    ['code' => 'STU-007', 'type' => 'father', 'priority' => 1, 'emergency' => true, 'portal_eligible' => false],
                ],
            ],
            [
                'code' => 'GRD-007',
                'full_name_ar' => 'منى حسن العواودة',
                'full_name_en' => 'Mona Hassan Al-Awawdeh',
                'sex' => 'female',
                'birth_date' => '1990-04-12',
                'students' => [
                    ['code' => 'STU-010', 'type' => 'mother', 'priority' => 1, 'emergency' => true, 'portal_eligible' => true],
                ],
            ],
        ];

        foreach ($guardianData as $g) {
            if (DB::table('guardian_profiles')->where('guardian_code', $g['code'])->exists()) {
                continue;
            }

            // Create Person for guardian
            $personId = DB::table('people')->insertGetId([
                'full_name_ar' => $g['full_name_ar'],
                'full_name_en' => $g['full_name_en'],
                'birth_date' => $g['birth_date'],
                'birth_date_precision' => 'exact',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create GuardianProfile
            $guardianId = DB::table('guardian_profiles')->insertGetId([
                'person_id' => $personId,
                'guardian_code' => $g['code'],
                'lifecycle_status' => 'active',
                'guardian_account_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Link to students
            foreach ($g['students'] as $rel) {
                $studentId = DB::table('student_profiles')
                    ->where('student_code', $rel['code'])
                    ->value('id');

                if ($studentId === null) {
                    continue;
                }

                $exists = DB::table('guardian_student_relationships')
                    ->where('guardian_profile_id', $guardianId)
                    ->where('student_profile_id', $studentId)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('guardian_student_relationships')->insert([
                    'student_profile_id' => $studentId,
                    'guardian_profile_id' => $guardianId,
                    'relationship_type' => $rel['type'],
                    'legal_authority' => 'primary',
                    'verification_status' => 'verified',
                    'portal_eligible' => $rel['portal_eligible'],
                    'contact_priority' => $rel['priority'],
                    'is_emergency_contact' => $rel['emergency'],
                    'starts_on' => null,
                    'ends_on' => null,
                    'restricted_notes' => null,
                    'evidence_status' => 'none',
                    'evidence_reference' => null,
                    'history_metadata' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // -----------------------------------------------------------
        // Sibling relationship: STU-003 ↔ STU-004
        // -----------------------------------------------------------
        $stu3Id = DB::table('student_profiles')->where('student_code', 'STU-003')->value('id');
        $stu4Id = DB::table('student_profiles')->where('student_code', 'STU-004')->value('id');

        // Note: Guardian_student_relationships doesn't support student-to-student links
        // directly. The sibling relationship is implied by shared guardian GRD-004.
        // This comment is intentional — actual sibling student-to-student relationship
        // tables are not part of the current schema; the linkage is via shared guardian.
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the complete marks workflow demo dataset for Academy 1.
 *
 * Creates (idempotent — check-then-create throughout):
 *  - Standard grading scale with 7 grade bands (A+ … F)
 *  - 3 global assessment definitions (Quiz 20%, Midterm 30%, Final 50%)
 *  - Teaching assignments for STAFF-004 (Arabic, Math) and STAFF-005 (English)
 *    covering class groups CG-KG1-A, CG-G1-A, and CG-G2-A
 *  - Homeroom assignments (STAFF-004 → G1-A lead; STAFF-005 → G2-A lead)
 *  - Staff position-period grants (teachers + secretary → OP-MORN)
 *  - One open mark-entry window and one closed window
 *  - Mark sheets in multiple lifecycle statuses across class groups
 *  - Student marks with realistic scores (with one correction for demo)
 *
 * Runs AFTER: DemoStaffSeeder, DemoEnrollmentSeeder, DemoAcademicStructureSeeder.
 */
final class DemoMarkSeeder extends Seeder
{
    public function run(): void
    {
        $inst1Id = (int) DB::table('institutions')->where('code', 'academy_1')->value('id');

        if ($inst1Id === 0) {
            $this->command->warn('DemoMarkSeeder: academy_1 not found. Skipping.');

            return;
        }

        $instSemId = (int) DB::table('institution_semesters')
            ->where('institution_id', $inst1Id)
            ->where('status', 'open')
            ->value('id');

        if ($instSemId === 0) {
            $this->command->warn('DemoMarkSeeder: No open semester for academy_1. Skipping.');

            return;
        }

        $opMornId = (int) DB::table('operational_periods')
            ->where('institution_semester_id', $instSemId)
            ->where('code', 'OP-MORN')
            ->value('id');

        // ── Class groups ──────────────────────────────────────────────────
        $cgKg1aId = (int) DB::table('class_groups')
            ->where('code', 'CG-KG1-A')
            ->where('institution_semester_id', $instSemId)
            ->value('id');

        $cgG1aId = (int) DB::table('class_groups')
            ->where('code', 'CG-G1-A')
            ->where('institution_semester_id', $instSemId)
            ->value('id');

        $cgG2aId = (int) DB::table('class_groups')
            ->where('code', 'CG-G2-A')
            ->where('institution_semester_id', $instSemId)
            ->value('id');

        // ── Subject offerings (all seeded by DemoAcademicStructureSeeder) ─
        $subjectOffering = fn (string $code): int => (int) DB::table('institution_subject_offerings as iso')
            ->join('subjects as s', 's.id', '=', 'iso.subject_id')
            ->where('iso.institution_semester_id', $instSemId)
            ->where('s.code', $code)
            ->value('iso.id');

        $offArabicId = $subjectOffering('ARABIC');
        $offMathId = $subjectOffering('MATH');
        $offEnglishId = $subjectOffering('ENGLISH');
        $offScienceId = $subjectOffering('SCIENCE');

        // ── Staff profiles and positions ──────────────────────────────────
        $staff2Id = (int) DB::table('staff_profiles')->where('staff_code', 'STAFF-002')->value('id');
        $staff4Id = (int) DB::table('staff_profiles')->where('staff_code', 'STAFF-004')->value('id');
        $staff5Id = (int) DB::table('staff_profiles')->where('staff_code', 'STAFF-005')->value('id');

        $pos2Id = (int) DB::table('staff_positions')
            ->where('staff_profile_id', $staff2Id)
            ->where('institution_semester_id', $instSemId)
            ->value('id');

        $pos4Id = (int) DB::table('staff_positions')
            ->where('staff_profile_id', $staff4Id)
            ->where('institution_semester_id', $instSemId)
            ->value('id');

        $pos5Id = (int) DB::table('staff_positions')
            ->where('staff_profile_id', $staff5Id)
            ->where('institution_semester_id', $instSemId)
            ->value('id');

        // ── 1. Grading scale ──────────────────────────────────────────────
        $scaleId = $this->seedGradingScale($inst1Id);

        // ── 2. Staff position-period grants ───────────────────────────────
        // Teachers and secretary need OP-MORN to access their scoped data
        foreach (array_filter([$pos2Id, $pos4Id, $pos5Id]) as $posId) {
            if ($opMornId && ! DB::table('staff_position_periods')
                ->where('staff_position_id', $posId)
                ->where('operational_period_id', $opMornId)
                ->exists()
            ) {
                DB::table('staff_position_periods')->insert([
                    'staff_position_id' => $posId,
                    'operational_period_id' => $opMornId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // ── 3. Teaching assignments ───────────────────────────────────────
        $assigns = [
            // STAFF-004 (teacher): Arabic and Math for G1-A and G2-A
            ['staff_profile_id' => $staff4Id, 'staff_position_id' => $pos4Id, 'class_group_id' => $cgG1aId,  'subject_offering_id' => $offArabicId],
            ['staff_profile_id' => $staff4Id, 'staff_position_id' => $pos4Id, 'class_group_id' => $cgG2aId,  'subject_offering_id' => $offArabicId],
            ['staff_profile_id' => $staff4Id, 'staff_position_id' => $pos4Id, 'class_group_id' => $cgG1aId,  'subject_offering_id' => $offMathId],
            ['staff_profile_id' => $staff4Id, 'staff_position_id' => $pos4Id, 'class_group_id' => $cgG2aId,  'subject_offering_id' => $offMathId],
            // STAFF-005 (teacher2): English and Science for G1-A, G2-A and KG1-A
            ['staff_profile_id' => $staff5Id, 'staff_position_id' => $pos5Id, 'class_group_id' => $cgKg1aId, 'subject_offering_id' => $offEnglishId],
            ['staff_profile_id' => $staff5Id, 'staff_position_id' => $pos5Id, 'class_group_id' => $cgG1aId,  'subject_offering_id' => $offEnglishId],
            ['staff_profile_id' => $staff5Id, 'staff_position_id' => $pos5Id, 'class_group_id' => $cgG2aId,  'subject_offering_id' => $offEnglishId],
            ['staff_profile_id' => $staff5Id, 'staff_position_id' => $pos5Id, 'class_group_id' => $cgG1aId,  'subject_offering_id' => $offScienceId],
        ];

        $assignmentIds = [];

        foreach ($assigns as $a) {
            $existing = DB::table('teaching_assignments')
                ->where('staff_profile_id', $a['staff_profile_id'])
                ->where('class_group_id', $a['class_group_id'])
                ->where('subject_offering_id', $a['subject_offering_id'])
                ->where('status', 'active')
                ->value('id');

            if ($existing) {
                $assignmentIds["{$a['class_group_id']}-{$a['subject_offering_id']}"] = (int) $existing;
            } else {
                $id = (int) DB::table('teaching_assignments')->insertGetId([
                    'staff_profile_id' => $a['staff_profile_id'],
                    'institution_semester_id' => $instSemId,
                    'staff_position_id' => $a['staff_position_id'],
                    'class_group_id' => $a['class_group_id'],
                    'subject_offering_id' => $a['subject_offering_id'],
                    'starts_on' => '2025-09-01',
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $assignmentIds["{$a['class_group_id']}-{$a['subject_offering_id']}"] = $id;
            }
        }

        // ── 4. Homeroom assignments ───────────────────────────────────────
        $homeroomAssigns = [
            ['staff_profile_id' => $staff4Id, 'staff_position_id' => $pos4Id, 'class_group_id' => $cgG1aId,  'is_co_lead' => false],
            ['staff_profile_id' => $staff5Id, 'staff_position_id' => $pos5Id, 'class_group_id' => $cgG2aId,  'is_co_lead' => false],
            ['staff_profile_id' => $staff5Id, 'staff_position_id' => $pos5Id, 'class_group_id' => $cgKg1aId, 'is_co_lead' => false],
        ];

        foreach ($homeroomAssigns as $ha) {
            if (! DB::table('homeroom_assignments')
                ->where('staff_profile_id', $ha['staff_profile_id'])
                ->where('class_group_id', $ha['class_group_id'])
                ->where('status', 'active')
                ->exists()
            ) {
                DB::table('homeroom_assignments')->insert([
                    'staff_profile_id' => $ha['staff_profile_id'],
                    'institution_semester_id' => $instSemId,
                    'staff_position_id' => $ha['staff_position_id'],
                    'class_group_id' => $ha['class_group_id'],
                    'is_co_lead' => $ha['is_co_lead'],
                    'starts_on' => '2025-09-01',
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // ── 5. Global assessment definitions ─────────────────────────────
        // Null class_group_id + null subject_offering_id = applies to all sheets
        $assessmentDefs = [
            ['name_ar' => 'اختبار شفوي', 'name_en' => 'Oral Quiz',   'assessment_type' => 'quiz',     'max_score' => 20, 'weight' => 20],
            ['name_ar' => 'اختبار فصلي',  'name_en' => 'Midterm Exam', 'assessment_type' => 'midterm',  'max_score' => 30, 'weight' => 30],
            ['name_ar' => 'امتحان نهائي',  'name_en' => 'Final Exam',  'assessment_type' => 'final',    'max_score' => 50, 'weight' => 50],
        ];

        $defIds = [];

        foreach ($assessmentDefs as $def) {
            $existing = DB::table('assessment_definitions')
                ->where('institution_semester_id', $instSemId)
                ->whereNull('class_group_id')
                ->whereNull('subject_offering_id')
                ->where('name_en', $def['name_en'])
                ->value('id');

            if ($existing) {
                $defIds[] = (int) $existing;
            } else {
                $defIds[] = (int) DB::table('assessment_definitions')->insertGetId([
                    'institution_semester_id' => $instSemId,
                    'class_group_id' => null,
                    'subject_offering_id' => null,
                    'name_ar' => $def['name_ar'],
                    'name_en' => $def['name_en'],
                    'assessment_type' => $def['assessment_type'],
                    'max_score' => $def['max_score'],
                    'weight' => $def['weight'],
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // ── 6. Mark entry windows ─────────────────────────────────────────
        // Open window (closes in future)
        $openWindowId = $this->upsertWindow($instSemId, 'Open Window 2025-S1', 'نافذة الإدخال الحالية',
            now()->subDays(30), now()->addDays(30), 'open');

        // Closed window (past)
        $closedWindowId = $this->upsertWindow($instSemId, 'Closed Window 2025-S1', 'نافذة الإدخال المغلقة',
            now()->subMonths(3), now()->subMonths(1), 'closed');

        // ── 7. Mark sheets ────────────────────────────────────────────────
        // Open window sheets
        $sheetG1Arabic = $this->upsertMarkSheet($instSemId, $cgG1aId, $offArabicId, $assignmentIds["{$cgG1aId}-{$offArabicId}"], $scaleId, $openWindowId, 'approved', $staff4Id);
        $sheetG1Math = $this->upsertMarkSheet($instSemId, $cgG1aId, $offMathId, $assignmentIds["{$cgG1aId}-{$offMathId}"], $scaleId, $openWindowId, 'approved', $staff4Id);
        $sheetG1English = $this->upsertMarkSheet($instSemId, $cgG1aId, $offEnglishId, $assignmentIds["{$cgG1aId}-{$offEnglishId}"], $scaleId, $openWindowId, 'approved', $staff5Id);
        $sheetG1Science = $this->upsertMarkSheet($instSemId, $cgG1aId, $offScienceId, $assignmentIds["{$cgG1aId}-{$offScienceId}"], $scaleId, $openWindowId, 'submitted', $staff5Id);
        $sheetKg1English = $this->upsertMarkSheet($instSemId, $cgKg1aId, $offEnglishId, $assignmentIds["{$cgKg1aId}-{$offEnglishId}"], $scaleId, $openWindowId, 'approved', $staff5Id);

        // Closed window sheets (variety of statuses)
        $sheetG2Arabic = $this->upsertMarkSheet($instSemId, $cgG2aId, $offArabicId, $assignmentIds["{$cgG2aId}-{$offArabicId}"], $scaleId, $closedWindowId, 'verified', null);
        $sheetG2Math = $this->upsertMarkSheet($instSemId, $cgG2aId, $offMathId, $assignmentIds["{$cgG2aId}-{$offMathId}"], null, $closedWindowId, 'returned', null);
        $sheetG2English = $this->upsertMarkSheet($instSemId, $cgG2aId, $offEnglishId, $assignmentIds["{$cgG2aId}-{$offEnglishId}"], null, null, 'draft', null);

        // ── 8. Student marks ──────────────────────────────────────────────
        // G1-A students: STU-004 and STU-005
        $enrollG1 = DB::table('student_enrollments')
            ->where('institution_semester_id', $instSemId)
            ->where('class_group_id', $cgG1aId)
            ->whereIn('enrollment_status', ['active'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // KG1-A students: STU-001 and STU-002
        $enrollKg1 = DB::table('student_enrollments')
            ->where('institution_semester_id', $instSemId)
            ->where('class_group_id', $cgKg1aId)
            ->whereIn('enrollment_status', ['active'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // Scores: [quiz, midterm, final] — realistic variation
        $g1Scores = [
            [$defIds[0], $defIds[1], $defIds[2]],
            // STU-004: strong student
            [17, 26, 44],
            // STU-005: average student
            [13, 20, 35],
        ];

        // Seed marks for G1-A approved sheets (Arabic, Math, English)
        foreach ([$sheetG1Arabic, $sheetG1Math, $sheetG1English] as $sheetId) {
            if ($sheetId === 0) {
                continue;
            }

            foreach ($enrollG1 as $idx => $enrollmentId) {
                $this->upsertMark($sheetId, $enrollmentId, $defIds[0], $idx === 0 ? 17 : 13);
                $this->upsertMark($sheetId, $enrollmentId, $defIds[1], $idx === 0 ? 26 : 20);
                $this->upsertMark($sheetId, $enrollmentId, $defIds[2], $idx === 0 ? 44 : 35);
            }
        }

        // Science (submitted): marks entered but not submitted yet — some blanks for demo
        if ($sheetG1Science !== 0 && ! empty($enrollG1)) {
            $this->upsertMark($sheetG1Science, $enrollG1[0], $defIds[0], 15);
            $this->upsertMark($sheetG1Science, $enrollG1[0], $defIds[1], 22);
            // Final exam not entered yet (null score with exception 'pending' would be normal here)
        }

        // KG1-A English (approved — for guardian publication demo)
        foreach ($enrollKg1 as $idx => $enrollmentId) {
            $this->upsertMark($sheetKg1English, $enrollmentId, $defIds[0], $idx === 0 ? 18 : 16);
            $this->upsertMark($sheetKg1English, $enrollmentId, $defIds[1], $idx === 0 ? 27 : 24);
            $this->upsertMark($sheetKg1English, $enrollmentId, $defIds[2], $idx === 0 ? 45 : 40);
        }

        // Add one correction to the first G1-A Arabic mark for demo purposes
        if ($sheetG1Arabic !== 0 && ! empty($enrollG1) && ! empty($defIds)) {
            $origMark = DB::table('student_marks')
                ->where('mark_sheet_id', $sheetG1Arabic)
                ->where('enrollment_id', $enrollG1[0])
                ->where('assessment_definition_id', $defIds[0])
                ->whereNull('correction_of_id')
                ->first();

            if ($origMark && ! DB::table('student_marks')->where('correction_of_id', $origMark->id)->exists()) {
                DB::table('student_marks')->insert([
                    'mark_sheet_id' => $sheetG1Arabic,
                    'enrollment_id' => $enrollG1[0],
                    'assessment_definition_id' => $defIds[0],
                    'score' => 19,
                    'correction_of_id' => $origMark->id,
                    'corrected_by_staff_profile_id' => $staff2Id, // secretary corrected
                    'corrected_at' => now()->subDays(2),
                    'correction_reason' => 'Handwriting was misread; original score was 17, corrected to 19 after paper re-check.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // G2-A sheets (verified/returned) — some marks for completeness
        $enrollG2 = DB::table('student_enrollments')
            ->where('institution_semester_id', $instSemId)
            ->where('class_group_id', $cgG2aId)
            ->whereIn('enrollment_status', ['active'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($sheetG2Arabic !== 0) {
            foreach ($enrollG2 as $idx => $enrollmentId) {
                $this->upsertMark($sheetG2Arabic, $enrollmentId, $defIds[0], $idx === 0 ? 14 : 11);
                $this->upsertMark($sheetG2Arabic, $enrollmentId, $defIds[1], $idx === 0 ? 22 : 18);
                $this->upsertMark($sheetG2Arabic, $enrollmentId, $defIds[2], $idx === 0 ? 38 : 30);
            }
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function seedGradingScale(int $institutionId): int
    {
        $existing = DB::table('grading_scales')
            ->where('institution_id', $institutionId)
            ->where('code', 'GCV-STD')
            ->value('id');

        if ($existing) {
            return (int) $existing;
        }

        $scaleId = (int) DB::table('grading_scales')->insertGetId([
            'institution_id' => $institutionId,
            'code' => 'GCV-STD',
            'name_ar' => 'مقياس التقدير المعياري',
            'name_en' => 'Standard Grading Scale',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $grades = [
            ['code' => 'A+', 'name_ar' => 'ممتاز جداً',   'name_en' => 'Excellent+',   'min_score' => 90, 'max_score' => 100, 'is_passing' => true,  'sequence' => 1],
            ['code' => 'A',  'name_ar' => 'ممتاز',         'name_en' => 'Excellent',     'min_score' => 80, 'max_score' => 89,  'is_passing' => true,  'sequence' => 2],
            ['code' => 'B+', 'name_ar' => 'جيد جداً',     'name_en' => 'Very Good+',    'min_score' => 75, 'max_score' => 79,  'is_passing' => true,  'sequence' => 3],
            ['code' => 'B',  'name_ar' => 'جيد',           'name_en' => 'Good',          'min_score' => 65, 'max_score' => 74,  'is_passing' => true,  'sequence' => 4],
            ['code' => 'C',  'name_ar' => 'مقبول',         'name_en' => 'Satisfactory',  'min_score' => 50, 'max_score' => 64,  'is_passing' => true,  'sequence' => 5],
            ['code' => 'D',  'name_ar' => 'ضعيف',          'name_en' => 'Poor',          'min_score' => 40, 'max_score' => 49,  'is_passing' => false, 'sequence' => 6],
            ['code' => 'F',  'name_ar' => 'راسب',          'name_en' => 'Fail',          'min_score' => 0,  'max_score' => 39,  'is_passing' => false, 'sequence' => 7],
        ];

        foreach ($grades as $g) {
            DB::table('grading_scale_grades')->insert([
                'grading_scale_id' => $scaleId,
                'code' => $g['code'],
                'name_ar' => $g['name_ar'],
                'name_en' => $g['name_en'],
                'min_score' => $g['min_score'],
                'max_score' => $g['max_score'],
                'is_passing' => $g['is_passing'],
                'sequence' => $g['sequence'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $scaleId;
    }

    private function upsertWindow(int $instSemId, string $nameEn, string $nameAr, \DateTimeInterface $opensAt, \DateTimeInterface $closesAt, string $status): int
    {
        $existing = DB::table('mark_entry_windows')
            ->where('institution_semester_id', $instSemId)
            ->where('name_en', $nameEn)
            ->value('id');

        if ($existing) {
            return (int) $existing;
        }

        return (int) DB::table('mark_entry_windows')->insertGetId([
            'institution_semester_id' => $instSemId,
            'class_group_id' => null,
            'subject_offering_id' => null,
            'name_ar' => $nameAr,
            'name_en' => $nameEn,
            'opens_at' => $opensAt,
            'closes_at' => $closesAt,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  'draft'|'submitted'|'verified'|'returned'|'approved'  $status
     */
    private function upsertMarkSheet(
        int $instSemId,
        int $classGroupId,
        int $subjectOfferingId,
        int $teachingAssignmentId,
        ?int $gradingScaleId,
        ?int $windowId,
        string $status,
        ?int $approverStaffProfileId,
    ): int {
        $existing = DB::table('mark_sheets')
            ->where('institution_semester_id', $instSemId)
            ->where('class_group_id', $classGroupId)
            ->where('subject_offering_id', $subjectOfferingId)
            ->whereNull('superseded_by_id')
            ->value('id');

        if ($existing) {
            return (int) $existing;
        }

        $row = [
            'institution_semester_id' => $instSemId,
            'class_group_id' => $classGroupId,
            'subject_offering_id' => $subjectOfferingId,
            'teaching_assignment_id' => $teachingAssignmentId,
            'mark_entry_window_id' => $windowId,
            'grading_scale_id' => $gradingScaleId,
            'version' => 1,
            'status' => $status,
            'created_at' => now()->subDays(10),
            'updated_at' => now(),
        ];

        if ($status === 'submitted' || $status === 'verified' || $status === 'returned' || $status === 'approved') {
            $row['submitted_by_staff_profile_id'] = $approverStaffProfileId ?? 1;
            $row['submitted_at'] = now()->subDays(8);
        }

        if ($status === 'verified' || $status === 'approved') {
            $row['verified_by_staff_profile_id'] = 1;
            $row['verified_at'] = now()->subDays(5);
        }

        if ($status === 'returned') {
            $row['returned_by_staff_profile_id'] = 1;
            $row['returned_at'] = now()->subDays(4);
            $row['return_reason'] = 'يرجى مراجعة علامات الاختبار النهائي للطلاب غير الحاضرين.';
        }

        if ($status === 'approved') {
            $row['approved_by_staff_profile_id'] = 1;
            $row['approved_at'] = now()->subDays(3);
        }

        return (int) DB::table('mark_sheets')->insertGetId($row);
    }

    private function upsertMark(int $sheetId, int $enrollmentId, int $definitionId, float $score): void
    {
        if (DB::table('student_marks')
            ->where('mark_sheet_id', $sheetId)
            ->where('enrollment_id', $enrollmentId)
            ->where('assessment_definition_id', $definitionId)
            ->whereNull('correction_of_id')
            ->exists()
        ) {
            return;
        }

        DB::table('student_marks')->insert([
            'mark_sheet_id' => $sheetId,
            'enrollment_id' => $enrollmentId,
            'assessment_definition_id' => $definitionId,
            'score' => $score,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

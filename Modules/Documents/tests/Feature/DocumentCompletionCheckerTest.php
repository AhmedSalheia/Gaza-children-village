<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Documents\Services\DocumentCompletionChecker;

uses(RefreshDatabase::class);

describe('DocumentCompletionChecker', function (): void {

    test('returns empty failures when no completeness_checks defined', function (): void {
        // Seed a document type with no checks
        DB::table('document_type_catalogue')->insert([
            'code' => 'test_type',
            'label_ar' => 'نوع اختبار',
            'label_en' => 'Test Type',
            'completeness_checks' => null,
            'required_context_keys' => '[]',
            'allowed_requesters' => '["guardian","staff"]',
            'public_verification' => true,
            'reissuable' => false,
            'validity_days' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $checker = app(DocumentCompletionChecker::class);
        $failures = $checker->check('test_type', 1);

        expect($failures)->toBeEmpty();
    });

    test('returns failure when enrollment not found', function (): void {
        DB::table('document_type_catalogue')->insert([
            'code' => 'test_type_2',
            'label_ar' => 'نوع اختبار 2',
            'label_en' => 'Test Type 2',
            'completeness_checks' => '["active_enrollment"]',
            'required_context_keys' => '[]',
            'allowed_requesters' => '["guardian"]',
            'public_verification' => true,
            'reissuable' => false,
            'validity_days' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $checker = app(DocumentCompletionChecker::class);
        $failures = $checker->check('test_type_2', 99999); // non-existent enrollment

        expect($failures)->not->toBeEmpty()
            ->and($failures[0])->toContain('التسجيل');
    });

    test('returns failure for inactive enrollment', function (): void {
        // Seed minimal data to test enrollment_is_active check
        $personId = DB::table('people')->insertGetId([
            'full_name_ar' => 'أحمد الاختبار',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $studentProfileId = DB::table('student_profiles')->insertGetId([
            'person_id' => $personId,
            'student_code' => 'STU-TEST-001',
            'registered_on' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orgId = DB::table('organizations')->insertGetId([
            'code' => 'GCV-CMP',
            'name_en' => 'Checker Org',
            'name_ar' => 'منظمة الفحص',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $instTypeId = DB::table('institution_types')->insertGetId([
            'code' => 'school',
            'name_ar' => 'مدرسة',
            'name_en' => 'School',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $instId = DB::table('institutions')->insertGetId([
            'organization_id' => $orgId,
            'institution_type_id' => $instTypeId,
            'code' => 'TST-001',
            'name_ar' => 'مدرسة الاختبار',
            'name_en' => 'Test School',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $academicYearId = DB::table('academic_years')->insertGetId([
            'organization_id' => $orgId,
            'code' => '2026',
            'name_en' => '2025-2026',
            'starts_on' => '2025-09-01',
            'ends_on' => '2026-06-30',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $globalSemesterId = DB::table('semesters')->insertGetId([
            'academic_year_id' => $academicYearId,
            'code' => 'S1',
            'name_ar' => 'الفصل الأول',
            'name_en' => 'First Semester',
            'sequence' => 1,
            'starts_on' => '2025-09-01',
            'ends_on' => '2026-01-31',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $semesterId = DB::table('institution_semesters')->insertGetId([
            'institution_id' => $instId,
            'semester_id' => $globalSemesterId,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $academicLevelId = DB::table('academic_levels')->insertGetId([
            'code' => 'GRADE5',
            'name_ar' => 'الصف الخامس',
            'name_en' => 'Grade 5',
            'sequence' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $classGroupId = DB::table('class_groups')->insertGetId([
            'code' => '5A',
            'name_ar' => '5-أ',
            'institution_semester_id' => $semesterId,
            'operational_period_id' => 1,
            'academic_level_id' => $academicLevelId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $enrollmentId = DB::table('student_enrollments')->insertGetId([
            'student_profile_id' => $studentProfileId,
            'class_group_id' => $classGroupId,
            'institution_semester_id' => $semesterId,
            'enrollment_status' => 'inactive', // NOT active
            'enrolled_on' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('document_type_catalogue')->insert([
            'code' => 'test_active_check',
            'label_ar' => 'اختبار التسجيل النشط',
            'label_en' => 'Active Enrollment Check',
            'completeness_checks' => '["active_enrollment"]',
            'required_context_keys' => '[]',
            'allowed_requesters' => '["guardian"]',
            'public_verification' => true,
            'reissuable' => false,
            'validity_days' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $checker = app(DocumentCompletionChecker::class);
        $failures = $checker->check('test_active_check', $enrollmentId);

        expect($failures)->not->toBeEmpty()
            ->and($failures[0])->toContain('نشطاً');
    });

    test('unknown rule name fails closed instead of silently passing', function (): void {
        // Seed a catalogue type with a rule name that is not implemented in the checker
        DB::table('document_type_catalogue')->insert([
            'code' => 'test_unknown_rule',
            'label_ar' => 'اختبار قاعدة مجهولة',
            'label_en' => 'Unknown Rule Check',
            'completeness_checks' => '["nonexistent_rule_xyz"]',
            'required_context_keys' => '[]',
            'allowed_requesters' => '["staff"]',
            'public_verification' => false,
            'reissuable' => false,
            'validity_days' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Seed the minimum FK chain needed for the checker to find the enrollment.
        // class_groups.institution_semester_id and operational_period_id are
        // plain (cross-module) ints with no FK, so they can hold any value.
        $orgId = DB::table('organizations')->insertGetId([
            'code' => 'UR-ORG', 'name_en' => 'UR Org', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $instTypeId = DB::table('institution_types')->insertGetId([
            'code' => 'school', 'name_ar' => 'مدرسة', 'name_en' => 'School',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $instId = DB::table('institutions')->insertGetId([
            'organization_id' => $orgId, 'institution_type_id' => $instTypeId,
            'code' => 'UR-001', 'name_ar' => 'مدرسة', 'name_en' => 'School',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $ayId = DB::table('academic_years')->insertGetId([
            'organization_id' => $orgId, 'code' => 'AY26', 'name_en' => '2025-2026',
            'starts_on' => '2025-09-01', 'ends_on' => '2026-06-30',
            'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $semGlobalId = DB::table('semesters')->insertGetId([
            'academic_year_id' => $ayId, 'code' => 'S1', 'name_en' => 'Sem 1',
            'sequence' => 1, 'starts_on' => '2025-09-01', 'ends_on' => '2026-01-31',
            'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $isId = DB::table('institution_semesters')->insertGetId([
            'institution_id' => $instId, 'semester_id' => $semGlobalId,
            'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $personId = DB::table('people')->insertGetId([
            'full_name_ar' => 'طالب الاختبار',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $spId = DB::table('student_profiles')->insertGetId([
            'person_id' => $personId, 'student_code' => 'UR-STU-01',
            'registered_on' => now()->toDateString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $alId = DB::table('academic_levels')->insertGetId([
            'code' => 'G5', 'name_ar' => 'خامس', 'name_en' => 'Grade 5',
            'sequence' => 5, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $cgId = DB::table('class_groups')->insertGetId([
            'code' => 'G5A', 'name_ar' => '5أ',
            'institution_semester_id' => $isId, 'operational_period_id' => 1,
            'academic_level_id' => $alId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $enrollmentId = DB::table('student_enrollments')->insertGetId([
            'student_profile_id' => $spId,
            'class_group_id' => $cgId,
            'institution_semester_id' => $isId,
            'enrollment_status' => 'active',
            'enrolled_on' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $checker = app(DocumentCompletionChecker::class);
        $failures = $checker->check('test_unknown_rule', $enrollmentId);

        // An unknown rule must FAIL, not silently pass
        expect($failures)->not->toBeEmpty()
            ->and($failures[0])->toContain('nonexistent_rule_xyz');
    });
});

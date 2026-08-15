<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\AcademicManagement\Actions\CalculateResults;
use Modules\AcademicManagement\Actions\CorrectMark;
use Modules\AcademicManagement\Actions\PublishResults;
use Modules\AcademicManagement\Actions\RevokeResultPublication;
use Modules\AcademicManagement\Exceptions\MarksException;
use Modules\AcademicManagement\Models\MarkSheet;
use Modules\AcademicManagement\Models\ResultPublication;
use Modules\AcademicManagement\Models\ResultPublicationRow;

uses(RefreshDatabase::class);

// ─────────────────────────────────────────────────────────────────────────────
// Test helpers
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Build a minimal full context using DB::table() inserts following the proven
 * pattern from MarksWorkflowTest. Returns all IDs needed for result tests.
 *
 * @return array<string, int>
 */
function resultCtx(string $tag = ''): array
{
    $tag = $tag ?: uniqid();

    $orgId = (int) DB::table('organizations')->insertGetId([
        'code' => 'ORG-'.$tag, 'name_en' => 'Org', 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $typeId = (int) DB::table('institution_types')->insertGetId([
        'code' => 'TYPE-'.$tag, 'name_en' => 'School', 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $instId = (int) DB::table('institutions')->insertGetId([
        'organization_id' => $orgId, 'institution_type_id' => $typeId,
        'code' => 'INST-'.$tag, 'name_en' => 'School', 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $yearId = (int) DB::table('academic_years')->insertGetId([
        'organization_id' => $orgId, 'code' => 'AY-'.$tag,
        'name_en' => 'Year', 'starts_on' => '2025-09-01', 'ends_on' => '2026-06-30',
        'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $semId = (int) DB::table('semesters')->insertGetId([
        'code' => 'SEM-'.$tag, 'name_en' => 'First', 'name_ar' => 'الأول',
        'sequence' => 1, 'status' => 'open', 'academic_year_id' => $yearId,
        'starts_on' => '2025-09-01', 'ends_on' => '2026-01-31',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $instSemId = (int) DB::table('institution_semesters')->insertGetId([
        'institution_id' => $instId, 'semester_id' => $semId, 'status' => 'open',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $levelId = (int) DB::table('academic_levels')->insertGetId([
        'code' => 'LVL-'.$tag, 'name_ar' => 'صف', 'name_en' => 'Grade',
        'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $classGroupId = (int) DB::table('class_groups')->insertGetId([
        'institution_semester_id' => $instSemId, 'operational_period_id' => 0,
        'academic_level_id' => $levelId, 'code' => 'CG-'.$tag,
        'name_ar' => 'الصف', 'lifecycle_status' => 'active',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $subjectId = (int) DB::table('subjects')->insertGetId([
        'code' => 'SUBJ-'.$tag, 'name_ar' => 'رياضيات', 'name_en' => 'Maths',
        'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $subjectOffId = (int) DB::table('institution_subject_offerings')->insertGetId([
        'institution_semester_id' => $instSemId, 'subject_id' => $subjectId,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    // Grading scale with grade tiers (code set directly, not through fillable)
    $scaleId = (int) DB::table('grading_scales')->insertGetId([
        'institution_id' => $instId, 'code' => 'SCALE-'.$tag,
        'name_ar' => 'سلم', 'name_en' => 'Scale', 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    DB::table('grading_scale_grades')->insert([
        ['grading_scale_id' => $scaleId, 'code' => 'A', 'name_ar' => 'ممتاز', 'min_score' => 90, 'max_score' => 100, 'is_passing' => true,  'sequence' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['grading_scale_id' => $scaleId, 'code' => 'B', 'name_ar' => 'جيد جداً', 'min_score' => 75, 'max_score' => 89.99, 'is_passing' => true, 'sequence' => 2, 'created_at' => now(), 'updated_at' => now()],
        ['grading_scale_id' => $scaleId, 'code' => 'F', 'name_ar' => 'راسب', 'min_score' => 0,  'max_score' => 74.99, 'is_passing' => false, 'sequence' => 3, 'created_at' => now(), 'updated_at' => now()],
    ]);

    // Student enrollment (requires person → student_profile chain)
    $personId = (int) DB::table('people')->insertGetId([
        'full_name_ar' => 'طالب '.$tag, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $studentProfileId = (int) DB::table('student_profiles')->insertGetId([
        'person_id' => $personId, 'student_code' => 'STU-'.$tag,
        'lifecycle_status' => 'active', 'registered_on' => today()->toDateString(),
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $enrollmentId = (int) DB::table('student_enrollments')->insertGetId([
        'student_profile_id'      => $studentProfileId,
        'institution_semester_id' => $instSemId,
        'class_group_id'          => $classGroupId,
        'enrollment_status'       => 'active',
        'enrolled_on'             => today()->subDay()->toDateString(),
        'created_at'              => now(),
        'updated_at'              => now(),
    ]);

    // Assessment definition
    $defId = (int) DB::table('assessment_definitions')->insertGetId([
        'institution_semester_id' => $instSemId,
        'class_group_id'          => $classGroupId,
        'subject_offering_id'     => $subjectOffId,
        'name_ar'                 => 'امتحان نهائي',
        'name_en'                 => 'Final Exam',
        'assessment_type'         => 'written_exam',
        'max_score'               => 100.0,
        'weight'                  => 100.0,
        'status'                  => 'active',
        'created_at'              => now(),
        'updated_at'              => now(),
    ]);

    // Teaching assignment (staff_profile_id / staff_position_id are cross-module plain ints)
    $assignmentId = (int) DB::table('teaching_assignments')->insertGetId([
        'staff_profile_id'        => 1,
        'institution_semester_id' => $instSemId,
        'staff_position_id'       => 1,
        'class_group_id'          => $classGroupId,
        'subject_offering_id'     => $subjectOffId,
        'starts_on'               => today()->subDay()->toDateString(),
        'status'                  => 'active',
        'created_at'              => now(),
        'updated_at'              => now(),
    ]);

    // Approved mark sheet
    $sheetId = (int) DB::table('mark_sheets')->insertGetId([
        'institution_semester_id'    => $instSemId,
        'class_group_id'             => $classGroupId,
        'subject_offering_id'        => $subjectOffId,
        'teaching_assignment_id'     => $assignmentId,
        'grading_scale_id'           => $scaleId,
        'status'                     => 'approved',
        'approved_at'                => now(),
        'approved_by_staff_profile_id' => 1,
        'created_at'                 => now(),
        'updated_at'                 => now(),
    ]);

    return compact(
        'instSemId', 'classGroupId', 'enrollmentId', 'sheetId',
        'defId', 'scaleId', 'studentProfileId', 'subjectOffId',
    );
}

/**
 * Insert a student mark for the given context and return its ID.
 */
function insertMark(array $ctx, float $score = null, string $exception = null): int
{
    return (int) DB::table('student_marks')->insertGetId([
        'mark_sheet_id'              => $ctx['sheetId'],
        'enrollment_id'              => $ctx['enrollmentId'],
        'assessment_definition_id'   => $ctx['defId'],
        'score'                      => $score,
        'exception_status'           => $exception,
        'created_at'                 => now(),
        'updated_at'                 => now(),
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// CalculateResults — pure computation
// ─────────────────────────────────────────────────────────────────────────────

test('CalculateResults returns empty collection when no approved sheets exist', function (): void {
    $result = app(CalculateResults::class)(9999, 9999);
    expect($result)->toBeEmpty();
});

test('CalculateResults computes normalized score and grade for student with a score', function (): void {
    $ctx = resultCtx();
    insertMark($ctx, score: 80);

    $rows = app(CalculateResults::class)($ctx['instSemId'], $ctx['classGroupId']);

    expect($rows)->toHaveCount(1);

    $row = $rows->first();
    expect($row->normalized_score)->toBe(80.0)
        ->and($row->grade_code)->toBe('B')
        ->and($row->is_passing)->toBeTrue()
        ->and($row->completeness_status)->toBe('complete');
});

test('CalculateResults returns A grade for score >= 90', function (): void {
    $ctx = resultCtx();
    insertMark($ctx, score: 95);

    $rows = app(CalculateResults::class)($ctx['instSemId'], $ctx['classGroupId']);

    expect($rows->first()->grade_code)->toBe('A')
        ->and($rows->first()->is_passing)->toBeTrue();
});

test('CalculateResults marks student as all_absent when exception_status is set', function (): void {
    $ctx = resultCtx();
    insertMark($ctx, exception: 'absent');

    $rows = app(CalculateResults::class)($ctx['instSemId'], $ctx['classGroupId']);

    $row = $rows->first();
    expect($row->completeness_status)->toBe('all_absent')
        ->and($row->normalized_score)->toBeNull()
        ->and($row->is_passing)->toBeNull();
});

test('CalculateResults marks incomplete when mark has neither score nor exception', function (): void {
    $ctx = resultCtx();

    // Insert a mark with no score and no exception
    DB::table('student_marks')->insert([
        'mark_sheet_id'            => $ctx['sheetId'],
        'enrollment_id'            => $ctx['enrollmentId'],
        'assessment_definition_id' => $ctx['defId'],
        'score'                    => null,
        'exception_status'         => null,
        'created_at'               => now(),
        'updated_at'               => now(),
    ]);

    $rows = app(CalculateResults::class)($ctx['instSemId'], $ctx['classGroupId']);

    expect($rows->first()->completeness_status)->toBe('incomplete');
});

test('CalculateResults returns no_assessments when no assessment definitions exist', function (): void {
    $tag = uniqid();

    $orgId = (int) DB::table('organizations')->insertGetId([
        'code' => 'ORG2-'.$tag, 'name_en' => 'Org2', 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $typeId = (int) DB::table('institution_types')->insertGetId([
        'code' => 'TYPE2-'.$tag, 'name_en' => 'School', 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $instId = (int) DB::table('institutions')->insertGetId([
        'organization_id' => $orgId, 'institution_type_id' => $typeId,
        'code' => 'INST2-'.$tag, 'name_en' => 'School', 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $yearId = (int) DB::table('academic_years')->insertGetId([
        'organization_id' => $orgId, 'code' => 'AY2-'.$tag,
        'name_en' => 'Year', 'starts_on' => '2025-09-01', 'ends_on' => '2026-06-30',
        'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $semId = (int) DB::table('semesters')->insertGetId([
        'code' => 'SEM2-'.$tag, 'name_en' => 'First', 'name_ar' => 'الأول',
        'sequence' => 1, 'status' => 'open', 'academic_year_id' => $yearId,
        'starts_on' => '2025-09-01', 'ends_on' => '2026-01-31',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $instSemId = (int) DB::table('institution_semesters')->insertGetId([
        'institution_id' => $instId, 'semester_id' => $semId, 'status' => 'open',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $levelId = (int) DB::table('academic_levels')->insertGetId([
        'code' => 'LVL2-'.$tag, 'name_ar' => 'صف', 'name_en' => 'Grade',
        'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $classGroupId = (int) DB::table('class_groups')->insertGetId([
        'institution_semester_id' => $instSemId, 'operational_period_id' => 0,
        'academic_level_id' => $levelId, 'code' => 'CG2-'.$tag,
        'name_ar' => 'الصف', 'lifecycle_status' => 'active',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $personId = (int) DB::table('people')->insertGetId([
        'full_name_ar' => 'طالب2', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $spId = (int) DB::table('student_profiles')->insertGetId([
        'person_id' => $personId, 'student_code' => 'STU2-'.$tag,
        'lifecycle_status' => 'active', 'registered_on' => today()->toDateString(),
        'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('student_enrollments')->insert([
        'student_profile_id' => $spId, 'institution_semester_id' => $instSemId,
        'class_group_id' => $classGroupId, 'enrollment_status' => 'active',
        'enrolled_on' => today()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
    ]);
    // Need a real subject_offering for the mark_sheet FK
    $subjectId2 = (int) DB::table('subjects')->insertGetId([
        'code' => 'SUBJ3-'.$tag, 'name_ar' => 'تاريخ', 'name_en' => 'History',
        'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $offId2 = (int) DB::table('institution_subject_offerings')->insertGetId([
        'institution_semester_id' => $instSemId, 'subject_id' => $subjectId2,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $assignId2 = (int) DB::table('teaching_assignments')->insertGetId([
        'staff_profile_id' => 1, 'institution_semester_id' => $instSemId,
        'staff_position_id' => 1, 'class_group_id' => $classGroupId,
        'subject_offering_id' => $offId2, 'starts_on' => today()->subDay()->toDateString(),
        'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
    ]);
    // Approved sheet with no assessment definitions
    DB::table('mark_sheets')->insert([
        'institution_semester_id' => $instSemId, 'class_group_id' => $classGroupId,
        'subject_offering_id' => $offId2, 'teaching_assignment_id' => $assignId2,
        'grading_scale_id' => null, 'status' => 'approved',
        'approved_at' => now(), 'approved_by_staff_profile_id' => 1,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $rows = app(CalculateResults::class)($instSemId, $classGroupId);

    expect($rows)->not->toBeEmpty()
        ->and($rows->first()->completeness_status)->toBe('no_assessments');
});

// ─────────────────────────────────────────────────────────────────────────────
// PublishResults
// ─────────────────────────────────────────────────────────────────────────────

test('PublishResults throws when no approved sheets exist', function (): void {
    expect(fn () => app(PublishResults::class)(9999, 9999, 1))
        ->toThrow(MarksException::class);
});

test('PublishResults throws when outstanding unapproved sheets remain', function (): void {
    $ctx = resultCtx();
    insertMark($ctx, score: 80);

    // Add a draft sheet alongside the approved one
    // Need a real subject_offering and teaching_assignment for the FK
    $extraSubjId = (int) DB::table('subjects')->insertGetId([
        'code' => 'EXTRA-'.uniqid(), 'name_ar' => 'فيزياء', 'name_en' => 'Physics',
        'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $extraOffId = (int) DB::table('institution_subject_offerings')->insertGetId([
        'institution_semester_id' => $ctx['instSemId'], 'subject_id' => $extraSubjId,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $extraAssignId = (int) DB::table('teaching_assignments')->insertGetId([
        'staff_profile_id' => 1, 'institution_semester_id' => $ctx['instSemId'],
        'staff_position_id' => 1, 'class_group_id' => $ctx['classGroupId'],
        'subject_offering_id' => $extraOffId, 'starts_on' => today()->subDay()->toDateString(),
        'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('mark_sheets')->insert([
        'institution_semester_id' => $ctx['instSemId'],
        'class_group_id'          => $ctx['classGroupId'],
        'subject_offering_id'     => $extraOffId,
        'teaching_assignment_id'  => $extraAssignId,
        'grading_scale_id'        => null,
        'status'                  => 'draft',
        'created_at'              => now(),
        'updated_at'              => now(),
    ]);

    expect(fn () => app(PublishResults::class)(
        $ctx['instSemId'], $ctx['classGroupId'], 1, requireAllApproved: true
    ))->toThrow(MarksException::class);
});

test('PublishResults creates publication header and immutable rows', function (): void {
    $ctx = resultCtx();
    insertMark($ctx, score: 95);

    $pub = app(PublishResults::class)($ctx['instSemId'], $ctx['classGroupId'], 1);

    expect($pub)->toBeInstanceOf(ResultPublication::class)
        ->and($pub->status)->toBe('published')
        ->and($pub->version)->toBe(1);

    $rows = ResultPublicationRow::where('result_publication_id', $pub->id)->get();
    expect($rows)->toHaveCount(1);
    expect($rows->first()->grade_code)->toBe('A');
});

test('CalculateResults uses corrected mark when CorrectMark has been applied before republish', function (): void {
    // This test uses the full CorrectMark action to verify that republishing
    // after a correction reflects the corrected score, not the original.
    // The sheet must be in 'approved' status for CorrectMark to accept it.
    $ctx = resultCtx();

    $originalMarkId = insertMark($ctx, score: 60);

    // First publish: score = 60
    $pub1 = app(PublishResults::class)($ctx['instSemId'], $ctx['classGroupId'], 1);

    $score1 = (float) DB::table('result_publication_rows')
        ->where('result_publication_id', $pub1->id)
        ->value('normalized_score');

    expect($score1)->toBe(60.0);

    // Apply correction via the supported CorrectMark action
    // CorrectMark takes the MarkSheet model as first argument
    $sheet = MarkSheet::findOrFail($ctx['sheetId']);

    app(CorrectMark::class)(
        sheet: $sheet,
        originalMarkId: $originalMarkId,
        newScore: 85.0,
        newExceptionStatus: null,
        reason: 'Grading error corrected by principal',
        actorStaffProfileId: 2,
    );

    // Verify the correction chain: original still has correction_of_id = null,
    // the new mark has correction_of_id = original_id
    $correctionMark = DB::table('student_marks')
        ->where('correction_of_id', $originalMarkId)
        ->first();

    expect($correctionMark)->not->toBeNull()
        ->and((float) $correctionMark->score)->toBe(85.0);

    // Republish: CalculateResults must pick up the corrected mark (score = 85),
    // not the original (score = 60)
    $pub2 = app(PublishResults::class)($ctx['instSemId'], $ctx['classGroupId'], 1);

    $score2 = (float) DB::table('result_publication_rows')
        ->where('result_publication_id', $pub2->id)
        ->value('normalized_score');

    expect($score2)->toBe(85.0);

    // Original publication snapshot remains unchanged (immutability)
    $score1Again = (float) DB::table('result_publication_rows')
        ->where('result_publication_id', $pub1->id)
        ->value('normalized_score');

    expect($score1Again)->toBe(60.0);
});

test('PublishResults supersedes the previous published version', function (): void {
    $ctx = resultCtx();
    insertMark($ctx, score: 80);

    $pub1 = app(PublishResults::class)($ctx['instSemId'], $ctx['classGroupId'], 1);
    $pub2 = app(PublishResults::class)($ctx['instSemId'], $ctx['classGroupId'], 1);

    expect($pub2->version)->toBe(2);

    $pub1->refresh();
    expect($pub1->superseded_by_id)->toBe($pub2->id);
    // Old rows remain (immutable audit trail)
    expect(ResultPublicationRow::where('result_publication_id', $pub1->id)->count())->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// RevokeResultPublication
// ─────────────────────────────────────────────────────────────────────────────

test('RevokeResultPublication transitions status to revoked with reason and timestamp', function (): void {
    $ctx = resultCtx();
    insertMark($ctx, score: 50);

    $pub     = app(PublishResults::class)($ctx['instSemId'], $ctx['classGroupId'], 1);
    $revoked = app(RevokeResultPublication::class)($pub, 'Data error in calculation', 2);

    expect($revoked->status)->toBe('revoked')
        ->and($revoked->revoke_reason)->toBe('Data error in calculation')
        ->and($revoked->revoked_at)->not->toBeNull()
        ->and($revoked->revoked_by_staff_profile_id)->toBe(2);
});

test('RevokeResultPublication throws when already revoked', function (): void {
    $ctx = resultCtx();
    insertMark($ctx, score: 50);

    $pub = app(PublishResults::class)($ctx['instSemId'], $ctx['classGroupId'], 1);
    app(RevokeResultPublication::class)($pub, 'First revoke', 2);

    expect(fn () => app(RevokeResultPublication::class)($pub->fresh(), 'Second revoke', 2))
        ->toThrow(MarksException::class);
});

test('RevokeResultPublication throws without a non-empty reason', function (): void {
    $ctx = resultCtx();
    insertMark($ctx, score: 60);

    $pub = app(PublishResults::class)($ctx['instSemId'], $ctx['classGroupId'], 1);

    expect(fn () => app(RevokeResultPublication::class)($pub, '', 2))
        ->toThrow(MarksException::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Snapshot immutability
// ─────────────────────────────────────────────────────────────────────────────

test('ResultPublicationRows remain after revocation — immutable audit trail', function (): void {
    $ctx = resultCtx();
    insertMark($ctx, score: 88);

    $pub = app(PublishResults::class)($ctx['instSemId'], $ctx['classGroupId'], 1);
    app(RevokeResultPublication::class)($pub, 'Audit trail test', 2);

    // Rows must survive revocation untouched
    $rows = ResultPublicationRow::where('result_publication_id', $pub->id)->get();
    expect($rows)->toHaveCount(1);
    expect($rows->first()->normalized_score)->toBe(88.0);
});

test('Correcting a mark after publication leaves existing publication rows unchanged', function (): void {
    $ctx = resultCtx();
    insertMark($ctx, score: 90);

    $pub1 = app(PublishResults::class)($ctx['instSemId'], $ctx['classGroupId'], 1);

    $originalScore = DB::table('result_publication_rows')
        ->where('result_publication_id', $pub1->id)
        ->value('normalized_score');

    // Simulate a correction: update the student mark
    DB::table('student_marks')
        ->where('enrollment_id', $ctx['enrollmentId'])
        ->update(['score' => 50]);

    // Original publication rows must still show the old score
    $scoreAfterCorrection = DB::table('result_publication_rows')
        ->where('result_publication_id', $pub1->id)
        ->value('normalized_score');

    expect((float) $scoreAfterCorrection)->toBe((float) $originalScore);

    // A new publication captures the corrected score
    $pub2 = app(PublishResults::class)($ctx['instSemId'], $ctx['classGroupId'], 1);

    $correctedScore = DB::table('result_publication_rows')
        ->where('result_publication_id', $pub2->id)
        ->value('normalized_score');

    expect((float) $correctedScore)->toBe(50.0);
});

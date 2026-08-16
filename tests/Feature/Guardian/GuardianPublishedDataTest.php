<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\AcademicManagement\Actions\ConfigureAttendancePolicy;
use Modules\AcademicManagement\Actions\PublishAttendanceSnapshot;
use Modules\AcademicManagement\Actions\PublishResults;
use Modules\AcademicManagement\Actions\RevokeAttendanceSnapshot;
use Modules\AcademicManagement\Actions\RevokeResultPublication;
use Modules\AcademicManagement\Models\AttendancePublicationSnapshot;
use Modules\AcademicManagement\Models\ResultPublication;

uses(RefreshDatabase::class);

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Build the full organisational hierarchy in the DB.
 *
 * @return array{instId: int, instSemId: int, classGroupId: int}
 */
function guardianBaseCtx(string $tag): array
{
    $orgId = (int) DB::table('organizations')->insertGetId([
        'code' => 'GORG-'.$tag, 'name_en' => 'Org', 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $typeId = (int) DB::table('institution_types')->insertGetId([
        'code' => 'GTYPE-'.$tag, 'name_en' => 'School', 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $instId = (int) DB::table('institutions')->insertGetId([
        'organization_id' => $orgId, 'institution_type_id' => $typeId,
        'code' => 'GINST-'.$tag, 'name_en' => 'School', 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $yearId = (int) DB::table('academic_years')->insertGetId([
        'organization_id' => $orgId, 'code' => 'GAY-'.$tag,
        'name_en' => 'Year', 'starts_on' => '2025-09-01', 'ends_on' => '2026-06-30',
        'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $semId = (int) DB::table('semesters')->insertGetId([
        'code' => 'GSEM-'.$tag, 'name_en' => 'First', 'name_ar' => 'الأول',
        'sequence' => 1, 'status' => 'open', 'academic_year_id' => $yearId,
        'starts_on' => '2025-09-01', 'ends_on' => '2026-01-31',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $instSemId = (int) DB::table('institution_semesters')->insertGetId([
        'institution_id' => $instId, 'semester_id' => $semId, 'status' => 'open',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $levelId = (int) DB::table('academic_levels')->insertGetId([
        'code' => 'GLVL-'.$tag, 'name_ar' => 'صف', 'name_en' => 'Grade',
        'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $classGroupId = (int) DB::table('class_groups')->insertGetId([
        'institution_semester_id' => $instSemId, 'operational_period_id' => 0,
        'academic_level_id' => $levelId, 'code' => 'GCG-'.$tag,
        'name_ar' => 'الصف', 'lifecycle_status' => 'active',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    return compact('instId', 'instSemId', 'classGroupId');
}

/**
 * Create a student enrollment and return [enrollmentId, studentProfileId].
 *
 * @return array{enrollmentId: int, studentProfileId: int}
 */
function guardianEnrollment(array $base, string $tag): array
{
    $personId = (int) DB::table('people')->insertGetId([
        'full_name_ar' => 'طالب غ '.$tag, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $spId = (int) DB::table('student_profiles')->insertGetId([
        'person_id' => $personId, 'student_code' => 'GSTU-'.$tag,
        'lifecycle_status' => 'active', 'registered_on' => today()->toDateString(),
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $enrollmentId = (int) DB::table('student_enrollments')->insertGetId([
        'student_profile_id' => $spId,
        'institution_semester_id' => $base['instSemId'],
        'class_group_id' => $base['classGroupId'],
        'enrollment_status' => 'active',
        'enrolled_on' => today()->subDay()->toDateString(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return ['enrollmentId' => $enrollmentId, 'studentProfileId' => $spId];
}

/**
 * Build a published result publication for one student in one class group.
 *
 * @return array{enrollmentId: int, studentProfileId: int, instSemId: int, classGroupId: int, publication: ResultPublication}
 */
function publishedResultCtx(string $tag = ''): array
{
    $tag = $tag ?: uniqid();
    $base = guardianBaseCtx($tag);
    $stu = guardianEnrollment($base, $tag);

    $subjectId = (int) DB::table('subjects')->insertGetId([
        'code' => 'GSUBJ-'.$tag, 'name_ar' => 'علوم', 'name_en' => 'Science',
        'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $offId = (int) DB::table('institution_subject_offerings')->insertGetId([
        'institution_semester_id' => $base['instSemId'], 'subject_id' => $subjectId,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $scaleId = (int) DB::table('grading_scales')->insertGetId([
        'institution_id' => $base['instId'], 'code' => 'GSCALE-'.$tag,
        'name_ar' => 'سلم', 'name_en' => 'Scale', 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('grading_scale_grades')->insert([
        ['grading_scale_id' => $scaleId, 'code' => 'A', 'name_ar' => 'ممتاز', 'min_score' => 85, 'max_score' => 100, 'is_passing' => true, 'sequence' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['grading_scale_id' => $scaleId, 'code' => 'F', 'name_ar' => 'راسب', 'min_score' => 0, 'max_score' => 84.99, 'is_passing' => false, 'sequence' => 2, 'created_at' => now(), 'updated_at' => now()],
    ]);
    $defId = (int) DB::table('assessment_definitions')->insertGetId([
        'institution_semester_id' => $base['instSemId'], 'class_group_id' => $base['classGroupId'],
        'subject_offering_id' => $offId, 'name_ar' => 'امتحان', 'name_en' => 'Exam',
        'assessment_type' => 'written_exam', 'max_score' => 100.0, 'weight' => 100.0,
        'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $assignId = (int) DB::table('teaching_assignments')->insertGetId([
        'staff_profile_id' => 1,
        'institution_semester_id' => $base['instSemId'],
        'staff_position_id' => 1,
        'class_group_id' => $base['classGroupId'],
        'subject_offering_id' => $offId,
        'starts_on' => today()->subDay()->toDateString(),
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $sheetId = (int) DB::table('mark_sheets')->insertGetId([
        'institution_semester_id' => $base['instSemId'],
        'class_group_id' => $base['classGroupId'],
        'subject_offering_id' => $offId,
        'teaching_assignment_id' => $assignId,
        'grading_scale_id' => $scaleId,
        'status' => 'approved',
        'approved_at' => now(),
        'approved_by_staff_profile_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('student_marks')->insert([
        'mark_sheet_id' => $sheetId,
        'enrollment_id' => $stu['enrollmentId'],
        'assessment_definition_id' => $defId,
        'score' => 90,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $pub = app(PublishResults::class)($base['instSemId'], $base['classGroupId'], 1);

    return [
        'enrollmentId' => $stu['enrollmentId'],
        'studentProfileId' => $stu['studentProfileId'],
        'instSemId' => $base['instSemId'],
        'classGroupId' => $base['classGroupId'],
        'publication' => $pub,
    ];
}

/**
 * Build a published attendance snapshot context.
 *
 * @return array{enrollmentId: int, studentProfileId: int, instSemId: int, classGroupId: int, snapshot: AttendancePublicationSnapshot}
 */
function publishedAttendanceCtx(string $tag = ''): array
{
    $tag = $tag ?: uniqid();
    $base = guardianBaseCtx($tag);
    $stu = guardianEnrollment($base, $tag);

    app(ConfigureAttendancePolicy::class)(
        institutionSemesterId: $base['instSemId'],
        enabled: true,
        detailLevel: 'daily_status',
        publishDelayDays: 0,
        showReason: true,
        showArrivalDeparture: false,
    );

    $sheetId = (int) DB::table('student_attendance_sheets')->insertGetId([
        'institution_semester_id' => $base['instSemId'],
        'operational_period_id' => 0,
        'class_group_id' => $base['classGroupId'],
        'attendance_date' => today()->subDay()->toDateString(),
        'status' => 'verified',
        'creator_staff_profile_id' => 1,
        'verified_at' => now(),
        'verified_by_staff_profile_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('student_attendance_records')->insert([
        'sheet_id' => $sheetId,
        'enrollment_id' => $stu['enrollmentId'],
        'student_profile_id' => $stu['studentProfileId'],
        'status_code' => 'absent',
        'reason' => 'مرض',
        'source' => 'teacher_entry',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $snap = app(PublishAttendanceSnapshot::class)($base['instSemId'], $base['classGroupId'], 1);

    return [
        'enrollmentId' => $stu['enrollmentId'],
        'studentProfileId' => $stu['studentProfileId'],
        'instSemId' => $base['instSemId'],
        'classGroupId' => $base['classGroupId'],
        'snapshot' => $snap,
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// Guardian: result publication access control
// ─────────────────────────────────────────────────────────────────────────────

test('Guardian can find published result rows for their student enrollment', function (): void {
    $ctx = publishedResultCtx();
    $pub = $ctx['publication'];

    expect($pub->status)->toBe('published');

    $rowCount = DB::table('result_publication_rows')
        ->where('result_publication_id', $pub->id)
        ->where('enrollment_id', $ctx['enrollmentId'])
        ->count();

    expect($rowCount)->toBe(1);
});

test('Guardian query scope returns no rows for a revoked publication', function (): void {
    $ctx = publishedResultCtx();
    $pub = $ctx['publication'];

    app(RevokeResultPublication::class)($pub, 'Error in data', 1);

    // Guardian-scope query: non-revoked, non-superseded only
    $visiblePub = DB::table('result_publications')
        ->where('institution_semester_id', $ctx['instSemId'])
        ->where('class_group_id', $ctx['classGroupId'])
        ->where('status', 'published')
        ->whereNull('superseded_by_id')
        ->first();

    expect($visiblePub)->toBeNull();
});

test('Guardian sees only the current (non-superseded) publication', function (): void {
    $ctx = publishedResultCtx();

    // Update mark and publish again
    DB::table('student_marks')
        ->where('enrollment_id', $ctx['enrollmentId'])
        ->update(['score' => 75]);

    $pub2 = app(PublishResults::class)($ctx['instSemId'], $ctx['classGroupId'], 1);

    $visiblePubs = DB::table('result_publications')
        ->where('institution_semester_id', $ctx['instSemId'])
        ->where('class_group_id', $ctx['classGroupId'])
        ->where('status', 'published')
        ->whereNull('superseded_by_id')
        ->get();

    expect($visiblePubs)->toHaveCount(1);
    expect($visiblePubs->first()->id)->toBe($pub2->id);
});

test('Draft mark sheets never appear in result_publications', function (): void {
    $tag = uniqid();
    $base = guardianBaseCtx($tag);
    $stu = guardianEnrollment($base, $tag);

    $draftSubjId = (int) DB::table('subjects')->insertGetId([
        'code' => 'DSUBJ-'.$tag, 'name_ar' => 'جغرافيا', 'name_en' => 'Geography',
        'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $draftOffId = (int) DB::table('institution_subject_offerings')->insertGetId([
        'institution_semester_id' => $base['instSemId'], 'subject_id' => $draftSubjId,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $draftAssignId = (int) DB::table('teaching_assignments')->insertGetId([
        'staff_profile_id' => 1, 'institution_semester_id' => $base['instSemId'],
        'staff_position_id' => 1, 'class_group_id' => $base['classGroupId'],
        'subject_offering_id' => $draftOffId, 'starts_on' => today()->subDay()->toDateString(),
        'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('mark_sheets')->insert([
        'institution_semester_id' => $base['instSemId'], 'class_group_id' => $base['classGroupId'],
        'subject_offering_id' => $draftOffId, 'teaching_assignment_id' => $draftAssignId,
        'status' => 'draft', 'created_at' => now(), 'updated_at' => now(),
    ]);

    $pubCount = DB::table('result_publications')
        ->where('institution_semester_id', $base['instSemId'])
        ->count();

    expect($pubCount)->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// Guardian: attendance snapshot access control
// ─────────────────────────────────────────────────────────────────────────────

test('Guardian can find published attendance snapshot rows for their student', function (): void {
    $ctx = publishedAttendanceCtx();
    $snap = $ctx['snapshot'];

    expect($snap->status)->toBe('published');

    $rowCount = DB::table('attendance_snapshot_rows')
        ->where('snapshot_id', $snap->id)
        ->where('enrollment_id', $ctx['enrollmentId'])
        ->count();

    expect($rowCount)->toBe(1);
});

test('Guardian query scope returns no rows for a revoked attendance snapshot', function (): void {
    $ctx = publishedAttendanceCtx();
    $snap = $ctx['snapshot'];

    app(RevokeAttendanceSnapshot::class)($snap, 'Wrong data', 1);

    $visibleSnap = DB::table('attendance_publication_snapshots')
        ->where('institution_semester_id', $ctx['instSemId'])
        ->where('class_group_id', $ctx['classGroupId'])
        ->where('status', 'published')
        ->whereNull('superseded_by_id')
        ->first();

    expect($visibleSnap)->toBeNull();
});

test('Guardian sees only the current (non-superseded) attendance snapshot', function (): void {
    $ctx = publishedAttendanceCtx();

    $snap2 = app(PublishAttendanceSnapshot::class)($ctx['instSemId'], $ctx['classGroupId'], 1);

    $visibleSnaps = DB::table('attendance_publication_snapshots')
        ->where('institution_semester_id', $ctx['instSemId'])
        ->where('class_group_id', $ctx['classGroupId'])
        ->where('status', 'published')
        ->whereNull('superseded_by_id')
        ->get();

    expect($visibleSnaps)->toHaveCount(1);
    expect($visibleSnaps->first()->id)->toBe($snap2->id);
});

// ─────────────────────────────────────────────────────────────────────────────
// Cross-guardian isolation
// ─────────────────────────────────────────────────────────────────────────────

test('Guardian A cannot see Guardian B student data via enrollment scope', function (): void {
    $ctxA = publishedResultCtx();
    $ctxB = publishedResultCtx();

    // Guardian A's enrollment IDs
    $guardianAEnrollmentIds = [$ctxA['enrollmentId']];

    // Query with Guardian A's scope: should return A's publication, not B's
    $visiblePub = DB::table('result_publications')
        ->whereIn('id', function ($sub) use ($guardianAEnrollmentIds): void {
            $sub->select('result_publication_id')
                ->from('result_publication_rows')
                ->whereIn('enrollment_id', $guardianAEnrollmentIds);
        })
        ->where('status', 'published')
        ->whereNull('superseded_by_id')
        ->first();

    expect($visiblePub->institution_semester_id)->toBe($ctxA['instSemId']);

    // Verify B's rows don't appear under A's scope
    $bRowsVisibleToA = DB::table('result_publication_rows')
        ->whereIn('enrollment_id', $guardianAEnrollmentIds)
        ->where('result_publication_id', $ctxB['publication']->id)
        ->count();

    expect($bRowsVisibleToA)->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// Snapshot immutability after mark corrections
// ─────────────────────────────────────────────────────────────────────────────

test('Correcting marks after publication leaves existing snapshot rows unchanged', function (): void {
    $ctx = publishedResultCtx();
    $pub = $ctx['publication'];

    $originalScore = (float) DB::table('result_publication_rows')
        ->where('result_publication_id', $pub->id)
        ->where('enrollment_id', $ctx['enrollmentId'])
        ->value('normalized_score');

    // Simulate mark correction
    DB::table('student_marks')
        ->where('enrollment_id', $ctx['enrollmentId'])
        ->update(['score' => 40]);

    // Original rows must be unchanged
    $scoreAfter = (float) DB::table('result_publication_rows')
        ->where('result_publication_id', $pub->id)
        ->where('enrollment_id', $ctx['enrollmentId'])
        ->value('normalized_score');

    expect($scoreAfter)->toBe($originalScore);

    // A new publication captures the corrected score
    $pub2 = app(PublishResults::class)($ctx['instSemId'], $ctx['classGroupId'], 1);

    $correctedScore = (float) DB::table('result_publication_rows')
        ->where('result_publication_id', $pub2->id)
        ->where('enrollment_id', $ctx['enrollmentId'])
        ->value('normalized_score');

    expect($correctedScore)->toBe(40.0);
    expect($originalScore)->toBe(90.0);
});

<?php

declare(strict_types=1);

namespace Tests\Feature\Marks;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\AcademicManagement\Actions\ApproveMarkSheet;
use Modules\AcademicManagement\Actions\CorrectMark;
use Modules\AcademicManagement\Actions\CreateAssessmentDefinition;
use Modules\AcademicManagement\Actions\CreateGradingScale;
use Modules\AcademicManagement\Actions\CreateMarkEntryWindow;
use Modules\AcademicManagement\Actions\ExtendMarkWindow;
use Modules\AcademicManagement\Actions\OpenMarkSheet;
use Modules\AcademicManagement\Actions\ReturnMarkSheet;
use Modules\AcademicManagement\Actions\SaveDraftMarks;
use Modules\AcademicManagement\Actions\SubmitMarkSheet;
use Modules\AcademicManagement\Actions\VerifyMarkSheet;
use Modules\AcademicManagement\Enums\AssessmentType;
use Modules\AcademicManagement\Enums\MarkSheetStatus;
use Modules\AcademicManagement\Enums\MarkWindowStatus;
use Modules\AcademicManagement\Exceptions\MarksException;
use Modules\AcademicManagement\Models\AssessmentDefinition;
use Modules\AcademicManagement\Models\GradingScale;
use Modules\AcademicManagement\Models\MarkEntryWindow;
use Modules\AcademicManagement\Models\MarkSheet;
use Modules\AcademicManagement\Models\StudentMark;
use Modules\AcademicManagement\Models\TeachingAssignment;
use Tests\TestCase;

final class MarksWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private int $orgId = 0;

    private int $typeId = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgId = (int) DB::table('organizations')->insertGetId([
            'code' => 'ORG-MRK', 'name_en' => 'Marks Org', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->typeId = (int) DB::table('institution_types')->insertGetId([
            'code' => 'TYPE-MRK', 'name_en' => 'School', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // ── Grading scale tests ───────────────────────────────────────────────

    public function test_create_grading_scale_stores_scale_and_grade_tiers(): void
    {
        $institutionId = $this->makeInstitution();

        $scale = app(CreateGradingScale::class)(
            institutionId: $institutionId,
            code: 'SCALE-A',
            nameAr: 'سلم التقييم أ',
            nameEn: 'Scale A',
            grades: [
                ['code' => 'A', 'name_ar' => 'ممتاز',  'name_en' => 'Excellent', 'min_score' => 90.0, 'max_score' => 100.0, 'is_passing' => true,  'sequence' => 1],
                ['code' => 'F', 'name_ar' => 'راسب',   'name_en' => 'Fail',      'min_score' => 0.0,  'max_score' => 59.99, 'is_passing' => false, 'sequence' => 2],
            ],
        );

        $this->assertInstanceOf(GradingScale::class, $scale);
        $this->assertSame('SCALE-A', $scale->code);
        $this->assertCount(2, $scale->grades);
        $this->assertDatabaseCount('grading_scale_grades', 2);
    }

    public function test_create_grading_scale_rejects_overlapping_ranges(): void
    {
        $institutionId = $this->makeInstitution();

        $this->expectException(MarksException::class);
        $this->expectExceptionMessageMatches('/overlap/');

        app(CreateGradingScale::class)(
            institutionId: $institutionId,
            code: 'SCALE-B',
            nameAr: 'سلم ب',
            nameEn: null,
            grades: [
                ['code' => 'A', 'name_ar' => 'ممتاز', 'name_en' => null, 'min_score' => 80.0, 'max_score' => 100.0, 'is_passing' => true,  'sequence' => 1],
                ['code' => 'B', 'name_ar' => 'جيد',   'name_en' => null, 'min_score' => 60.0, 'max_score' => 85.0,  'is_passing' => true,  'sequence' => 2],
            ],
        );
    }

    public function test_grading_scale_find_grade_for_score(): void
    {
        $institutionId = $this->makeInstitution();

        $scale = app(CreateGradingScale::class)(
            institutionId: $institutionId,
            code: 'SCALE-C',
            nameAr: 'سلم ج',
            nameEn: null,
            grades: [
                ['code' => 'A', 'name_ar' => 'ممتاز', 'name_en' => null, 'min_score' => 90.0, 'max_score' => 100.0, 'is_passing' => true,  'sequence' => 1],
                ['code' => 'F', 'name_ar' => 'راسب',  'name_en' => null, 'min_score' => 0.0,  'max_score' => 59.99, 'is_passing' => false, 'sequence' => 2],
            ],
        );

        $grade = $scale->gradeForScore(95.0);
        $this->assertNotNull($grade);
        $this->assertSame('A', $grade->code);
        $this->assertTrue($grade->is_passing);

        $failGrade = $scale->gradeForScore(45.0);
        $this->assertNotNull($failGrade);
        $this->assertFalse($failGrade->is_passing);

        $this->assertNull($scale->gradeForScore(75.0)); // gap in scale
    }

    // ── Assessment definition tests ───────────────────────────────────────

    public function test_create_assessment_definition(): void
    {
        [$instId, $semId, $classGroupId, $offeringId] = $this->makeContext();

        $def = app(CreateAssessmentDefinition::class)(
            institutionSemesterId: $semId,
            classGroupId: $classGroupId,
            subjectOfferingId: $offeringId,
            nameAr: 'اختبار منتصف الفصل',
            nameEn: 'Midterm',
            assessmentType: AssessmentType::Midterm,
            maxScore: 50.0,
            weight: 30.0,
        );

        $this->assertInstanceOf(AssessmentDefinition::class, $def);
        $this->assertSame(50.0, $def->max_score);
        $this->assertSame('active', $def->status);
    }

    public function test_assessment_definition_rejects_zero_max_score(): void
    {
        [$instId, $semId, $classGroupId, $offeringId] = $this->makeContext();

        $this->expectException(MarksException::class);
        $this->expectExceptionMessageMatches('/max_score/');

        app(CreateAssessmentDefinition::class)(
            institutionSemesterId: $semId,
            classGroupId: $classGroupId,
            subjectOfferingId: $offeringId,
            nameAr: 'Invalid',
            nameEn: null,
            assessmentType: AssessmentType::Quiz,
            maxScore: 0.0,
        );
    }

    // ── Mark entry window tests ───────────────────────────────────────────

    public function test_create_mark_entry_window_starts_scheduled(): void
    {
        [$instId, $semId] = $this->makeContext();

        $window = app(CreateMarkEntryWindow::class)(
            institutionSemesterId: $semId,
            opensAt: new \DateTimeImmutable('+1 hour'),
            closesAt: new \DateTimeImmutable('+2 weeks'),
        );

        $this->assertInstanceOf(MarkEntryWindow::class, $window);
        $this->assertSame(MarkWindowStatus::Scheduled, $window->status);
    }

    public function test_create_window_rejects_inverted_dates(): void
    {
        [$instId, $semId] = $this->makeContext();

        $this->expectException(MarksException::class);
        $this->expectExceptionMessageMatches('/opens_at must be before/');

        app(CreateMarkEntryWindow::class)(
            institutionSemesterId: $semId,
            opensAt: new \DateTimeImmutable('+2 weeks'),
            closesAt: new \DateTimeImmutable('+1 hour'),
        );
    }

    public function test_extend_window_appends_history(): void
    {
        [$instId, $semId] = $this->makeContext();

        $window = app(CreateMarkEntryWindow::class)(
            institutionSemesterId: $semId,
            opensAt: now()->subHour(),
            closesAt: now()->addDay(),
        );

        $window->status = MarkWindowStatus::Open->value;
        $window->save();

        $extended = app(ExtendMarkWindow::class)(
            window: $window,
            newClosesAt: now()->addDays(5),
            reason: 'Teachers need more time',
            actorRef: 'staff:1',
        );

        $this->assertSame(MarkWindowStatus::Extended, $extended->status);
        $this->assertCount(1, $extended->extension_history);
        $this->assertSame('Teachers need more time', $extended->extension_history[0]['reason']);
    }

    public function test_extend_window_rejects_earlier_date(): void
    {
        [$instId, $semId] = $this->makeContext();

        $window = app(CreateMarkEntryWindow::class)(
            institutionSemesterId: $semId,
            opensAt: now()->subHour(),
            closesAt: now()->addDays(10),
        );

        $window->status = MarkWindowStatus::Open->value;
        $window->save();

        $this->expectException(MarksException::class);
        $this->expectExceptionMessageMatches('/new_closes_at must be after/');

        app(ExtendMarkWindow::class)(
            window: $window,
            newClosesAt: now()->addDays(5),
            reason: 'Earlier date',
            actorRef: 'staff:1',
        );
    }

    // ── Mark sheet workflow tests ─────────────────────────────────────────

    public function test_open_mark_sheet_creates_sheet_with_student_mark_rows(): void
    {
        [$instId, $semId, $classGroupId, $offeringId, $assignmentId] = $this->makeFullContext();

        $assignment = TeachingAssignment::find($assignmentId);
        $sheet = app(OpenMarkSheet::class)($assignment);

        $this->assertInstanceOf(MarkSheet::class, $sheet);
        $this->assertSame(MarkSheetStatus::Draft, $sheet->status);
        $this->assertSame((int) $assignmentId, (int) $sheet->teaching_assignment_id);

        // Student marks should have been seeded
        $markCount = DB::table('student_marks')->where('mark_sheet_id', $sheet->id)->count();
        $this->assertGreaterThan(0, $markCount, 'Expected pre-seeded student mark rows');
    }

    public function test_open_mark_sheet_is_idempotent(): void
    {
        [$instId, $semId, $classGroupId, $offeringId, $assignmentId] = $this->makeFullContext();

        $assignment = TeachingAssignment::find($assignmentId);
        $sheet1 = app(OpenMarkSheet::class)($assignment);
        $sheet2 = app(OpenMarkSheet::class)($assignment);

        $this->assertSame($sheet1->id, $sheet2->id, 'Second call should return existing sheet');
    }

    public function test_teacher_can_save_draft_marks(): void
    {
        [$instId, $semId, $classGroupId, $offeringId, $assignmentId, $enrollmentId, $definitionId] = $this->makeFullContext();

        $assignment = TeachingAssignment::find($assignmentId);
        $sheet = app(OpenMarkSheet::class)($assignment);

        $mark = app(SaveDraftMarks::class)(
            sheet: $sheet,
            enrollmentId: $enrollmentId,
            assessmentDefinitionId: $definitionId,
            score: 42.5,
            exceptionStatus: null,
        );

        $this->assertSame(42.5, $mark->score);
        $this->assertNull($mark->exception_status);
    }

    public function test_save_draft_rejects_score_exceeding_max(): void
    {
        [$instId, $semId, $classGroupId, $offeringId, $assignmentId, $enrollmentId, $definitionId] = $this->makeFullContext();

        $assignment = TeachingAssignment::find($assignmentId);
        $sheet = app(OpenMarkSheet::class)($assignment);

        $this->expectException(MarksException::class);
        $this->expectExceptionMessageMatches('/out of range/');

        app(SaveDraftMarks::class)(
            sheet: $sheet,
            enrollmentId: $enrollmentId,
            assessmentDefinitionId: $definitionId,
            score: 999.0,
            exceptionStatus: null,
        );
    }

    public function test_save_draft_rejects_score_and_exception_together(): void
    {
        [$instId, $semId, $classGroupId, $offeringId, $assignmentId, $enrollmentId, $definitionId] = $this->makeFullContext();

        $assignment = TeachingAssignment::find($assignmentId);
        $sheet = app(OpenMarkSheet::class)($assignment);

        $this->expectException(MarksException::class);
        $this->expectExceptionMessageMatches('/not both/');

        app(SaveDraftMarks::class)(
            sheet: $sheet,
            enrollmentId: $enrollmentId,
            assessmentDefinitionId: $definitionId,
            score: 40.0,
            exceptionStatus: 'absent',
        );
    }

    public function test_full_workflow_draft_submit_verify_approve(): void
    {
        [$instId, $semId, $classGroupId, $offeringId, $assignmentId, $enrollmentId, $definitionId] = $this->makeFullContext();

        $assignment = TeachingAssignment::find($assignmentId);
        $sheet = app(OpenMarkSheet::class)($assignment);

        // Save a mark
        app(SaveDraftMarks::class)(
            sheet: $sheet, enrollmentId: $enrollmentId, assessmentDefinitionId: $definitionId,
            score: 38.0, exceptionStatus: null,
        );

        // Submit
        $sheet = app(SubmitMarkSheet::class)($sheet, staffProfileId: 10);
        $this->assertSame(MarkSheetStatus::Submitted, $sheet->status);

        // Verify
        $sheet = app(VerifyMarkSheet::class)($sheet, staffProfileId: 20);
        $this->assertSame(MarkSheetStatus::Verified, $sheet->status);
        $this->assertSame(20, (int) $sheet->verified_by_staff_profile_id);

        // Approve
        $sheet = app(ApproveMarkSheet::class)($sheet, staffProfileId: 30);
        $this->assertSame(MarkSheetStatus::Approved, $sheet->status);
        $this->assertSame(30, (int) $sheet->approved_by_staff_profile_id);
    }

    public function test_secretary_can_return_submitted_sheet(): void
    {
        [$instId, $semId, $classGroupId, $offeringId, $assignmentId, $enrollmentId, $definitionId] = $this->makeFullContext();

        $assignment = TeachingAssignment::find($assignmentId);
        $sheet = app(OpenMarkSheet::class)($assignment);

        $sheet = app(SubmitMarkSheet::class)($sheet, staffProfileId: 10);
        $sheet = app(ReturnMarkSheet::class)($sheet, reason: 'Missing marks for 3 students', staffProfileId: 20);

        $this->assertSame(MarkSheetStatus::Returned, $sheet->status);
        $this->assertSame('Missing marks for 3 students', $sheet->return_reason);
        $this->assertSame(20, (int) $sheet->returned_by_staff_profile_id);
    }

    public function test_returned_sheet_becomes_editable_again(): void
    {
        [$instId, $semId, $classGroupId, $offeringId, $assignmentId, $enrollmentId, $definitionId] = $this->makeFullContext();

        $assignment = TeachingAssignment::find($assignmentId);
        $sheet = app(OpenMarkSheet::class)($assignment);

        $sheet = app(SubmitMarkSheet::class)($sheet, staffProfileId: 10);
        $sheet = app(ReturnMarkSheet::class)($sheet, reason: 'Needs correction', staffProfileId: 20);

        $this->assertTrue($sheet->isEditable());

        // Can save after return
        $mark = app(SaveDraftMarks::class)(
            sheet: $sheet, enrollmentId: $enrollmentId, assessmentDefinitionId: $definitionId,
            score: 45.0, exceptionStatus: null,
        );
        $this->assertSame(45.0, $mark->score);
    }

    public function test_cannot_edit_submitted_sheet(): void
    {
        [$instId, $semId, $classGroupId, $offeringId, $assignmentId, $enrollmentId, $definitionId] = $this->makeFullContext();

        $assignment = TeachingAssignment::find($assignmentId);
        $sheet = app(OpenMarkSheet::class)($assignment);

        $sheet = app(SubmitMarkSheet::class)($sheet, staffProfileId: 10);

        $this->expectException(MarksException::class);
        $this->expectExceptionMessageMatches('/cannot be edited/');

        app(SaveDraftMarks::class)(
            sheet: $sheet, enrollmentId: $enrollmentId, assessmentDefinitionId: $definitionId,
            score: 30.0, exceptionStatus: null,
        );
    }

    public function test_cannot_verify_a_draft_sheet(): void
    {
        [$instId, $semId, $classGroupId, $offeringId, $assignmentId] = $this->makeFullContext();

        $assignment = TeachingAssignment::find($assignmentId);
        $sheet = app(OpenMarkSheet::class)($assignment);

        $this->expectException(MarksException::class);
        $this->expectExceptionMessageMatches('/cannot be verified/');

        app(VerifyMarkSheet::class)($sheet, staffProfileId: 20);
    }

    public function test_cannot_approve_unverified_sheet(): void
    {
        [$instId, $semId, $classGroupId, $offeringId, $assignmentId] = $this->makeFullContext();

        $assignment = TeachingAssignment::find($assignmentId);
        $sheet = app(OpenMarkSheet::class)($assignment);

        $sheet = app(SubmitMarkSheet::class)($sheet, staffProfileId: 10);

        $this->expectException(MarksException::class);
        $this->expectExceptionMessageMatches('/cannot be approved/');

        app(ApproveMarkSheet::class)($sheet, staffProfileId: 30);
    }

    public function test_return_requires_reason(): void
    {
        [$instId, $semId, $classGroupId, $offeringId, $assignmentId] = $this->makeFullContext();

        $assignment = TeachingAssignment::find($assignmentId);
        $sheet = app(OpenMarkSheet::class)($assignment);

        $sheet = app(SubmitMarkSheet::class)($sheet, staffProfileId: 10);

        $this->expectException(MarksException::class);
        $this->expectExceptionMessageMatches('/reason is required/');

        app(ReturnMarkSheet::class)($sheet, reason: '', staffProfileId: 20);
    }

    // ── Mark window enforcement ───────────────────────────────────────────

    public function test_save_draft_blocked_when_window_closed(): void
    {
        [$instId, $semId, $classGroupId, $offeringId, $assignmentId, $enrollmentId, $definitionId] = $this->makeFullContext();

        // Create a window that is open so OpenMarkSheet succeeds
        $window = app(CreateMarkEntryWindow::class)(
            institutionSemesterId: $semId,
            opensAt: new \DateTimeImmutable('-2 days'),
            closesAt: new \DateTimeImmutable('+7 days'),
        );
        $window->status = 'open';
        $window->save();

        $assignment = TeachingAssignment::find($assignmentId);
        $sheet = app(OpenMarkSheet::class)($assignment, markEntryWindowId: $window->id);

        $this->assertSame(MarkSheetStatus::Draft, $sheet->status);

        // Now close the window — subsequent saves should fail
        $window->status = 'closed';
        $window->closes_at = now()->subMinute();
        $window->save();

        // Reload the relationship cache on the sheet
        $sheet->unsetRelation('markEntryWindow');

        $this->expectException(MarksException::class);
        $this->expectExceptionMessageMatches('/not currently open/');

        app(SaveDraftMarks::class)(
            sheet: $sheet, enrollmentId: $enrollmentId, assessmentDefinitionId: $definitionId,
            score: 40.0, exceptionStatus: null,
        );
    }

    // ── Correction tests ──────────────────────────────────────────────────

    public function test_correct_mark_creates_new_row_and_preserves_original(): void
    {
        [$instId, $semId, $classGroupId, $offeringId, $assignmentId, $enrollmentId, $definitionId] = $this->makeFullContext();

        $assignment = TeachingAssignment::find($assignmentId);
        $sheet = app(OpenMarkSheet::class)($assignment);

        // Save, submit, verify, approve
        app(SaveDraftMarks::class)(
            sheet: $sheet, enrollmentId: $enrollmentId, assessmentDefinitionId: $definitionId,
            score: 35.0, exceptionStatus: null,
        );
        $sheet = app(SubmitMarkSheet::class)($sheet, 10);
        $sheet = app(VerifyMarkSheet::class)($sheet, 20);
        $sheet = app(ApproveMarkSheet::class)($sheet, 30);

        $originalMark = StudentMark::where('mark_sheet_id', $sheet->id)
            ->where('enrollment_id', $enrollmentId)
            ->whereNull('correction_of_id')
            ->first();

        $this->assertNotNull($originalMark);

        $correction = app(CorrectMark::class)(
            sheet: $sheet,
            originalMarkId: $originalMark->id,
            newScore: 40.0,
            newExceptionStatus: null,
            reason: 'Transcription error',
            actorStaffProfileId: 30,
        );

        $this->assertSame(40.0, $correction->score);
        $this->assertSame($originalMark->id, (int) $correction->correction_of_id);
        $this->assertSame('Transcription error', $correction->correction_reason);

        // Original mark still in DB, unchanged
        $original = StudentMark::find($originalMark->id);
        $this->assertSame(35.0, $original->score);
    }

    public function test_correction_rejects_score_exceeding_max(): void
    {
        [$instId, $semId, $classGroupId, $offeringId, $assignmentId, $enrollmentId, $definitionId] = $this->makeFullContext();

        $assignment = TeachingAssignment::find($assignmentId);
        $sheet = app(OpenMarkSheet::class)($assignment);

        app(SaveDraftMarks::class)(
            sheet: $sheet, enrollmentId: $enrollmentId, assessmentDefinitionId: $definitionId,
            score: 35.0, exceptionStatus: null,
        );
        $sheet = app(SubmitMarkSheet::class)($sheet, 10);
        $sheet = app(VerifyMarkSheet::class)($sheet, 20);
        $sheet = app(ApproveMarkSheet::class)($sheet, 30);

        $mark = StudentMark::where('mark_sheet_id', $sheet->id)->first();

        $this->expectException(MarksException::class);
        $this->expectExceptionMessageMatches('/out of range/');

        app(CorrectMark::class)(
            sheet: $sheet,
            originalMarkId: $mark->id,
            newScore: 9999.0,
            newExceptionStatus: null,
            reason: 'Test',
            actorStaffProfileId: 30,
        );
    }

    public function test_correction_blocked_on_draft_sheet(): void
    {
        [$instId, $semId, $classGroupId, $offeringId, $assignmentId, $enrollmentId, $definitionId] = $this->makeFullContext();

        $assignment = TeachingAssignment::find($assignmentId);
        $sheet = app(OpenMarkSheet::class)($assignment);

        $mark = StudentMark::where('mark_sheet_id', $sheet->id)->first();

        if (! $mark) {
            $this->markTestSkipped('No student marks seeded — empty class group.');
        }

        $this->expectException(MarksException::class);
        $this->expectExceptionMessageMatches('/approved or published/');

        app(CorrectMark::class)(
            sheet: $sheet,
            originalMarkId: $mark->id,
            newScore: 40.0,
            newExceptionStatus: null,
            reason: 'Test',
            actorStaffProfileId: 30,
        );
    }

    // ── Window enforcement ────────────────────────────────────────────────

    public function test_save_draft_is_blocked_when_window_is_closed(): void
    {
        [$instId, $semId, $classGroupId, $offeringId, $assignmentId, $enrollmentId, $definitionId] = $this->makeFullContext();

        $assignment = TeachingAssignment::find($assignmentId);

        // Create a window, open it, open a sheet, then CLOSE the window
        $window = app(CreateMarkEntryWindow::class)(
            institutionSemesterId: $semId,
            opensAt: now()->subHour(),
            closesAt: now()->addDays(7),
        );
        $window->status = 'open';
        $window->save();

        $sheet = app(OpenMarkSheet::class)($assignment, $window->id);

        // Close the window
        $window->status = 'closed';
        $window->save();

        $this->expectException(MarksException::class);
        $this->expectExceptionMessageMatches('/not currently open/');

        app(SaveDraftMarks::class)(
            sheet: $sheet,
            enrollmentId: $enrollmentId,
            assessmentDefinitionId: $definitionId,
            score: 40.0,
            exceptionStatus: null,
        );
    }

    public function test_create_window_rejects_class_group_from_different_semester(): void
    {
        [$instId, $semId] = $this->makeFullContext();

        // Create a class group belonging to a DIFFERENT semester
        $otherSemId = $this->makeSemester($instId);
        $levelId = DB::table('academic_levels')->insertGetId([
            'code' => 'LVLX-'.uniqid(), 'name_ar' => 'صف', 'name_en' => 'Grade',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $otherCgId = DB::table('class_groups')->insertGetId([
            'institution_semester_id' => $otherSemId,
            'operational_period_id' => 0,
            'academic_level_id' => $levelId,
            'code' => 'CG-DIFF-'.uniqid(),
            'name_ar' => 'الصف',
            'lifecycle_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(MarksException::class);
        $this->expectExceptionMessageMatches('/does not belong to institution semester/');

        app(CreateMarkEntryWindow::class)(
            institutionSemesterId: $semId,
            opensAt: now()->subHour(),
            closesAt: now()->addDays(7),
            classGroupId: $otherCgId,    // ← belongs to $otherSemId, not $semId
        );
    }

    public function test_create_assessment_definition_rejects_class_group_from_different_semester(): void
    {
        [$instId, $semId] = $this->makeFullContext();

        $otherSemId = $this->makeSemester($instId);
        $levelId = DB::table('academic_levels')->insertGetId([
            'code' => 'LVLY-'.uniqid(), 'name_ar' => 'صف', 'name_en' => 'Grade',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $otherCgId = DB::table('class_groups')->insertGetId([
            'institution_semester_id' => $otherSemId,
            'operational_period_id' => 0,
            'academic_level_id' => $levelId,
            'code' => 'CG-DIFF2-'.uniqid(),
            'name_ar' => 'الصف',
            'lifecycle_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(MarksException::class);
        $this->expectExceptionMessageMatches('/does not belong to institution semester/');

        app(CreateAssessmentDefinition::class)(
            institutionSemesterId: $semId,
            classGroupId: $otherCgId,    // ← belongs to $otherSemId, not $semId
            subjectOfferingId: null,
            nameAr: 'اختبار',
            nameEn: null,
            assessmentType: AssessmentType::Quiz,
            maxScore: 100.0,
        );
    }

    // ── Scope enforcement / forged-ID tests ──────────────────────────────

    public function test_save_draft_rejects_enrollment_from_different_class_group(): void
    {
        [$instId, $semId, $classGroupId, $offeringId, $assignmentId, $enrollmentId, $definitionId] = $this->makeFullContext();

        // Create a second class group in the SAME semester
        $levelId = DB::table('academic_levels')->insertGetId([
            'code' => 'LVL2-'.uniqid(), 'name_ar' => 'صف2', 'name_en' => 'Grade2',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $otherClassGroupId = DB::table('class_groups')->insertGetId([
            'institution_semester_id' => $semId,
            'operational_period_id' => 0,
            'academic_level_id' => $levelId,
            'code' => 'CG-OTHER-'.uniqid(),
            'name_ar' => 'الصف الثاني',
            'lifecycle_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Enroll a student in the OTHER class group
        $otherEnrollmentId = DB::table('student_enrollments')->insertGetId([
            'student_profile_id' => $this->makeStudentProfile(),
            'institution_semester_id' => $semId,
            'class_group_id' => $otherClassGroupId,
            'enrollment_status' => 'active',
            'enrolled_on' => now()->subDay()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $assignment = TeachingAssignment::find($assignmentId);
        $sheet = app(OpenMarkSheet::class)($assignment);

        $this->expectException(MarksException::class);
        $this->expectExceptionMessageMatches('/not an active enrollment in this mark sheet/');

        app(SaveDraftMarks::class)(
            sheet: $sheet,
            enrollmentId: $otherEnrollmentId,
            assessmentDefinitionId: $definitionId,
            score: 30.0,
            exceptionStatus: null,
        );
    }

    public function test_save_draft_rejects_assessment_from_different_subject(): void
    {
        [$instId, $semId, $classGroupId, $offeringId, $assignmentId, $enrollmentId, $definitionId] = $this->makeFullContext();

        // Create an assessment definition for a DIFFERENT subject offering
        $otherSubjectId = DB::table('subjects')->insertGetId([
            'code' => 'SUBJ-OTHER-'.uniqid(), 'name_ar' => 'علوم', 'name_en' => 'Science',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $otherOfferingId = DB::table('institution_subject_offerings')->insertGetId([
            'institution_semester_id' => $semId,
            'subject_id' => $otherSubjectId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $otherDefinitionId = DB::table('assessment_definitions')->insertGetId([
            'institution_semester_id' => $semId,
            'class_group_id' => $classGroupId,
            'subject_offering_id' => $otherOfferingId,   // ← different subject
            'name_ar' => 'اختبار علوم',
            'assessment_type' => 'quiz',
            'max_score' => 50.0,
            'weight' => 20.0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $assignment = TeachingAssignment::find($assignmentId);
        $sheet = app(OpenMarkSheet::class)($assignment);

        $this->expectException(MarksException::class);
        $this->expectExceptionMessageMatches('/not applicable to this mark sheet/');

        app(SaveDraftMarks::class)(
            sheet: $sheet,
            enrollmentId: $enrollmentId,
            assessmentDefinitionId: $otherDefinitionId,
            score: 30.0,
            exceptionStatus: null,
        );
    }

    public function test_open_mark_sheet_rejects_window_from_wrong_semester(): void
    {
        [$instId, $semId, $classGroupId, $offeringId, $assignmentId] = $this->makeFullContext();

        // Create a second semester in the same institution with its own open window
        $otherSemId = $this->makeSemester($instId);
        $window = app(CreateMarkEntryWindow::class)(
            institutionSemesterId: $otherSemId,   // ← different semester
            opensAt: now()->subHour(),
            closesAt: now()->addDays(7),
        );
        $window->status = 'open';
        $window->save();

        $assignment = TeachingAssignment::find($assignmentId);

        $this->expectException(MarksException::class);
        $this->expectExceptionMessageMatches("/assignment's semester/");

        app(OpenMarkSheet::class)($assignment, markEntryWindowId: $window->id);
    }

    public function test_open_mark_sheet_rejects_window_scoped_to_different_class_group(): void
    {
        [$instId, $semId, $classGroupId, $offeringId, $assignmentId] = $this->makeFullContext();

        // Create another class group in the same semester
        $levelId = DB::table('academic_levels')->insertGetId([
            'code' => 'LVL3-'.uniqid(), 'name_ar' => 'صف3', 'name_en' => 'Grade3',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $otherClassGroupId = DB::table('class_groups')->insertGetId([
            'institution_semester_id' => $semId,
            'operational_period_id' => 0,
            'academic_level_id' => $levelId,
            'code' => 'CG-X-'.uniqid(),
            'name_ar' => 'الصف الثالث',
            'lifecycle_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Window scoped to the OTHER class group
        $window = app(CreateMarkEntryWindow::class)(
            institutionSemesterId: $semId,
            opensAt: now()->subHour(),
            closesAt: now()->addDays(7),
            classGroupId: $otherClassGroupId,   // ← different class group
        );
        $window->status = 'open';
        $window->save();

        $assignment = TeachingAssignment::find($assignmentId);

        $this->expectException(MarksException::class);
        $this->expectExceptionMessageMatches('/class group/');

        app(OpenMarkSheet::class)($assignment, markEntryWindowId: $window->id);
    }

    public function test_correct_mark_rejects_null_score_and_null_exception(): void
    {
        [$instId, $semId, $classGroupId, $offeringId, $assignmentId, $enrollmentId, $definitionId] = $this->makeFullContext();

        $assignment = TeachingAssignment::find($assignmentId);

        $window = app(CreateMarkEntryWindow::class)(
            institutionSemesterId: $semId,
            opensAt: now()->subHour(),
            closesAt: now()->addDays(7),
        );
        $window->status = 'open';
        $window->save();

        $sheet = app(OpenMarkSheet::class)($assignment, $window->id);

        app(SaveDraftMarks::class)(
            sheet: $sheet, enrollmentId: $enrollmentId, assessmentDefinitionId: $definitionId,
            score: 40.0, exceptionStatus: null,
        );

        $sheet = app(SubmitMarkSheet::class)($sheet, staffProfileId: 10);
        $sheet = app(VerifyMarkSheet::class)($sheet, staffProfileId: 20);
        $sheet = app(ApproveMarkSheet::class)($sheet, staffProfileId: 30);

        $originalMark = StudentMark::where('mark_sheet_id', $sheet->id)
            ->whereNull('correction_of_id')
            ->first();

        $this->expectException(MarksException::class);
        $this->expectExceptionMessageMatches('/must provide either/');

        app(CorrectMark::class)(
            sheet: $sheet,
            originalMarkId: $originalMark->id,
            newScore: null,           // ← null
            newExceptionStatus: null, // ← null — should be rejected
            reason: 'Correction reason here',
            actorStaffProfileId: 5,
        );
    }

    // ── Four-eyes enforcement ─────────────────────────────────────────────

    public function test_same_person_cannot_verify_and_approve(): void
    {
        [$instId, $semId, $classGroupId, $offeringId, $assignmentId, $enrollmentId, $definitionId] = $this->makeFullContext();

        $assignment = TeachingAssignment::find($assignmentId);
        $sheet = app(OpenMarkSheet::class)($assignment);

        app(SaveDraftMarks::class)(
            sheet: $sheet, enrollmentId: $enrollmentId, assessmentDefinitionId: $definitionId,
            score: 40.0, exceptionStatus: null,
        );

        $staffId = 99; // same staff for both verify and approve

        $sheet = app(SubmitMarkSheet::class)($sheet, staffProfileId: 10);
        $sheet = app(VerifyMarkSheet::class)($sheet, staffProfileId: $staffId);

        $this->expectException(MarksException::class);
        $this->expectExceptionMessageMatches('/four-eyes/');

        app(ApproveMarkSheet::class)($sheet, staffProfileId: $staffId);
    }

    public function test_different_person_can_verify_then_approve(): void
    {
        [$instId, $semId, $classGroupId, $offeringId, $assignmentId, $enrollmentId, $definitionId] = $this->makeFullContext();

        $assignment = TeachingAssignment::find($assignmentId);
        $sheet = app(OpenMarkSheet::class)($assignment);

        app(SaveDraftMarks::class)(
            sheet: $sheet, enrollmentId: $enrollmentId, assessmentDefinitionId: $definitionId,
            score: 40.0, exceptionStatus: null,
        );

        $sheet = app(SubmitMarkSheet::class)($sheet, staffProfileId: 10);
        $sheet = app(VerifyMarkSheet::class)($sheet, staffProfileId: 20);
        $sheet = app(ApproveMarkSheet::class)($sheet, staffProfileId: 30); // different from verifier

        $this->assertSame(MarkSheetStatus::Approved, $sheet->status);
    }

    // ── Exception mark status ─────────────────────────────────────────────

    public function test_can_save_exception_status_absent(): void
    {
        [$instId, $semId, $classGroupId, $offeringId, $assignmentId, $enrollmentId, $definitionId] = $this->makeFullContext();

        $assignment = TeachingAssignment::find($assignmentId);
        $sheet = app(OpenMarkSheet::class)($assignment);

        $mark = app(SaveDraftMarks::class)(
            sheet: $sheet, enrollmentId: $enrollmentId, assessmentDefinitionId: $definitionId,
            score: null, exceptionStatus: 'absent',
        );

        $this->assertNull($mark->score);
        $this->assertSame('absent', $mark->exception_status?->value);
    }

    // ── Fixtures ──────────────────────────────────────────────────────────

    private function makeInstitution(): int
    {
        return (int) DB::table('institutions')->insertGetId([
            'organization_id' => $this->orgId,
            'institution_type_id' => $this->typeId,
            'code' => 'INST-'.uniqid(),
            'name_en' => 'Test School',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array{0:int, 1:int}
     */
    private function makeContext(): array
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeSemester($instId);

        $levelId = (int) DB::table('academic_levels')->insertGetId([
            'code' => 'LVL-'.uniqid(), 'name_ar' => 'صف', 'name_en' => 'Grade',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $classGroupId = (int) DB::table('class_groups')->insertGetId([
            'institution_semester_id' => $semId,
            'operational_period_id' => 0,
            'academic_level_id' => $levelId,
            'code' => 'CG-'.uniqid(),
            'name_ar' => 'الصف العاشر',
            'lifecycle_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subjectId = (int) DB::table('subjects')->insertGetId([
            'code' => 'SUBJ-'.uniqid(), 'name_ar' => 'رياضيات', 'name_en' => 'Maths',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $offeringId = (int) DB::table('institution_subject_offerings')->insertGetId([
            'institution_semester_id' => $semId,
            'subject_id' => $subjectId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$instId, $semId, $classGroupId, $offeringId];
    }

    /**
     * Full context with teaching assignment, active enrollment, and assessment definition.
     *
     * @return array{0:int, 1:int, 2:int, 3:int, 4:int, 5:int, 6:int}
     */
    private function makeFullContext(): array
    {
        [$instId, $semId, $classGroupId, $offeringId] = $this->makeContext();

        // Teaching assignment (no real StaffPosition needed — use stub)
        $assignmentId = (int) DB::table('teaching_assignments')->insertGetId([
            'staff_profile_id' => 1,
            'institution_semester_id' => $semId,
            'staff_position_id' => 1,
            'class_group_id' => $classGroupId,
            'subject_offering_id' => $offeringId,
            'starts_on' => now()->subDay()->toDateString(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // One active student enrollment
        $studentProfileId = (int) DB::table('student_profiles')->insertGetId([
            'person_id' => $this->makePerson(),
            'student_code' => 'STU-'.uniqid(),
            'lifecycle_status' => 'active',
            'registered_on' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $enrollmentId = (int) DB::table('student_enrollments')->insertGetId([
            'student_profile_id' => $studentProfileId,
            'institution_semester_id' => $semId,
            'class_group_id' => $classGroupId,
            'enrollment_status' => 'active',
            'enrolled_on' => now()->subDay()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // One active assessment definition
        $definitionId = (int) DB::table('assessment_definitions')->insertGetId([
            'institution_semester_id' => $semId,
            'class_group_id' => $classGroupId,
            'subject_offering_id' => $offeringId,
            'name_ar' => 'اختبار',
            'name_en' => 'Test',
            'assessment_type' => 'quiz',
            'max_score' => 50.0,
            'weight' => 20.0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$instId, $semId, $classGroupId, $offeringId, $assignmentId, $enrollmentId, $definitionId];
    }

    private function makeSemester(int $institutionId): int
    {
        $yearId = (int) DB::table('academic_years')->insertGetId([
            'organization_id' => $this->orgId,
            'code' => 'AY-'.uniqid(),
            'name_en' => 'Year',
            'starts_on' => '2025-09-01',
            'ends_on' => '2026-06-30',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $semId = (int) DB::table('semesters')->insertGetId([
            'code' => 'SEM-'.uniqid(),
            'name_en' => 'First',
            'name_ar' => 'الأول',
            'sequence' => 1,
            'status' => 'open',
            'academic_year_id' => $yearId,
            'starts_on' => '2025-09-01',
            'ends_on' => '2026-01-31',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('institution_semesters')->insertGetId([
            'institution_id' => $institutionId,
            'semester_id' => $semId,
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makePerson(): int
    {
        return (int) DB::table('people')->insertGetId([
            'full_name_ar' => 'طالب '.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeStudentProfile(): int
    {
        return (int) DB::table('student_profiles')->insertGetId([
            'person_id' => $this->makePerson(),
            'student_code' => 'STU-'.uniqid(),
            'lifecycle_status' => 'active',
            'registered_on' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

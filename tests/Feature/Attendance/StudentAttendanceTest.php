<?php

declare(strict_types=1);

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Actions\BulkMarkPresent;
use Modules\Attendance\Actions\CorrectVerifiedAttendance;
use Modules\Attendance\Actions\OpenDailySheet;
use Modules\Attendance\Actions\PopulateEnrolledStudents;
use Modules\Attendance\Actions\ReopenForCorrection;
use Modules\Attendance\Actions\ReturnSheet;
use Modules\Attendance\Actions\SaveDraft;
use Modules\Attendance\Actions\SubmitSheet;
use Modules\Attendance\Actions\UpdateRecord;
use Modules\Attendance\Actions\VerifySheet;
use Modules\Attendance\Data\StudentAttendanceStatus;
use Modules\Attendance\Enums\SheetStatus;
use Modules\Attendance\Exceptions\AttendanceException;
use Modules\Attendance\Models\AttendanceRecord;
use Modules\Attendance\Models\AttendanceSheet;
use Tests\TestCase;

/**
 * Domain tests for the student attendance workflow.
 *
 * Data setup uses raw DB inserts; tests are isolated via RefreshDatabase.
 * Covers: OpenDailySheet, PopulateEnrolledStudents, UpdateRecord, BulkMarkPresent,
 * SaveDraft, SubmitSheet, ReturnSheet, VerifySheet, ReopenForCorrection,
 * CorrectVerifiedAttendance, and all boundary/rejection rules.
 */
class StudentAttendanceTest extends TestCase
{
    use RefreshDatabase;

    // ── Shared fixtures ───────────────────────────────────────────────────

    private int $orgId = 0;

    private int $typeId = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgId = (int) DB::table('organizations')->insertGetId([
            'code' => 'ORG-ATT',
            'name_en' => 'Attendance Test Org',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->typeId = (int) DB::table('institution_types')->insertGetId([
            'code' => 'TYPE-ATT',
            'name_en' => 'School',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ── Insert helpers ─────────────────────────────────────────────────────

    private function makeInstitution(): int
    {
        return (int) DB::table('institutions')->insertGetId([
            'organization_id' => $this->orgId,
            'institution_type_id' => $this->typeId,
            'code' => 'INST-'.uniqid(),
            'name_en' => 'Test Institution',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeInstitutionSemester(int $institutionId, string $status = 'open'): int
    {
        $yearId = (int) DB::table('academic_years')->insertGetId([
            'organization_id' => $this->orgId,
            'code' => 'AY-'.uniqid(),
            'name_en' => 'Test Year',
            'starts_on' => '2025-09-01',
            'ends_on' => '2026-06-30',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $semId = (int) DB::table('semesters')->insertGetId([
            'code' => 'SEM-'.uniqid(),
            'name_en' => 'First Semester',
            'name_ar' => 'First Semester',
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
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeClassGroup(int $instSemId, int $opId = 0): int
    {
        if ($opId === 0) {
            $opId = $this->makeOperationalPeriod($instSemId);
        }

        $levelId = (int) DB::table('academic_levels')->insertGetId([
            'code' => 'LVL-'.uniqid(),
            'name_en' => 'Level',
            'name_ar' => 'Level',
            'sequence' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('class_groups')->insertGetId([
            'institution_semester_id' => $instSemId,
            'operational_period_id' => $opId,
            'academic_level_id' => $levelId,
            'code' => 'CG-'.uniqid(),
            'name_ar' => 'Class Group',
            'lifecycle_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeOperationalPeriod(int $instSemId): int
    {
        return (int) DB::table('operational_periods')->insertGetId([
            'institution_semester_id' => $instSemId,
            'code' => 'OP-'.uniqid(),
            'name_en' => 'Morning',
            'sequence' => 1,
            'starts_at' => '08:00:00',
            'ends_at' => '13:00:00',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeEnrollment(int $classGroupId, int $instSemId): array
    {
        $personId = (int) DB::table('people')->insertGetId([
            'full_name_ar' => 'Student '.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $profileId = (int) DB::table('student_profiles')->insertGetId([
            'person_id' => $personId,
            'student_code' => 'SC-'.uniqid(),
            'lifecycle_status' => 'active',
            'registered_on' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $enrollmentId = (int) DB::table('student_enrollments')->insertGetId([
            'student_profile_id' => $profileId,
            'institution_semester_id' => $instSemId,
            'class_group_id' => $classGroupId,
            'enrollment_status' => 'active',
            'enrolled_on' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['enrollmentId' => $enrollmentId, 'profileId' => $profileId];
    }

    private function makeStaffProfile(): int
    {
        $personId = (int) DB::table('people')->insertGetId([
            'full_name_ar' => 'Staff '.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('staff_profiles')->insertGetId([
            'person_id' => $personId,
            'staff_code' => 'SP-'.uniqid(),
            'employment_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ── Helpers to build a sheet at different lifecycle stages ─────────────

    private function openSheet(int $classGroupId, int $instSemId, string $date = '2025-10-15'): AttendanceSheet
    {
        $staffId = $this->makeStaffProfile();

        return app(OpenDailySheet::class)(
            classGroupId: $classGroupId,
            date: new \DateTimeImmutable($date),
            creatorStaffProfileId: $staffId,
        );
    }

    private function fillAllRecords(AttendanceSheet $sheet, string $statusCode = 'present'): void
    {
        AttendanceRecord::where('sheet_id', $sheet->id)->update(['status_code' => $statusCode]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // OpenDailySheet tests
    // ══════════════════════════════════════════════════════════════════════

    public function test_opens_daily_sheet_and_populates_enrolled_students(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $student1 = $this->makeEnrollment($classId, $semId);
        $student2 = $this->makeEnrollment($classId, $semId);
        $staffId = $this->makeStaffProfile();

        $sheet = app(OpenDailySheet::class)(
            classGroupId: $classId,
            date: new \DateTimeImmutable('2025-10-15'),
            creatorStaffProfileId: $staffId,
        );

        $this->assertInstanceOf(AttendanceSheet::class, $sheet);
        $this->assertEquals(SheetStatus::Draft, $sheet->status);
        $this->assertEquals($classId, $sheet->class_group_id);
        $this->assertEquals($semId, $sheet->institution_semester_id);

        // Two enrollment records created — one per active student
        $this->assertEquals(2, AttendanceRecord::where('sheet_id', $sheet->id)->count());

        // Records start unfilled
        $this->assertEquals(0, AttendanceRecord::where('sheet_id', $sheet->id)->whereNotNull('status_code')->count());
    }

    public function test_rejects_duplicate_sheet_for_same_class_and_date(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $staffId = $this->makeStaffProfile();

        app(OpenDailySheet::class)(
            classGroupId: $classId,
            date: new \DateTimeImmutable('2025-10-15'),
            creatorStaffProfileId: $staffId,
        );

        $this->expectException(AttendanceException::class);
        $this->expectExceptionMessageMatches('/already exists/');

        app(OpenDailySheet::class)(
            classGroupId: $classId,
            date: new \DateTimeImmutable('2025-10-15'),
            creatorStaffProfileId: $staffId,
        );
    }

    public function test_allows_different_dates_for_same_class(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $staffId = $this->makeStaffProfile();

        $sheet1 = app(OpenDailySheet::class)(
            classGroupId: $classId,
            date: new \DateTimeImmutable('2025-10-14'),
            creatorStaffProfileId: $staffId,
        );

        $sheet2 = app(OpenDailySheet::class)(
            classGroupId: $classId,
            date: new \DateTimeImmutable('2025-10-15'),
            creatorStaffProfileId: $staffId,
        );

        $this->assertNotEquals($sheet1->id, $sheet2->id);
    }

    public function test_rejects_open_sheet_in_closed_semester(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId, 'closed');
        $classId = $this->makeClassGroup($semId);
        $staffId = $this->makeStaffProfile();

        $this->expectException(AttendanceException::class);
        $this->expectExceptionMessageMatches('/does not accept/');

        app(OpenDailySheet::class)(
            classGroupId: $classId,
            date: new \DateTimeImmutable('2025-10-15'),
            creatorStaffProfileId: $staffId,
        );
    }

    public function test_only_active_enrollees_are_populated(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);

        // One active, one inactive enrollment
        $active = $this->makeEnrollment($classId, $semId);
        $inactive = $this->makeEnrollment($classId, $semId);
        DB::table('student_enrollments')
            ->where('id', $inactive['enrollmentId'])
            ->update(['enrollment_status' => 'completed']);

        $sheet = $this->openSheet($classId, $semId);

        // Only the active student gets a record
        $this->assertEquals(1, AttendanceRecord::where('sheet_id', $sheet->id)->count());
    }

    // ══════════════════════════════════════════════════════════════════════
    // UpdateRecord / BulkMarkPresent / SaveDraft
    // ══════════════════════════════════════════════════════════════════════

    public function test_update_record_sets_status(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $student = $this->makeEnrollment($classId, $semId);
        $sheet = $this->openSheet($classId, $semId);

        app(UpdateRecord::class)(
            sheet: $sheet,
            enrollmentId: $student['enrollmentId'],
            statusCode: StudentAttendanceStatus::PRESENT,
        );

        $record = AttendanceRecord::where('sheet_id', $sheet->id)
            ->where('enrollment_id', $student['enrollmentId'])
            ->first();

        $this->assertEquals(StudentAttendanceStatus::PRESENT, $record->status_code);
    }

    public function test_update_record_rejects_invalid_status_code(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $student = $this->makeEnrollment($classId, $semId);
        $sheet = $this->openSheet($classId, $semId);

        $this->expectException(AttendanceException::class);
        $this->expectExceptionMessageMatches('/not a valid/');

        app(UpdateRecord::class)(
            sheet: $sheet,
            enrollmentId: $student['enrollmentId'],
            statusCode: 'flying_on_vacation',
        );
    }

    public function test_excused_absence_requires_reason(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $student = $this->makeEnrollment($classId, $semId);
        $sheet = $this->openSheet($classId, $semId);

        $this->expectException(AttendanceException::class);
        $this->expectExceptionMessageMatches('/requires a reason/');

        app(UpdateRecord::class)(
            sheet: $sheet,
            enrollmentId: $student['enrollmentId'],
            statusCode: StudentAttendanceStatus::EXCUSED_ABSENCE,
            reason: null, // missing!
        );
    }

    public function test_excused_absence_accepted_with_reason(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $student = $this->makeEnrollment($classId, $semId);
        $sheet = $this->openSheet($classId, $semId);

        $record = app(UpdateRecord::class)(
            sheet: $sheet,
            enrollmentId: $student['enrollmentId'],
            statusCode: StudentAttendanceStatus::EXCUSED_ABSENCE,
            reason: 'Medical appointment',
        );

        $this->assertEquals(StudentAttendanceStatus::EXCUSED_ABSENCE, $record->status_code);
        $this->assertEquals('Medical appointment', $record->reason);
    }

    public function test_update_record_rejected_on_submitted_sheet(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $student = $this->makeEnrollment($classId, $semId);
        $sheet = $this->openSheet($classId, $semId);

        $this->fillAllRecords($sheet);

        $staffId = $this->makeStaffProfile();
        app(SubmitSheet::class)($sheet, $staffId);

        $sheet->refresh();

        $this->expectException(AttendanceException::class);
        $this->expectExceptionMessageMatches('/not editable/');

        app(UpdateRecord::class)(
            sheet: $sheet,
            enrollmentId: $student['enrollmentId'],
            statusCode: StudentAttendanceStatus::ABSENT,
        );
    }

    public function test_bulk_mark_present_fills_unfilled_records(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $this->makeEnrollment($classId, $semId);
        $this->makeEnrollment($classId, $semId);
        $this->makeEnrollment($classId, $semId);
        $sheet = $this->openSheet($classId, $semId);

        $count = app(BulkMarkPresent::class)($sheet);

        $this->assertEquals(3, $count);
        $this->assertEquals(0, AttendanceRecord::where('sheet_id', $sheet->id)->whereNull('status_code')->count());
        $this->assertEquals(
            3,
            AttendanceRecord::where('sheet_id', $sheet->id)->where('status_code', 'present')->count()
        );
    }

    public function test_bulk_mark_skips_already_filled_records(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $student1 = $this->makeEnrollment($classId, $semId);
        $student2 = $this->makeEnrollment($classId, $semId);
        $sheet = $this->openSheet($classId, $semId);

        // Pre-fill one as absent
        app(UpdateRecord::class)($sheet, $student1['enrollmentId'], StudentAttendanceStatus::ABSENT);

        $count = app(BulkMarkPresent::class)($sheet);

        // Only one was unfilled (student2)
        $this->assertEquals(1, $count);

        $absenceRecord = AttendanceRecord::where('sheet_id', $sheet->id)
            ->where('enrollment_id', $student1['enrollmentId'])
            ->first();

        $this->assertEquals('absent', $absenceRecord->status_code, 'Pre-filled record must not be overwritten.');
    }

    // ══════════════════════════════════════════════════════════════════════
    // SubmitSheet
    // ══════════════════════════════════════════════════════════════════════

    public function test_submits_sheet_when_all_records_filled(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $this->makeEnrollment($classId, $semId);
        $sheet = $this->openSheet($classId, $semId);

        app(BulkMarkPresent::class)($sheet);

        $staffId = $this->makeStaffProfile();
        $submitted = app(SubmitSheet::class)($sheet, $staffId);

        $this->assertEquals(SheetStatus::Submitted, $submitted->status);
        $this->assertNotNull($submitted->submitted_at);
    }

    public function test_rejects_submit_with_unfilled_records(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $this->makeEnrollment($classId, $semId);
        $sheet = $this->openSheet($classId, $semId);

        // Do NOT fill records

        $this->expectException(AttendanceException::class);
        $this->expectExceptionMessageMatches('/unfilled/');

        app(SubmitSheet::class)($sheet, $this->makeStaffProfile());
    }

    public function test_rejects_double_submit(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $this->makeEnrollment($classId, $semId);
        $sheet = $this->openSheet($classId, $semId);

        app(BulkMarkPresent::class)($sheet);
        $staffId = $this->makeStaffProfile();
        app(SubmitSheet::class)($sheet, $staffId);
        $sheet->refresh();

        $this->expectException(AttendanceException::class);
        $this->expectExceptionMessageMatches('/cannot be submitted/');

        app(SubmitSheet::class)($sheet, $staffId);
    }

    // ══════════════════════════════════════════════════════════════════════
    // ReturnSheet
    // ══════════════════════════════════════════════════════════════════════

    public function test_returns_submitted_sheet_with_reason(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $this->makeEnrollment($classId, $semId);
        $sheet = $this->openSheet($classId, $semId);
        app(BulkMarkPresent::class)($sheet);
        app(SubmitSheet::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();

        $returned = app(ReturnSheet::class)($sheet, 'Student list is incomplete.', $this->makeStaffProfile());

        $this->assertEquals(SheetStatus::Returned, $returned->status);
        $this->assertEquals('Student list is incomplete.', $returned->return_reason);
    }

    public function test_rejects_return_without_reason(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $this->makeEnrollment($classId, $semId);
        $sheet = $this->openSheet($classId, $semId);
        app(BulkMarkPresent::class)($sheet);
        app(SubmitSheet::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();

        $this->expectException(AttendanceException::class);
        $this->expectExceptionMessageMatches('/reason/');

        app(ReturnSheet::class)($sheet, '', $this->makeStaffProfile());
    }

    public function test_rejects_return_on_reopened_sheet(): void
    {
        // Reopened sheets must go through CorrectVerifiedAttendance + re-verify,
        // NOT through the return-to-teacher path. Returning a reopened sheet
        // would bypass the correction audit trail.
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $student = $this->makeEnrollment($classId, $semId);
        $sheet = $this->openSheet($classId, $semId);
        app(UpdateRecord::class)($sheet, $student['enrollmentId'], StudentAttendanceStatus::PRESENT);
        app(SubmitSheet::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();
        app(VerifySheet::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();
        app(ReopenForCorrection::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();

        $this->expectException(AttendanceException::class);
        $this->expectExceptionMessageMatches("/Only 'submitted' sheets can be returned/");

        app(ReturnSheet::class)($sheet, 'Some reason', $this->makeStaffProfile());
    }

    public function test_returned_sheet_can_be_resubmitted(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $this->makeEnrollment($classId, $semId);
        $sheet = $this->openSheet($classId, $semId);
        app(BulkMarkPresent::class)($sheet);
        app(SubmitSheet::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();
        app(ReturnSheet::class)($sheet, 'Please check.', $this->makeStaffProfile());
        $sheet->refresh();

        $resubmitted = app(SubmitSheet::class)($sheet, $this->makeStaffProfile());

        $this->assertEquals(SheetStatus::Submitted, $resubmitted->status);
    }

    // ══════════════════════════════════════════════════════════════════════
    // VerifySheet
    // ══════════════════════════════════════════════════════════════════════

    public function test_verifies_submitted_sheet(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $this->makeEnrollment($classId, $semId);
        $sheet = $this->openSheet($classId, $semId);
        app(BulkMarkPresent::class)($sheet);
        app(SubmitSheet::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();

        $secretaryId = $this->makeStaffProfile();
        $verified = app(VerifySheet::class)($sheet, $secretaryId);

        $this->assertEquals(SheetStatus::Verified, $verified->status);
        $this->assertNotNull($verified->verified_at);
        $this->assertEquals($secretaryId, $verified->verified_by_staff_profile_id);
    }

    public function test_rejects_verify_on_non_submitted_sheet(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $this->makeEnrollment($classId, $semId);
        $sheet = $this->openSheet($classId, $semId);  // Still draft

        $this->expectException(AttendanceException::class);
        $this->expectExceptionMessageMatches('/cannot be verified/');

        app(VerifySheet::class)($sheet, $this->makeStaffProfile());
    }

    // ══════════════════════════════════════════════════════════════════════
    // ReopenForCorrection
    // ══════════════════════════════════════════════════════════════════════

    public function test_reopens_verified_sheet(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $this->makeEnrollment($classId, $semId);
        $sheet = $this->openSheet($classId, $semId);
        app(BulkMarkPresent::class)($sheet);
        app(SubmitSheet::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();
        app(VerifySheet::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();

        $reopened = app(ReopenForCorrection::class)($sheet, $this->makeStaffProfile());

        $this->assertEquals(SheetStatus::Reopened, $reopened->status);
    }

    public function test_rejects_reopen_on_non_verified_sheet(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $this->makeEnrollment($classId, $semId);
        $sheet = $this->openSheet($classId, $semId);  // Still draft

        $this->expectException(AttendanceException::class);
        $this->expectExceptionMessageMatches('/cannot be reopened/');

        app(ReopenForCorrection::class)($sheet, $this->makeStaffProfile());
    }

    public function test_rejects_reopen_in_closed_semester(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId, 'open');
        $classId = $this->makeClassGroup($semId);
        $this->makeEnrollment($classId, $semId);
        $sheet = $this->openSheet($classId, $semId);
        app(BulkMarkPresent::class)($sheet);
        app(SubmitSheet::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();
        app(VerifySheet::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();

        // Close the semester
        DB::table('institution_semesters')->where('id', $semId)->update(['status' => 'closed']);

        $this->expectException(AttendanceException::class);
        $this->expectExceptionMessageMatches('/does not accept/');

        app(ReopenForCorrection::class)($sheet, $this->makeStaffProfile());
    }

    // ══════════════════════════════════════════════════════════════════════
    // CorrectVerifiedAttendance
    // ══════════════════════════════════════════════════════════════════════

    public function test_correction_persists_arrival_time_for_late_status(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $student = $this->makeEnrollment($classId, $semId);
        $sheet = $this->openSheet($classId, $semId);
        app(UpdateRecord::class)($sheet, $student['enrollmentId'], StudentAttendanceStatus::PRESENT);
        app(SubmitSheet::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();
        app(VerifySheet::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();
        app(ReopenForCorrection::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();

        $corrected = app(CorrectVerifiedAttendance::class)(
            sheet: $sheet,
            enrollmentId: $student['enrollmentId'],
            newStatusCode: StudentAttendanceStatus::LATE,
            reason: null,
            actorStaffProfileId: $this->makeStaffProfile(),
            arrivedAt: '08:35',
            departedAt: null,
        );

        $this->assertEquals('08:35', $corrected->arrived_at,
            'arrived_at must be persisted from caller input, not carried from old record.');
        $this->assertNull($corrected->departed_at);
    }

    public function test_corrects_record_on_reopened_sheet_and_preserves_history(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $student = $this->makeEnrollment($classId, $semId);
        $sheet = $this->openSheet($classId, $semId);
        app(UpdateRecord::class)($sheet, $student['enrollmentId'], StudentAttendanceStatus::PRESENT);
        app(SubmitSheet::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();
        app(VerifySheet::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();
        app(ReopenForCorrection::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();

        $actorId = $this->makeStaffProfile();
        $corrected = app(CorrectVerifiedAttendance::class)(
            sheet: $sheet,
            enrollmentId: $student['enrollmentId'],
            newStatusCode: StudentAttendanceStatus::ABSENT,
            reason: null,
            actorStaffProfileId: $actorId,
        );

        $this->assertEquals(StudentAttendanceStatus::ABSENT, $corrected->status_code);
        $this->assertEquals(StudentAttendanceStatus::PRESENT, $corrected->previous_status_code,
            'Previous status must be preserved for audit trail.');
        $this->assertEquals($actorId, $corrected->corrected_by_staff_profile_id);
        $this->assertNotNull($corrected->corrected_at);
        $this->assertEquals('correction', $corrected->source);
    }

    public function test_second_correction_in_same_cycle_is_rejected(): void
    {
        // One correction per reopen cycle is permitted. A second correction in
        // the same cycle is rejected; the secretary must re-verify then reopen.
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $student = $this->makeEnrollment($classId, $semId);
        $sheet = $this->openSheet($classId, $semId);
        app(UpdateRecord::class)($sheet, $student['enrollmentId'], StudentAttendanceStatus::PRESENT);
        app(SubmitSheet::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();
        app(VerifySheet::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();
        app(ReopenForCorrection::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();

        // First correction in cycle 1: present → absent (OK)
        app(CorrectVerifiedAttendance::class)(
            sheet: $sheet,
            enrollmentId: $student['enrollmentId'],
            newStatusCode: StudentAttendanceStatus::ABSENT,
            reason: null,
            actorStaffProfileId: $this->makeStaffProfile(),
        );

        $this->expectException(AttendanceException::class);
        $this->expectExceptionMessageMatches('/already been corrected/');

        // Second correction in same cycle (must be rejected)
        app(CorrectVerifiedAttendance::class)(
            sheet: $sheet,
            enrollmentId: $student['enrollmentId'],
            newStatusCode: StudentAttendanceStatus::LATE,
            reason: null,
            actorStaffProfileId: $this->makeStaffProfile(),
        );
    }

    public function test_two_full_reopen_cycles_preserve_all_audit_entries(): void
    {
        // Regression: correct → re-verify → reopen → correct must work, and
        // both history entries (cycle 1 and cycle 2) must remain in the history table.
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $student = $this->makeEnrollment($classId, $semId);
        $sheet = $this->openSheet($classId, $semId);
        app(UpdateRecord::class)($sheet, $student['enrollmentId'], StudentAttendanceStatus::PRESENT);
        app(SubmitSheet::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();
        app(VerifySheet::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();

        // ── Cycle 1: present → absent ─────────────────────────────────────
        app(ReopenForCorrection::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();

        app(CorrectVerifiedAttendance::class)(
            sheet: $sheet,
            enrollmentId: $student['enrollmentId'],
            newStatusCode: StudentAttendanceStatus::ABSENT,
            reason: null,
            actorStaffProfileId: $this->makeStaffProfile(),
        );

        // Re-verify to close cycle 1
        app(VerifySheet::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();

        // ── Cycle 2: absent → late ────────────────────────────────────────
        app(ReopenForCorrection::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();

        app(CorrectVerifiedAttendance::class)(
            sheet: $sheet,
            enrollmentId: $student['enrollmentId'],
            newStatusCode: StudentAttendanceStatus::LATE,
            reason: null,
            actorStaffProfileId: $this->makeStaffProfile(),
        );

        // Verify the record has the current (cycle-2) status
        $record = AttendanceRecord::where('sheet_id', $sheet->id)
            ->where('enrollment_id', $student['enrollmentId'])
            ->first();

        $this->assertEquals(StudentAttendanceStatus::LATE, $record->status_code,
            'Record must reflect the cycle-2 correction.');
        $this->assertEquals(2, $record->correction_cycle);

        // Verify BOTH history entries are preserved in the append-only table
        $history = DB::table('student_attendance_correction_history')
            ->where('enrollment_id', $student['enrollmentId'])
            ->orderBy('correction_cycle')
            ->get();

        $this->assertCount(2, $history,
            'Both correction cycles must have a row in the history table.');

        $this->assertEquals(1, $history[0]->correction_cycle);
        $this->assertEquals(StudentAttendanceStatus::PRESENT, $history[0]->previous_status_code,
            'Cycle-1 history must preserve the original verified value (present).');

        $this->assertEquals(2, $history[1]->correction_cycle);
        $this->assertEquals(StudentAttendanceStatus::ABSENT, $history[1]->previous_status_code,
            'Cycle-2 history must preserve the cycle-1 result (absent).');
    }

    public function test_correction_requires_reason_for_excused_absence(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $student = $this->makeEnrollment($classId, $semId);
        $sheet = $this->openSheet($classId, $semId);
        app(UpdateRecord::class)($sheet, $student['enrollmentId'], StudentAttendanceStatus::PRESENT);
        app(SubmitSheet::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();
        app(VerifySheet::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();
        app(ReopenForCorrection::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();

        $this->expectException(AttendanceException::class);
        $this->expectExceptionMessageMatches('/requires a correction reason/');

        app(CorrectVerifiedAttendance::class)(
            sheet: $sheet,
            enrollmentId: $student['enrollmentId'],
            newStatusCode: StudentAttendanceStatus::EXCUSED_ABSENCE,
            reason: null,  // missing!
            actorStaffProfileId: $this->makeStaffProfile(),
        );
    }

    public function test_correction_rejected_on_non_reopened_sheet(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $student = $this->makeEnrollment($classId, $semId);
        $sheet = $this->openSheet($classId, $semId);
        app(BulkMarkPresent::class)($sheet);
        app(SubmitSheet::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();
        app(VerifySheet::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();

        // Sheet is 'verified' but NOT reopened

        $this->expectException(AttendanceException::class);
        $this->expectExceptionMessageMatches('/does not allow corrections/');

        app(CorrectVerifiedAttendance::class)(
            sheet: $sheet,
            enrollmentId: $student['enrollmentId'],
            newStatusCode: StudentAttendanceStatus::ABSENT,
            reason: null,
            actorStaffProfileId: $this->makeStaffProfile(),
        );
    }

    // ══════════════════════════════════════════════════════════════════════
    // Re-verification after correction (reopened → verified)
    // ══════════════════════════════════════════════════════════════════════

    public function test_reopened_sheet_can_be_re_verified(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $student = $this->makeEnrollment($classId, $semId);
        $sheet = $this->openSheet($classId, $semId);
        app(UpdateRecord::class)($sheet, $student['enrollmentId'], StudentAttendanceStatus::PRESENT);
        app(SubmitSheet::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();
        app(VerifySheet::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();
        app(ReopenForCorrection::class)($sheet, $this->makeStaffProfile());
        $sheet->refresh();

        // Make a correction then re-verify
        app(CorrectVerifiedAttendance::class)(
            sheet: $sheet,
            enrollmentId: $student['enrollmentId'],
            newStatusCode: StudentAttendanceStatus::ABSENT,
            reason: null,
            actorStaffProfileId: $this->makeStaffProfile(),
        );
        $sheet->refresh();

        // Re-verification: VerifySheet must accept 'reopened' status
        $reverified = app(VerifySheet::class)($sheet, $this->makeStaffProfile());

        $this->assertEquals(SheetStatus::Verified, $reverified->status,
            'VerifySheet must accept reopened sheets (re-verification after correction).');
        $this->assertNotNull($reverified->verified_at);
    }

    public function test_verify_sheet_rejects_draft_sheet(): void
    {
        $instId = $this->makeInstitution();
        $semId = $this->makeInstitutionSemester($instId);
        $classId = $this->makeClassGroup($semId);
        $this->makeEnrollment($classId, $semId);
        $sheet = $this->openSheet($classId, $semId); // draft

        $this->expectException(AttendanceException::class);
        $this->expectExceptionMessageMatches('/cannot be verified/');

        app(VerifySheet::class)($sheet, $this->makeStaffProfile());
    }

    // ══════════════════════════════════════════════════════════════════════
    // Status catalogue
    // ══════════════════════════════════════════════════════════════════════

    public function test_status_catalogue_covers_all_codes(): void
    {
        $catalogue = StudentAttendanceStatus::catalogue();

        $this->assertArrayHasKey(StudentAttendanceStatus::PRESENT, $catalogue);
        $this->assertArrayHasKey(StudentAttendanceStatus::ABSENT, $catalogue);
        $this->assertArrayHasKey(StudentAttendanceStatus::EXCUSED_ABSENCE, $catalogue);
        $this->assertArrayHasKey(StudentAttendanceStatus::LATE, $catalogue);
        $this->assertArrayHasKey(StudentAttendanceStatus::LEFT_EARLY, $catalogue);
    }

    public function test_excused_absence_requires_reason_flag(): void
    {
        $this->assertTrue(StudentAttendanceStatus::requiresReason(StudentAttendanceStatus::EXCUSED_ABSENCE));
        $this->assertFalse(StudentAttendanceStatus::requiresReason(StudentAttendanceStatus::PRESENT));
        $this->assertFalse(StudentAttendanceStatus::requiresReason(StudentAttendanceStatus::ABSENT));
    }

    public function test_late_allows_arrival_time_flag(): void
    {
        $this->assertTrue(StudentAttendanceStatus::allowsArrivalTime(StudentAttendanceStatus::LATE));
        $this->assertFalse(StudentAttendanceStatus::allowsArrivalTime(StudentAttendanceStatus::PRESENT));
    }

    public function test_left_early_allows_departure_time_flag(): void
    {
        $this->assertTrue(StudentAttendanceStatus::allowsDepartureTime(StudentAttendanceStatus::LEFT_EARLY));
        $this->assertFalse(StudentAttendanceStatus::allowsDepartureTime(StudentAttendanceStatus::ABSENT));
    }
}

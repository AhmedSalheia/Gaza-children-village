<?php

declare(strict_types=1);

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Actions\CorrectVerifiedStaffRecord;
use Modules\Attendance\Actions\CreateDailyStaffRecord;
use Modules\Attendance\Actions\GenerateQrCredential;
use Modules\Attendance\Actions\ReviewScanEvent;
use Modules\Attendance\Actions\RevokeQrCredential;
use Modules\Attendance\Actions\SubmitScanEvent;
use Modules\Attendance\Actions\VerifyStaffRecord;
use Modules\Attendance\Data\StaffAttendanceStatus;
use Modules\Attendance\Exceptions\StaffAttendanceException;
use Modules\Attendance\Models\AttendanceScanEvent;
use Modules\Attendance\Models\StaffAttendanceRecord;
use Modules\Attendance\Models\StaffQrCredential;
use Tests\TestCase;

/**
 * Feature tests for the staff attendance and QR check-in system.
 *
 * Covers:
 *  - Status catalogue completeness and flags
 *  - Record creation, duplicate prevention, closed-semester guard, reason guard
 *  - Institution membership guard (cross-institution isolation)
 *  - Verification + re-verification (opens next correction window)
 *  - Correction history (append-only, one-per-cycle)
 *  - Two full correction cycles
 *  - QR credential HMAC hashing, revocation, auto-reject pending events
 *  - Scan event lifecycle (submit → review → accept/reject)
 *  - Institution guard on scan submission (cross-institution block)
 */
final class StaffAttendanceTest extends TestCase
{
    use RefreshDatabase;

    // ── Shared org + institution_type (created once per test) ─────────────────

    private int $orgId  = 0;
    private int $typeId = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgId = (int) DB::table('organizations')->insertGetId([
            'code'       => 'ORG-STAFF',
            'name_en'    => 'Staff Attendance Test Org',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->typeId = (int) DB::table('institution_types')->insertGetId([
            'code'       => 'TYPE-STAFF',
            'name_en'    => 'School',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ── Fixture helpers ───────────────────────────────────────────────────────

    private function makeInstitution(): int
    {
        return (int) DB::table('institutions')->insertGetId([
            'organization_id'     => $this->orgId,
            'institution_type_id' => $this->typeId,
            'code'                => 'INST-'.uniqid(),
            'name_en'             => 'Test Institution',
            'is_active'           => true,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    private function makeSemester(int $institutionId, string $status = 'open'): int
    {
        $yearId = (int) DB::table('academic_years')->insertGetId([
            'organization_id' => $this->orgId,
            'code'            => 'AY-'.uniqid(),
            'name_en'         => 'Test Year',
            'starts_on'       => '2025-09-01',
            'ends_on'         => '2026-06-30',
            'status'          => 'open',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $semId = (int) DB::table('semesters')->insertGetId([
            'code'             => 'SEM-'.uniqid(),
            'name_en'          => 'First Semester',
            'name_ar'          => 'First Semester',
            'sequence'         => 1,
            'status'           => 'open',
            'academic_year_id' => $yearId,
            'starts_on'        => '2025-09-01',
            'ends_on'          => '2026-01-31',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return (int) DB::table('institution_semesters')->insertGetId([
            'institution_id' => $institutionId,
            'semester_id'    => $semId,
            'status'         => $status,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    private function makePeriod(int $semesterId): int
    {
        return (int) DB::table('operational_periods')->insertGetId([
            'institution_semester_id' => $semesterId,
            'code'                    => 'OP-'.uniqid(),
            'name_en'                 => 'Morning',
            'name_ar'                 => 'Morning',
            'sequence'                => 1,
            'starts_at'               => '08:00:00',
            'ends_at'                 => '13:00:00',
            'is_active'               => true,
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);
    }

    /**
     * Create a staff profile and optionally attach it to an institution.
     *
     * Pass $institutionId for any test that calls CreateDailyStaffRecord or
     * SubmitScanEvent — both now enforce institution membership.
     * Omit it for pure credential tests (GenerateQrCredential / RevokeQrCredential).
     */
    private function makeStaffProfile(?int $institutionId = null): int
    {
        $personId = (int) DB::table('people')->insertGetId([
            'full_name_ar' => 'موظف '.uniqid(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $profileId = (int) DB::table('staff_profiles')->insertGetId([
            'person_id'         => $personId,
            'staff_code'        => 'SP-'.uniqid(),
            'employment_status' => 'active',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        if ($institutionId !== null) {
            DB::table('staff_institution_assignments')->insert([
                'staff_profile_id' => $profileId,
                'institution_id'   => $institutionId,
                'started_on'       => '2025-09-01',
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }

        return $profileId;
    }

    /** Build institution → semester → period; return ids keyed by name. */
    private function makeScope(string $semesterStatus = 'open'): array
    {
        $instId   = $this->makeInstitution();
        $semId    = $this->makeSemester($instId, $semesterStatus);
        $periodId = $this->makePeriod($semId);

        return ['instId' => $instId, 'semId' => $semId, 'periodId' => $periodId];
    }

    private function makeRecord(int $staffProfileId, int $periodId, string $date = ''): StaffAttendanceRecord
    {
        if ($date === '') {
            $date = now()->toDateString();
        }

        return app(CreateDailyStaffRecord::class)(
            staffProfileId:        $staffProfileId,
            operationalPeriodId:   $periodId,
            date:                  $date,
            statusCode:            StaffAttendanceStatus::PRESENT,
            reason:                null,
            creatorStaffProfileId: $staffProfileId,
        );
    }

    // ── Status catalogue ──────────────────────────────────────────────────────

    public function test_catalogue_has_all_six_status_codes(): void
    {
        $catalogue = StaffAttendanceStatus::catalogue();

        $this->assertArrayHasKey(StaffAttendanceStatus::PRESENT,       $catalogue);
        $this->assertArrayHasKey(StaffAttendanceStatus::ABSENT,        $catalogue);
        $this->assertArrayHasKey(StaffAttendanceStatus::EXCUSED,       $catalogue);
        $this->assertArrayHasKey(StaffAttendanceStatus::LATE,          $catalogue);
        $this->assertArrayHasKey(StaffAttendanceStatus::LEAVE,         $catalogue);
        $this->assertArrayHasKey(StaffAttendanceStatus::OFFICIAL_DUTY, $catalogue);

        $this->assertCount(6, $catalogue);
    }

    public function test_requires_reason_flags_are_correct(): void
    {
        $this->assertFalse(StaffAttendanceStatus::requiresReason(StaffAttendanceStatus::PRESENT));
        $this->assertFalse(StaffAttendanceStatus::requiresReason(StaffAttendanceStatus::ABSENT));
        $this->assertTrue(StaffAttendanceStatus::requiresReason(StaffAttendanceStatus::EXCUSED));
        $this->assertFalse(StaffAttendanceStatus::requiresReason(StaffAttendanceStatus::LATE));
        $this->assertTrue(StaffAttendanceStatus::requiresReason(StaffAttendanceStatus::LEAVE));
        $this->assertTrue(StaffAttendanceStatus::requiresReason(StaffAttendanceStatus::OFFICIAL_DUTY));
    }

    public function test_arrival_time_flag_is_set_only_for_late(): void
    {
        $this->assertTrue(StaffAttendanceStatus::allowsArrivalTime(StaffAttendanceStatus::LATE));
        $this->assertFalse(StaffAttendanceStatus::allowsArrivalTime(StaffAttendanceStatus::PRESENT));
        $this->assertFalse(StaffAttendanceStatus::allowsArrivalTime(StaffAttendanceStatus::ABSENT));
    }

    // ── Record creation ───────────────────────────────────────────────────────

    public function test_record_is_created_for_staff_without_login_account(): void
    {
        // Guards and non-login staff have StaffProfiles and institutional
        // assignments but no StaffAccount. Attendance must work for them.
        $scope   = $this->makeScope();
        $staffId = $this->makeStaffProfile($scope['instId']);

        // No staff_accounts row created — this is the "no-login" guard scenario

        $record = $this->makeRecord($staffId, $scope['periodId']);

        $this->assertInstanceOf(StaffAttendanceRecord::class, $record);
        $this->assertEquals($staffId, $record->staff_profile_id);
        $this->assertEquals(StaffAttendanceStatus::PRESENT, $record->status_code);
        $this->assertFalse($record->is_verified);
    }

    public function test_duplicate_record_for_same_period_and_date_updates_existing(): void
    {
        $scope   = $this->makeScope();
        $staffId = $this->makeStaffProfile($scope['instId']);

        $first = $this->makeRecord($staffId, $scope['periodId']);

        $second = app(CreateDailyStaffRecord::class)(
            staffProfileId:        $staffId,
            operationalPeriodId:   $scope['periodId'],
            date:                  now()->toDateString(),
            statusCode:            StaffAttendanceStatus::ABSENT,
            reason:                null,
            creatorStaffProfileId: $staffId,
        );

        $this->assertEquals($first->id, $second->id, 'Must update existing record, not create a new one.');
        $this->assertEquals(StaffAttendanceStatus::ABSENT, $second->status_code);

        $this->assertDatabaseCount('staff_attendance_records', 1);
    }

    public function test_cannot_create_record_for_closed_semester(): void
    {
        $scope   = $this->makeScope('closed');
        $staffId = $this->makeStaffProfile($scope['instId']);

        $this->expectException(StaffAttendanceException::class);
        $this->expectExceptionMessageMatches('/does not accept/');

        $this->makeRecord($staffId, $scope['periodId']);
    }

    public function test_reason_required_when_status_demands_it(): void
    {
        $scope   = $this->makeScope();
        $staffId = $this->makeStaffProfile($scope['instId']);

        $this->expectException(StaffAttendanceException::class);
        $this->expectExceptionMessageMatches('/requires a reason/');

        app(CreateDailyStaffRecord::class)(
            staffProfileId:        $staffId,
            operationalPeriodId:   $scope['periodId'],
            date:                  now()->toDateString(),
            statusCode:            StaffAttendanceStatus::LEAVE,
            reason:                null,
            creatorStaffProfileId: $staffId,
        );
    }

    public function test_reason_accepted_for_status_that_requires_it(): void
    {
        $scope   = $this->makeScope();
        $staffId = $this->makeStaffProfile($scope['instId']);

        $record = app(CreateDailyStaffRecord::class)(
            staffProfileId:        $staffId,
            operationalPeriodId:   $scope['periodId'],
            date:                  now()->toDateString(),
            statusCode:            StaffAttendanceStatus::LEAVE,
            reason:                'Annual leave',
            creatorStaffProfileId: $staffId,
        );

        $this->assertEquals('Annual leave', $record->reason);
    }

    public function test_cross_institution_record_creation_is_blocked(): void
    {
        // Staff from institution A cannot have records created for institution B's period.
        $scopeA  = $this->makeScope();
        $scopeB  = $this->makeScope();

        // Staff is only assigned to institution A
        $staffId = $this->makeStaffProfile($scopeA['instId']);

        $this->expectException(StaffAttendanceException::class);
        $this->expectExceptionMessageMatches('/does not have an active assignment/');

        // Attempting to record attendance for institution B's period → must fail
        $this->makeRecord($staffId, $scopeB['periodId']);
    }

    // ── Verification ──────────────────────────────────────────────────────────

    public function test_record_can_be_verified(): void
    {
        $scope   = $this->makeScope();
        $staffId = $this->makeStaffProfile($scope['instId']);
        $record  = $this->makeRecord($staffId, $scope['periodId']);

        $verified = app(VerifyStaffRecord::class)($record, $staffId);

        $this->assertTrue($verified->is_verified);
        $this->assertNotNull($verified->verified_at);
        $this->assertEquals($staffId, $verified->verified_by_staff_profile_id);
    }

    public function test_re_verifying_an_already_verified_record_is_allowed(): void
    {
        // Re-verification is intentional: it advances correction_cycle so the
        // secretary can make a second correction in a new window.
        $scope   = $this->makeScope();
        $staffId = $this->makeStaffProfile($scope['instId']);
        $record  = $this->makeRecord($staffId, $scope['periodId']);

        app(VerifyStaffRecord::class)($record, $staffId);
        $record->refresh();

        // Correct (cycle 0)
        app(CorrectVerifiedStaffRecord::class)(
            record:              $record,
            newStatusCode:       StaffAttendanceStatus::ABSENT,
            reason:              null,
            actorStaffProfileId: $staffId,
        );
        $record->refresh();

        // Re-verify — should NOT throw; should advance cycle to 1
        $reVerified = app(VerifyStaffRecord::class)($record, $staffId);

        $this->assertTrue($reVerified->is_verified);
        $this->assertEquals(1, $reVerified->correction_cycle,
            'Re-verification after a correction must advance correction_cycle to open the next window.');
    }

    public function test_cannot_update_verified_record_via_create_action(): void
    {
        $scope   = $this->makeScope();
        $staffId = $this->makeStaffProfile($scope['instId']);
        $record  = $this->makeRecord($staffId, $scope['periodId']);

        app(VerifyStaffRecord::class)($record, $staffId);

        $this->expectException(StaffAttendanceException::class);
        $this->expectExceptionMessageMatches('/already verified/');

        app(CreateDailyStaffRecord::class)(
            staffProfileId:        $staffId,
            operationalPeriodId:   $scope['periodId'],
            date:                  now()->toDateString(),
            statusCode:            StaffAttendanceStatus::ABSENT,
            reason:                null,
            creatorStaffProfileId: $staffId,
        );
    }

    // ── Correction ────────────────────────────────────────────────────────────

    public function test_verified_record_can_be_corrected(): void
    {
        $scope   = $this->makeScope();
        $staffId = $this->makeStaffProfile($scope['instId']);
        $record  = $this->makeRecord($staffId, $scope['periodId']);

        app(VerifyStaffRecord::class)($record, $staffId);
        $record->refresh();

        $corrected = app(CorrectVerifiedStaffRecord::class)(
            record:              $record,
            newStatusCode:       StaffAttendanceStatus::ABSENT,
            reason:              null,
            actorStaffProfileId: $staffId,
        );

        $this->assertEquals(StaffAttendanceStatus::ABSENT, $corrected->status_code);
        // Cycle stays at 0 — only VerifyStaffRecord (re-verify) advances it.
        $this->assertEquals(0, $corrected->correction_cycle);
        $this->assertTrue($corrected->is_verified, 'Record stays verified after correction.');
    }

    public function test_correction_appends_to_history_table(): void
    {
        $scope   = $this->makeScope();
        $staffId = $this->makeStaffProfile($scope['instId']);
        $record  = $this->makeRecord($staffId, $scope['periodId']);

        app(VerifyStaffRecord::class)($record, $staffId);
        $record->refresh();

        app(CorrectVerifiedStaffRecord::class)(
            record:              $record,
            newStatusCode:       StaffAttendanceStatus::ABSENT,
            reason:              null,
            actorStaffProfileId: $staffId,
        );

        $history = DB::table('staff_attendance_correction_history')
            ->where('staff_attendance_record_id', $record->id)
            ->get();

        $this->assertCount(1, $history, 'One history row must be appended per correction.');
        $this->assertEquals(StaffAttendanceStatus::PRESENT, $history->first()->previous_status_code);
        $this->assertEquals(0, $history->first()->correction_cycle, 'Cycle 0 = the first correction window.');
    }

    public function test_second_correction_in_same_cycle_is_rejected(): void
    {
        $scope   = $this->makeScope();
        $staffId = $this->makeStaffProfile($scope['instId']);
        $record  = $this->makeRecord($staffId, $scope['periodId']);

        app(VerifyStaffRecord::class)($record, $staffId);
        $record->refresh();

        app(CorrectVerifiedStaffRecord::class)(
            record:              $record,
            newStatusCode:       StaffAttendanceStatus::ABSENT,
            reason:              null,
            actorStaffProfileId: $staffId,
        );
        $record->refresh();

        $this->expectException(StaffAttendanceException::class);
        $this->expectExceptionMessageMatches('/already been corrected/');

        app(CorrectVerifiedStaffRecord::class)(
            record:              $record,
            newStatusCode:       StaffAttendanceStatus::LATE,
            reason:              null,
            actorStaffProfileId: $staffId,
        );
    }

    public function test_two_full_correction_cycles_preserve_all_history(): void
    {
        $scope   = $this->makeScope();
        $staffId = $this->makeStaffProfile($scope['instId']);
        $record  = $this->makeRecord($staffId, $scope['periodId']); // present

        // Cycle 1 verify + correct: present → absent
        app(VerifyStaffRecord::class)($record, $staffId);
        $record->refresh();

        app(CorrectVerifiedStaffRecord::class)(
            record:              $record,
            newStatusCode:       StaffAttendanceStatus::ABSENT,
            reason:              null,
            actorStaffProfileId: $staffId,
        );
        $record->refresh();

        // Re-verify to open cycle 1
        app(VerifyStaffRecord::class)($record, $staffId);
        $record->refresh();

        // Cycle 1 correct: absent → late
        app(CorrectVerifiedStaffRecord::class)(
            record:              $record,
            newStatusCode:       StaffAttendanceStatus::LATE,
            reason:              null,
            actorStaffProfileId: $staffId,
            confirmedArrivedAt:  '08:05:00',
        );
        $record->refresh();

        $this->assertEquals(StaffAttendanceStatus::LATE, $record->status_code);
        // Cycle is 1: VerifyStaffRecord advanced it once (at re-verify); corrections don't change it.
        $this->assertEquals(1, $record->correction_cycle);

        $history = DB::table('staff_attendance_correction_history')
            ->where('staff_attendance_record_id', $record->id)
            ->orderBy('correction_cycle')
            ->get();

        $this->assertCount(2, $history, 'Both correction windows must have a history row.');
        $this->assertEquals(0, $history[0]->correction_cycle);
        $this->assertEquals(StaffAttendanceStatus::PRESENT, $history[0]->previous_status_code);
        $this->assertEquals(1, $history[1]->correction_cycle);
        $this->assertEquals(StaffAttendanceStatus::ABSENT, $history[1]->previous_status_code);
    }

    public function test_cannot_correct_unverified_record(): void
    {
        $scope   = $this->makeScope();
        $staffId = $this->makeStaffProfile($scope['instId']);
        $record  = $this->makeRecord($staffId, $scope['periodId']);

        $this->expectException(StaffAttendanceException::class);
        $this->expectExceptionMessageMatches('/not verified/');

        app(CorrectVerifiedStaffRecord::class)(
            record:              $record,
            newStatusCode:       StaffAttendanceStatus::ABSENT,
            reason:              null,
            actorStaffProfileId: $staffId,
        );
    }

    // ── QR credentials ────────────────────────────────────────────────────────

    public function test_credential_stores_hmac_hash_not_plaintext(): void
    {
        // Credential-only test — no institution assignment required
        $staffId = $this->makeStaffProfile();

        $result     = app(GenerateQrCredential::class)($staffId, $staffId);
        $token      = $result['plaintext_token'];
        $credential = $result['credential'];

        $this->assertInstanceOf(StaffQrCredential::class, $credential);
        $this->assertNotEquals($token, $credential->token_hash,
            'Plaintext must not be stored; only the HMAC hash should be persisted.');

        $expectedHash = hash_hmac('sha256', $token, config('app.key'));
        $this->assertEquals($expectedHash, $credential->token_hash);
    }

    public function test_generating_second_credential_revokes_first(): void
    {
        $staffId = $this->makeStaffProfile();

        $first  = app(GenerateQrCredential::class)($staffId, $staffId);
        $second = app(GenerateQrCredential::class)($staffId, $staffId);

        $firstDb = StaffQrCredential::find($first['credential']->id);

        $this->assertFalse($firstDb->is_active,
            'First credential must be revoked when a second is generated.');
        $this->assertTrue($second['credential']->is_active);
    }

    public function test_revoke_action_deactivates_credential(): void
    {
        $staffId    = $this->makeStaffProfile();
        $result     = app(GenerateQrCredential::class)($staffId, $staffId);
        $credential = $result['credential'];

        app(RevokeQrCredential::class)($credential, $staffId);
        $credential->refresh();

        $this->assertFalse($credential->is_active);
        $this->assertNotNull($credential->revoked_at);
    }

    public function test_revoking_credential_rejects_pending_scan_events(): void
    {
        $scope   = $this->makeScope();
        $staffId = $this->makeStaffProfile($scope['instId']);

        $result     = app(GenerateQrCredential::class)($staffId, $staffId);
        $credential = $result['credential'];
        $token      = $result['plaintext_token'];

        app(SubmitScanEvent::class)(
            plaintextToken:      $token,
            operationalPeriodId: $scope['periodId'],
        );

        app(RevokeQrCredential::class)($credential, $staffId);

        $event = AttendanceScanEvent::where('qr_credential_id', $credential->id)->first();
        $this->assertEquals('rejected', $event->processing_status,
            'Pending events must be auto-rejected on credential revocation.');
    }

    // ── Scan events ───────────────────────────────────────────────────────────

    public function test_valid_scan_creates_pending_event(): void
    {
        $scope   = $this->makeScope();
        $staffId = $this->makeStaffProfile($scope['instId']);

        $result = app(GenerateQrCredential::class)($staffId, $staffId);
        $token  = $result['plaintext_token'];

        $scan = app(SubmitScanEvent::class)(
            plaintextToken:      $token,
            operationalPeriodId: $scope['periodId'],
            direction:           'arrival',
        );

        $this->assertInstanceOf(AttendanceScanEvent::class, $scan['event']);
        $this->assertEquals('pending', $scan['event']->processing_status);
        $this->assertFalse($scan['is_duplicate']);
    }

    public function test_invalid_token_is_rejected(): void
    {
        $scope = $this->makeScope();

        $this->expectException(StaffAttendanceException::class);
        $this->expectExceptionMessageMatches('/Invalid or revoked/');

        app(SubmitScanEvent::class)(
            plaintextToken:      'not-a-real-token-abcdefghijklmnopq',
            operationalPeriodId: $scope['periodId'],
        );
    }

    public function test_cross_institution_scan_submission_is_blocked(): void
    {
        // Staff from institution A cannot scan into institution B's period.
        $scopeA  = $this->makeScope();
        $scopeB  = $this->makeScope();
        $staffId = $this->makeStaffProfile($scopeA['instId']); // only assigned to A

        $result = app(GenerateQrCredential::class)($staffId, $staffId);
        $token  = $result['plaintext_token'];

        $this->expectException(StaffAttendanceException::class);
        $this->expectExceptionMessageMatches('/not valid for this institution/');

        // Attempt to submit scan for institution B's period → must fail
        app(SubmitScanEvent::class)(
            plaintextToken:      $token,
            operationalPeriodId: $scopeB['periodId'],
        );
    }

    public function test_scan_replay_returns_existing_event(): void
    {
        $scope   = $this->makeScope();
        $staffId = $this->makeStaffProfile($scope['instId']);

        $result = app(GenerateQrCredential::class)($staffId, $staffId);
        $token  = $result['plaintext_token'];

        $first  = app(SubmitScanEvent::class)($token, $scope['periodId'], 'arrival');
        $second = app(SubmitScanEvent::class)($token, $scope['periodId'], 'arrival');

        $this->assertTrue($second['is_duplicate'],
            'Second scan in same direction/period/date must be a duplicate.');
        $this->assertEquals($first['event']->id, $second['event']->id,
            'Duplicate scan must return the existing pending event.');

        $this->assertDatabaseCount('attendance_scan_events', 1);
    }

    public function test_different_direction_on_same_day_creates_new_event(): void
    {
        $scope   = $this->makeScope();
        $staffId = $this->makeStaffProfile($scope['instId']);

        $result = app(GenerateQrCredential::class)($staffId, $staffId);
        $token  = $result['plaintext_token'];

        $arrival   = app(SubmitScanEvent::class)($token, $scope['periodId'], 'arrival');
        $departure = app(SubmitScanEvent::class)($token, $scope['periodId'], 'departure');

        $this->assertFalse($departure['is_duplicate'],
            'Arrival and departure are different events for the same day.');
        $this->assertNotEquals($arrival['event']->id, $departure['event']->id);
        $this->assertDatabaseCount('attendance_scan_events', 2);
    }

    public function test_revoked_credential_cannot_create_scan_events(): void
    {
        $scope   = $this->makeScope();
        $staffId = $this->makeStaffProfile($scope['instId']);

        $result     = app(GenerateQrCredential::class)($staffId, $staffId);
        $credential = $result['credential'];
        $token      = $result['plaintext_token'];

        app(RevokeQrCredential::class)($credential, $staffId);

        $this->expectException(StaffAttendanceException::class);
        $this->expectExceptionMessageMatches('/Invalid or revoked/');

        app(SubmitScanEvent::class)($token, $scope['periodId']);
    }

    // ── Scan review ───────────────────────────────────────────────────────────

    public function test_accepted_scan_writes_scanned_time_to_attendance_record(): void
    {
        $scope    = $this->makeScope();
        $staffId  = $this->makeStaffProfile($scope['instId']);
        $reviewer = $this->makeStaffProfile($scope['instId']);

        $result = app(GenerateQrCredential::class)($staffId, $staffId);
        $token  = $result['plaintext_token'];

        $scan  = app(SubmitScanEvent::class)($token, $scope['periodId'], 'arrival');
        $event = $scan['event'];

        app(ReviewScanEvent::class)(
            event:                  $event,
            outcome:                'accepted',
            reviewerStaffProfileId: $reviewer,
        );

        $attendanceRecord = DB::table('staff_attendance_records')
            ->where('staff_profile_id', $staffId)
            ->where('operational_period_id', $scope['periodId'])
            ->first();

        $this->assertNotNull($attendanceRecord,
            'Accepting a scan must create/update the attendance record.');
        $this->assertNotNull($attendanceRecord->scanned_arrived_at,
            'Scanned arrival time must be written to scanned_arrived_at (not confirmed_arrived_at).');
        $this->assertNull($attendanceRecord->confirmed_arrived_at,
            'confirmed_arrived_at must NOT be set automatically — QR scans are never official.');
    }

    public function test_accepted_scan_does_not_override_existing_confirmed_time(): void
    {
        $scope    = $this->makeScope();
        $staffId  = $this->makeStaffProfile($scope['instId']);
        $reviewer = $this->makeStaffProfile($scope['instId']);

        // Secretary manually enters a confirmed record first
        app(CreateDailyStaffRecord::class)(
            staffProfileId:        $staffId,
            operationalPeriodId:   $scope['periodId'],
            date:                  now()->toDateString(),
            statusCode:            StaffAttendanceStatus::LATE,
            reason:                null,
            creatorStaffProfileId: $staffId,
            confirmedArrivedAt:    '08:05:00',
        );

        $result = app(GenerateQrCredential::class)($staffId, $staffId);
        $token  = $result['plaintext_token'];

        $scan  = app(SubmitScanEvent::class)($token, $scope['periodId'], 'arrival');
        $event = $scan['event'];

        app(ReviewScanEvent::class)(
            event:                  $event,
            outcome:                'accepted',
            reviewerStaffProfileId: $reviewer,
        );

        $record = DB::table('staff_attendance_records')
            ->where('staff_profile_id', $staffId)
            ->where('operational_period_id', $scope['periodId'])
            ->first();

        $this->assertEquals('08:05:00', $record->confirmed_arrived_at,
            'Accepting a scan must not overwrite the confirmed time already set by the secretary.');
    }

    public function test_rejecting_scan_changes_status_to_rejected(): void
    {
        $scope    = $this->makeScope();
        $staffId  = $this->makeStaffProfile($scope['instId']);
        $reviewer = $this->makeStaffProfile($scope['instId']);

        $result = app(GenerateQrCredential::class)($staffId, $staffId);
        $token  = $result['plaintext_token'];

        $scan  = app(SubmitScanEvent::class)($token, $scope['periodId']);
        $event = $scan['event'];

        $reviewed = app(ReviewScanEvent::class)(
            event:                  $event,
            outcome:                'rejected',
            reviewerStaffProfileId: $reviewer,
            rejectionReason:        'Scan outside permitted hours.',
        );

        $this->assertEquals('rejected', $reviewed->processing_status);
        $this->assertEquals('Scan outside permitted hours.', $reviewed->rejection_reason);

        // Rejecting a scan must not create an attendance record
        $this->assertDatabaseCount('staff_attendance_records', 0);
    }

    public function test_rescan_after_accepted_review_returns_existing_event_without_crash(): void
    {
        $scope    = $this->makeScope();
        $staffId  = $this->makeStaffProfile($scope['instId']);
        $reviewer = $this->makeStaffProfile($scope['instId']);

        $result = app(GenerateQrCredential::class)($staffId, $staffId);
        $token  = $result['plaintext_token'];

        // Submit and accept the initial scan
        $scan1 = app(SubmitScanEvent::class)($token, $scope['periodId'], 'arrival');

        app(ReviewScanEvent::class)(
            event:                  $scan1['event'],
            outcome:                'accepted',
            reviewerStaffProfileId: $reviewer,
        );

        // Rescan in the same direction after acceptance — the unique index would
        // cause a DB 500 without the "any status" replay check
        $scan2 = app(SubmitScanEvent::class)($token, $scope['periodId'], 'arrival');

        $this->assertTrue($scan2['is_duplicate'],
            'Rescan after acceptance must return existing event, not attempt a duplicate insert.');
        $this->assertEquals($scan1['event']->id, $scan2['event']->id,
            'Returned event must be the original accepted event.');
        $this->assertEquals(
            'accepted',
            $scan2['event']->fresh()->processing_status,
            'Duplicate rescan must not alter the accepted status.'
        );
        $this->assertDatabaseCount('attendance_scan_events', 1);
    }

    public function test_rescan_after_rejected_review_returns_existing_event_without_crash(): void
    {
        $scope    = $this->makeScope();
        $staffId  = $this->makeStaffProfile($scope['instId']);
        $reviewer = $this->makeStaffProfile($scope['instId']);

        $result = app(GenerateQrCredential::class)($staffId, $staffId);
        $token  = $result['plaintext_token'];

        // Submit and reject the initial scan
        $scan1 = app(SubmitScanEvent::class)($token, $scope['periodId'], 'arrival');

        app(ReviewScanEvent::class)(
            event:                  $scan1['event'],
            outcome:                'rejected',
            reviewerStaffProfileId: $reviewer,
            rejectionReason:        'Invalid scan.',
        );

        // Rescan in the same direction after rejection — must not crash
        $scan2 = app(SubmitScanEvent::class)($token, $scope['periodId'], 'arrival');

        $this->assertTrue($scan2['is_duplicate'],
            'Rescan after rejection must return existing event, not attempt a duplicate insert.');
        $this->assertEquals($scan1['event']->id, $scan2['event']->id,
            'Returned event must be the original rejected event.');
        $this->assertEquals(
            'rejected',
            $scan2['event']->fresh()->processing_status,
            'Duplicate rescan must not alter the rejected status.'
        );
        $this->assertDatabaseCount('attendance_scan_events', 1);
    }

    public function test_cannot_review_already_reviewed_event(): void
    {
        $scope    = $this->makeScope();
        $staffId  = $this->makeStaffProfile($scope['instId']);
        $reviewer = $this->makeStaffProfile($scope['instId']);

        $result = app(GenerateQrCredential::class)($staffId, $staffId);
        $token  = $result['plaintext_token'];
        $scan   = app(SubmitScanEvent::class)($token, $scope['periodId']);
        $event  = $scan['event'];

        app(ReviewScanEvent::class)($event, 'rejected', $reviewer, 'Test.');
        $event->refresh();

        $this->expectException(StaffAttendanceException::class);
        $this->expectExceptionMessageMatches('/already been reviewed/');

        app(ReviewScanEvent::class)($event, 'accepted', $reviewer);
    }
}

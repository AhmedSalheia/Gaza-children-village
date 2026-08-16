<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\AcademicManagement\Actions\ConfigureAttendancePolicy;
use Modules\AcademicManagement\Actions\PublishAttendanceSnapshot;
use Modules\AcademicManagement\Actions\RevokeAttendanceSnapshot;
use Modules\AcademicManagement\Exceptions\MarksException;
use Modules\AcademicManagement\Models\AttendancePublicationPolicy;
use Modules\AcademicManagement\Models\AttendancePublicationSnapshot;
use Modules\AcademicManagement\Models\AttendanceSnapshotRow;

uses(RefreshDatabase::class);

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Build a full attendance-publication context.
 *
 * @return array<string, int|string>
 */
function attendanceCtx(
    string $tag = '',
    bool $policyEnabled = true,
    string $detailLevel = 'daily_status',
    bool $showReason = false,
    bool $showArrival = false,
    int $delayDays = 0,
): array {
    $tag = $tag ?: uniqid();

    $orgId = (int) DB::table('organizations')->insertGetId([
        'code' => 'AORG-'.$tag, 'name_en' => 'Org', 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $typeId = (int) DB::table('institution_types')->insertGetId([
        'code' => 'ATYPE-'.$tag, 'name_en' => 'School', 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $instId = (int) DB::table('institutions')->insertGetId([
        'organization_id' => $orgId, 'institution_type_id' => $typeId,
        'code' => 'AINST-'.$tag, 'name_en' => 'School', 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $yearId = (int) DB::table('academic_years')->insertGetId([
        'organization_id' => $orgId, 'code' => 'AAY-'.$tag,
        'name_en' => 'Year', 'starts_on' => '2025-09-01', 'ends_on' => '2026-06-30',
        'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $semId = (int) DB::table('semesters')->insertGetId([
        'code' => 'ASEM-'.$tag, 'name_en' => 'First', 'name_ar' => 'الأول',
        'sequence' => 1, 'status' => 'open', 'academic_year_id' => $yearId,
        'starts_on' => '2025-09-01', 'ends_on' => '2026-01-31',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $instSemId = (int) DB::table('institution_semesters')->insertGetId([
        'institution_id' => $instId, 'semester_id' => $semId, 'status' => 'open',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $levelId = (int) DB::table('academic_levels')->insertGetId([
        'code' => 'ALVL-'.$tag, 'name_ar' => 'صف', 'name_en' => 'Grade',
        'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $classGroupId = (int) DB::table('class_groups')->insertGetId([
        'institution_semester_id' => $instSemId, 'operational_period_id' => 0,
        'academic_level_id' => $levelId, 'code' => 'ACG-'.$tag,
        'name_ar' => 'الصف', 'lifecycle_status' => 'active',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    // Policy
    app(ConfigureAttendancePolicy::class)(
        institutionSemesterId: $instSemId,
        enabled: $policyEnabled,
        detailLevel: $detailLevel,
        publishDelayDays: $delayDays,
        showReason: $showReason,
        showArrivalDeparture: $showArrival,
    );

    // Student enrollment (requires person → student_profile chain)
    $personId = (int) DB::table('people')->insertGetId([
        'full_name_ar' => 'طالب أ '.$tag, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $spId = (int) DB::table('student_profiles')->insertGetId([
        'person_id' => $personId, 'student_code' => 'ASTU-'.$tag,
        'lifecycle_status' => 'active', 'registered_on' => today()->toDateString(),
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $enrollmentId = (int) DB::table('student_enrollments')->insertGetId([
        'student_profile_id' => $spId,
        'institution_semester_id' => $instSemId,
        'class_group_id' => $classGroupId,
        'enrollment_status' => 'active',
        'enrolled_on' => today()->subDay()->toDateString(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Verified attendance sheet (operational_period_id is NOT NULL — use 0 as stub)
    $sheetId = (int) DB::table('student_attendance_sheets')->insertGetId([
        'institution_semester_id' => $instSemId,
        'operational_period_id' => 0,
        'class_group_id' => $classGroupId,
        'attendance_date' => today()->subDay()->toDateString(),
        'status' => 'verified',
        'creator_staff_profile_id' => 1,
        'verified_at' => now(),
        'verified_by_staff_profile_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Attendance record
    DB::table('student_attendance_records')->insert([
        'sheet_id' => $sheetId,
        'enrollment_id' => $enrollmentId,
        'student_profile_id' => $spId,
        'status_code' => 'present',
        'reason' => 'مبرر',
        'arrived_at' => '08:00:00',
        'source' => 'teacher_entry',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return compact('instSemId', 'classGroupId', 'enrollmentId', 'spId', 'sheetId');
}

// ─────────────────────────────────────────────────────────────────────────────
// ConfigureAttendancePolicy
// ─────────────────────────────────────────────────────────────────────────────

test('ConfigureAttendancePolicy creates a policy row on first call', function (): void {
    $ctx = attendanceCtx(detailLevel: 'daily_status', showReason: true);

    $policy = AttendancePublicationPolicy::where('institution_semester_id', $ctx['instSemId'])->first();

    expect($policy)->not->toBeNull()
        ->and($policy->enabled)->toBeTrue()
        ->and($policy->detail_level)->toBe('daily_status')
        ->and($policy->show_reason)->toBeTrue();
});

test('ConfigureAttendancePolicy updates existing policy idempotently', function (): void {
    $tag = uniqid();

    // Create a minimal instSemId — use attendanceCtx for brevity
    $ctx = attendanceCtx($tag);

    app(ConfigureAttendancePolicy::class)(
        institutionSemesterId: $ctx['instSemId'],
        enabled: false,
        detailLevel: 'summary_only',
    );

    $policy = AttendancePublicationPolicy::where('institution_semester_id', $ctx['instSemId'])->first();
    expect($policy->enabled)->toBeFalse()
        ->and($policy->detail_level)->toBe('summary_only');

    expect(AttendancePublicationPolicy::where('institution_semester_id', $ctx['instSemId'])->count())->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// PublishAttendanceSnapshot
// ─────────────────────────────────────────────────────────────────────────────

test('PublishAttendanceSnapshot throws when policy is disabled', function (): void {
    $ctx = attendanceCtx(policyEnabled: false);

    expect(fn () => app(PublishAttendanceSnapshot::class)($ctx['instSemId'], $ctx['classGroupId'], 1))
        ->toThrow(MarksException::class);
});

test('PublishAttendanceSnapshot throws when no verified sheets found', function (): void {
    // Create context but mark the sheet as draft instead of verified
    $tag = uniqid();
    $ctx = attendanceCtx($tag);

    // Override: change sheet status to draft
    DB::table('student_attendance_sheets')
        ->where('id', $ctx['sheetId'])
        ->update(['status' => 'draft']);

    expect(fn () => app(PublishAttendanceSnapshot::class)($ctx['instSemId'], $ctx['classGroupId'], 1))
        ->toThrow(MarksException::class);
});

test('PublishAttendanceSnapshot creates snapshot and row for each attendance record', function (): void {
    $ctx = attendanceCtx();

    $snap = app(PublishAttendanceSnapshot::class)($ctx['instSemId'], $ctx['classGroupId'], 1);

    expect($snap)->toBeInstanceOf(AttendancePublicationSnapshot::class)
        ->and($snap->status)->toBe('published')
        ->and($snap->version)->toBe(1);

    $rows = AttendanceSnapshotRow::where('snapshot_id', $snap->id)->get();
    expect($rows)->toHaveCount(1);
    expect($rows->first()->status_code)->toBe('present');
});

test('PublishAttendanceSnapshot hides reason when policy forbids it', function (): void {
    $ctx = attendanceCtx(showReason: false);
    $snap = app(PublishAttendanceSnapshot::class)($ctx['instSemId'], $ctx['classGroupId'], 1);
    $row = AttendanceSnapshotRow::where('snapshot_id', $snap->id)->first();

    expect($row->reason)->toBeNull();
});

test('PublishAttendanceSnapshot includes reason when policy allows it', function (): void {
    $ctx = attendanceCtx(showReason: true);
    $snap = app(PublishAttendanceSnapshot::class)($ctx['instSemId'], $ctx['classGroupId'], 1);
    $row = AttendanceSnapshotRow::where('snapshot_id', $snap->id)->first();

    expect($row->reason)->toBe('مبرر');
});

test('PublishAttendanceSnapshot hides arrived_at when policy forbids it', function (): void {
    $ctx = attendanceCtx(showArrival: false);
    $snap = app(PublishAttendanceSnapshot::class)($ctx['instSemId'], $ctx['classGroupId'], 1);
    $row = AttendanceSnapshotRow::where('snapshot_id', $snap->id)->first();

    expect($row->arrived_at)->toBeNull();
});

test('PublishAttendanceSnapshot includes arrived_at when policy allows it', function (): void {
    $ctx = attendanceCtx(showArrival: true);
    $snap = app(PublishAttendanceSnapshot::class)($ctx['instSemId'], $ctx['classGroupId'], 1);
    $row = AttendanceSnapshotRow::where('snapshot_id', $snap->id)->first();

    expect($row->arrived_at)->not->toBeNull();
});

test('PublishAttendanceSnapshot supersedes previous published snapshot', function (): void {
    $ctx = attendanceCtx();

    $snap1 = app(PublishAttendanceSnapshot::class)($ctx['instSemId'], $ctx['classGroupId'], 1);
    $snap2 = app(PublishAttendanceSnapshot::class)($ctx['instSemId'], $ctx['classGroupId'], 1);

    expect($snap2->version)->toBe(2);

    $snap1->refresh();
    expect($snap1->superseded_by_id)->toBe($snap2->id);
    // Old rows survive (immutable)
    expect(AttendanceSnapshotRow::where('snapshot_id', $snap1->id)->count())->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// RevokeAttendanceSnapshot
// ─────────────────────────────────────────────────────────────────────────────

test('RevokeAttendanceSnapshot transitions status to revoked', function (): void {
    $ctx = attendanceCtx();
    $snap = app(PublishAttendanceSnapshot::class)($ctx['instSemId'], $ctx['classGroupId'], 1);

    $revoked = app(RevokeAttendanceSnapshot::class)($snap, 'Correction needed', 2);

    expect($revoked->status)->toBe('revoked')
        ->and($revoked->revoke_reason)->toBe('Correction needed');
});

test('RevokeAttendanceSnapshot throws when already revoked', function (): void {
    $ctx = attendanceCtx();
    $snap = app(PublishAttendanceSnapshot::class)($ctx['instSemId'], $ctx['classGroupId'], 1);

    app(RevokeAttendanceSnapshot::class)($snap, 'First revoke', 1);

    expect(fn () => app(RevokeAttendanceSnapshot::class)($snap->fresh(), 'Retry', 1))
        ->toThrow(MarksException::class);
});

test('RevokeAttendanceSnapshot throws without a non-empty reason', function (): void {
    $ctx = attendanceCtx();
    $snap = app(PublishAttendanceSnapshot::class)($ctx['instSemId'], $ctx['classGroupId'], 1);

    expect(fn () => app(RevokeAttendanceSnapshot::class)($snap, '', 1))
        ->toThrow(MarksException::class);
});

test('AttendanceSnapshotRows remain after revocation — immutable audit trail', function (): void {
    $ctx = attendanceCtx();
    $snap = app(PublishAttendanceSnapshot::class)($ctx['instSemId'], $ctx['classGroupId'], 1);

    app(RevokeAttendanceSnapshot::class)($snap, 'Audit trail test', 2);

    expect(AttendanceSnapshotRow::where('snapshot_id', $snap->id)->count())->toBe(1);
});

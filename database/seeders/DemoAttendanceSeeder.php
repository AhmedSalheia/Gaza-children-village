<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds student and staff attendance demo data for Academy 1.
 *
 * Creates (idempotent — check-then-create throughout):
 *  - Student attendance sheets for the last 10 school days in G1-A and G2-A
 *    covering all lifecycle statuses: draft, submitted, verified, returned
 *  - Student attendance records (present / absent / late mix)
 *  - Staff attendance records for all 6 staff members
 *  - QR credential for STAFF-004 (teacher)
 *  - Pending and reviewed QR scan events for STAFF-004
 *
 * Runs AFTER: DemoMarkSeeder (needs op-period grants),
 *             DemoStaffSeeder (needs staff_profiles).
 */
final class DemoAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $inst1Id = (int) DB::table('institutions')->where('code', 'academy_1')->value('id');

        if ($inst1Id === 0) {
            $this->command?->warn('DemoAttendanceSeeder: academy_1 not found. Skipping.');

            return;
        }

        $instSemId = (int) DB::table('institution_semesters')
            ->where('institution_id', $inst1Id)
            ->where('status', 'open')
            ->value('id');

        if ($instSemId === 0) {
            $this->command?->warn('DemoAttendanceSeeder: No open semester. Skipping.');

            return;
        }

        $opMornId = (int) DB::table('operational_periods')
            ->where('institution_semester_id', $instSemId)
            ->where('code', 'OP-MORN')
            ->value('id');

        // Class groups
        $cgG1aId = (int) DB::table('class_groups')->where('code', 'CG-G1-A')->where('institution_semester_id', $instSemId)->value('id');
        $cgG2aId = (int) DB::table('class_groups')->where('code', 'CG-G2-A')->where('institution_semester_id', $instSemId)->value('id');
        $cgKg1aId = (int) DB::table('class_groups')->where('code', 'CG-KG1-A')->where('institution_semester_id', $instSemId)->value('id');

        // Staff
        $staffCreatorId = (int) DB::table('staff_profiles')->where('staff_code', 'STAFF-004')->value('id');
        $staffVerifierId = (int) DB::table('staff_profiles')->where('staff_code', 'STAFF-002')->value('id');

        // ── 1. Student attendance sheets & records ────────────────────────
        // Generate 10 weekday attendance dates going backwards from yesterday
        $dates = $this->recentWeekdays(10);

        foreach ([$cgG1aId, $cgG2aId, $cgKg1aId] as $cgId) {
            if ($cgId === 0) {
                continue;
            }

            // Get active enrollments for this class group
            $enrollments = DB::table('student_enrollments as se')
                ->join('student_profiles as sp', 'sp.id', '=', 'se.student_profile_id')
                ->where('se.institution_semester_id', $instSemId)
                ->where('se.class_group_id', $cgId)
                ->where('se.enrollment_status', 'active')
                ->select('se.id as enrollment_id', 'se.student_profile_id')
                ->get();

            if ($enrollments->isEmpty()) {
                continue;
            }

            foreach ($dates as $i => $date) {
                // Skip if sheet already exists (unique constraint on class_group_id + attendance_date)
                if (DB::table('student_attendance_sheets')
                    ->where('class_group_id', $cgId)
                    ->where('attendance_date', $date)
                    ->exists()
                ) {
                    continue;
                }

                // Assign lifecycle status based on date recency
                $status = match (true) {
                    $i === 0 => 'draft',       // most recent — still draft
                    $i === 1 => 'submitted',   // submitted awaiting verification
                    $i === 2 => 'returned',    // returned for correction
                    $i <= 7 => 'verified',    // verified
                    default => 'verified',
                };

                $sheetRow = [
                    'institution_semester_id' => $instSemId,
                    'operational_period_id' => $opMornId,
                    'class_group_id' => $cgId,
                    'attendance_date' => $date,
                    'status' => $status,
                    'creator_staff_profile_id' => $staffCreatorId,
                    'created_at' => now()->subDays(10 - $i),
                    'updated_at' => now()->subDays(9 - $i),
                ];

                if (in_array($status, ['submitted', 'returned', 'verified'], true)) {
                    $sheetRow['submitted_at'] = now()->subDays(10 - $i)->addHours(1);
                }

                if ($status === 'returned') {
                    $sheetRow['return_reason'] = 'يرجى مراجعة حضور الطالب المُدرج خطأً.';
                }

                if ($status === 'verified') {
                    $sheetRow['verified_at'] = now()->subDays(10 - $i)->addHours(2);
                    $sheetRow['verified_by_staff_profile_id'] = $staffVerifierId;
                }

                $sheetId = (int) DB::table('student_attendance_sheets')->insertGetId($sheetRow);

                // Attendance records for each enrollment
                foreach ($enrollments as $idx => $enr) {
                    // Create a realistic mix: mostly present, some absent, some late
                    $statusCode = match (true) {
                        $i >= 3 && $idx === 1 && ($i % 3 === 0) => 'absent',
                        $i >= 5 && $idx === 0 && ($i % 4 === 0) => 'late',
                        default => 'present',
                    };

                    DB::table('student_attendance_records')->insert([
                        'sheet_id' => $sheetId,
                        'enrollment_id' => $enr->enrollment_id,
                        'student_profile_id' => $enr->student_profile_id,
                        'status_code' => $statusCode,
                        'reason' => $statusCode === 'absent' ? 'مرض - تقرير طبي' : null,
                        'arrived_at' => $statusCode === 'late' ? '08:15:00' : null,
                        'source' => 'teacher_entry',
                        'created_at' => now()->subDays(10 - $i),
                        'updated_at' => now()->subDays(10 - $i),
                    ]);
                }
            }
        }

        // ── 2. Staff attendance records ───────────────────────────────────
        $staffCodes = ['STAFF-001', 'STAFF-002', 'STAFF-003', 'STAFF-004', 'STAFF-005', 'STAFF-006'];

        foreach ($staffCodes as $code) {
            $spId = (int) DB::table('staff_profiles')->where('staff_code', $code)->value('id');

            if ($spId === 0) {
                continue;
            }

            foreach ($dates as $i => $date) {
                if (DB::table('staff_attendance_records')
                    ->where('staff_profile_id', $spId)
                    ->where('operational_period_id', $opMornId)
                    ->where('record_date', $date)
                    ->exists()
                ) {
                    continue;
                }

                $statusCode = match (true) {
                    $i >= 4 && $code === 'STAFF-003' && ($i % 4 === 0) => 'absent',
                    $i >= 5 && $code === 'STAFF-005' && ($i % 5 === 0) => 'late',
                    default => 'present',
                };

                $isVerified = $i >= 2;

                DB::table('staff_attendance_records')->insert([
                    'staff_profile_id' => $spId,
                    'institution_semester_id' => $instSemId,
                    'operational_period_id' => $opMornId,
                    'record_date' => $date,
                    'status_code' => $statusCode,
                    'reason' => $statusCode === 'absent' ? 'إجازة مرضية' : null,
                    'confirmed_arrived_at' => in_array($statusCode, ['present', 'late']) ? '07:30:00' : null,
                    'confirmed_departed_at' => in_array($statusCode, ['present', 'late']) ? '12:00:00' : null,
                    'scanned_arrived_at' => null,
                    'scanned_departed_at' => null,
                    'is_verified' => $isVerified,
                    'verified_at' => $isVerified ? now()->subDays(10 - $i)->addHours(13) : null,
                    'verified_by_staff_profile_id' => $isVerified ? $staffVerifierId : null,
                    'source' => 'manual_entry',
                    'creator_staff_profile_id' => $staffVerifierId,
                    'created_at' => now()->subDays(10 - $i),
                    'updated_at' => now()->subDays(10 - $i),
                ]);
            }
        }

        // ── 3. QR credential for STAFF-004 ────────────────────────────────
        if (! DB::table('staff_qr_credentials')->where('staff_profile_id', $staffCreatorId)->where('is_active', true)->exists()) {
            $tokenHash = hash('sha256', 'demo-qr-token-staff004-'.now()->format('Y'));

            $credentialId = (int) DB::table('staff_qr_credentials')->insertGetId([
                'staff_profile_id' => $staffCreatorId,
                'token_hash' => $tokenHash,
                'is_active' => true,
                'issued_at' => now()->subMonths(2),
                'issued_by_staff_profile_id' => $staffVerifierId,
                'created_at' => now()->subMonths(2),
                'updated_at' => now()->subMonths(2),
            ]);

            // Seed some scan events
            foreach ($dates as $i => $date) {
                if ($i >= 4) {
                    break; // only recent events
                }

                $reviewed = $i >= 1;

                DB::table('attendance_scan_events')->insert([
                    'qr_credential_id' => $credentialId,
                    'staff_profile_id' => $staffCreatorId,
                    'institution_semester_id' => $instSemId,
                    'operational_period_id' => $opMornId,
                    'scanned_at' => now()->subDays(10 - $i)->setTime(7, 28, 0),
                    'scan_date' => $date,
                    'direction' => 'arrival',
                    'processing_status' => $reviewed ? 'reviewed' : 'pending',
                    'reviewed_by_staff_profile_id' => $reviewed ? $staffVerifierId : null,
                    'reviewed_at' => $reviewed ? now()->subDays(10 - $i)->addHours(5) : null,
                    'created_at' => now()->subDays(10 - $i),
                    'updated_at' => now()->subDays(10 - $i),
                ]);
            }
        }
    }

    /**
     * Return $n recent weekday dates (Mon–Fri), starting from yesterday.
     *
     * @return list<string>
     */
    private function recentWeekdays(int $n): array
    {
        $dates = [];
        $cursor = now()->subDay();

        while (count($dates) < $n) {
            if (! in_array($cursor->dayOfWeek, [0, 6], true)) { // skip Sun/Sat
                $dates[] = $cursor->toDateString();
            }

            $cursor = $cursor->subDay();
        }

        return $dates;
    }
}

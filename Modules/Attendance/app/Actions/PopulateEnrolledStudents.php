<?php

declare(strict_types=1);

namespace Modules\Attendance\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Attendance\Models\AttendanceRecord;
use Modules\Attendance\Models\AttendanceSheet;

/**
 * Populate a new attendance sheet with blank records for every active enrollee
 * in the sheet's class group.
 *
 * Called by OpenDailySheet immediately after sheet creation. Also callable
 * standalone if a sheet was created without records (e.g. after an enrollment
 * was added mid-day and the sheet needs an additional record).
 *
 * Uses DB::table() to cross-module query student_enrollments without importing
 * the AcademicManagement model (boundary-safe).
 *
 * Existing records for the same (sheet_id, enrollment_id) pair are skipped via
 * INSERT IGNORE semantics (insertOrIgnore). This makes the action idempotent.
 */
final class PopulateEnrolledStudents
{
    public function __invoke(AttendanceSheet $sheet, string $source = 'teacher_entry'): int
    {
        $enrollees = DB::table('student_enrollments')
            ->where('class_group_id', $sheet->class_group_id)
            ->where('institution_semester_id', $sheet->institution_semester_id)
            ->where('enrollment_status', 'active')
            ->select('id as enrollment_id', 'student_profile_id')
            ->get();

        $now = now()->toDateTimeString();
        $count = 0;

        foreach ($enrollees as $enrollee) {
            $exists = DB::table('student_attendance_records')
                ->where('sheet_id', $sheet->id)
                ->where('enrollment_id', $enrollee->enrollment_id)
                ->exists();

            if (! $exists) {
                $record = new AttendanceRecord;
                $record->sheet_id = $sheet->id;
                $record->enrollment_id = (int) $enrollee->enrollment_id;
                $record->student_profile_id = (int) $enrollee->student_profile_id;
                $record->source = $source;
                $record->save();
                $count++;
            }
        }

        return $count;
    }
}

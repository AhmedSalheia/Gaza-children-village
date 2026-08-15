<?php

declare(strict_types=1);

namespace Modules\Attendance\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Attendance\Enums\SheetStatus;
use Modules\Attendance\Exceptions\AttendanceException;
use Modules\Attendance\Models\AttendanceSheet;

/**
 * Open a new daily attendance sheet for a class group on a given date.
 *
 * Rules enforced:
 *  1. The institution semester must be open or draft (mutable).
 *  2. The class group must belong to the requested semester.
 *  3. No active (non-reopened) sheet may already exist for this class+date.
 *     A reopened sheet is allowed to co-exist as the correction is in progress.
 *  4. After creating the sheet, active enrollees are populated as blank records
 *     so the teacher sees the full class roster immediately.
 *
 * Authorization (who may call this) is enforced by the Livewire component or
 * route middleware; the action only validates data integrity.
 */
final class OpenDailySheet
{
    private const MUTABLE_STATUSES = ['open', 'draft'];

    public function __construct(
        private readonly PopulateEnrolledStudents $populate,
    ) {}

    public function __invoke(
        int $classGroupId,
        \DateTimeInterface $date,
        int $creatorStaffProfileId,
        string $source = 'teacher_entry',
    ): AttendanceSheet {
        $dateStr = $date->format('Y-m-d');

        return DB::transaction(function () use ($classGroupId, $dateStr, $creatorStaffProfileId, $source): AttendanceSheet {
            // Load class group and resolve its semester (cross-module via raw query)
            $classGroup = DB::table('class_groups')
                ->where('id', $classGroupId)
                ->select('id', 'institution_semester_id', 'operational_period_id', 'lifecycle_status')
                ->first();

            if (! $classGroup) {
                throw new AttendanceException("Class group #{$classGroupId} not found.");
            }

            if ((string) $classGroup->lifecycle_status !== 'active') {
                throw new AttendanceException("Class group #{$classGroupId} is not active.");
            }

            // Semester mutability guard
            $semester = DB::table('institution_semesters')
                ->where('id', $classGroup->institution_semester_id)
                ->select('id', 'status')
                ->first();

            if (! $semester) {
                throw new AttendanceException(
                    "InstitutionSemester #{$classGroup->institution_semester_id} not found."
                );
            }

            if (! in_array((string) $semester->status, self::MUTABLE_STATUSES, true)) {
                throw new AttendanceException(
                    "InstitutionSemester #{$semester->id} has status '{$semester->status}' ".
                    'and does not accept mutations. Attendance sheets require an open or draft semester.'
                );
            }

            // Duplicate sheet guard — one active sheet per class group per date.
            // Uses whereDate() because Eloquent's date cast serialises as Y-m-d H:i:s in SQLite,
            // which fails a plain string equality check against 'Y-m-d'.
            $existing = DB::table('student_attendance_sheets')
                ->where('class_group_id', $classGroupId)
                ->whereDate('attendance_date', $dateStr)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw new AttendanceException(
                    "An attendance sheet already exists for class group #{$classGroupId} ".
                    "on {$dateStr} (status: {$existing->status}). ".
                    'Use the existing sheet or reopen it for corrections.'
                );
            }

            // Create the sheet
            $sheet = new AttendanceSheet;
            $sheet->institution_semester_id  = (int) $classGroup->institution_semester_id;
            $sheet->operational_period_id    = (int) $classGroup->operational_period_id;
            $sheet->class_group_id           = $classGroupId;
            $sheet->attendance_date          = $dateStr;
            $sheet->status                   = SheetStatus::Draft->value;
            $sheet->creator_staff_profile_id = $creatorStaffProfileId;
            $sheet->save();

            // Populate with active enrollees
            ($this->populate)($sheet, $source);

            return $sheet;
        });
    }
}

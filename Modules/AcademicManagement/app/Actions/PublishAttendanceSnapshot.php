<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Actions;

use Illuminate\Support\Facades\DB;
use Modules\AcademicManagement\Exceptions\MarksException;
use Modules\AcademicManagement\Models\AttendancePublicationPolicy;
use Modules\AcademicManagement\Models\AttendancePublicationSnapshot;

/**
 * Publish a versioned attendance snapshot for a class group.
 *
 * Enforced rules:
 *  1. The attendance publication policy for this semester must exist and be enabled.
 *  2. Only VERIFIED attendance sheets are included (status = 'verified').
 *  3. Policy privacy rules are applied: reason and arrived_at are null unless
 *     the policy permits them at publication time.
 *  4. publish_delay_days: if > 0, records newer than (today - delay) are excluded.
 *  5. An existing published snapshot for the same class group is superseded.
 *  6. The snapshot rows are written once and never mutated.
 *
 * @throws MarksException
 */
final class PublishAttendanceSnapshot
{
    public function __invoke(
        int $institutionSemesterId,
        int $classGroupId,
        int $publisherStaffProfileId,
        ?\DateTimeInterface $periodFrom = null,
        ?\DateTimeInterface $periodTo = null,
    ): AttendancePublicationSnapshot {
        return DB::transaction(function () use (
            $institutionSemesterId, $classGroupId, $publisherStaffProfileId, $periodFrom, $periodTo
        ): AttendancePublicationSnapshot {
            // 1. Load and validate policy
            $policy = AttendancePublicationPolicy::where('institution_semester_id', $institutionSemesterId)->first();

            if (! $policy || ! $policy->enabled) {
                throw new MarksException(
                    'Attendance publication is not enabled for semester #'.$institutionSemesterId.'.'
                );
            }

            // 2. Compute effective date range with delay applied
            $cutoffDate = now()->subDays($policy->publish_delay_days)->toDateString();
            $fromDate   = $periodFrom?->format('Y-m-d');
            $toDate     = $periodTo ? min($periodTo->format('Y-m-d'), $cutoffDate) : $cutoffDate;

            // 3. Load verified attendance sheets for this class group
            $sheetQuery = DB::table('student_attendance_sheets')
                ->where('institution_semester_id', $institutionSemesterId)
                ->where('class_group_id', $classGroupId)
                ->where('status', 'verified')
                ->when($fromDate, fn ($q) => $q->whereDate('attendance_date', '>=', $fromDate))
                ->whereDate('attendance_date', '<=', $toDate);

            $sheets = $sheetQuery->get(['id', 'attendance_date']);

            if ($sheets->isEmpty()) {
                throw new MarksException(
                    'No verified attendance sheets found for class group #'.$classGroupId.
                    ' in the requested date range.'
                );
            }

            $sheetIds = $sheets->pluck('id')->all();

            // 4. Load attendance records for these sheets
            $records = DB::table('student_attendance_records')
                ->whereIn('sheet_id', $sheetIds)
                ->get(['sheet_id', 'enrollment_id', 'student_profile_id', 'status_code', 'reason', 'arrived_at']);

            if ($records->isEmpty()) {
                throw new MarksException(
                    'No attendance records found in the verified sheets for class group #'.$classGroupId.'.'
                );
            }

            // 5. Determine version
            $lastVersion = DB::table('attendance_publication_snapshots')
                ->where('institution_semester_id', $institutionSemesterId)
                ->where('class_group_id', $classGroupId)
                ->max('version') ?? 0;

            // 6. Create snapshot header
            $snapshot = new AttendancePublicationSnapshot;
            $snapshot->institution_semester_id    = $institutionSemesterId;
            $snapshot->class_group_id             = $classGroupId;
            $snapshot->period_from                = $fromDate;
            $snapshot->period_to                  = $toDate;
            $snapshot->version                    = $lastVersion + 1;
            $snapshot->detail_level               = $policy->detail_level;
            $snapshot->show_reason                = $policy->show_reason;
            $snapshot->show_arrival_departure     = $policy->show_arrival_departure;
            $snapshot->status                     = 'published';
            $snapshot->published_at               = now();
            $snapshot->publisher_staff_profile_id = $publisherStaffProfileId;
            $snapshot->save();

            // 7. Supersede previous published snapshot
            DB::table('attendance_publication_snapshots')
                ->where('institution_semester_id', $institutionSemesterId)
                ->where('class_group_id', $classGroupId)
                ->where('id', '!=', $snapshot->id)
                ->where('status', 'published')
                ->whereNull('superseded_by_id')
                ->update(['superseded_by_id' => $snapshot->id]);

            // 8. Build sheet_id → attendance_date lookup
            $sheetDateMap = $sheets->keyBy('id');

            // 9. Write snapshot rows (apply policy privacy rules)
            $now  = now()->toDateTimeString();
            $rows = [];
            foreach ($records as $record) {
                $attendanceDate = $sheetDateMap[$record->sheet_id]?->attendance_date;

                if (! $attendanceDate) {
                    continue;
                }

                $rows[] = [
                    'snapshot_id'        => $snapshot->id,
                    'student_profile_id' => (int) $record->student_profile_id,
                    'enrollment_id'      => (int) $record->enrollment_id,
                    'attendance_date'    => $attendanceDate,
                    'status_code'        => $record->status_code,
                    'reason'             => $policy->show_reason ? $record->reason : null,
                    'arrived_at'         => $policy->show_arrival_departure ? $record->arrived_at : null,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ];
            }

            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('attendance_snapshot_rows')->insert($chunk);
            }

            return $snapshot->fresh();
        });
    }
}

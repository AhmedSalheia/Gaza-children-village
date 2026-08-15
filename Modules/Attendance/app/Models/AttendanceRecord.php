<?php

declare(strict_types=1);

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Single student's attendance entry within a daily sheet.
 *
 * One record per student (enrollment) per sheet. Status_code null means the
 * record was auto-created during sheet population but not yet filled.
 *
 * Cross-module plain integer references (no DB FK):
 *   enrollment_id              — AcademicManagement.student_enrollments
 *   student_profile_id         — Students module (denormalized for joins)
 *   corrected_by_staff_profile_id — Staff
 *
 * Correction history is immutable: CorrectVerifiedAttendance writes the old
 * status into previous_status_code before overwriting.
 *
 * source values: teacher_entry | secretary_entry | correction
 */
final class AttendanceRecord extends Model
{
    protected $table = 'student_attendance_records';

    /** @var list<string> */
    protected $fillable = [
        'correction_cycle',
        'sheet_id',
        'enrollment_id',
        'student_profile_id',
        'status_code',
        'reason',
        'arrived_at',
        'departed_at',
        'safe_note',
        'source',
        'previous_status_code',
        'previous_reason',
        'corrected_by_staff_profile_id',
        'corrected_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'corrected_at' => 'datetime',
    ];

    /** @return BelongsTo<AttendanceSheet, $this> */
    public function sheet(): BelongsTo
    {
        return $this->belongsTo(AttendanceSheet::class, 'sheet_id');
    }

    /** Whether the attendance has been corrected after verification. */
    public function wasCorrected(): bool
    {
        return $this->previous_status_code !== null;
    }

    /** Whether this record has a status assigned yet. */
    public function isFilled(): bool
    {
        return $this->status_code !== null;
    }

    /**
     * @param  Builder<AttendanceRecord>  $query
     * @return Builder<AttendanceRecord>
     */
    public function scopeForSheet(Builder $query, int $sheetId): Builder
    {
        return $query->where('sheet_id', $sheetId);
    }

    /**
     * @param  Builder<AttendanceRecord>  $query
     * @return Builder<AttendanceRecord>
     */
    public function scopeUnfilled(Builder $query): Builder
    {
        return $query->whereNull('status_code');
    }
}

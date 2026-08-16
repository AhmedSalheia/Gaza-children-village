<?php

declare(strict_types=1);

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Attendance\Enums\SheetStatus;

/**
 * Daily attendance sheet for a class group within an institution semester.
 *
 * One sheet per class group per date; status tracks the workflow from draft
 * through submission, optional return, and verification.
 *
 * Cross-module column references (plain integers — no DB FK):
 *   institution_semester_id        — AcademicCalendar
 *   operational_period_id          — AcademicCalendar
 *   class_group_id                 — AcademicManagement
 *   creator_staff_profile_id       — Staff
 *   verified_by_staff_profile_id   — Staff
 *   parent_sheet_id                — self (previous sheet for corrections)
 */
final class AttendanceSheet extends Model
{
    protected $table = 'student_attendance_sheets';

    /** @var list<string> */
    protected $fillable = [
        'institution_semester_id',
        'operational_period_id',
        'class_group_id',
        'attendance_date',
        'status',
        'return_reason',
        'creator_staff_profile_id',
        'submitted_at',
        'verified_at',
        'verified_by_staff_profile_id',
        'parent_sheet_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status' => SheetStatus::class,
        'attendance_date' => 'date',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /** @return HasMany<AttendanceRecord, $this> */
    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'sheet_id');
    }

    /** Whether the teacher can still edit this sheet. */
    public function isEditable(): bool
    {
        $status = $this->status instanceof SheetStatus
            ? $this->status
            : SheetStatus::from((string) $this->status);

        return $status->isEditable();
    }

    /** Whether the sheet is awaiting secretary review. */
    public function awaitingReview(): bool
    {
        $status = $this->status instanceof SheetStatus
            ? $this->status
            : SheetStatus::from((string) $this->status);

        return $status->awaitingReview();
    }

    /** Whether corrections are allowed (reopened state). */
    public function allowsCorrection(): bool
    {
        $status = $this->status instanceof SheetStatus
            ? $this->status
            : SheetStatus::from((string) $this->status);

        return $status->allowsCorrection();
    }

    /**
     * @param  Builder<AttendanceSheet>  $query
     * @return Builder<AttendanceSheet>
     */
    public function scopeForSemester(Builder $query, int $semesterId): Builder
    {
        return $query->where('institution_semester_id', $semesterId);
    }

    /**
     * @param  Builder<AttendanceSheet>  $query
     * @return Builder<AttendanceSheet>
     */
    public function scopeForClassGroup(Builder $query, int $classGroupId): Builder
    {
        return $query->where('class_group_id', $classGroupId);
    }

    /**
     * @param  Builder<AttendanceSheet>  $query
     * @return Builder<AttendanceSheet>
     */
    public function scopeAwaitingReview(Builder $query): Builder
    {
        return $query->where('status', SheetStatus::Submitted->value);
    }
}

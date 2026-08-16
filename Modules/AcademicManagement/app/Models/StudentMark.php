<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AcademicManagement\Enums\MarkExceptionStatus;

/**
 * One student's mark for one assessment within a mark sheet.
 *
 * Either score or exception_status is set — not both.
 *   score != null && exception_status == null → scored
 *   score == null && exception_status != null → absent/exempt/medical
 *
 * correction_of_id chains corrections: a corrected row points to the
 * original. The original is never deleted — immutable audit trail.
 *
 * corrected_by_staff_profile_id is a plain cross-module integer (no DB FK).
 */
final class StudentMark extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'mark_sheet_id',
        'enrollment_id',
        'assessment_definition_id',
        'score',
        'exception_status',
        'teacher_note',
        'correction_of_id',
        'corrected_by_staff_profile_id',
        'corrected_at',
        'correction_reason',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'score' => 'float',
        'exception_status' => MarkExceptionStatus::class,
        'corrected_at' => 'datetime',
    ];

    /** @return BelongsTo<MarkSheet, $this> */
    public function markSheet(): BelongsTo
    {
        return $this->belongsTo(MarkSheet::class);
    }

    /** @return BelongsTo<StudentEnrollment, $this> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    /** @return BelongsTo<AssessmentDefinition, $this> */
    public function assessmentDefinition(): BelongsTo
    {
        return $this->belongsTo(AssessmentDefinition::class);
    }

    /** @return BelongsTo<StudentMark, $this> */
    public function correctionOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'correction_of_id');
    }

    public function isScored(): bool
    {
        return $this->score !== null;
    }

    public function isException(): bool
    {
        return $this->exception_status !== null;
    }

    public function isCorrection(): bool
    {
        return $this->correction_of_id !== null;
    }

    /**
     * @param  Builder<StudentMark>  $query
     * @return Builder<StudentMark>
     */
    public function scopeForSheet(Builder $query, int $markSheetId): Builder
    {
        return $query->where('mark_sheet_id', $markSheetId);
    }

    /**
     * @param  Builder<StudentMark>  $query
     * @return Builder<StudentMark>
     */
    public function scopeOriginalOnly(Builder $query): Builder
    {
        return $query->whereNull('correction_of_id');
    }
}

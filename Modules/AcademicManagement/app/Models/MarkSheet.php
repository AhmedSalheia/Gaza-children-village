<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\AcademicManagement\Enums\MarkSheetStatus;

/**
 * The workflow aggregate for teacher → secretary → principal mark review.
 *
 * One MarkSheet ties a TeachingAssignment to a MarkEntryWindow (or to a
 * semester when no window is configured). It is the unit of review.
 *
 * Cross-module plain integers (no DB FK):
 *   institution_semester_id, submitted_by_staff_profile_id,
 *   verified_by_staff_profile_id, approved_by_staff_profile_id,
 *   returned_by_staff_profile_id
 *
 * Within-module FKs:
 *   class_group_id, subject_offering_id, teaching_assignment_id,
 *   mark_entry_window_id (nullable), grading_scale_id (nullable),
 *   superseded_by_id (nullable self-ref)
 */
final class MarkSheet extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'institution_semester_id',
        'class_group_id',
        'subject_offering_id',
        'teaching_assignment_id',
        'mark_entry_window_id',
        'grading_scale_id',
        'version',
        'status',
        'submitted_by_staff_profile_id',
        'submitted_at',
        'verified_by_staff_profile_id',
        'verified_at',
        'approved_by_staff_profile_id',
        'approved_at',
        'returned_by_staff_profile_id',
        'returned_at',
        'return_reason',
        'superseded_by_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status'       => MarkSheetStatus::class,
        'submitted_at' => 'datetime',
        'verified_at'  => 'datetime',
        'approved_at'  => 'datetime',
        'returned_at'  => 'datetime',
        'version'      => 'integer',
    ];

    /** @return BelongsTo<ClassGroup, $this> */
    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    /** @return BelongsTo<InstitutionSubjectOffering, $this> */
    public function subjectOffering(): BelongsTo
    {
        return $this->belongsTo(InstitutionSubjectOffering::class);
    }

    /** @return BelongsTo<TeachingAssignment, $this> */
    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class);
    }

    /** @return BelongsTo<MarkEntryWindow, $this> */
    public function markEntryWindow(): BelongsTo
    {
        return $this->belongsTo(MarkEntryWindow::class);
    }

    /** @return BelongsTo<GradingScale, $this> */
    public function gradingScale(): BelongsTo
    {
        return $this->belongsTo(GradingScale::class);
    }

    /** @return HasMany<StudentMark, $this> */
    public function studentMarks(): HasMany
    {
        return $this->hasMany(StudentMark::class);
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    public function isPublished(): bool
    {
        return $this->status->isPublished();
    }

    /**
     * @param  Builder<MarkSheet>  $query
     * @return Builder<MarkSheet>
     */
    public function scopeForSemester(Builder $query, int $institutionSemesterId): Builder
    {
        return $query->where('institution_semester_id', $institutionSemesterId);
    }

    /**
     * @param  Builder<MarkSheet>  $query
     * @return Builder<MarkSheet>
     */
    public function scopeForClassGroup(Builder $query, int $classGroupId): Builder
    {
        return $query->where('class_group_id', $classGroupId);
    }

    /**
     * @param  Builder<MarkSheet>  $query
     * @return Builder<MarkSheet>
     */
    public function scopeNotSuperseded(Builder $query): Builder
    {
        return $query->where('status', '!=', MarkSheetStatus::Superseded->value);
    }
}

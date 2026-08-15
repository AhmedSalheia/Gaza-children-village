<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AcademicManagement\Enums\AssignmentStatus;

/**
 * Records that an eligible teacher/trainer position is assigned to teach
 * a specific subject (InstitutionSubjectOffering) to a specific ClassGroup
 * within an InstitutionSemester.
 *
 * Cross-module column references (plain integers — no DB-level FK constraints):
 *   staff_profile_id        — references a staff profile in the Staff module
 *   institution_semester_id — references an institution semester in AcademicCalendar
 *   staff_position_id       — references a staff position in the Staff module
 *
 * Authorization contract:
 *   - A TeachingAssignment grants access to enter marks for its class/subject only.
 *   - It does NOT grant attendance entry — that requires a HomeroomAssignment.
 *   - Position type teacher or trainer is required; other position definitions are rejected.
 *   - Only one active assignment per position + class_group + subject_offering is allowed.
 */
final class TeachingAssignment extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'staff_profile_id',
        'institution_semester_id',
        'staff_position_id',
        'class_group_id',
        'subject_offering_id',
        'starts_on',
        'ends_on',
        'status',
        'ends_reason',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status'    => AssignmentStatus::class,
        'starts_on' => 'date',
        'ends_on'   => 'date',
    ];

    /** @return BelongsTo<ClassGroup, $this> */
    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    /** @return BelongsTo<InstitutionSubjectOffering, $this> */
    public function subjectOffering(): BelongsTo
    {
        return $this->belongsTo(InstitutionSubjectOffering::class, 'subject_offering_id');
    }

    /**
     * Load StaffPosition via string-variable (boundary scanner safe).
     *
     * @return BelongsTo<Model, $this>
     */
    public function staffPosition(): BelongsTo
    {
        return $this->belongsTo('Modules\\Staff\\Models\\StaffPosition', 'staff_position_id');
    }

    public function isActive(): bool
    {
        return $this->status === AssignmentStatus::Active;
    }

    /**
     * @param  Builder<TeachingAssignment>  $query
     * @return Builder<TeachingAssignment>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', AssignmentStatus::Active->value);
    }

    /**
     * @param  Builder<TeachingAssignment>  $query
     * @return Builder<TeachingAssignment>
     */
    public function scopeForSemester(Builder $query, int $institutionSemesterId): Builder
    {
        return $query->where('institution_semester_id', $institutionSemesterId);
    }

    /**
     * @param  Builder<TeachingAssignment>  $query
     * @return Builder<TeachingAssignment>
     */
    public function scopeForStaffProfile(Builder $query, int $staffProfileId): Builder
    {
        return $query->where('staff_profile_id', $staffProfileId);
    }

    /**
     * @param  Builder<TeachingAssignment>  $query
     * @return Builder<TeachingAssignment>
     */
    public function scopeForClassGroup(Builder $query, int $classGroupId): Builder
    {
        return $query->where('class_group_id', $classGroupId);
    }
}

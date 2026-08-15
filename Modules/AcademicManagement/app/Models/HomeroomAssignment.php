<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AcademicManagement\Enums\AssignmentStatus;

/**
 * Designates a lead or co-lead homeroom teacher for a ClassGroup within an
 * InstitutionSemester.
 *
 * Cross-module column references (plain integers — no DB-level FK constraints):
 *   staff_profile_id        — references a staff profile in the Staff module
 *   institution_semester_id — references an institution semester in AcademicCalendar
 *   staff_position_id       — references a staff position in the Staff module
 *
 * Authorization contract:
 *   - A HomeroomAssignment grants attendance-entry rights for its class group ONLY.
 *   - It does NOT grant marks access — that requires a matching TeachingAssignment.
 *   - At most one active lead (is_co_lead = false) per class group per semester.
 *   - Co-leads (is_co_lead = true) may be multiple but must be explicit.
 */
final class HomeroomAssignment extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'staff_profile_id',
        'institution_semester_id',
        'staff_position_id',
        'class_group_id',
        'is_co_lead',
        'starts_on',
        'ends_on',
        'status',
        'ends_reason',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status'     => AssignmentStatus::class,
        'is_co_lead' => 'boolean',
        'starts_on'  => 'date',
        'ends_on'    => 'date',
    ];

    /** @return BelongsTo<ClassGroup, $this> */
    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
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

    public function isLead(): bool
    {
        return ! $this->is_co_lead;
    }

    /**
     * @param  Builder<HomeroomAssignment>  $query
     * @return Builder<HomeroomAssignment>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', AssignmentStatus::Active->value);
    }

    /**
     * @param  Builder<HomeroomAssignment>  $query
     * @return Builder<HomeroomAssignment>
     */
    public function scopeForSemester(Builder $query, int $institutionSemesterId): Builder
    {
        return $query->where('institution_semester_id', $institutionSemesterId);
    }

    /**
     * @param  Builder<HomeroomAssignment>  $query
     * @return Builder<HomeroomAssignment>
     */
    public function scopeForClassGroup(Builder $query, int $classGroupId): Builder
    {
        return $query->where('class_group_id', $classGroupId);
    }

    /**
     * @param  Builder<HomeroomAssignment>  $query
     * @return Builder<HomeroomAssignment>
     */
    public function scopeLeadsOnly(Builder $query): Builder
    {
        return $query->where('is_co_lead', false);
    }
}

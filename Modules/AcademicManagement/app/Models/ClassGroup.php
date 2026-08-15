<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AcademicManagement\Database\Factories\ClassGroupFactory;
use Modules\AcademicManagement\Enums\ClassGroupLifecycleStatus;

/**
 * A cohort of students (section) for a given academic level within an
 * InstitutionSemester and OperationalPeriod.
 *
 * institution_semester_id and operational_period_id are plain integers —
 * cross-module references to AcademicCalendar (no DB-level FK).
 *
 * The institution is derived through the InstitutionSemester chain. The
 * institutionSemester() and institution() helpers use string-variable class
 * references to avoid cross-module imports flagged by the boundary scanner.
 *
 * The stable code is unique within the institution semester (composite index).
 */
final class ClassGroup extends Model
{
    /** @use HasFactory<ClassGroupFactory> */
    use HasFactory;

    protected static function newFactory(): ClassGroupFactory
    {
        return ClassGroupFactory::new();
    }

    /**
     * code is excluded from $fillable — set by action.
     *
     * @var list<string>
     */
    protected $fillable = [
        'institution_semester_id',
        'operational_period_id',
        'academic_level_id',
        'classroom_id',
        'name_en',
        'name_ar',
        'capacity',
        'lifecycle_status',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'lifecycle_status' => ClassGroupLifecycleStatus::class,
        'capacity' => 'integer',
    ];

    /** @return BelongsTo<AcademicLevel, $this> */
    public function academicLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicLevel::class);
    }

    /** @return BelongsTo<Classroom, $this> */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Load the parent InstitutionSemester via string-variable (boundary scanner safe).
     *
     * @return BelongsTo<Model, $this>
     */
    public function institutionSemester(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\AcademicCalendar\\Models\\InstitutionSemester',
            'institution_semester_id'
        );
    }

    /**
     * Load the parent OperationalPeriod via string-variable (boundary scanner safe).
     *
     * @return BelongsTo<Model, $this>
     */
    public function operationalPeriod(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\AcademicCalendar\\Models\\OperationalPeriod',
            'operational_period_id'
        );
    }

    /**
     * Resolve the owning Institution through the InstitutionSemester chain.
     * Returns null if the semester or institution cannot be loaded.
     */
    public function resolveInstitution(): ?object
    {
        return $this->institutionSemester?->institution;
    }

    /**
     * @param  Builder<ClassGroup>  $query
     * @return Builder<ClassGroup>
     */
    public function scopeForInstitutionSemester(Builder $query, int $institutionSemesterId): Builder
    {
        return $query->where('institution_semester_id', $institutionSemesterId);
    }

    /**
     * @param  Builder<ClassGroup>  $query
     * @return Builder<ClassGroup>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('lifecycle_status', ClassGroupLifecycleStatus::Active->value);
    }

    /**
     * @param  Builder<ClassGroup>  $query
     * @return Builder<ClassGroup>
     */
    public function scopeForPeriod(Builder $query, int $operationalPeriodId): Builder
    {
        return $query->where('operational_period_id', $operationalPeriodId);
    }

    public function isActive(): bool
    {
        return $this->lifecycle_status === ClassGroupLifecycleStatus::Active;
    }
}

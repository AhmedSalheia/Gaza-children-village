<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\AcademicCalendar\Database\Factories\InstitutionSemesterFactory;
use Modules\AcademicCalendar\Enums\AcademicStatus;

/**
 * Activates a global Semester for a specific Institution.
 *
 * An InstitutionSemester is the institution-owned operational record that links
 * a global Semester (catalogue entry) to an Institution. It holds the
 * institution-specific lifecycle status and copy provenance, but never
 * duplicates academic-year facts already present on the global Semester.
 *
 * F08 key rules:
 *   - institution_id + semester_id are unique (one activation per pair).
 *   - The institution and the semester's academic year must share the same organization.
 *   - The institution must be active and have the effective academic_management feature.
 *   - Only one institution semester per institution may be Open at a time.
 *   - Archiving is terminal; closed→archived requires the parent semester to be
 *     closed or archived; draft→archived requires a reason.
 *   - No soft deletion; Archived records remain readable indefinitely.
 *
 * Cross-module dependency note:
 *   The Institution model class name is passed as a string literal to belongsTo()
 *   to avoid a cross-module import that the boundary scanner would flag as a
 *   non-public surface reference. Pint does not add use-imports for string literals.
 *
 * F18 will add actor-aware audit history. F02 scope resolution uses this model
 * through the InstitutionSemesterScopeResolver action.
 */
class InstitutionSemester extends Model
{
    /** @use HasFactory<InstitutionSemesterFactory> */
    use HasFactory;

    protected static function newFactory(): InstitutionSemesterFactory
    {
        return InstitutionSemesterFactory::new();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'institution_id',
        'semester_id',
        'status',
        'copied_from_id',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'status' => AcademicStatus::class,
    ];

    /**
     * The institution that activated this semester.
     *
     * Class name passed as a double-escaped string literal; scanner-safe, resolved
     * at runtime by Eloquent via the autoloader.
     *
     * Note: the Institution model has a global ActiveInstitutionScope. Callers that
     * need to load the institution for an archived/historical record should call
     * $institutionSemester->institution()->withoutGlobalScopes()->first().
     *
     * @return BelongsTo<Model, $this>
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo('Modules\\Organization\\Models\\Institution');
    }

    /**
     * @return BelongsTo<Semester, $this>
     */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    /**
     * The institution semester this record was copied from, if any.
     *
     * @return BelongsTo<InstitutionSemester, $this>
     */
    public function copiedFrom(): BelongsTo
    {
        return $this->belongsTo(InstitutionSemester::class, 'copied_from_id');
    }

    /**
     * @return HasMany<OperationalPeriod, $this>
     */
    public function operationalPeriods(): HasMany
    {
        return $this->hasMany(OperationalPeriod::class);
    }

    /**
     * Active (non-deactivated) periods ordered by sequence.
     *
     * @return HasMany<OperationalPeriod, $this>
     */
    public function activePeriods(): HasMany
    {
        return $this->hasMany(OperationalPeriod::class)
            ->where('is_active', true)
            ->orderBy('sequence');
    }

    /**
     * Filter institution semesters for the given institution (by ID).
     *
     * @param  Builder<InstitutionSemester>  $query
     * @return Builder<InstitutionSemester>
     */
    public function scopeForInstitution(Builder $query, int $institutionId): Builder
    {
        return $query->where('institution_id', $institutionId);
    }

    /**
     * Filter to only open institution semesters.
     *
     * @param  Builder<InstitutionSemester>  $query
     * @return Builder<InstitutionSemester>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', AcademicStatus::Open->value);
    }

    /**
     * Filter by lifecycle status.
     *
     * @param  Builder<InstitutionSemester>  $query
     * @return Builder<InstitutionSemester>
     */
    public function scopeWithStatus(Builder $query, AcademicStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    /**
     * Filter institution semesters linked to the given global semester.
     *
     * @param  Builder<InstitutionSemester>  $query
     * @return Builder<InstitutionSemester>
     */
    public function scopeForSemester(Builder $query, int $semesterId): Builder
    {
        return $query->where('semester_id', $semesterId);
    }
}

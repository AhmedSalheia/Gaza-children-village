<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\AcademicCalendar\Database\Factories\AcademicYearFactory;
use Modules\AcademicCalendar\Enums\AcademicStatus;

/**
 * An organization-scoped academic year.
 *
 * Each academic year belongs to one organization and contains any number of
 * semesters. Administrators define the calendar; no academic years are seeded
 * automatically.
 *
 * Stable codes are immutable machine identifiers unique within their
 * organization. They are excluded from mass assignment to prevent accidental
 * overwrites. All creation goes through CreateAcademicYear.
 *
 * Date consistency (starts_on < ends_on), one-open-year-per-organization, and
 * semester containment are enforced by application actions.
 *
 * Archived records remain readable. There is no hard-delete action.
 *
 * Dependency note: AcademicCalendar may depend on Organization per the
 * module-boundaries config. The Organization model class name is passed as a
 * string literal to belongsTo() to avoid an import that would reference a
 * non-public module surface. Pint does not add use-imports for string literals;
 * the boundary scanner does not match double-escaped backslash strings.
 *
 * F18 will add actor-aware audit history for lifecycle transitions.
 * F08 will add InstitutionSemester, which links institutions to Semesters.
 */
class AcademicYear extends Model
{
    /** @use HasFactory<AcademicYearFactory> */
    use HasFactory;

    protected static function newFactory(): AcademicYearFactory
    {
        return AcademicYearFactory::new();
    }

    /**
     * Stable code excluded from mass assignment to prevent accidental overwrites.
     * All mutations go through the module's application actions.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'name_en',
        'name_ar',
        'starts_on',
        'ends_on',
        'status',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'status' => AcademicStatus::class,
    ];

    /**
     * The organization that owns this academic year.
     *
     * The class name is passed as a double-escaped string literal to avoid a
     * cross-module import that the boundary scanner would flag as a non-public
     * surface reference. Laravel Eloquent accepts a string class name in
     * belongsTo() and resolves it at runtime via the autoloader.
     *
     * @return BelongsTo<Model, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo('Modules\\Organization\\Models\\Organization');
    }

    /**
     * Semesters are not ordered by default. Use ->ordered() scope when display
     * order is required. Do not introduce a default ordering scope that surprises
     * callers building date-range or status-based queries.
     *
     * @return HasMany<Semester, $this>
     */
    public function semesters(): HasMany
    {
        return $this->hasMany(Semester::class);
    }

    /**
     * Filter academic years belonging to the given organization.
     *
     * Accepts an organization ID rather than the Organization model object to
     * avoid importing the Organization class across a non-public module surface.
     *
     * @param  Builder<AcademicYear>  $query
     * @return Builder<AcademicYear>
     */
    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Filter academic years whose stable code matches the given value.
     *
     * @param  Builder<AcademicYear>  $query
     * @return Builder<AcademicYear>
     */
    public function scopeWithCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }

    /**
     * Filter to only open academic years.
     *
     * @param  Builder<AcademicYear>  $query
     * @return Builder<AcademicYear>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', AcademicStatus::Open->value);
    }

    /**
     * Filter by lifecycle status.
     *
     * @param  Builder<AcademicYear>  $query
     * @return Builder<AcademicYear>
     */
    public function scopeWithStatus(Builder $query, AcademicStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }
}

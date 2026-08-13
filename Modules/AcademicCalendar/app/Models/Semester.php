<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AcademicCalendar\Database\Factories\SemesterFactory;
use Modules\AcademicCalendar\Enums\AcademicStatus;

/**
 * A semester within an academic year.
 *
 * Any positive number of semesters is allowed. Summer semesters and
 * exceptional semesters are representable. Never assume exactly two semesters.
 *
 * Semantic codes such as S1, S2, SUMMER are examples only; the database does
 * not enforce any fixed code catalogue.
 *
 * Stable codes are immutable machine identifiers unique within their academic
 * year. Sequence numbers are unique and positive within their year. Both are
 * excluded from mass assignment.
 *
 * Date constraints (containment within year, non-overlapping with siblings)
 * are enforced by application actions, not the database.
 *
 * An archived year prevents ordinary mutation of its semesters. Archived
 * semesters remain readable. There is no hard-delete action.
 *
 * This is a global Semester (the calendar definition). F08 will introduce
 * InstitutionSemester, which links an institution to a Semester for
 * institution-specific lifecycle management. Do not conflate them.
 *
 * F18 will add actor-aware audit history for lifecycle transitions.
 */
class Semester extends Model
{
    /** @use HasFactory<SemesterFactory> */
    use HasFactory;

    protected static function newFactory(): SemesterFactory
    {
        return SemesterFactory::new();
    }

    /**
     * Stable code and sequence are excluded from mass assignment.
     * All mutations go through the module's application actions.
     *
     * @var list<string>
     */
    protected $fillable = [
        'academic_year_id',
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
        'sequence' => 'integer',
        'starts_on' => 'date',
        'ends_on' => 'date',
        'status' => AcademicStatus::class,
    ];

    /**
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Filter semesters belonging to the given academic year.
     *
     * @param  Builder<Semester>  $query
     * @return Builder<Semester>
     */
    public function scopeForYear(Builder $query, AcademicYear $year): Builder
    {
        return $query->where('academic_year_id', $year->id);
    }

    /**
     * Order semesters by sequence ascending.
     *
     * Not applied by default (no global scope). Use explicitly when display
     * order matters.
     *
     * @param  Builder<Semester>  $query
     * @return Builder<Semester>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sequence');
    }

    /**
     * Filter to only open semesters.
     *
     * @param  Builder<Semester>  $query
     * @return Builder<Semester>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', AcademicStatus::Open->value);
    }

    /**
     * Filter by stable code.
     *
     * @param  Builder<Semester>  $query
     * @return Builder<Semester>
     */
    public function scopeWithCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }

    /**
     * Filter by lifecycle status.
     *
     * @param  Builder<Semester>  $query
     * @return Builder<Semester>
     */
    public function scopeWithStatus(Builder $query, AcademicStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }
}

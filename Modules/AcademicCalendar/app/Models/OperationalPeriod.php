<?php

declare(strict_types=1);

namespace Modules\AcademicCalendar\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AcademicCalendar\Database\Factories\OperationalPeriodFactory;

/**
 * A named time block within an InstitutionSemester.
 *
 * Common examples are morning and afternoon shifts, but administrators may
 * configure any number of periods. The sequence is unique within the parent
 * institution semester. The stable code is unique within the institution semester.
 *
 * F08 key rules:
 *   - Ordinary creation and modification are allowed only while the parent
 *     institution semester is Draft.
 *   - Active periods in the same institution semester must not overlap (checked
 *     by application actions; adjacent boundaries are permitted).
 *   - Overnight periods (ends_at ≤ starts_at) are rejected.
 *   - Deactivation is used instead of hard deletion; inactive periods remain
 *     queryable for historical reference.
 *   - No soft deletion column.
 *
 * starts_at and ends_at are stored as TIME values (HH:MM:SS). They are returned
 * as raw strings by Eloquent; callers use string comparison or strtotime() for
 * ordering and overlap checks.
 *
 * F18 will add actor-aware audit history.
 */
class OperationalPeriod extends Model
{
    /** @use HasFactory<OperationalPeriodFactory> */
    use HasFactory;

    protected static function newFactory(): OperationalPeriodFactory
    {
        return OperationalPeriodFactory::new();
    }

    /**
     * Stable code and sequence are excluded from mass assignment.
     * All mutations go through application actions.
     *
     * @var list<string>
     */
    protected $fillable = [
        'institution_semester_id',
        'name_en',
        'name_ar',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<InstitutionSemester, $this>
     */
    public function institutionSemester(): BelongsTo
    {
        return $this->belongsTo(InstitutionSemester::class);
    }

    /**
     * Filter only active periods.
     *
     * @param  Builder<OperationalPeriod>  $query
     * @return Builder<OperationalPeriod>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Order periods by sequence ascending.
     *
     * @param  Builder<OperationalPeriod>  $query
     * @return Builder<OperationalPeriod>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sequence');
    }

    /**
     * Filter by stable code within the parent institution semester.
     *
     * @param  Builder<OperationalPeriod>  $query
     * @return Builder<OperationalPeriod>
     */
    public function scopeWithCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }
}

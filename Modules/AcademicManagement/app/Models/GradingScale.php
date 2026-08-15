<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Institution-scoped grading scale.
 *
 * institution_id is a plain cross-module integer (no DB-level FK).
 * Grade tiers live in the grading_scale_grades table (GradingScaleGrade).
 */
final class GradingScale extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'institution_id',
        'name_ar',
        'name_en',
        'is_active',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** @return HasMany<GradingScaleGrade, $this> */
    public function grades(): HasMany
    {
        return $this->hasMany(GradingScaleGrade::class)->orderBy('sequence');
    }

    /**
     * @param  Builder<GradingScale>  $query
     * @return Builder<GradingScale>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<GradingScale>  $query
     * @return Builder<GradingScale>
     */
    public function scopeForInstitution(Builder $query, int $institutionId): Builder
    {
        return $query->where('institution_id', $institutionId);
    }

    /**
     * Find the matching grade tier for a numeric score.
     * Returns null if no grade covers the given score.
     */
    public function gradeForScore(float $score): ?GradingScaleGrade
    {
        return $this->grades
            ->first(fn (GradingScaleGrade $g) => $score >= $g->min_score && $score <= $g->max_score);
    }
}

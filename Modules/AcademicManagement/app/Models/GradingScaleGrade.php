<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single grade tier within a GradingScale.
 *
 * Ranges are inclusive: a score s matches when min_score <= s <= max_score.
 */
final class GradingScaleGrade extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'grading_scale_id',
        'code',
        'name_ar',
        'name_en',
        'min_score',
        'max_score',
        'is_passing',
        'sequence',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'min_score'  => 'float',
        'max_score'  => 'float',
        'is_passing' => 'boolean',
        'sequence'   => 'integer',
    ];

    /** @return BelongsTo<GradingScale, $this> */
    public function gradingScale(): BelongsTo
    {
        return $this->belongsTo(GradingScale::class);
    }

    public function covers(float $score): bool
    {
        return $score >= $this->min_score && $score <= $this->max_score;
    }
}

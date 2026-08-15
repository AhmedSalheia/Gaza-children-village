<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable per-student per-subject result row written at publication time.
 *
 * student_profile_id, enrollment_id, subject_offering_id are plain cross-module
 * integers (no DB FK). Never mutated after insert.
 *
 * completeness_status values:
 *   complete      — all assessments have a score or valid exception
 *   incomplete    — at least one assessment is missing both score and exception
 *   all_absent    — every assessment has an exception status (no numeric scores)
 *   no_assessments — no assessment definitions found for this context
 */
final class ResultPublicationRow extends Model
{
    protected $table = 'result_publication_rows';

    protected $fillable = [
        'result_publication_id',
        'student_profile_id',
        'enrollment_id',
        'subject_offering_id',
        'raw_total_score',
        'raw_max_possible',
        'normalized_score',
        'grade_code',
        'grade_name_ar',
        'is_passing',
        'completeness_status',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'raw_total_score'  => 'float',
        'raw_max_possible' => 'float',
        'normalized_score' => 'float',
        'is_passing'       => 'boolean',
    ];

    /** @return BelongsTo<ResultPublication, $this> */
    public function publication(): BelongsTo
    {
        return $this->belongsTo(ResultPublication::class, 'result_publication_id');
    }
}

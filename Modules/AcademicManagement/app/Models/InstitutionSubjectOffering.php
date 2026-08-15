<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AcademicManagement\Database\Factories\InstitutionSubjectOfferingFactory;

/**
 * Records that a Subject is offered by an institution in a specific semester.
 *
 * institution_semester_id is a plain integer cross-module reference.
 * subject_id is a within-module FK.
 *
 * No teacher assignment here — that is a future teaching-assignments feature.
 */
final class InstitutionSubjectOffering extends Model
{
    /** @use HasFactory<InstitutionSubjectOfferingFactory> */
    use HasFactory;

    protected static function newFactory(): InstitutionSubjectOfferingFactory
    {
        return InstitutionSubjectOfferingFactory::new();
    }

    /** @var list<string> */
    protected $fillable = [
        'institution_semester_id',
        'subject_id',
    ];

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
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
     * @param  Builder<InstitutionSubjectOffering>  $query
     * @return Builder<InstitutionSubjectOffering>
     */
    public function scopeForInstitutionSemester(Builder $query, int $institutionSemesterId): Builder
    {
        return $query->where('institution_semester_id', $institutionSemesterId);
    }
}

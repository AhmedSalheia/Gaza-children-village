<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AcademicManagement\Enums\AssessmentType;

/**
 * Defines a single assessable task within a semester/class/subject context.
 *
 * institution_semester_id is a plain cross-module integer (no DB FK).
 * class_group_id and subject_offering_id are within-module nullable FKs.
 *
 * A definition with status 'archived' cannot be used in new mark sheets.
 * Published results that reference an archived definition remain intact.
 */
final class AssessmentDefinition extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'institution_semester_id',
        'class_group_id',
        'subject_offering_id',
        'name_ar',
        'name_en',
        'assessment_type',
        'max_score',
        'weight',
        'assessment_date',
        'status',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'assessment_type' => AssessmentType::class,
        'max_score' => 'float',
        'weight' => 'float',
        'assessment_date' => 'date',
    ];

    /** @return BelongsTo<ClassGroup, $this> */
    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    /** @return BelongsTo<InstitutionSubjectOffering, $this> */
    public function subjectOffering(): BelongsTo
    {
        return $this->belongsTo(InstitutionSubjectOffering::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    /**
     * @param  Builder<AssessmentDefinition>  $query
     * @return Builder<AssessmentDefinition>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * @param  Builder<AssessmentDefinition>  $query
     * @return Builder<AssessmentDefinition>
     */
    public function scopeForSemester(Builder $query, int $institutionSemesterId): Builder
    {
        return $query->where('institution_semester_id', $institutionSemesterId);
    }

    /**
     * @param  Builder<AssessmentDefinition>  $query
     * @return Builder<AssessmentDefinition>
     */
    public function scopeForClassGroup(Builder $query, int $classGroupId): Builder
    {
        return $query->where('class_group_id', $classGroupId);
    }

    /**
     * @param  Builder<AssessmentDefinition>  $query
     * @return Builder<AssessmentDefinition>
     */
    public function scopeForSubjectOffering(Builder $query, int $subjectOfferingId): Builder
    {
        return $query->where('subject_offering_id', $subjectOfferingId);
    }
}

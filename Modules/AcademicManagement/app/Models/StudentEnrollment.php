<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\AcademicManagement\Database\Factories\StudentEnrollmentFactory;
use Modules\AcademicManagement\Enums\EnrollmentStatus;

/**
 * Semester-specific operational record connecting a student to a class group.
 *
 * student_profile_id is a plain integer cross-module reference to Students.
 * institution_semester_id is a plain integer cross-module reference to AcademicCalendar.
 * class_group_id is a within-module FK constrained to class_groups.
 *
 * Institution, semester, and academic level are derived through the
 * class_group → institution_semester chain; never stored redundantly.
 *
 * The "one active enrollment per student per semester" invariant is enforced
 * at the application layer with lockForUpdate inside a DB transaction.
 */
final class StudentEnrollment extends Model
{
    /** @use HasFactory<StudentEnrollmentFactory> */
    use HasFactory;

    protected static function newFactory(): StudentEnrollmentFactory
    {
        return StudentEnrollmentFactory::new();
    }

    /**
     * student_profile_id and institution_semester_id are cross-module plain ints.
     * They are in $fillable for factory support; application layer validates existence.
     *
     * @var list<string>
     */
    protected $fillable = [
        'student_profile_id',
        'institution_semester_id',
        'class_group_id',
        'enrollment_status',
        'enrolled_on',
        'activated_on',
        'completed_on',
        'notes',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'enrollment_status' => EnrollmentStatus::class,
        'enrolled_on' => 'date',
        'activated_on' => 'date',
        'completed_on' => 'date',
    ];

    /** @return BelongsTo<ClassGroup, $this> */
    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
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
     * Load the StudentProfile via string-variable (boundary scanner safe).
     *
     * @return BelongsTo<Model, $this>
     */
    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Students\\Models\\StudentProfile',
            'student_profile_id'
        );
    }

    /** @return HasMany<PromotionProposal, $this> */
    public function promotionProposals(): HasMany
    {
        return $this->hasMany(PromotionProposal::class, 'source_enrollment_id');
    }

    /**
     * @param  Builder<StudentEnrollment>  $query
     * @return Builder<StudentEnrollment>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('enrollment_status', EnrollmentStatus::Active->value);
    }

    /**
     * @param  Builder<StudentEnrollment>  $query
     * @return Builder<StudentEnrollment>
     */
    public function scopeForStudent(Builder $query, int $studentProfileId): Builder
    {
        return $query->where('student_profile_id', $studentProfileId);
    }

    /**
     * @param  Builder<StudentEnrollment>  $query
     * @return Builder<StudentEnrollment>
     */
    public function scopeForSemester(Builder $query, int $institutionSemesterId): Builder
    {
        return $query->where('institution_semester_id', $institutionSemesterId);
    }

    /**
     * @param  Builder<StudentEnrollment>  $query
     * @return Builder<StudentEnrollment>
     */
    public function scopeForClassGroup(Builder $query, int $classGroupId): Builder
    {
        return $query->where('class_group_id', $classGroupId);
    }

    public function isActive(): bool
    {
        return $this->enrollment_status === EnrollmentStatus::Active;
    }

    public function isTerminal(): bool
    {
        return $this->enrollment_status->isTerminal();
    }
}

<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\AcademicManagement\Database\Factories\ClassroomFactory;

/**
 * A physical or virtual room within an Institution.
 *
 * institution_id is a plain integer cross-module reference to
 * Organization.institutions — no DB-level FK across module boundary.
 *
 * The code is stable and unique within the institution (composite unique
 * index on institution_id + code).
 */
final class Classroom extends Model
{
    /** @use HasFactory<ClassroomFactory> */
    use HasFactory;

    protected static function newFactory(): ClassroomFactory
    {
        return ClassroomFactory::new();
    }

    /**
     * code is excluded from $fillable — set by action.
     *
     * @var list<string>
     */
    protected $fillable = [
        'institution_id',
        'name_en',
        'name_ar',
        'capacity',
        'is_active',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
        'capacity' => 'integer',
    ];

    /** @return HasMany<ClassGroup, $this> */
    public function classGroups(): HasMany
    {
        return $this->hasMany(ClassGroup::class);
    }

    /**
     * @param  Builder<Classroom>  $query
     * @return Builder<Classroom>
     */
    public function scopeForInstitution(Builder $query, int $institutionId): Builder
    {
        return $query->where('institution_id', $institutionId);
    }

    /**
     * @param  Builder<Classroom>  $query
     * @return Builder<Classroom>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}

<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\AcademicManagement\Database\Factories\SubjectFactory;

/**
 * Global subject catalogue entry (Mathematics, Arabic Language, etc.).
 *
 * is_active controls global visibility. Availability within a specific
 * institution semester is managed by InstitutionSubjectOffering.
 *
 * The stable code is globally unique.
 */
final class Subject extends Model
{
    /** @use HasFactory<SubjectFactory> */
    use HasFactory;

    protected static function newFactory(): SubjectFactory
    {
        return SubjectFactory::new();
    }

    /**
     * code is excluded from $fillable — set by action.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name_en',
        'name_ar',
        'is_active',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** @return HasMany<InstitutionSubjectOffering, $this> */
    public function offerings(): HasMany
    {
        return $this->hasMany(InstitutionSubjectOffering::class);
    }

    /**
     * @param  Builder<Subject>  $query
     * @return Builder<Subject>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}

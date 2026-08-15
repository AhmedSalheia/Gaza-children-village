<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\AcademicManagement\Database\Factories\AcademicLevelFactory;

/**
 * Global academic level catalogue entry (KG1, KG2, Grade1–Grade12).
 *
 * No institution-type junction at this level; is_active controls global
 * visibility. Sequence determines display order.
 */
final class AcademicLevel extends Model
{
    /** @use HasFactory<AcademicLevelFactory> */
    use HasFactory;

    protected static function newFactory(): AcademicLevelFactory
    {
        return AcademicLevelFactory::new();
    }

    /**
     * code is excluded from $fillable — generated or assigned by action only.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name_en',
        'name_ar',
        'sequence',
        'is_active',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
        'sequence' => 'integer',
    ];

    /** @return HasMany<ClassGroup, $this> */
    public function classGroups(): HasMany
    {
        return $this->hasMany(ClassGroup::class);
    }

    /**
     * @param  Builder<AcademicLevel>  $query
     * @return Builder<AcademicLevel>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<AcademicLevel>  $query
     * @return Builder<AcademicLevel>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sequence');
    }
}

<?php

declare(strict_types=1);

namespace Modules\Staff\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Staff\Database\Factories\StaffPositionFactory;
use Modules\Staff\Enums\PositionDefinition;

/**
 * Effective-dated position record for a staff member.
 *
 * One StaffProfile may hold multiple simultaneous positions at the same
 * institution (e.g. teacher + counselor), EXCEPT for the mutually exclusive
 * pair principal ↔ deputy_principal.
 *
 * Cross-module relationships (institution, institution_semester, operational_periods)
 * are exposed as plain integer IDs. ORM joins to cross-module models use
 * string-variable class references (double-backslash pattern) or are not
 * implemented — callers retrieve those records through their owning module.
 *
 * Does NOT grant permissions. Authorization is in F17/F19.
 * Teacher/trainer positions grant no class, subject, student, or mark access.
 */
final class StaffPosition extends Model
{
    /** @use HasFactory<StaffPositionFactory> */
    use HasFactory;

    protected $fillable = [];

    /** @var array<string, string> */
    protected $casts = [
        'position_definition' => PositionDefinition::class,
        'started_on' => 'date',
        'ended_on' => 'date',
    ];

    /** @return BelongsTo<StaffProfile, $this> */
    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    /** @return BelongsTo<StaffInstitutionAssignment, $this> */
    public function staffInstitutionAssignment(): BelongsTo
    {
        return $this->belongsTo(StaffInstitutionAssignment::class);
    }

    /** @return HasMany<StaffPositionPeriod, $this> */
    public function positionPeriods(): HasMany
    {
        return $this->hasMany(StaffPositionPeriod::class);
    }

    /** Whether this position is currently active (no ended_on). */
    public function isOpen(): bool
    {
        return $this->ended_on === null;
    }

    /**
     * Filter active (not yet ended) positions.
     *
     * @param  Builder<StaffPosition>  $query
     * @return Builder<StaffPosition>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('ended_on');
    }

    /**
     * Filter positions effective on the given date.
     *
     * @param  Builder<StaffPosition>  $query
     * @return Builder<StaffPosition>
     */
    public function scopeEffectiveOn(Builder $query, \DateTimeInterface $date): Builder
    {
        $dateStr = $date->format('Y-m-d');

        return $query
            ->where('started_on', '<=', $dateStr)
            ->where(fn (Builder $q) => $q
                ->whereNull('ended_on')
                ->orWhere('ended_on', '>=', $dateStr)
            );
    }
}

<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\AcademicManagement\Enums\MarkWindowStatus;

/**
 * A time-bounded window during which teachers may enter marks.
 *
 * institution_semester_id is a plain cross-module integer (no DB FK).
 * class_group_id and subject_offering_id are nullable within-module FKs;
 * null means the window applies to all groups/subjects in the semester.
 *
 * Extension history is stored as JSON (array of {extended_at, new_closes_at,
 * reason, actor_ref}) for a full audit trail without a separate table.
 */
final class MarkEntryWindow extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'institution_semester_id',
        'class_group_id',
        'subject_offering_id',
        'name_ar',
        'name_en',
        'opens_at',
        'closes_at',
        'status',
        'extension_history',
        'created_by_staff_position_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'opens_at'          => 'datetime',
        'closes_at'         => 'datetime',
        'status'            => MarkWindowStatus::class,
        'extension_history' => 'array',
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

    /** @return HasMany<MarkSheet, $this> */
    public function markSheets(): HasMany
    {
        return $this->hasMany(MarkSheet::class);
    }

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }

    public function isCurrentlyOpen(): bool
    {
        if (! $this->isOpen()) {
            return false;
        }

        $now = now();

        return $now->gte($this->opens_at) && $now->lte($this->closes_at);
    }

    /**
     * @param  Builder<MarkEntryWindow>  $query
     * @return Builder<MarkEntryWindow>
     */
    public function scopeForSemester(Builder $query, int $institutionSemesterId): Builder
    {
        return $query->where('institution_semester_id', $institutionSemesterId);
    }

    /**
     * @param  Builder<MarkEntryWindow>  $query
     * @return Builder<MarkEntryWindow>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [MarkWindowStatus::Open->value, MarkWindowStatus::Extended->value]);
    }
}

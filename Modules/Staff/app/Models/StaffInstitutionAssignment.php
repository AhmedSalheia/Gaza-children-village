<?php

declare(strict_types=1);

namespace Modules\Staff\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Effective-dated record of a staff member's institution assignment.
 *
 * started_on and ended_on are inclusive calendar dates.
 * A null ended_on means the assignment is currently active (open-ended).
 *
 * Assignments are immutable once closed: the historical row is never
 * updated or deleted. Transfers create a new row atomically.
 */
final class StaffInstitutionAssignment extends Model
{
    protected $fillable = [];

    /** @var array<string, string> */
    protected $casts = [
        'started_on' => 'date',
        'ended_on' => 'date',
    ];

    /** @return BelongsTo<StaffProfile, $this> */
    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    public function isActive(): bool
    {
        return $this->ended_on === null;
    }
}

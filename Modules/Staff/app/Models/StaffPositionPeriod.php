<?php

declare(strict_types=1);

namespace Modules\Staff\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Explicit operational-period scope link for a staff position.
 *
 * Each row grants the position access to one operational period.
 * "All periods" is represented by having one row per active approved period —
 * there is no wildcard; adding a new period to the institution semester does NOT
 * automatically extend access.
 *
 * operational_period_id references AcademicCalendar.OperationalPeriod. It is
 * stored as a plain integer (no ORM relationship) to comply with the module
 * boundary rules. Callers retrieve the period through AcademicCalendar actions.
 */
final class StaffPositionPeriod extends Model
{
    protected $fillable = [];

    /** @return BelongsTo<StaffPosition, $this> */
    public function staffPosition(): BelongsTo
    {
        return $this->belongsTo(StaffPosition::class);
    }
}

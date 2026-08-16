<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Per-institution-semester configuration controlling what attendance data
 * is visible to guardians.
 *
 * institution_semester_id is a plain cross-module integer (no DB FK).
 * One row per institution semester; unique constraint enforced in migration.
 *
 * detail_level values:
 *   summary_only — guardian sees only aggregate present/absent/late counts
 *   daily_status — guardian sees individual date-level status codes
 */
final class AttendancePublicationPolicy extends Model
{
    protected $table = 'attendance_publication_policies';

    protected $fillable = [
        'institution_semester_id',
        'enabled',
        'detail_level',
        'publish_delay_days',
        'show_reason',
        'show_arrival_departure',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'enabled' => 'boolean',
        'show_reason' => 'boolean',
        'show_arrival_departure' => 'boolean',
        'publish_delay_days' => 'integer',
    ];

    public function isEnabled(): bool
    {
        return (bool) $this->enabled;
    }
}

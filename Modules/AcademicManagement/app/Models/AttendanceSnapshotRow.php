<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable per-student per-day attendance row written at snapshot publication.
 *
 * student_profile_id and enrollment_id are plain cross-module integers (no DB FK).
 * reason and arrived_at are null unless the policy at publication time permitted them.
 */
final class AttendanceSnapshotRow extends Model
{
    protected $table = 'attendance_snapshot_rows';

    protected $fillable = [
        'snapshot_id',
        'student_profile_id',
        'enrollment_id',
        'attendance_date',
        'status_code',
        'reason',
        'arrived_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'attendance_date' => 'date',
    ];

    /** @return BelongsTo<AttendancePublicationSnapshot, $this> */
    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(AttendancePublicationSnapshot::class, 'snapshot_id');
    }
}

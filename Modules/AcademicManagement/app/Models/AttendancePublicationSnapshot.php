<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Versioned immutable snapshot of verified attendance data for a class group.
 *
 * institution_semester_id, class_group_id, publisher_staff_profile_id,
 * revoked_by_staff_profile_id are plain cross-module integers (no DB FK).
 *
 * Policy fields (detail_level, show_reason, show_arrival_departure) are captured
 * at publication time so the snapshot is self-describing if the policy later changes.
 */
final class AttendancePublicationSnapshot extends Model
{
    protected $table = 'attendance_publication_snapshots';

    protected $fillable = [
        'institution_semester_id',
        'class_group_id',
        'period_from',
        'period_to',
        'version',
        'superseded_by_id',
        'detail_level',
        'show_reason',
        'show_arrival_departure',
        'status',
        'published_at',
        'publisher_staff_profile_id',
        'revoked_at',
        'revoke_reason',
        'revoked_by_staff_profile_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'published_at'          => 'datetime',
        'revoked_at'            => 'datetime',
        'period_from'           => 'date',
        'period_to'             => 'date',
        'show_reason'           => 'boolean',
        'show_arrival_departure' => 'boolean',
        'version'               => 'integer',
    ];

    /** @return HasMany<AttendanceSnapshotRow, $this> */
    public function rows(): HasMany
    {
        return $this->hasMany(AttendanceSnapshotRow::class, 'snapshot_id');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}

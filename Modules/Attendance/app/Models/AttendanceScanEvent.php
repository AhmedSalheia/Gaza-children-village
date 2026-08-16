<?php

declare(strict_types=1);

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A single QR scan (or manual token entry) that awaits secretary review.
 *
 * Scan events are NEVER automatically promoted to official attendance.
 * A secretary must review each event and explicitly accept or reject it.
 *
 * The raw token string is NEVER stored here — only qr_credential_id.
 */
final class AttendanceScanEvent extends Model
{
    protected $table = 'attendance_scan_events';

    protected $fillable = [];

    /** @var array<string, string> */
    protected $casts = [
        'scanned_at' => 'datetime',
        'scan_date' => 'date:Y-m-d',
        'reviewed_at' => 'datetime',
    ];

    public function isPending(): bool
    {
        return $this->processing_status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->processing_status === 'accepted';
    }
}

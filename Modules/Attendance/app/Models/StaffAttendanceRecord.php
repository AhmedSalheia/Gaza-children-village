<?php

declare(strict_types=1);

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;

final class StaffAttendanceRecord extends Model
{
    protected $table = 'staff_attendance_records';

    protected $fillable = [
        'staff_profile_id',
        'institution_semester_id',
        'operational_period_id',
        'record_date',
        'status_code',
        'reason',
        'confirmed_arrived_at',
        'confirmed_departed_at',
        'scanned_arrived_at',
        'scanned_departed_at',
        'is_verified',
        'verified_at',
        'verified_by_staff_profile_id',
        'correction_cycle',
        'source',
        'creator_staff_profile_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'record_date'  => 'date:Y-m-d',
        'is_verified'  => 'boolean',
        'verified_at'  => 'datetime',
        'correction_cycle' => 'integer',
    ];

    public function isFilled(): bool
    {
        return $this->status_code !== null;
    }

    public function isVerified(): bool
    {
        return (bool) $this->is_verified;
    }

    public function wasCorrected(): bool
    {
        return $this->correction_cycle > 0;
    }
}

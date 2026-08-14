<?php

declare(strict_types=1);

namespace Modules\Staff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Staff\Database\Factories\StaffProfileFactory;
use Modules\Staff\Enums\EmploymentStatus;

/**
 * Employment record for a staff member.
 *
 * One-to-one with Person (a Person has at most one StaffProfile, enforced by
 * the unique index on person_id). May exist without a StaffAccount (guards
 * and non-login staff are valid StaffProfiles). May also exist without a
 * current institution assignment.
 *
 * Does not contain passwords, permissions, roles, semester positions, or
 * period scopes. Those are added in F16/F17.
 */
final class StaffProfile extends Model
{
    /** @use HasFactory<StaffProfileFactory> */
    use HasFactory;

    protected $fillable = [];

    /** @var array<string, string> */
    protected $casts = [
        'employment_status' => EmploymentStatus::class,
        'hired_on' => 'date',
        'ended_on' => 'date',
    ];

    /** @return HasMany<StaffInstitutionAssignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(StaffInstitutionAssignment::class);
    }

    /** @return HasOne<StaffInstitutionAssignment, $this> */
    public function currentAssignment(): HasOne
    {
        return $this->hasOne(StaffInstitutionAssignment::class)
            ->whereNull('ended_on');
    }

    public function isActive(): bool
    {
        return $this->employment_status === EmploymentStatus::Active;
    }
}

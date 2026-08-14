<?php

declare(strict_types=1);

namespace Modules\Staff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Staff\Database\Factories\StaffProfileFactory;
use Modules\Staff\Enums\EmploymentStatus;

/**
 * Employment record for a staff member.
 *
 * One-to-one with Person (a Person has at most one StaffProfile).
 * May exist without a StaffAccount; may exist without a current assignment.
 * Does not contain passwords, permissions, roles, semester positions, or period scopes.
 *
 * Cross-module model reference (Organization\Models\Institution and People\Models\Person)
 * are accessed via string-variable static calls to comply with ModuleBoundaries constraints.
 */
final class StaffProfile extends Model
{
    /** @use HasFactory<StaffProfileFactory> */
    use HasFactory;

    protected $fillable = [];

    protected $casts = [
        'employment_status' => EmploymentStatus::class,
        'hired_on'          => 'date',
        'ended_on'          => 'date',
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

<?php

declare(strict_types=1);

namespace Modules\Students\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A correction request submitted by a guardian via the guardian portal.
 *
 * Records the guardian's proposed change to contact_priority and/or
 * is_emergency_contact on a specific guardian_student_relationship.
 *
 * Status lifecycle: pending → approved | rejected
 * Rows are never deleted; resolved requests keep their audit trail.
 */
final class GuardianCorrectionRequest extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'guardian_student_relationship_id',
        'requested_contact_priority',
        'requested_is_emergency_contact',
        'note',
        'status',
        'resolved_by_admin_id',
        'resolved_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'requested_is_emergency_contact' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    /** @return BelongsTo<GuardianStudentRelationship, $this> */
    public function relationship(): BelongsTo
    {
        return $this->belongsTo(GuardianStudentRelationship::class, 'guardian_student_relationship_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}

<?php

declare(strict_types=1);

namespace Modules\Students\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Students\Database\Factories\GuardianStudentRelationshipFactory;
use Modules\Students\Enums\EvidenceStatus;
use Modules\Students\Enums\LegalAuthorityStatus;
use Modules\Students\Enums\RelationshipType;
use Modules\Students\Enums\VerificationStatus;

/**
 * Historical relationship record between a GuardianProfile and a StudentProfile.
 *
 * Rows are NEVER deleted. When a relationship ends, ends_on is set and a new
 * row is created for any replacement. Correction history is stored in
 * history_metadata.
 *
 * Portal eligibility for a guardian to access a student requires:
 *   verification_status = 'verified' AND portal_eligible = true
 *   AND (ends_on IS NULL OR ends_on >= today)
 */
final class GuardianStudentRelationship extends Model
{
    /** @use HasFactory<GuardianStudentRelationshipFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'student_profile_id',
        'guardian_profile_id',
        'relationship_type',
        'legal_authority',
        'verification_status',
        'portal_eligible',
        'contact_priority',
        'is_emergency_contact',
        'starts_on',
        'ends_on',
        'restricted_notes',
        'evidence_status',
        'evidence_reference',
        'history_metadata',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'relationship_type' => RelationshipType::class,
        'legal_authority' => LegalAuthorityStatus::class,
        'verification_status' => VerificationStatus::class,
        'evidence_status' => EvidenceStatus::class,
        'portal_eligible' => 'boolean',
        'is_emergency_contact' => 'boolean',
        'starts_on' => 'date',
        'ends_on' => 'date',
        'history_metadata' => 'array',
    ];

    /** @return BelongsTo<StudentProfile, $this> */
    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    /** @return BelongsTo<GuardianProfile, $this> */
    public function guardianProfile(): BelongsTo
    {
        return $this->belongsTo(GuardianProfile::class);
    }

    public function isActive(): bool
    {
        return is_null($this->ends_on) || $this->ends_on->gte(now());
    }

    public function isPortalEligible(): bool
    {
        return $this->verification_status === VerificationStatus::Verified
            && $this->portal_eligible === true
            && $this->isActive();
    }

    protected static function newFactory(): GuardianStudentRelationshipFactory
    {
        return GuardianStudentRelationshipFactory::new();
    }
}

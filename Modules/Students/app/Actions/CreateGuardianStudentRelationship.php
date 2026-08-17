<?php

declare(strict_types=1);

namespace Modules\Students\Actions;

use Modules\Students\Enums\EvidenceStatus;
use Modules\Students\Enums\LegalAuthorityStatus;
use Modules\Students\Enums\RelationshipType;
use Modules\Students\Enums\VerificationStatus;
use Modules\Students\Models\GuardianProfile;
use Modules\Students\Models\GuardianStudentRelationship;
use Modules\Students\Models\StudentProfile;

/**
 * Create a new guardian–student relationship record.
 *
 * Relationships are historical: rows are NEVER deleted. When an existing
 * active relationship of the same type exists, it must be ended before
 * creating a replacement (enforced here).
 *
 * portal_eligible defaults to false; it must be explicitly set by an
 * authorized staff action (ActivateRelationship or a separate setter).
 */
final class CreateGuardianStudentRelationship
{
    public function __invoke(
        StudentProfile $student,
        GuardianProfile $guardian,
        RelationshipType $type,
        LegalAuthorityStatus $legalAuthority = LegalAuthorityStatus::Unknown,
        ?\DateTimeInterface $startsOn = null,
        ?int $contactPriority = null,
        bool $isEmergencyContact = false,
    ): GuardianStudentRelationship {
        // Prevent duplicate active relationship of the same type between the same pair.
        // ends_on = today means the relationship ended today; use '>' so today's end is treated as closed.
        $duplicate = GuardianStudentRelationship::where('student_profile_id', $student->id)
            ->where('guardian_profile_id', $guardian->id)
            ->where('relationship_type', $type->value)
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>', now()->toDateString()))
            ->exists();

        if ($duplicate) {
            throw new \InvalidArgumentException(
                "An active {$type->value} relationship already exists between this guardian and student."
            );
        }

        $rel = new GuardianStudentRelationship;
        $rel->student_profile_id = $student->id;
        $rel->guardian_profile_id = $guardian->id;
        $rel->relationship_type = $type->value;
        $rel->legal_authority = $legalAuthority->value;
        $rel->verification_status = VerificationStatus::Unverified->value;
        $rel->portal_eligible = false;
        $rel->contact_priority = $contactPriority;
        $rel->is_emergency_contact = $isEmergencyContact;
        $rel->starts_on = $startsOn?->format('Y-m-d');
        $rel->ends_on = null;
        $rel->evidence_status = EvidenceStatus::None->value;
        $rel->save();

        return $rel;
    }
}

<?php

declare(strict_types=1);

namespace Modules\Students\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Students\Enums\EvidenceStatus;
use Modules\Students\Enums\LegalAuthorityStatus;
use Modules\Students\Enums\RelationshipType;
use Modules\Students\Enums\VerificationStatus;
use Modules\Students\Models\GuardianStudentRelationship;

/**
 * @extends Factory<GuardianStudentRelationship>
 */
final class GuardianStudentRelationshipFactory extends Factory
{
    protected $model = GuardianStudentRelationship::class;

    public function definition(): array
    {
        return [
            'student_profile_id' => null, // must be provided explicitly
            'guardian_profile_id' => null, // must be provided explicitly
            'relationship_type' => RelationshipType::Father->value,
            'legal_authority' => LegalAuthorityStatus::Full->value,
            'verification_status' => VerificationStatus::Unverified->value,
            'portal_eligible' => false,
            'contact_priority' => 1,
            'is_emergency_contact' => false,
            'starts_on' => null,
            'ends_on' => null,
            'restricted_notes' => null,
            'evidence_status' => EvidenceStatus::None->value,
            'evidence_reference' => null,
            'history_metadata' => null,
        ];
    }

    public function verified(): static
    {
        return $this->state(['verification_status' => VerificationStatus::Verified->value]);
    }

    public function portalEligible(): static
    {
        return $this->state([
            'verification_status' => VerificationStatus::Verified->value,
            'portal_eligible' => true,
        ]);
    }

    public function ended(string $endsOn): static
    {
        return $this->state(['ends_on' => $endsOn]);
    }

    public function active(): static
    {
        return $this->state(['ends_on' => null]);
    }

    public function mother(): static
    {
        return $this->state(['relationship_type' => RelationshipType::Mother->value]);
    }
}

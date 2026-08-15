<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\AcademicManagement\Enums\ProposalReviewStatus;
use Modules\AcademicManagement\Enums\ProposalStatus;
use Modules\AcademicManagement\Models\PromotionProposal;
use Modules\AcademicManagement\Models\StudentEnrollment;

/**
 * @extends Factory<PromotionProposal>
 */
final class PromotionProposalFactory extends Factory
{
    protected $model = PromotionProposal::class;

    public function definition(): array
    {
        return [
            'source_enrollment_id' => StudentEnrollment::factory()->completed(),
            'proposed_status' => ProposalStatus::Promoted->value,
            'proposed_class_group_id' => null,
            'review_status' => ProposalReviewStatus::Pending->value,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'reason' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state([
            'review_status' => ProposalReviewStatus::Approved->value,
            'reviewed_by' => 'admin-001',
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'review_status' => ProposalReviewStatus::Rejected->value,
            'reviewed_by' => 'admin-001',
            'reviewed_at' => now(),
        ]);
    }

    public function forEnrollment(StudentEnrollment $enrollment): static
    {
        return $this->state(['source_enrollment_id' => $enrollment->id]);
    }
}

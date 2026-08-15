<?php

declare(strict_types=1);

namespace Modules\AcademicManagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AcademicManagement\Database\Factories\PromotionProposalFactory;
use Modules\AcademicManagement\Enums\ProposalReviewStatus;
use Modules\AcademicManagement\Enums\ProposalStatus;

/**
 * Staff-authored recommendation for what should happen to a student at semester end.
 *
 * An approved proposal must be explicitly applied via ApplyApprovedProposal;
 * it never auto-activates a new enrollment.
 *
 * reviewed_by is a string actor reference (not a FK to any account table).
 */
final class PromotionProposal extends Model
{
    /** @use HasFactory<PromotionProposalFactory> */
    use HasFactory;

    protected static function newFactory(): PromotionProposalFactory
    {
        return PromotionProposalFactory::new();
    }

    /** @var list<string> */
    protected $fillable = [
        'source_enrollment_id',
        'proposed_status',
        'proposed_class_group_id',
        'review_status',
        'reviewed_by',
        'reviewed_at',
        'reason',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'proposed_status' => ProposalStatus::class,
        'review_status' => ProposalReviewStatus::class,
        'reviewed_at' => 'datetime',
    ];

    /** @return BelongsTo<StudentEnrollment, $this> */
    public function sourceEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'source_enrollment_id');
    }

    /** @return BelongsTo<ClassGroup, $this> */
    public function proposedClassGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class, 'proposed_class_group_id');
    }

    /**
     * @param  Builder<PromotionProposal>  $query
     * @return Builder<PromotionProposal>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('review_status', ProposalReviewStatus::Pending->value);
    }

    /**
     * @param  Builder<PromotionProposal>  $query
     * @return Builder<PromotionProposal>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('review_status', ProposalReviewStatus::Approved->value);
    }

    public function isPending(): bool
    {
        return $this->review_status === ProposalReviewStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->review_status === ProposalReviewStatus::Approved;
    }
}

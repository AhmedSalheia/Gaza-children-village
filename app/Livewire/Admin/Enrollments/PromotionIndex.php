<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Enrollments;

use App\Livewire\Admin\Concerns\HasAdminAuth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\AcademicManagement\Actions\ReviewPromotionProposal;
use Modules\AcademicManagement\Enums\ProposalReviewStatus;
use Modules\AcademicManagement\Models\PromotionProposal;

/**
 * List pending promotion proposals with approve/reject actions.
 */
final class PromotionIndex extends Component
{
    use HasAdminAuth;
    use WithPagination;

    #[Url]
    public string $reviewStatusFilter = 'pending';

    public string $flashMessage = '';

    public string $flashType = '';

    public function mount(): void
    {
        $this->requirePermission('enrollment.promote');
    }

    public function updatingReviewStatusFilter(): void
    {
        $this->resetPage();
    }

    public function proposals(): LengthAwarePaginator
    {
        return DB::table('promotion_proposals as pp')
            ->join('student_enrollments as se', 'se.id', '=', 'pp.source_enrollment_id')
            ->join('student_profiles as sp', 'sp.id', '=', 'se.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->join('class_groups as cg', 'cg.id', '=', 'se.class_group_id')
            ->leftJoin('class_groups as target_cg', 'target_cg.id', '=', 'pp.proposed_class_group_id')
            ->leftJoin('academic_levels as target_al', 'target_al.id', '=', 'target_cg.academic_level_id')
            ->select([
                'pp.id',
                'pp.review_status',
                'pp.proposed_status',
                'pp.reason',
                'pp.reviewed_by',
                'pp.reviewed_at',
                'pp.created_at',
                'sp.student_code',
                'p.full_name_ar as student_name',
                'cg.name_ar as current_class_group',
                'target_al.name_ar as proposed_level',
            ])
            ->when(
                $this->reviewStatusFilter !== '',
                fn ($q) => $q->where('pp.review_status', $this->reviewStatusFilter)
            )
            ->orderByDesc('pp.created_at')
            ->paginate(25);
    }

    public function approve(int $proposalId): void
    {
        $this->requirePermission('enrollment.promote');

        try {
            $proposal = PromotionProposal::findOrFail($proposalId);
            app(ReviewPromotionProposal::class)(
                $proposal,
                ProposalReviewStatus::Approved,
                'admin:'.$this->adminId(),
            );
            $this->flash('success', __('ui.saved', [], null, 'Proposal approved.'));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function reject(int $proposalId, string $reason = ''): void
    {
        $this->requirePermission('enrollment.promote');

        try {
            $proposal = PromotionProposal::findOrFail($proposalId);
            app(ReviewPromotionProposal::class)(
                $proposal,
                ProposalReviewStatus::Rejected,
                'admin:'.$this->adminId(),
                $reason ?: null,
            );
            $this->flash('success', __('ui.saved', [], null, 'Proposal rejected.'));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    private function flash(string $type, string $message): void
    {
        $this->flashType = $type;
        $this->flashMessage = $message;
    }

    public function render(): View
    {
        return view('livewire.admin.enrollments.promotions', [
            'proposals' => $this->proposals(),
        ])->layout('layouts.admin');
    }
}

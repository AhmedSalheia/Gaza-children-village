<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Enrollments;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\AcademicManagement\Actions\CreatePromotionProposal;
use Modules\AcademicManagement\Actions\ReviewPromotionProposal;
use Modules\AcademicManagement\Enums\ProposalReviewStatus;
use Modules\AcademicManagement\Enums\ProposalStatus;

/**
 * Promotion proposal management for staff.
 *
 * Secretaries create proposals (enrollment.manage).
 * Principals approve or reject proposals (enrollment.promote).
 *
 * ── Period restriction ────────────────────────────────────────────────────
 * All listing queries (proposals, activeEnrollments, nextLevelClassGroups)
 * apply the same isFullScopePosition() + allowedPeriodIds() filter used
 * everywhere else in the portal.
 *
 * All mutations (createProposal, startReview, submitReview) call the
 * appropriate assertEnrollmentInScope() or assertClassGroupInScope() guard
 * to prevent out-of-scope IDs submitted via Livewire wire calls from
 * operating on other periods.
 *
 * ── Access ────────────────────────────────────────────────────────────────
 * mount() requires enrollment.promote for the list; createProposal also
 * checks enrollment.manage; submitReview checks enrollment.promote.
 *
 * Note: enrollment.promote is held by principal; enrollment.manage by secretary.
 * mount() allows principals onto this page. Secretaries who only have
 * enrollment.manage can use the create form via the route but will be
 * redirected if mount() requires promote exclusively. Therefore mount()
 * accepts either permission; individual actions enforce their own gates.
 */
final class PromotionReview extends Component
{
    use HasStaffAuth;
    use WithPagination;

    #[Url]
    public string $reviewFilter = 'pending';

    // Create proposal form
    public bool $showCreateForm = false;

    public int $createEnrollmentId = 0;

    public string $proposalStatus = 'promoted';

    public ?int $proposedClassGroupId = null;

    public string $proposalReason = '';

    // Review form
    public ?int $reviewingProposalId = null;

    public string $reviewDecision = '';

    public string $reviewReason = '';

    public function mount(): void
    {
        // Allow staff with either enrollment.manage (secretary) or
        // enrollment.promote (principal) onto this page.
        if (! $this->staffCan('enrollment.manage') && ! $this->staffCan('enrollment.promote')) {
            abort(403);
        }
    }

    public function updatedReviewFilter(): void
    {
        $this->resetPage();
    }

    // ── Listing queries ───────────────────────────────────────────────────

    public function proposals(): LengthAwarePaginator
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return new LengthAwarePaginator([], 0, 20);
        }

        $query = DB::table('promotion_proposals as pp')
            ->join('student_enrollments as se', 'se.id', '=', 'pp.source_enrollment_id')
            ->join('student_profiles as sp', 'sp.id', '=', 'se.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->join('class_groups as cg', 'cg.id', '=', 'se.class_group_id')
            ->join('academic_levels as al', 'al.id', '=', 'cg.academic_level_id')
            ->where('se.institution_semester_id', $scope['institution_semester_id'])
            ->select(
                'pp.id',
                'pp.proposed_status',
                'pp.review_status',
                'pp.reason',
                'pp.reviewed_by',
                'pp.reviewed_at',
                'pp.created_at',
                'se.id as enrollment_id',
                'sp.id as student_id',
                'p.full_name_ar as student_name',
                'cg.name_ar as class_group_name',
                'al.name_ar as level_name'
            );

        // Period restriction: principals/counselors are full-scope; secretaries
        // see only proposals for enrollments within their allowed periods.
        if (! $this->isFullScopePosition()) {
            $allowedPeriods = $this->allowedPeriodIds();

            if (empty($allowedPeriods)) {
                return new LengthAwarePaginator([], 0, 20);
            }

            $query->whereIn('cg.operational_period_id', $allowedPeriods);
        }

        if ($this->reviewFilter !== '') {
            $query->where('pp.review_status', $this->reviewFilter);
        }

        return $query->orderByDesc('pp.created_at')->paginate(20);
    }

    public function activeEnrollments(): Collection
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return collect();
        }

        $query = DB::table('student_enrollments as se')
            ->join('student_profiles as sp', 'sp.id', '=', 'se.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->join('class_groups as cg', 'cg.id', '=', 'se.class_group_id')
            ->where('se.institution_semester_id', $scope['institution_semester_id'])
            ->whereIn('se.enrollment_status', ['active', 'completed'])
            ->select('se.id', 'p.full_name_ar as student_name', 'cg.name_ar as class_group_name');

        if (! $this->isFullScopePosition()) {
            $allowedPeriods = $this->allowedPeriodIds();

            if (empty($allowedPeriods)) {
                return collect();
            }

            $query->whereIn('cg.operational_period_id', $allowedPeriods);
        }

        return $query->orderBy('p.full_name_ar')->get();
    }

    public function nextLevelClassGroups(): Collection
    {
        $scope = $this->staffScope();

        if ($scope['institution_semester_id'] === null) {
            return collect();
        }

        $query = DB::table('class_groups as cg')
            ->join('academic_levels as al', 'al.id', '=', 'cg.academic_level_id')
            ->where('cg.institution_semester_id', $scope['institution_semester_id'])
            ->where('cg.lifecycle_status', 'active');

        if (! $this->isFullScopePosition()) {
            $allowedPeriods = $this->allowedPeriodIds();

            if (empty($allowedPeriods)) {
                return collect();
            }

            $query->whereIn('cg.operational_period_id', $allowedPeriods);
        }

        return $query->orderBy('al.name_ar')->orderBy('cg.name_ar')
            ->get(['cg.id', 'cg.name_ar', 'al.name_ar as level_name']);
    }

    // ── Mutations ─────────────────────────────────────────────────────────

    public function createProposal(CreatePromotionProposal $action): void
    {
        $this->requirePermission('enrollment.manage');

        $this->validate([
            'createEnrollmentId' => ['required', 'integer', 'min:1'],
            'proposalStatus' => ['required', 'string'],
        ]);

        // Guard source enrollment: institution + period check.
        $this->assertEnrollmentInScope($this->createEnrollmentId);

        // Guard proposed class group if provided.
        if ($this->proposedClassGroupId !== null) {
            $this->assertClassGroupInScope($this->proposedClassGroupId);
        }

        $enrollmentClass = 'Modules\\AcademicManagement\\Models\\StudentEnrollment';
        $enrollment = $enrollmentClass::findOrFail($this->createEnrollmentId);

        $proposedClassGroup = null;
        if ($this->proposedClassGroupId !== null) {
            $classGroupClass = 'Modules\\AcademicManagement\\Models\\ClassGroup';
            $proposedClassGroup = $classGroupClass::findOrFail($this->proposedClassGroupId);
        }

        try {
            $action(
                sourceEnrollment: $enrollment,
                proposedStatus: ProposalStatus::from($this->proposalStatus),
                proposedClassGroup: $proposedClassGroup,
                reason: $this->proposalReason ?: null,
            );

            session()->flash('success', __('ui.proposal_created', [], null, 'Promotion proposal created.'));
            $this->showCreateForm = false;
            $this->reset(['createEnrollmentId', 'proposedClassGroupId', 'proposalReason']);
            $this->proposalStatus = 'promoted';
        } catch (\Throwable $e) {
            $this->addError('createProposal', $e->getMessage());
        }
    }

    public function startReview(int $proposalId): void
    {
        $this->requirePermission('enrollment.promote');

        // Verify the proposal's source enrollment is in scope before
        // revealing the review form — prevents probing other periods.
        $proposal = DB::table('promotion_proposals')->find($proposalId);

        if (! $proposal) {
            abort(404);
        }

        $this->assertEnrollmentInScope((int) $proposal->source_enrollment_id);

        $this->reviewingProposalId = $proposalId;
        $this->reviewDecision = '';
        $this->reviewReason = '';
    }

    public function cancelReview(): void
    {
        $this->reviewingProposalId = null;
    }

    public function submitReview(ReviewPromotionProposal $action): void
    {
        $this->requirePermission('enrollment.promote');
        abort_if($this->reviewingProposalId === null, 400);

        $this->validate([
            'reviewDecision' => ['required', 'in:approved,rejected'],
        ]);

        $proposalClass = 'Modules\\AcademicManagement\\Models\\PromotionProposal';
        $proposal = $proposalClass::findOrFail($this->reviewingProposalId);

        // Re-assert scope on mutation (batchId / proposalId are public properties).
        $this->assertEnrollmentInScope((int) $proposal->source_enrollment_id);

        try {
            $action(
                proposal: $proposal,
                decision: ProposalReviewStatus::from($this->reviewDecision),
                reviewedBy: $this->staffActorReference(),
                reason: $this->reviewReason ?: null,
            );

            $label = $this->reviewDecision === 'approved'
                ? __('ui.proposal_approved', [], null, 'Proposal approved.')
                : __('ui.proposal_rejected', [], null, 'Proposal rejected.');

            session()->flash('success', $label);
            $this->reviewingProposalId = null;
        } catch (\Throwable $e) {
            $this->addError('review', $e->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.staff.enrollments.promotions', [
            'proposals' => $this->proposals(),
            'activeEnrollments' => $this->activeEnrollments(),
            'nextLevelGroups' => $this->nextLevelClassGroups(),
            'canCreate' => $this->staffCan('enrollment.manage'),
            'canApprove' => $this->staffCan('enrollment.promote'),
            'proposalStatuses' => ProposalStatus::cases(),
        ])->layout('layouts.staff');
    }
}

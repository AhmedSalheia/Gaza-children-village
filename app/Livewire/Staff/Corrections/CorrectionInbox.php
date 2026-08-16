<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Corrections;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Authorization\Data\PermissionKey;

/**
 * Staff portal: inbox of correction requests awaiting review or approval.
 *
 * Secretary (correction.review): sees all requests in submitted, resubmitted,
 *   clarification_requested, and under_review states for their institution.
 *
 * Principal/deputy (correction.approve): additionally sees requests in
 *   under_review state where classification = 'sensitive'.
 *
 * Access gated on CORRECTION_REVIEW.
 */
final class CorrectionInbox extends Component
{
    use HasStaffAuth;

    public string $stateFilter = 'pending';

    public function mount(): void
    {
        $this->requirePermission(PermissionKey::CORRECTION_REVIEW);
    }

    public function render(): View
    {
        $scope = $this->staffScope();
        $requests = $this->loadRequests($scope);
        $canApprove = $this->staffCan(PermissionKey::CORRECTION_APPROVE);

        return view('livewire.staff.corrections.correction-inbox', [
            'requests' => $requests,
            'canApprove' => $canApprove,
        ])->layout('layouts.staff');
    }

    private function loadRequests(array $scope): Collection
    {
        $institutionId = $scope['institution_id'] ?? null;

        $pendingStates = ['submitted', 'resubmitted', 'under_review'];

        $query = DB::table('student_correction_requests as scr')
            ->join('workflow_instances as wi', 'wi.id', '=', 'scr.workflow_instance_id')
            ->join('student_profiles as sp', 'sp.id', '=', 'scr.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->leftJoin('correction_field_proposals as cfp', function ($join): void {
                $join->on('cfp.correction_request_id', '=', 'scr.id')
                    ->whereRaw('cfp.submission_sequence = (SELECT MAX(s.submission_sequence) FROM correction_field_proposals s WHERE s.correction_request_id = scr.id)');
            })
            ->whereIn('wi.current_state', $pendingStates)
            ->select(
                'scr.id',
                'scr.field_catalogue_code',
                'scr.classification',
                'scr.conflict_flag',
                'scr.created_at',
                'wi.current_state',
                'p.full_name_ar as student_name',
            )
            ->orderByDesc('scr.created_at');

        if ($institutionId !== null) {
            $query->where('scr.institution_id', $institutionId);
        }

        return $query->get();
    }
}

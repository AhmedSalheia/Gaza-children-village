<?php

declare(strict_types=1);

namespace App\Livewire\Staff\Documents;

use App\Livewire\Staff\Concerns\HasStaffAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Authorization\Data\PermissionKey;

/**
 * Staff portal (secretary): document request review queue.
 *
 * Shows document requests within the secretary's trusted institution semester.
 * The filter applies both institution_id AND institution_semester_id from the
 * staff's active position — matching the same scope contract used by all other
 * staff portal read queries.
 *
 * For full-scope positions (principal, deputy_principal, counselor): all
 * requests in the semester are shown.
 * For period-restricted positions (secretary, teacher): requests are further
 * filtered to enrollments in the allowed operational periods.
 *
 * Gated on DOCUMENT_REVIEW permission.
 */
final class DocumentReviewQueue extends Component
{
    use HasStaffAuth;

    public function mount(): void
    {
        $this->requirePermission(PermissionKey::DOCUMENT_REVIEW);
    }

    public function render(): View
    {
        $scope    = $this->staffScope();
        $requests = $this->loadQueue($scope['institution_id'], $scope['institution_semester_id']);

        return view('livewire.staff.documents.document-review-queue', [
            'requests' => $requests,
            'scope'    => $scope,
        ])->layout('layouts.staff');
    }

    private function loadQueue(?int $institutionId, ?int $institutionSemesterId): \Illuminate\Support\Collection
    {
        if ($institutionId === null || $institutionSemesterId === null) {
            return collect();
        }

        $query = DB::table('student_document_requests as dr')
            ->join('student_profiles as sp', 'sp.id', '=', 'dr.student_profile_id')
            ->join('people as p', 'p.id', '=', 'sp.person_id')
            ->where('dr.institution_id', $institutionId)
            ->where('dr.institution_semester_id', $institutionSemesterId)
            ->whereIn('dr.status', [
                'submitted',
                'pending_completeness',
                'completeness_failed',
                'pending_clarification',
                'completeness_passed',
            ]);

        // Period-restricted positions (secretary, teacher): further scope by
        // enrollment's class group operational period.
        if (! $this->isFullScopePosition()) {
            $allowedPeriods = $this->allowedPeriodIds();

            if (empty($allowedPeriods)) {
                return collect();
            }

            $query->join('student_enrollments as se', 'se.id', '=', 'dr.enrollment_id')
                ->join('class_groups as cg', 'cg.id', '=', 'se.class_group_id')
                ->whereIn('cg.operational_period_id', $allowedPeriods);
        }

        return $query->select(
            'dr.id',
            'dr.document_type_code',
            'dr.locale',
            'dr.status',
            'dr.created_at',
            'dr.submitted_at',
            'p.full_name_ar as student_name_ar',
        )
            ->orderBy('dr.submitted_at')
            ->get();
    }
}
